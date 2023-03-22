<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/searchengine/solr/searcher.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//////////////////////////////////////////
////		인제스트 작업 INSERT		  ////
////		작성자 : 조훈휘			  ////
////		작성일 : 2010년 12월 10일   ////
/////////////////////////////////////////
$created = date('Ymd');
try
{
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

	$receive_xml = file_get_contents('php://input');
	//$receive_xml = file_get_contents('sample_ingest_insert.xml');

	file_put_contents(LOG_PATH.'/ingest_insert'.$created.'.html', date("Y-m-d H:i:s\t").$receive_xml."\n\n", FILE_APPEND);
	
	$xml = checkXMLSyntax($receive_xml);


	//////////////////////////////받아온 xml 처리부분////////////////////////////////////
	$content_id 	= $xml->registmeta->content['content_id'];
	$category_id 	= $xml->registmeta->content->category_id;
	$meta_table_id 	= $xml->registmeta->content['metatableid'];
	$tc_id			= $xml->registmeta->content['tc_id'];

	$contents	 	= $xml->registmeta->content;
	$medias 		= $xml->registmeta->medias;
	$systems 		= $xml->registmeta->{'system'};
	$customs 		= $xml->registmeta->custom;
	$cur_time		= date('YmdHis');
	
//	$db->setTransaction(true);

	$content_id = updateContent($contents, $content_id, $category_id, $meta_table_id, $tc_id);

	updateContentValue($systems, $content_id);
	$ori_path = updateMediaMeta($medias, $content_id);
	updateMetaValue($customs, $content_id, $meta_table_id, $meta_field_id);

	/////////////////////////////////
	////task 테이블 job추가     ///////
	/////////////////////////////////
	$ingest_server_num	= $medias->media['ingestid'];
//	$original		= $db->queryRow("select media_id, path from bc_media where content_id=$content_id and media_type='original'");
//	$target_path	= date('Y/m/d/Hi').'/'.$content_id.'/'.basename($original['path']);


//	$task = insert_task_query($content_id, $original['path'], $target_path.'/'.$original['path'], $cur_time, $ingest_server_num);
//	$task = insert_task_query($content_id, $original['path'], $target_path.'/'.$original['path'], $cur_time, $channel);

//	$channel = $meta_table_id.'_'.$ingest_server_num;
//	$task = insert_task_query($content_id, $original['path'], $target_path, $cur_time, $channel);
	
	$channel = '1';
	$job_priority = 1;
	$task_user_id = 'admin';
	$task = new TaskManager($db);
	$task->insert_task_query_outside_data($content_id, $channel, $job_priority, $task_user_id, $ori_path );

	//검색엔진에 등록
//	$s = new Searcher($db);
//	$s->add($content_id, 'DAS');

//	$db->commit();

	/////////////////////던져주기///////////////////////
	$response->result->addAttribute('success', 'true');

	file_put_contents(LOG_PATH.'/ingest_insert'.$created.'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);

	echo $response->asXML();
}
catch (Exception $e)
{
//	$db->rollback();

	$response->result->addAttribute('success', 'false');
	$response->result->addAttribute('message', $e->getMessage());
	$response->result->addAttribute('query', $db->last_query);
	file_put_contents(LOG_PATH.'/ingest_insert'.$created.'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);

	echo $response->asXML();
}



////////////////////////////
//////////// 함수
////////////////////////////

function updateContent($content, $content_id, $category_id, $ud_content_id, $tc_id)
{
	global $db;

	$bs_content_id		= $content['contenttypeid'];
	$title				= reConvertSpecialChar($content->title);
	$is_hidden			= $content->hidden;
	$user_id			= $content->userid;
	$category_full_path	= getCategoryFullPath($category_id);
	$cur_time 			= date('YmdHis');

	$expired_date		= $db->queryOne("select expired_date from bc_ud_content where ud_content_id=".$ud_content_id);
	if ($expired_date != -1)
	{
		$d = new DateTime();
		$d->add(new DateInterval($expired_date));
		$expired_date = $d->format('Y-m-d');
	}
	else
	{
		$expired_date = '9999-12-31';
	}

	$content_id = getSequence('SEQ_CONTENT_ID');
	$Temp_Title = str_replace('\'', '\'\'', reConvertSpecialChar($title));   //타이틀에 특수 기호 있는것에 대한 수정 진행함.  //도훈 수정
	$insert_content_query = $db->exec("insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, ".
															"BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, ".
															"TITLE, REG_USER_ID, ".
															"CREATED_DATE, STATUS, EXPIRED_DATE) ".

												"values('$category_id', '$category_full_path', '$bs_content_id', ".
															"'$ud_content_id', '$content_id', '$Temp_Title', ".
															"'$user_id', '$cur_time', '".INGEST_READY."', '$expired_date')");

	//log테이블에 기록 남김 /function 으로 변경 2011-04-11 by 이성용
	$action = 'regist';
	$description = 'ingest_manager';
	insertLog($action, $user_id, $content_id, $description);

	return $content_id;
}

function updateMediaMeta($medias, $content_id)
{
	global $db;

	$r = $db->exec("delete from bc_media where content_id=".$content_id);

	foreach ( $medias as $media )
	{
		foreach ( $media as $media_meta => $value )
		{
			$type		= $value['type'];

			if( $type == 'original' )
			{	
				$media_id 	= getSequence('SEQ_MEDIA_ID');
				
				$path		= $db->escape($value['path']);
				$filesize	= $value['filesize'];
				if($filesize == '')
				{
					$filesize = 0;
				}
				$register	= $value['ingestid'];
				$cur_time	= date('YmdHis');

				//스토리지 테이블에 각 타입의 대표 스토리지 정의 입력이 필요함.
				$storage_info 	= get_storage_info($type); // 함수에 하드코딩 되어잇슴.
				$storage_path 	= $storage_info['path'];
				$storage_id 	= 6;//$storage_info['storage_id'];
				//$insert_media_query = $db->exec("insert into bc_media ".
				//					"(CONTENT_ID, MEDIA_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE, STATUS, DELETE_DATE, FLAG) ".
				//				"values ".
				//					"('$content_id', '$media_id', '$storage_id', '$type', '$path', '$filesize', '$cur_time', '$register', '0','','')");
			}
		}
	}

	return $path;
}

function updateContentValue($systems, $content_id)
{
	global $db;

	$r = $db->exec("delete from BC_SYS_META_VALUE where content_id=".$content_id);

	foreach($systems as $system)
	{
		foreach($system as $system_meta => $value)
		{

			$conFd_id 			= $value['contentfieldid'];
			//$content_value_id 	= getNextSequence();
			$value 				= addslashes($value);

			$insert_contentValue_query = $db->exec("insert into BC_SYS_META_VALUE values('$content_id', '$conFd_id', '', '$value')");
		}
	}

	//file_put_contents(LOG_PATH.'/ingest/ingest_insert'.$created.'.log', date("Y-m-d H:i:s\t").' insert sysmeta_value query : >> '.$db->last_query."\n\n", FILE_APPEND);
}

function updateMetaValue($customs, $content_id, $meta_table_id, $meta_field_id)
{
	global $db;

	$r = $db->exec("delete from BC_USR_META_VALUE where content_id=".$content_id);

	foreach($customs as $custom)
	{
		foreach($custom as $custom_meta => $value)
		{

			$meta_field_id 	= $value['metafieldid'];

			$value			= str_replace('\'', '\'\'', reConvertSpecialChar($value));
			//$meta_value_id 	= getNextSequence();

			if($meta_field_id =='4002633' || $meta_field_id == '4002632' || $meta_field_id == '4037526' )
			{
				continue;
			}else
			{
			$insert_metaValue_query = $db->exec ("insert into BC_USR_META_VALUE
															values ('$content_id', '$meta_table_id', '$meta_field_id', '', '$value')");
			}

		}
	}

	//file_put_contents(LOG_PATH.'/ingest/ingest_insert'.$created.'.log', date("Y-m-d H:i:s\t").' insert user_meta_query : >> '.$db->last_query."\n\n", FILE_APPEND);
}

function updateMetaMultiValue( $ingest_id , $content_id ) //소재영상일때 TC정보 등록 함수 by 이성용 2011-3-17
{
	global $db;
	$meta_field_id = $db->queryOne("select meta_field_id from meta_field where meta_table_id='".CLEAN."' and type='listview' and name='TC정보' ");//TC정보 meta_field_id
	$meta_multi_list = $db->queryAll("select * from ingest_meta_multi_xml where ingest_id='$ingest_id' and meta_field_id='$meta_field_id' order by sort");

	foreach( $meta_multi_list as $meta_multi )
	{
		$meta_field_id= $meta_multi['meta_field_id'];
		$sort= $meta_multi['sort'];
		$meta_multi_xml_id = getNextMetaMultiSequence(); //인제스트멀티리스트에 등록할때 같은 시퀀스를 쓰기때문에 그대로 넣어줌
		$columns =$db->escape( $meta_multi['val']);

		$meta_multi_insert_q = "insert into meta_multi_xml (content_id, meta_field_id, sort, meta_multi_xml_id, val) values ($content_id, $meta_field_id, $sort,'$meta_multi_xml_id' ,'$columns')";

		$meta_multi_insert = $db->exec( $meta_multi_insert_q );
	}
}
?>

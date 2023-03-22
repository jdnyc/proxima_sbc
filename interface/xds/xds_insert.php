<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try
{
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

	$receive_xml = file_get_contents('php://input');
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.html', date("Y-m-d H:i:s\t").$receive_xml."\n\n", FILE_APPEND);

	$xml = checkXMLSyntax($receive_xml);

	//////////////////////////////받아온 xml 처리부분////////////////////////////////////
	$content_id 	= $xml->registmeta->content['content_id'];			//사용하지 않는 변수 이지만 함수 파라미터로 사용하고 있음.
	$category_id 	= $xml->registmeta->content->category_id;
	$ud_content_id 	= $xml->registmeta->content['metatableid'];
	$contents	 	= $xml->registmeta->content;
	$medias 		= $xml->registmeta->medias;
	$systems 		= $xml->registmeta->{'system'};
	$customs 		= $xml->registmeta->custom;
	$user_id		= $xml->registmeta->content->userid;//'userid';	

	//편성정보 코드정보
	
	$medcd = $xml->registmeta->content['medcd'];// "001" ;
	$progcd = $xml->registmeta->content['progcd'];//"11T0ET0031" ;
	$formbaseymd = $xml->registmeta->content['formbaseymd'];//"20120227" ;
	$subprogcd = $xml->registmeta->content['subprogcd'];//"0010";
	$brodymd = $xml->registmeta->content['brodymd'];//"0010";

	$content_id = updateContent($contents, $content_id, $category_id, $ud_content_id);

	$r = $db->exec("insert into content_code_info (CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,FORMBASEYMD,BRODYMD) values ('$content_id', '$medcd', '$progcd', '$subprogcd', '$formbaseymd', '$brodymd') ");

	updateContentValue($systems, $content_id);
	$original_path = updateMediaMeta($medias, $content_id);
	updateMetaValue($customs, $content_id, $ud_content_id);

	/////////////////////////////////
	////task 테이블 job추가     ///////
	/////////////////////////////////
	
	$channel = 'xds';

	$job_priority = 1;	

	$original_path = $original_path;

	$insert_task = new TaskManager($db);
	$insert_task->insert_task_query_outside_data($content_id, $channel, $job_priority, $user_id, $original_path);

	/////////////////////던져주기///////////////////////
	$response->result->addAttribute('success', 'true');
	$response->result->addAttribute('msg', '등록 성공');
	
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);

	echo $response->asXML();
}
catch (Exception $e)
{
	$response->result->addAttribute('success', 'false');
	$response->result->addAttribute('message', $e->getMessage());
	$response->result->addAttribute('query', $db->last_query);
	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).date('Ymd').'.html', date("Y-m-d H:i:s\t").$response->asXML()."\n\n", FILE_APPEND);

	echo $response->asXML();
}



////////////////////////////
//////////// 함수
////////////////////////////

function updateContent($content, $content_id, $category_id, $ud_content_id)
{
	global $db;

	$bs_content_id		= $content['contenttypeid'];
	$title				= str_replace("'", "''", $content->title);
	$is_hidden			= $content->hidden;
	$reg_user_id		= $content->userid;
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

	$db->exec("insert into bc_content 
					(content_id, category_id, category_full_path, bs_content_id, ud_content_id, title, reg_user_id, created_date, status, expired_date ) 
				values
					($content_id, $category_id, '$category_full_path', $bs_content_id, $ud_content_id, '$title', '$reg_user_id', '$cur_time', -2, '$expired_date' )");

	//log테이블에 기록 남김 /function 으로 변경 2011-04-11 by 이성용
	$action = 'regist';
	$description = 'edius';
	//insertLog($action, $user_id, $content_id);

	return $content_id;
}

function updateMediaMeta($medias, $content_id)
{
	global $db;

	//$r = $db->exec("delete from bc_media where content_id=".$content_id);

	foreach ( $medias as $media )
	{
		foreach ( $media as $media_meta => $value )
		{			
			$type		= $value['type'];

			if($type != 'original') continue;

			$original_path		= reConvertSpecialChar($value['path']);
			$filesize	= $value['filesize'];
			$register	= $value['ingestid'];
			$cur_time	= date('YmdHis');

						//스토리지 테이블에 각 타입의 대표 스토리지 정의 입력이 필요함.
			//			$storage_info 	= get_storage_info($type);
			//			$storage_path 	= $storage_info['path'];
			//			$storage_id 	= 0;//$storage_info['storage_id'];

			//			if ($type == 'thumb')
			//			{
			//				$path = 'incoming.jpg';
			//			}
			//			else if (empty($path))
			//			{
			//				$path = 'Temp';
			//			}
						
			//			$insert_media_query = $db->exec("insert into bc_media
			//							(content_id, storage_id, media_type, path, filesize, created_date, reg_type)
			//						values
			//							($content_id, '$storage_id', '$type', '$path', '$filesize', '$cur_time', '$register')");
		}
	}


	return $original_path;
}

function updateContentValue($systems, $content_id)
{
	global $db;

	$r = $db->exec("delete from bc_sys_meta_value where content_id=".$content_id);

	foreach($systems as $system)
	{
		foreach($system as $value)
		{
			$sys_meta_field_id 	= $value['contentfieldid'];
			$sys_meta_value		= addslashes($value);

			$insert_contentValue_query = $db->exec("insert into bc_sys_meta_value 
															(content_id, sys_meta_field_id, sys_meta_value) 
														values
															($content_id, $sys_meta_field_id, '$sys_meta_value')");
		}
	}
}

function updateMetaValue($customs, $content_id, $ud_content_id)
{
	global $db;

	$db->exec("delete from bc_usr_meta_value where content_id=".$content_id);

	foreach($customs as $custom)
	{
		foreach($custom as $custom_meta => $value)
		{
			$usr_meta_field_id 	= $value['metafieldid'];
			

			$usr_meta_field_type = $db->queryOne("select usr_meta_field_type from bc_usr_meta_field where  usr_meta_field_id='$usr_meta_field_id'");

			if($usr_meta_field_type == 'datefield')
			{
				if( !empty($value) && strtotime($value) )
				{
					$value = date('YmdHis', strtotime($value) );
				}
				else
				{
					$value = '';
				}
			}

			$value	= $db->escape($value);

			$db->exec ("insert into bc_usr_meta_value
							(content_id, ud_content_id, usr_meta_field_id, usr_meta_value )
						values 
							($content_id, $ud_content_id, $usr_meta_field_id, '$value')");
		}
	}
}
?>

<?php

function insertContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id,$title, $user_id)
{
	global $db;

	$category_full_path	= getCategoryFullPath($category_id);
	$cur_time 			= date('YmdHis');
	$expired_date = '9999-12-31';
	//제목
	if( empty($title) ){
		$title = 'Temp';
	}
	$Temp_Title = $db->escape($title);
	$r = $db->exec("insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, ".
															"BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, ".
															"TITLE, REG_USER_ID, ".
															"CREATED_DATE, STATUS, EXPIRED_DATE) ".

												"values('$category_id', '$category_full_path', '$bs_content_id', ".
															"'$ud_content_id', '$content_id', '$Temp_Title', ".
															"'$user_id', '$cur_time', '".INGEST_READY."', '$expired_date')");


	$action = 'regist';
	$description = 'regist';
	insertLog($action, $user_id, $content_id, $description);

	return $content_id;
}

function insertBaseContentValue($content_id, $content_type_id )
{
	global $db;
	//$r = $db->exec("delete from content_value where content_id=".$content_id);
	$system_fields = $db->queryAll("select * from BC_SYS_META_FIELD where BS_CONTENT_ID ='$content_type_id' order by SHOW_ORDER ");

	foreach($system_fields as $field)
	{
		$content_field_id 	= $field['sys_meta_field_id'];
		$value 				= '';
		//시작타임코드 강제로 01:00:00:00로 변경 fcp
		if($content_field_id == '6073034'){
			$value 	= '00:00:00:00';
		}
		$r = $db->exec("insert into BC_SYS_META_VALUE (CONTENT_ID,SYS_META_FIELD_ID,SYS_META_VALUE) values('$content_id', '$content_field_id',  '$value')");
	}
	return true;
}

function  insertMetaValues($metaValues, $content_id, $meta_table_id ,$update = null )
{
	global $db;
	//$r = $db->exec("delete from meta_value where content_id=".$content_id);
	$meta_fields = $db->queryAll("select * from BC_USR_META_FIELD where UD_CONTENT_ID ='$meta_table_id' order by SHOW_ORDER ");

	foreach($meta_fields as $field)
	{
		$meta_field_id 	= $field['usr_meta_field_id'];
		$value = $metaValues[$meta_field_id];

		if( $field['usr_meta_field_type'] == 'datefield' ){
			if( !empty($value) && strtotime($value) ){
				$value = date("YmdHis", strtotime($value) );
			}
		}

		$value = $db->escape($value);

		if($update){
			//업데이트시
			//업데이트값이 존재하는 필드만 없데이트
			if( array_key_exists('usr_meta_field_id', $metaValues) ){

				$field_chk = $db->queryRow("select * from BC_USR_META_VALUE where content_id='$content_id' and usr_meta_field_id='$meta_field_id'");
				if( !empty($field_chk) ){
					$r = $db->exec ("update BC_USR_META_VALUE set USR_META_VALUE='$value' where content_id='$content_id' and usr_meta_field_id='$meta_field_id' ");
				}else{
					$r = $db->exec ("insert into BC_USR_META_VALUE	(CONTENT_ID,UD_CONTENT_ID,USR_META_FIELD_ID,USR_META_VALUE) values ('$content_id', '$meta_table_id', '$meta_field_id',  '$value')");
				}
			}
		}else{
			//신규등록시
			$r = $db->exec ("insert into BC_USR_META_VALUE	(CONTENT_ID,UD_CONTENT_ID,USR_META_FIELD_ID,USR_META_VALUE) values ('$content_id', '$meta_table_id', '$meta_field_id',  '$value')");
		}
	}
	return true;
}

function insertContentCodeInfo($metaValues, $content_id,  $is_update = null )
{
	global $db;
	$medcd = $metaValues[0]['k_medcd'];
	$brodymd = $metaValues[0]['k_brodymd'];
	$formbaseymd = $metaValues[0]['k_formbaseymd'];;
	$progcd = $metaValues[0]['k_progcd'];
	$subprogcd = $metaValues[0]['k_subprogcd'];

	$datagrade = $metavalues[0]['k_datagrade'];
	$storterm = $metavalues[0]['k_storterm'];

	if(!$is_update){		//신규일때

		//등록시 전송처 코드 입력 2013-02-15 이성용
		$register_type = 'E';//편집 전송 코드

		$r = $db->exec ("insert into CONTENT_CODE_INFO	(CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
	}else{
		//업데이트
		$r = $db->exec ("update CONTENT_CODE_INFO set medcd='$medcd',brodymd='$brodymd' where content_id='$content_id' ");
	}

	return true;
}

function insertContentCodeInfo2($metaValues, $content_id,  $is_update = null )
{
	global $db;
	$medcd = $metaValues[0]['k_medcd'];
	$brodymd = $metaValues[0]['k_brodymd'];
	$formbaseymd = $metaValues[0]['k_formbaseymd'];;
	$progcd = $metaValues[0]['k_progcd'];
	$subprogcd = $metaValues[0]['k_subprogcd'];

	$datagrade = $metavalues[0]['k_datagrade'];
	$storterm = $metavalues[0]['k_storterm'];

	if(!$is_update){		//신규일때

		//등록시 전송처 코드 입력 2013-02-15 이성용
		$register_type = 'I';//편집 전송 코드

		$r = $db->exec ("insert into CONTENT_CODE_INFO	(CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
	}else{
		//업데이트
		$r = $db->exec ("update CONTENT_CODE_INFO set medcd='$medcd',brodymd='$brodymd' where content_id='$content_id' ");
	}

	return true;
}

function getMetaValues( $metadatas )
{
	$metaValues = array();
	foreach($metadatas as $metadata)
	{
		foreach($metadata as $key => $value)
		{
			if( is_numeric($key) ){
				$metaValues[$key] = $value;
			}
		}
	}
	return $metaValues;
}

function getMetaMultiValues($metadatas)
{

	foreach($metadatas as $metadata)
	{
		if( !empty($metadata['multi']) )
		{
			return $metadata['multi'];
		}
	}

	return array();
}

function insertMetaMultiValue($multi_lists, $content_id, $meta_table_id , $meta_field_id, $is_update = null )
{
	global $db;

	foreach($multi_lists as $json_values)
	{
		$meta_field_id = trim($json_values['id'], 'list');
		$json_value = $json_values['store'];

		for( $i=0 ; $i < count($json_value) ; $i++ )
		{
			$sort_value = $i+1;

			$json_value[$i]['columnA'] = $sort_value; //순번 재 입력
			if( ( timecode::getConvSec( trim($json_value[$i]['columnB']) )  !== false ) && ( timecode::getConvSec( trim($json_value[$i]['columnC']) ) !== false ) )
			{
				$start_tc = timecode::getConvSec( trim($json_value[$i]['columnB']) );
				$end_tc =  timecode::getConvSec( trim($json_value[$i]['columnC']) );

				$json_value[$i]['columnD'] = timecode::getConvTimecode( $end_tc - $start_tc );
			}

			if( $meta_table_id == CLEAN )
			{
				if( strstr($json_value[$i]['columnE'], '->') )
				{
					$categories = explode('->', $json_value[$i]['columnE']);

					$category_array = array();

					foreach($categories as $item)
					{//2012. 07. 06 기준. 3936212 소재영상. 부모ID는 0
						$item = trim($item);
						if( !empty($item) && $item != '3936212' && $item != '')
						{
							$category_array[] = trim($item);
						}
					}
					$json_value[$i]['columnE'] = join('/', $category_array);
				}
				else if($json_value[$i]['columnE'] == '3936212')
				{
					$json_value[$i]['columnE'] ='';
				}
				else if( strstr($json_value[$i]['columnE'], '/') )
				{
					$json_value[$i]['columnE'] = trim($json_value[$i]['columnE']);
				}
				else
				{
					$parent_id = $db->queryOne("select parent_id from categories where id='".$json_value[$i]['columnE']."'");
					if(!empty($parent_id) && !PEAR::isError($parent_id) )
					{
						if( $parent_id == '3936212' )
						{

						}
						else
						{
							$json_value[$i]['columnE'] = $parent_id.'/'.$json_value[$i]['columnE'];
							$first_parent_id = $db->queryOne("select parent_id from categories where id='".$parent_id."'");

							if(!empty($first_parent_id) && !PEAR::isError($first_parent_id) )
							{
								if( $first_parent_id != '3936212' )
								{
									$json_value[$i]['columnE'] = $first_parent_id.'/'.$json_value[$i]['columnE'];
								}
							}
						}
					}
					else
					{
						$json_value[$i]['columnE'] = '';
					}
				}
			}

			$tmp = array();
			ksort( $json_value[$i] );
			foreach ( $json_value[$i] as $k => $v )
			{
				if( $k != 'meta_multi_xml_id' && $k != 'tc_category' && $k != 'sub_content_id' )
				{
					array_push($tmp, '<'.$k.'>'.$db->escape(htmlspecialchars($v)).'</'.$k.'>');
				}
			}

			$columns = '<columns>'.join('', $tmp).'</columns>';

			//업데이트모드이면서 xml_id 가 있으면 업데이트
			if( $is_update && !empty( $json_value[$i]['meta_multi_xml_id'] ) )
			{
				$result = $db->exec("update meta_multi_xml set sort=$sort_value , val='$columns' where meta_multi_xml_id=".$json_value[$i]['meta_multi_xml_id']);
			}
			else
			{
				$meta_multi_xml_id=getNextMetaMultiSequence();
				$result = $db->exec("insert into meta_multi_xml (content_id, meta_field_id, sort, meta_multi_xml_id, val) values ($content_id, $meta_field_id, $sort_value,'$meta_multi_xml_id' ,'$columns')");
			}
		}

	}

}

//들어온 메타데이터에서 인자로 넘오온 항목을 찾아서 값을 반환
function findUsrMetaValue($metadatas, $usr_meta_field_id)
{
	foreach($metadatas as $meta)
	{
		foreach($meta as $meta_field => $meta_value)
		{
			if($meta_field == $usr_meta_field_id)
			{
				return $meta_value;
			}
		}
	}
	return '';
}
?>
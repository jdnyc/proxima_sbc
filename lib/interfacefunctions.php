<?php


function insertContent($metaValues, $content_id, $category_id, $bs_content_id,
		$ud_content_id, $title, $user_id, $topic_id, $group_type, $group_count, $parent_content_id) {
	global $db;

	$category_full_path = getCategoryFullPath($category_id);
	$cur_time           = date('YmdHis');

	$expired_date = '9999-12-31';

	//제목
	$title = trim($title);
	if (empty($title)){
		$title ='no title';
	}

	$db->insert('BC_CONTENT', array(
			'CATEGORY_ID' => $category_id,
			'CATEGORY_FULL_PATH' => $category_full_path,
			'BS_CONTENT_ID' => $bs_content_id,
			'UD_CONTENT_ID' => $ud_content_id,
			'CONTENT_ID' => $content_id,
			'TITLE' => $title,
			'REG_USER_ID' => $user_id,
			'CREATED_DATE' => $cur_time,
			'STATUS' => INGEST_READY,
			'EXPIRED_DATE' => $expired_date,
			'IS_GROUP' => $group_type,
			'GROUP_COUNT' => $group_count,
			'PARENT_CONTENT_ID' => $parent_content_id
	));

	$action = 'regist';
	$description = 'nle register';
	insertLog($action, $user_id, $content_id, $description);

	return $content_id;
}

function insertMediaMetadata($content_id, $type, $filename, $channel) {
	global $db;

	$db->insert('BC_MEDIA', array(
			'CONTENT_ID' => $content_id,
			'CREATED_DATE' => date('YmdHis'),
			'PATH' => $filename,
			'MEDIA_TYPE' => $type,
			'STORAGE_ID' => 0,
			'REG_TYPE' => $channel,
			'EXPIRED_DATE' => '99981231000000'
	));
}

function insertBaseContentValue($content_id, $content_type_id) {
	global $db;

	//$r = $db->exec("delete from content_value where content_id=".$content_id);
	$system_fields = $db->queryAll("select * from BC_SYS_META_FIELD where BS_CONTENT_ID ='$content_type_id' order by SHOW_ORDER ");

	foreach($system_fields as $field)
	{

		$content_field_id   = $field['sys_meta_field_id'];
		$value              = '';
		//시작타임코드 강제로 01:00:00:00로 변경 fcp
		if($content_field_id == '6073034')
		{
			$value  = '00:00:00:00';
		}

		$r = $db->exec("insert into BC_SYS_META_VALUE (CONTENT_ID,SYS_META_FIELD_ID,SYS_META_VALUE) values('$content_id', '$content_field_id',  '$value')");

	}
	return true;
}

function  insertMetaValues($metaValues, $content_id, $meta_table_id ,$update = null ) {
	global $db;
	//$r = $db->exec("delete from meta_value where content_id=".$content_id);

	$fieldKey = array();
	$fieldValue = array();

	//필드 목록 배열
	$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $meta_table_id );

	//필드의 id => name
	$fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $meta_table_id );

	//테이블 명
	$tablename = MetaDataClass::getTableName('usr', $meta_table_id );

	//기본 데이터유형 변환
	$metaValues = MetaDataClass::getDefValueRender('usr' , $meta_table_id , $metaValues);

	foreach ($fieldNameMap as $usr_meta_field_id => $name ) {
		$value = $metaValues[$usr_meta_field_id];
		$value = $db->escape($value);
		array_push($fieldKey, $name );
		array_push($fieldValue, $value);
	}

	if (MetaDataClass::isNewMeta($table_type, $meta_table_id , $content_id)) {

		// 신규 등록
		array_push($fieldKey, 'usr_content_id' );
		array_push($fieldValue, $content_id );
		$query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);
	} else {

		//업데이트
		$query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "usr_content_id='$content_id'" );
	}
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').']'.$query."\n", FILE_APPEND);

	$db->exec($query);

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

	if(!$is_update){        //신규일때

		//등록시 전송처 코드 입력 2013-02-15 이성용
		$register_type = 'E';//편집 전송 코드

		$r = $db->exec ("insert into CONTENT_CODE_INFO  (CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
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

	if(!$is_update){        //신규일때

		//등록시 전송처 코드 입력 2013-02-15 이성용
		$register_type = 'I';//편집 전송 코드

		$r = $db->exec ("insert into CONTENT_CODE_INFO  (CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
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

function createMaterialCategory($params) {

	$name = $params['4778411'];
	$code = $params['4778410'];

	$category = isExistsCategory($code);

	if ( ! empty($category)) {
		return $category['category_id'];
	} else {
		return addCategory($name, $code);
	}
}

function isExistsCategory($code) {
	global $db;

	return $db->queryRow("select * from bc_category where code='$code'");
}

function addCategory($name, $code) {
	global $db;

	$category_id = getSequence('SEQ_BC_CATEGORY_ID');

	$db->exec("
			insert into BC_CATEGORY (CATEGORY_ID ,PARENT_ID, CATEGORY_TITLE, CODE, NO_CHILDREN)
			values ($category_id, -2, '$name', '$code', 1)
			");

	return $category_id;
}

function makeTitleWithSuffix($title, $suffix) {
	if ( ! empty($title) && empty($suffix)) {
		$_title = $title;
	} else if ( ! empty($title) && ! empty($suffix)) {
		$_title = $title . '_' . $suffix;
	} else if (empty($title) && ! empty($suffix)) {
		$_title = $suffix;
	} else {
		$_title = 'No Title';
	}

	return $_title;
}

function getUserOfGroup($user_id) {
	global $db;

	$groups = array();

	$result = $db->queryAll('
select b.member_group_id
from bc_member a, bc_member_group_member b
        where a.user_id=%s', $user_id, '
          and a.member_id=b.member_id
    ');

	foreach ($result as $item) {
		array_push($groups, $item['member_group_id']);
	}

	return $groups;
}

function isGroupContent($content_id) {
	global $db;

	$group_type = $db->queryOne("SELECT IS_GROUP FROM BC_CONTENT WHERE CONTENT_ID = ".$content_id);
	if ($group_type == 'G' || $group_type == 'C') {
		return true;
	} else {
		return false;
	}
}


?>
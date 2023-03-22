<?php
/**
 * 2017-12-20 이승수
 * CJO 와치폴더 등록
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

use \Proxima\models\content\UserMetadata;

define('WATCHER_REGISTER_ID', 'nps');//와치폴더 등록시 사용자ID

try
{
	$title = $_REQUEST['title'];
	$filename = $_REQUEST['filename'];
	$user_id = $_REQUEST['user_id'];
	$watchername = $_REQUEST['watchername'];
	$org_filename = $_REQUEST['org_filename'];

	file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/watcher_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] title : '.$title."\nfilename : ".$filename."\nuser_id : ".$user_id."\nwatchername : ".$watchername."\norg_filename : ".$org_filename, FILE_APPEND);

	if(empty($user_id) || $user_id == 'watcher') {
		$user_id = WATCHER_REGISTER_ID;
	}

	if ($user_id != WATCHER_REGISTER_ID) {
		$user_nm = $db->queryOne("select user_nm from bc_member where user_id = '{$user_id}'");
		if ($user_nm == '') {
			$user_id = WATCHER_REGISTER_ID;
		}
	}
	
	if(!count($title)) throw new Exception('제목 값이 없습니다.(no title)');
	if(empty($filename)) throw new Exception('파일 경로 값이 없습니다.(no filename)');
	if(empty($org_filename)) throw new Exception('원본파일명 값이 없습니다.(no org_filename)');
    
    $ud_all = $db->queryAll("select * from bc_ud_content");
    foreach($ud_all as $ud_info) {
        if($ud_info['ud_content_code'] == strtoupper($watchername)) {
            $ud_content_id = $ud_info['ud_content_id'];
        }
    }

    if(empty($ud_content_id)) {
        throw new Exception('지원하지 않는 콘텐츠형식 입니다.('.$watchername.')');
    }
	
	$content_id = getSequence('SEQ_CONTENT_ID');
	$map_categories = getCategoryMapInfo();
	$root_category_id = $map_categories[$ud_content_id]['category_id'];
	$root_category_text = $map_categories[$ud_content_id]['category_title'];
	$category_id = $root_category_id;//기본값

	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\IngestCustom')) {
		$category_id = \ProximaCustom\core\IngestCustom::getCategoryByUdContent($ud_content_id, $category_id);
	}

	$bs_content_id = MOVIE;
	insertContent($content_id, $category_id, $bs_content_id, $ud_content_id, $title, $user_id);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/watcher_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] insertContent('.$content_id.', '.$category_id.', '.$bs_content_id.', '.$ud_content_id.', '.$title.', '.$user_id.')', FILE_APPEND);

	// media_value 테이블에 빈값추가 ::
	$userMeta = UserMetadata::create($content_id, $ud_content_id);
	$userMeta->save();

	//원본 파일명 등록
	$sys_metafields = MetaDataClass::getFieldNametoIdMap('sys',$bs_content_id);
	$sysInfo = array();
	$ori_filename = $sys_metafields[sys_ORI_FILENAME];
	$sysInfo[$ori_filename] = $db->escape($org_filename);
	InterfaceClass::insertSysMetaValus($sysInfo, $content_id, $bs_content_id);

	$task = new TaskManager($db);
	$channel = 'watchfolder';

	 /* 사전제작일 경우에만 QC적용된 워크플로우 태우도록 판단하는 함수 - 2018.03.20 Alex */
	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\IngestCustom')) {		
		$channel = \ProximaCustom\core\IngestCustom::getTaskChannelByUdContent($ud_content_id, $channel);		
	}	
	
	$filename = 'upload\\'.$filename;
	$org_filename = addslashes($org_filename);
	$path = str_replace('\\', '/', $filename);
	$paths = addslashes($path);
	$task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $paths);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/watcher_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] start_task_workflow(task_id:'.$task_id.')', FILE_APPEND);
	searchUpdate($content_id);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/watcher_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] searchUpdate(content_id:'.$content_id.')', FILE_APPEND);

	$action = 'regist';
	$description = 'watchfolder';
	insertLog($action, $user_id, $content_id, $description);

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/watcher_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Success(content_id:'.$content_id.')', FILE_APPEND);

	echo json_encode(array(
		'success' => true,
		'msg' => 'ok'
	));
}
catch(Exception $e)
{
	$msg = $e->getMessage();
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg .= $db->last_query;
		break;
    }

    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/watcher_register_'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] Error(content_id:'.$content_id.'),'.$msg, FILE_APPEND);

	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}


function insertContent($content_id, $category_id, $bs_content_id,$ud_content_id, $title, $user_id) {
	global $db;

	$category_full_path = getCategoryFullPath($category_id);
	$cur_time		   = date('YmdHis');

	$expired_date = '9999-12-31';

	//제목
	$title = trim($title);
	if (empty($title)){
		$title ='no title';
	}

	//2016-02-24 INSERT QUERY 수정
	$insert_data = array(
			'CATEGORY_ID' => $category_id,
			'CATEGORY_FULL_PATH' => $category_full_path,
			'BS_CONTENT_ID' => $bs_content_id,
			'UD_CONTENT_ID' => $ud_content_id,
			'CONTENT_ID' => $content_id,
			'TITLE' => $title,
			'REG_USER_ID' => $user_id,
			'CREATED_DATE' => $cur_time,
			'STATUS' => INGEST_READY,
			'EXPIRED_DATE' => $expired_date
	);

	$db->insert('BC_CONTENT', $insert_data);

	$action = 'regist';
	$description = 'nle register';
	insertLog($action, $user_id, $content_id, $description);

	return $content_id;
}

function  insertMetaValues($metaValues, $content_id, $ud_content_id ) {
	global $db;
	//$r = $db->exec("delete from meta_value where content_id=".$content_id);

	$fieldKey = array();
	$fieldValue = array();

	//필드 목록 배열
	$metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $ud_content_id );

	//테이블 명
	$tablename = MetaDataClass::getTableName('usr', $ud_content_id );

	//usr_meta_field_id가 아니라 usr_meta_field_code로 등록하도록 바꿈
	foreach ($metaValues as $usr_meta_field_code => $value ) {
		if (!preg_match('/^usr\_/', $usr_meta_field_code)) continue;
		foreach($metaFieldInfo as $metaInfo) {
			if('USR_'.$metaInfo['usr_meta_field_code'] == strtoupper($usr_meta_field_code)) {
				if($usr_meta_field_code == 'usr_item_list'){
					//CJO, 아이템 리스트 DB에 저장
					if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
						if($value != null) {
							$items = json_decode($value, true);
							\ProximaCustom\models\metadata\Item::saveItems($content_id, $items);
						}
					}
				} else if( in_array($metaInfo['usr_meta_field_type'], array('datefield','broad_datetime')) && !empty($value)) {
					$value = date('YmdHis', strtotime($value));
				} else {
					$value = $db->escape($value);
				}
			}
		}
		array_push($fieldKey, $usr_meta_field_code );
		array_push($fieldValue, $value);
	}

	if (MetaDataClass::isNewMeta($table_type, $ud_content_id , $content_id)) {
		// 신규 등록
		array_push($fieldKey, 'usr_content_id' );
		array_push($fieldValue, $content_id );
		$insert_arr = array_combine($fieldKey,$fieldValue);
		$db->insert($tablename ,$insert_arr);
	} else {

		//업데이트
		$update_arr = array_combine($fieldKey,$fieldValue);
		$db->update($tablename ,$update_arr, "usr_content_id=$content_id" );
	}

	return true;
}
?>
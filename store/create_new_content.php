<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

try {
	$user_id = $_SESSION['user']['user_id'];

	$content_id = $_POST['content_id'];
	$title = $_POST['title'];
	$tc_start	= $_POST['start'];
	$tc_end		= $_POST['end'];

    $channel = 'PFR_MXF';

    $tc_start = (int) ($tc_start * 29.97);
    $tc_end = (int) ($tc_end * 29.97);

//    print_r($_REQUEST);

    $new_content_id = getSequence('SEQ_CONTENT_ID');
    $db->exec("INSERT INTO BC_CONTENT
                            (CATEGORY_ID,
                            CATEGORY_FULL_PATH,
                            BS_CONTENT_ID,
                            UD_CONTENT_ID,
                            CONTENT_ID,
                            TITLE,
                            REG_USER_ID,
                            EXPIRED_DATE,
                            CREATED_DATE,
                            STATUS)
                    SELECT CATEGORY_ID,
                            CATEGORY_FULL_PATH,
                            BS_CONTENT_ID,
                            UD_CONTENT_ID,
                            {$new_content_id},
                            '{$title}',
                            '{$user_id}',
                            EXPIRED_DATE,
                            '".date("YmdHis")."',
                            -3
                      FROM BC_CONTENT
                     WHERE CONTENT_ID=".$content_id);
    $ud_content_id = $db->queryOne("SELECT UD_CONTENT_ID FROM BC_CONTENT WHERE CONTENT_ID=".$content_id);

	$original_info = $db->queryRow("SELECT * FROM BC_MEDIA WHERE MEDIA_TYPE='original' AND CONTENT_ID=".$content_id);
	$original_file = $original_info['path'];

//	//new_content_id에 original 값 넣어주기(작업 밀어넣을때 실패하니까)
//	$media_id 	= getSequence('SEQ_MEDIA_ID');
//	$db->exec("insert into bc_media ".
//			"(CONTENT_ID, MEDIA_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE , VR_START, VR_END ) ".
//		"values ".
//			"('$new_content_id', '$media_id', '".$original_info['storage_id']."', 'original', '$path', '$filesize', '$cur_time', '$reg_type' , '$ori_vr_start', '$ori_vr_end' )");

    $table = MetaDataClass::getTableName('usr', $ud_content_id);
    $field = MetaDataClass::getFieldIdtoNameMap('usr', $ud_content_id);
    $db->exec("
        INSERT INTO $table (USR_CONTENT_ID, " . join(', ', $field) . ")
        SELECT $new_content_id, " . join(', ', $field) . "
        FROM $table
        WHERE USR_CONTENT_ID = $content_id
    ");

    $arr_param_info = array(
        array('value' => $tc_start),
        array('value' => $tc_end)
    );

    $task = new TaskManager($db);
    $task->insert_task_query_outside_data($new_content_id, $channel, 1, $user_id, $original_file, null, $arr_param_info);
//    $task->start_task_workflow($new_content_id, $channel, $user_id, $arr_param_info);

//    copyContent($content_id, $tc_start, $tc_end);

	echo '{"success":true}';
} catch (Exception $e) {
	echo $e->getMessage().' '.$db->last_query;
}

function copyContent($old_content_id, $tc_start, $tc_end) {
	global $db;

    //프레임단위로 변경
	$tc_start	= (int)($tc_start * 29.97);
	$tc_end		= (int)($tc_end * 29.97);

    // task 등록
	$channal = 'PFR_NEW_REGIST';

	$content_id = copyNewContent($old_content, $old_content_id );
	copyNewContentValue($old_system_values, $content_id);
	copyNewMedia($old_medias, $content_id , $tc_start, $tc_end, $channal);
	copyNewMetaValue($old_user_meta_values, $content_id);

	$orginal_media_path = $db->queryOne("select path from bc_media where media_type='original' and content_id=".$old_content_id);
	$path_array = explode('/', $orginal_media_path);
	$path_file = array_pop($path_array);
	$path_file_array =  explode('.', $path_file);
	$path_file_ext =  array_pop($path_file_array);

	$path_file_name = join('', $path_file_array);
	$pfr_media_path = date('Y/m/d/H/is').'/'.$path_file_name.'_pfr_'.$content_id.'_'.$pfr_cnt.substr($orginal_media_path, strrpos($orginal_media_path, '.'));

	$cur_time = date('YmdHis');

	insert_task_query($content_id, $orginal_media_path, $pfr_media_path, $cur_time, $channal);

	return true;
}


////////////////////////////
//////////// 함수
////////////////////////////

function copyNewContent($content, $content_id )
{
	global $db;

	$bs_content_id		= $content['bs_content_id'];
	$ud_content_id		= $content['ud_content_id'];
	$user_id		= is_null( $_SESSION['user']['user_id'] ) ? $content['reg_user_id'] : $_SESSION['user']['user_id'];
	$category_id		= $content['category_id'];
	$title				= $db->escape( $content['title'] );
	$category_full_path	= $content['category_full_path'];
	$cur_time 			= date('YmdHis');
	$expired_date		= $content['expired_date'];

//	if ($expired_date != -1)
//	{
//		$d = new DateTime();
//		$d->add(new DateInterval($expired_date));
//		$expired_date = $d->format('Y-m-d');
//	}
//	else
//	{
//		$expired_date = '9999-12-31';
//	}

	$content_id = getSequence('SEQ_CONTENT_ID');

	$insert_content_query = $db->exec("insert into bc_content(CATEGORY_ID, CATEGORY_FULL_PATH, ".
															"BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, ".
															"TITLE, REG_USER_ID, ".
															"CREATED_DATE, STATUS, EXPIRED_DATE) ".

												"values('$category_id', '$category_full_path', '$bs_content_id', ".
															"'$ud_content_id', '$content_id', '$title', ".
															"'$user_id', '$cur_time', '".INGEST_READY."', '$expired_date')");

	//log테이블에 기록 남김 /function 으로 변경 2011-04-11 by 이성용
	$action = 'regist';
	$description = 'pfr_regist';
	insertLog($action, $user_id, $content_id, $description);

	return $content_id;
}

function copyNewMedia($medias, $content_id , $vr_start , $vr_end, $channal)
{
	global $db;

	$r = $db->exec("delete from bc_media where content_id=".$content_id);

	foreach ($medias as $media) {
		$media_id 	= getSequence('SEQ_MEDIA_ID');
		$media_type		= $media['media_type'];
		$path		= $db->escape( $media['path'] );
		$filesize	= $media['filesize'];
		$reg_type	= $channal;
		$cur_time	= date('YmdHis');

		$storage_id 	=  $media['storage_id'];
		if ( ! ($media_type == 'original' ||   $media_type == 'proxy' ||  $media_type == 'thumb')) continue;

		if ($media_type == 'original') {
			$ori_vr_start = $vr_start;
			$ori_vr_end = $vr_end;
		} else if ($media_type == 'thumb') {
			$path = 'incoming.jpg';
		} else {
			$path = 'Temp';
		}

		$db->exec("insert into bc_media ".
							"(CONTENT_ID, MEDIA_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE , VR_START, VR_END ) ".
						"values ".
							"('$content_id', '$media_id', '$storage_id', '$media_type', '$path', '$filesize', '$cur_time', '$reg_type' , '$ori_vr_start', '$ori_vr_end' )");

		unset($ori_vr_start);
		unset($ori_vr_end);
	}
}

function copyNewContentValue($systems, $content_id) {
	global $db;

	$r = $db->exec("delete from BC_SYS_META_VALUE where content_id=".$content_id);

	foreach($systems as $system)
	{
		$sys_meta_field_id 	= $system['sys_meta_field_id'];
		$sys_meta_value		= $db->escape($system['sys_meta_value']);
		$db->exec("insert into BC_SYS_META_VALUE (CONTENT_ID,SYS_META_FIELD_ID,SYS_META_VALUE) values('$content_id', '$sys_meta_field_id', '$sys_meta_value')");
	}
}

function copyNewMetaValue($meta_values, $content_id)
{
	global $db;

	$r = $db->exec("delete from BC_USR_META_VALUE where content_id=".$content_id);

	foreach($meta_values as $meta_value)
	{

		$ud_content_id		= $meta_value['ud_content_id'];
		$usr_meta_field_id 	= $meta_value['usr_meta_field_id'];
		$usr_meta_value		= $db->escape($meta_value['usr_meta_value']);

		$db->exec ("insert into BC_USR_META_VALUE (CONTENT_ID,UD_CONTENT_ID,USR_META_FIELD_ID,USR_META_VALUE) values ('$content_id', '$ud_content_id', '$usr_meta_field_id','$usr_meta_value')");
	}
}

function updateMetaMultiValue( $ingest_id , $content_id ) //소재영상일때 TC정보 등록 함수 by 이성용 2011-3-17
{
	global $db;
	$meta_field_id = $db->queryOne("select meta_field_id from meta_field where meta_table_id='81767' and type='listview' and name='TC정보' ");//TC정보 meta_field_id
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

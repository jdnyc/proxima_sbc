<?php
session_start();

require_once('common.php');
require_once('DBOracle2.class.php');
$db = new Database('nps', 'nps', '192.168.0.102/knnnpsdb01');

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/content.util.php');

$fields			= json_decode($_POST['fields'], true);

$content_id		= getSequence('seq_content_id');
$ud_content_id	= $_POST['content_type_id'];
$category_id	= $_POST['category_id'];
$title			= $_POST['title'];
$category_full_path = '/0'.getCategoryFullPath($category_id);
$bs_content_id	= getBsContentId($ud_content_id); //echo $bs_content_id;
$reg_user_id	= $_SESSION['user']['user_id'];
$expired_date	= getExpiredDate($ud_content_id); //echo $expired_date;
$created_date	= date('YmdHis');
$status			= -10;
$storage_id		= $_POST['storage_id'];
$file			= $_POST['file'];
$source_file	= $file;
$filename		= preg_replace('/^tmp\_/', '', basename($file));
$target_file	= buildPath($filename, $content_id);
$media_type		= 'original';

try {

	// 트랜잭션 시작
	$db->setTransaction(true);

	$queryInsertContent = "insert into bc_content 
								(CATEGORY_ID, CATEGORY_FULL_PATH, BS_CONTENT_ID, UD_CONTENT_ID, CONTENT_ID, TITLE, REG_USER_ID, EXPIRED_DATE, CREATED_DATE, STATUS) 
							values 
								($category_id, '$category_full_path', $bs_content_id, $ud_content_id, $content_id, '$title', '$reg_user_id', '$expired_date', '$created_date', '$status')";
	//echo $queryInsertContent;
	$result = $db->exec($queryInsertContent);
	if (!$result) {
		handleError('insert content error : ' . $db->error);
	}

	foreach ($fields as $field) {
		$queryInsertUsrMetaFieldValue = "INSERT INTO BC_USR_META_VALUE 
						(CONTENT_ID, UD_CONTENT_ID, USR_META_FIELD_ID, USR_META_VALUE) 
					VALUES 
						($content_id, $ud_content_id, {$field['id']}, '{$field['value']}')";
		//echo $queryInsertUsrMetaFieldValue.chr(10);
		$result = $db->exec($queryInsertUsrMetaFieldValue);
		if (!$result) {
			handleError('insert usr_meta_field_value error : ' . $db->error);
		}
	}

	$queryInsertMedia = "INSERT INTO BC_MEDIA 
						(CONTENT_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE) 
					VALUES 
						($content_id, $storage_id, '$media_type', '$target_file', '$filesize', '$created_date', '$reg_user_id')";
	$result = $db->exec($queryInsertMedia);
	if (!$result) {
		handleError('insert content error : ' . $db->error);
	}
	$media_id = getLastMediaId();


	$queryInsertMedia = "INSERT INTO BC_MEDIA 
						(CONTENT_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE) 
					VALUES 
						($content_id, $storage_id, 'thumb', 'incoming.jpg', '0', '$created_date', '$reg_user_id')";
	$result = $db->exec($queryInsertMedia);
	if (!$result) {
		handleError('insert content error : ' . $db->error);
	}

	$queryInsertMedia = "INSERT INTO BC_MEDIA 
						(CONTENT_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE) 
					VALUES 
						($content_id, $storage_id, 'proxy', 'Temp', '$filesize', '$created_date', '$reg_user_id')";
	$result = $db->exec($queryInsertMedia);
	if (!$result) {
		handleError('insert content error : ' . $db->error);
	}

	$channel = 'CONTENT_CENTER_TO_'.$ud_content_id;

	//echo $content_id.' '.$source_file.' '.$target_file.' '.$created_date.' '.$channel;
	insert_task_query($content_id, $source_file, $target_file, $created_date, $channel, $media_id);

	// 커밋
	$db->commit();

	echo json_encode(array(
		'success' => true
	));
}
catch (Exception $e) {
	//롤백
	$db->rollback();

	handleError('insert content error : ' . $db->error);
}
?>
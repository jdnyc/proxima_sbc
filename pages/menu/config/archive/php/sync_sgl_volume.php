<?php
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/db.mssql.php');

try{
	$sgl = new SGL();

	$query = "SELECT  B.REQUEST_ID
			--,A.*
	FROM    ARCHIVE_HEADER A
			LEFT OUTER JOIN API_SESSION B ON(B.ARCHIVE_ID = A.ARCHIVE_ID)
	WHERE   DATEDIFF(DAY, A.CR_TIME, GETDATE()) <= 20
	AND     B.REQUEST_ID > 0
	;";
	$id_all = db_ms_fetchAll($query);
	$arr_request_id = array();
	foreach($id_all as $id) {
		$arr_request_id[] = $id['request_id'];
	}

	if(!empty($arr_request_id)) {
		$request_id_in = implode(',', $arr_request_id);
		$content_info_all = $db->queryAll("
			SELECT	*
			FROM 	SGL_ARCHIVE
			WHERE	SESSION_ID IN (".$request_id_in.")
		");
		foreach($content_info_all as $sgl_info) {
			$unique_id = $sgl_info['unique_id'];//Actually, content_id
			$vol_one = $sgl->FlashNetListGuid($unique_id);
			$db->exec("DELETE FROM SGL_ARCHIVE_VOLUME WHERE CONTENT_ID=".$unique_id);
			if( $vol_one['groups'] ) {
				foreach($vol_one['groups']->File as $file) {
					$db->exec("INSERT INTO SGL_ARCHIVE_VOLUME
						(CONTENT_ID,VOLUME_NAME,VOLUME_GROUP,STATUS,ARCHIVEDATE)
						VALUES
						(".$unique_id.",'".(string)$file['VolumeName']."','".(string)$file['VolumeGroup']."','".(string)$file['Status']."','".(string)$file['ArchiveDate']."')");
				}
			}
		}
	}

	echo json_encode(array(
		'success' => true,
		'msg' => _text('MSG02114')//'The FlashNet volume information syncronize is completed successfully.'
	));
} catch(Exception $e) {
	$msg = $e->getMessage();
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}


?>
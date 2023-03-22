<?php

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/SGL.class.php');

$work_type = $_POST['work_type'];
$content_id = $_POST['content_id'];
$task_id = $_POST['task_id'];

if($work_type == 'get_log_and_volume') {
	$sgl = new SGL();
	$archive_info = $db->queryRow("SELECT * FROM SGL_ARCHIVE WHERE TASK_ID=".$task_id);
	$logkey = $archive_info['logkey'];
	$unique_id = $archive_info['unique_id'];//Actually, content_id
	$log_one = $sgl->FlashNetReadLog($logkey);
	$log_one = (string)$log_one['logs'][0];
	if($log_one != '') {
		$log_msg[] = $log_one;
	}
	if(empty($log_msg)) {
		$log_msg = '';
	} else {
		$log_msg = implode("\n", $log_msg);
	}
	$log_msg = $db->escape($log_msg);

	$update_data = array(
		'logtext'			=>	 ':logtext'
	);
	$query_update_sgl = $db->update('SGL_ARCHIVE', $update_data, ' TASK_ID = '.$task_id.' ','not exec');
	$query_update_sgl_clob = str_replace("':logtext'", ":logtext", $query_update_sgl);
	$db->clob_exec($query_update_sgl_clob,':logtext',$log_msg, -1);

	$vol_one = $sgl->FlashNetListGuid($unique_id);
	$db->exec("DELETE FROM SGL_ARCHIVE_VOLUME WHERE CONTENT_ID=".$content_id);
	if( $vol_one['groups'] ) {
		foreach($vol_one['groups']->File as $file) {
			$db->exec("INSERT INTO SGL_ARCHIVE_VOLUME
					(CONTENT_ID,VOLUME_NAME,VOLUME_GROUP,STATUS,ARCHIVEDATE)
					VALUES
					(".$content_id.",'".(string)$file['VolumeName']."','".(string)$file['VolumeGroup']."','".(string)$file['Status']."','".(string)$file['ArchiveDate']."')");
		}
	}
}

?>
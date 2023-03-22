<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
function getBsContentId($ud_content_id) {
	global $db;

	return $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id=".$ud_content_id);
}

function getExpiredDate($ud_content_id) {
	global $db;

	$expired_date = '9999-12-31';

	return $expired_date;
}

function getLastMediaId() {
	global $db;

	return $db->queryOne("SELECT MAX(MEDIA_ID) FROM BC_MEDIA");
}

function getSequence($seq_name)
{
	global $db;

	$seq_name = trim($seq_name);
	if( DB_TYPE == 'oracle' ){
		$query_seq = "select ".$seq_name.".nextval from dual";
	}else{
		$query_seq ="select nextval('".$seq_name."')";
	}
	return $db->queryOne($query_seq);
}

function buildPath($filename, $content_id) {

	return date('Y/m/d/Hi').'/'.$content_id.'/'.$filename;
}
?>
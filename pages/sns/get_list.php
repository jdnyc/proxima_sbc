<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$limit = !empty($_POST['limit']) ? $_POST['limit'] : 50;
	$start = !empty($_POST['start']) ? $_POST['start'] : 0;

	$s_date = $_POST['s_date'];
	$e_date = $_POST['e_date'];
	$keyword = $_POST['keyword'];
	$keyword = strtolower($keyword);

	if(!empty($s_date)) {
		$date_query = " AND CREATED_DATE BETWEEN '".$s_date."' AND '".$e_date."' ";
	}
	if(!empty($keyword)) {
		$key_query = " AND (LOWER(CONTENT) LIKE '%".$keyword."%' OR LOWER(TITLE) LIKE '%".$keyword."%') ";
	}

	//For count
	$count_query = "
		SELECT	*
		FROM	BC_SOCIAL_TRANSFER 
		WHERE	1=1
		".$date_query.$key_query."
	";

	$total = $db->queryOne("SELECT COUNT(*) FROM (".$count_query.") A");

	//For data. More join.
	$query = "
		SELECT	B.USER_NM AS REG_USER_NM
				,C.NAME AS SOCIAL_TYPE_NM
				,A.*
		FROM	(
				SELECT	*
				FROM	BC_SOCIAL_TRANSFER 
				WHERE	1=1
				".$date_query.$key_query."
				) A 
				LEFT OUTER JOIN
				BC_MEMBER B
				ON (A.REG_USER_ID=B.USER_ID)
				LEFT OUTER JOIN
				BC_CODE C
				ON (A.SOCIAL_TYPE=C.CODE)
		ORDER BY A.SNS_SEQ_NO DESC
	";

	$db->setLimit($limit,$start);
	$arr_info = $db->queryAll($query);
	$data = array();
	$youtube_url = 'https://www.youtube.com/';
	$facebook_url = 'https://www.facebook.com/';
	$twitter_url = 'https://twitter.com/';
	foreach($arr_info as $ai)
	{
		if($ai['created_date'] != '') {
			$ai['created_date'] = date('Y-m-d H:i:s', strtotime($ai['created_date']));
		}
		if($ai['deleted_date'] != '') {
			$ai['deleted_date'] = date('Y-m-d H:i:s', strtotime($ai['deleted_date']));
		}

		if($ai['social_type'] == 'YOUTUBE') {
			if($ai['web_url1'] != '') {
				$ai['web_url1'] = '<a href="'.$ai['web_url1'].'" target="_blank">'.$ai['web_url1'].'<a/>';
			} else {
				$ai['web_url1'] = '<a href="'.$youtube_url.'" target="_blank">'.$youtube_url.'<a/>';
			}
		}
		if($ai['social_type'] == 'FACEBOOK') {
			if($ai['web_url1'] != '') {
				$ai['web_url1'] = '<a href="'.$ai['web_url1'].'" target="_blank">'.$ai['web_url1'].'<a/>';
			} else {
				$ai['web_url1'] = '<a href="'.$facebook_url.'" target="_blank">'.$facebook_url.'<a/>';
			}
		}
		if($ai['social_type'] == 'TWITTER') {
			if($ai['web_url1'] != '') {
				$ai['web_url1'] = '<a href="'.$ai['web_url1'].'" target="_blank">'.$ai['web_url1'].'<a/>';
			} else {
				$ai['web_url1'] = '<a href="'.$twitter_url.'" target="_blank">'.$twitter_url.'<a/>';
			}
		}

		array_push($data, $ai);
	}

	echo json_encode(array(
		'success' => true,
		'data' => $data,
		'total' => $total
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

?>
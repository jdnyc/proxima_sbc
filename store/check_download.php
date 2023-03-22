<?php
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

try
{
	$user_id = $_SESSION['user']['user_id'];

	if( is_null($user_id) || ( $user_id == 'temp' ) ) throw new Exception('세션이 만료되어 로그인이 필요합니다.');

	$content_ids	= json_decode($_REQUEST['content_id_list'], true);

	$query = "
		SELECT	*
		FROM	BC_MEDIA
		WHERE	CONTENT_ID IN(".join(',', $content_ids).") AND
				MEDIA_TYPE = '".$_REQUEST['media_type']."'
	";
	$media = $db->queryAll($query);

	$a_possible = array();
	if(count($media)>0)
	{
		foreach($media as $m)
		{
			array_push($a_possible, $m[content_id]);
		}
	}


	$request = count($content_ids);
	$possible = count($media);
	$impossible = $request-$possible;

	if( $possible == 0 )
	{
		$msg = '다운로드 할 수 있는 콘텐츠가 없습니다.';
	}
	else if( $impossible>0 )
	{
		$msg = '다운로드 할 수 없는 콘텐츠가 포함되어 있습니다.<br>나머지 파일들을 다운로드 하시겠습니까?';
	}

	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'request' => $request,
		'possible' => $possible,
		'impossible' => $impossible,
		'records' => $a_possible
	));
} catch(Exception $e) {

	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}


?>
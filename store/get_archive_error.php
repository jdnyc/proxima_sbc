<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/favorite_functions.php');

try
{

	$user_id = $_SESSION['user']['user_id'];

	if(is_null($user_id) || $user_id == 'temp')
	{
		throw new Exception ('재 로그인이 필요합니다');
	}

	$cur_datetime = date('YmdHis');

	$action = $_POST['action'];
        $start	= $_POST['start'];
        $limit	= $_POST['limit'];

	switch($_POST['action'])
	{
		case 'listing':
			$query = "select c.* from BC_ARCHIVE_ERR f,view_content c where f.content_id=c.content_id and c.is_deleted='N' order by c.content_id desc";
                        $db->setLimit($limit, $start);
			$content_list = $db->queryAll($query);

			$data = fetchMetadata($content_list);
                        $total = $db->queryOne("select count(*) from (".$query.") cnt");
		break;

		default: 
			throw new Exception ('알수 없는 action 입니다');
		break;
	}

	
	echo json_encode(array(
		'success' => true,
                'total' => $total,
		'data' => $data,
		'action' => $action
	));
}
catch (Exception $e)
{
	$msg = $e->getMessage();
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
}

?>
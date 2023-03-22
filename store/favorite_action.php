<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/get_content_list/libs/functions.php');

try
{

	$user_id = $_SESSION['user']['user_id'];

	if(is_null($user_id) || $user_id == 'temp')
	{
		throw new Exception ('재 로그인이 필요합니다');
	}

	$cur_datetime = date('YmdHis');

	$action = $_POST['action'];

	switch($_POST['action'])
	{
		case 'listing':
			$query = "select c.* from BC_FAVORITE f,view_content c where f.content_id=c.content_id and f.user_id='$user_id' and c.is_deleted='N' and c.status > '0' order by f.show_order desc";
			$content_list = $db->queryAll($query);

			$data = fetchMetadata($content_list);
		break;


		case 'delete':

			$contents = json_decode($_POST['records'], true);

			foreach ($contents as $content)
			{
				$content_id = $content['content_id'];

				$db->exec("delete from BC_FAVORITE where user_id='$user_id' and content_id='$content_id'");
			}

		break;

		case 'add':

		$contents = json_decode($_POST['records'], true);

		foreach ($contents as $content)
		{
			$content_id = $content['content_id'];
			$show_order = date('YmdHis');

			$is_list = $db->queryOne("select content_id from BC_FAVORITE where user_id='$user_id' and content_id='$content_id'");

			if($is_list)
			{
				$db->exec("update BC_FAVORITE set SHOW_ORDER='$show_order' where user_id='$user_id' and content_id='$content_id'");
			}
			else
			{
				$db->exec("insert into BC_FAVORITE (USER_ID, CONTENT_ID, SHOW_ORDER)  values ('$user_id', '$content_id' , '$show_order')");
			}
		}
		break;

		default:
			throw new Exception ('알수 없는 action 입니다');
		break;
	}


	echo json_encode(array(
		'success' => true,
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
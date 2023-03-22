<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

use Proxima\core\Session;
use Proxima\core\Response;

Session::init();

$userId = Session::get('user')['user_id'];

Session::checkUserAuth();

try{
	if (empty($userId)) {
		Response::echoJsonOk('userId is empty.');
		die();
	}
	$action = $_POST['action'];

	switch( $action ){
		case 'collapse':
			$update_query = " CATEGORY_VISIBLE = 'N' ";
		break;
		case 'expand':
			$update_query = " CATEGORY_VISIBLE = 'Y' ";
		break;
		case 'category_width':
			$update_query = " CATEGORY_WIDTH = ".$_POST['value']." ";
		break;
		default:
			$update_query = " CATEGORY_VISIBLE = 'N' ";
		break;
	}

	$query = "
		UPDATE	BC_MEMBER_OPTION	 SET
					".$update_query."
		WHERE	MEMBER_ID = (
											SELECT	MEMBER_ID
											FROM		BC_MEMBER
											WHERE	USER_ID = '{$userId}'
										)
	";

	$db->exec($query);
	
	Response::echoJson([
		'success' => true,
		'query'	=>	 $query
	]);

} catch(Exception $e) {

	Response::echoJson([
		'success' => false,
		'msg' => $e->getMessage(),
		'query'	=>	 $query
	]);
}
?>
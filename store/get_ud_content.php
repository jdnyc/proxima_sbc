<?php
session_start();

use Proxima\core\Request;
use Proxima\core\Response;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');


$user_id = $_SESSION['user']['user_id'];
$includeAll = Request::get('includeAll');

$query = "select ud_content_id, ud_content_title from bc_ud_content
	order by show_order";

$ud_contents = $db->queryAll($query);
$total=0;
$result = [];
if($includeAll == 'Y') {
	$result[] = array(
		'ud_content_id' => 'all',
		'ud_content_title' => '전체'
	);
}

foreach($ud_contents as $row) {
	if(checkAllowUdContentGrant($user_id, $row['ud_content_id'], GRANT_READ)) {
		array_push($result, $row);
	}
}

echo json_encode(array(
	"success" => true,
	"total" => $total,
	"user_id" => $user_id,
	"data" => $result
));

?>
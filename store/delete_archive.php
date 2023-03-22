<?php
require_once("../lib/config.php");
require_once("../lib/functions.php");

$content_id = $_POST['content_id'];



$media = $db->queryRow("select media_id as id, path from bc_media where media_type='original' and content_id=".$content_id);
$archive = $db->queryRow("select * from alto_archive where media_id=".$media['id']);
$alto_id = 'alto';
$alto_pw = 'password';


// ALTO
$ip = '192.168.0.99';
$port = 6480;

$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
	HandleError("소켓 생성 실패: ".iconv('euc-kr', 'utf-8', socket_strerror(socket_last_error())));
}

$result = @socket_connect($socket, $ip, $port);
if ($result === false) {
	HandleError("소켓 연결 실패: ".iconv('euc-kr', 'utf-8', socket_strerror(socket_last_error())));
}

$put = "get|db";
socket_write($socket, $put, strlen($put));
while ($out = socket_read($socket, 2048)) {
	$in .= $out;
}
socket_close($socket);


$xml = simplexml_load_string($in);

foreach ($xml->disks->disk as $disk)
{
	if ((string)$disk['uuid'] == $archive['uuid']) {
		$disk_name = (string)$disk['name'];
		$disk_name = substr($disk_name, strrpos($disk_name, '/')+1);
		break;
	}
}
// END ALTO

$channel = 'ALTO_DELETE';
$source = 'alto\\'.$disk_name.'\\'.$archive['archive_id'].'.mxf';
$cur_time = date('YmdHis');

$task = insert_task_query($content_id, $source, '', $cur_time, $channel, $media['id']);


$db->exec("DELETE FROM ALTO_ARCHIVE WHERE MEDIA_ID=".$media['id']);

echo json_encode(array(
	'success' => true
));
?>
<?php
require_once('common.php');
require_once('ExFTP.class.php');

$type = !empty($_POST['type']) ? strtoupper($_POST['type']) : 'ALL';
$sort = !empty($_POST['sort']) ? $_POST['sort'] : 'type';
$path = !empty($_POST['path']) ? $_POST['path'] : '.';

$sites = parse_ini_file('../config/site.info.php', true);
$nodes = array();

$arrPaths = explode('/', $path);
if (count($arrPaths) == 1) {
	$site = $arrPaths[0];
	$path = '';
}
else {
	$site = current(array_slice(explode('/', $path), 0, 1));
	$path = implode('/', (array_slice(explode('/', $path), 1)));
}
$site_info = $sites[$site];

if (empty($site_info)) {
	handleError('"'.$site.'"에 대한 사이트 정보가 없습니다.');
}

$ftp = new ExFTP($site_info['host'], $site_info['port'], $site_info['id'], $site_info['password'], $site_info['path'], $site_info['storage_id']);

switch ($type) {
case "ALL":
	$files = $ftp->getAllList($path, '/^tmp\\_/');
	break;

case "DIR":
	$files = $ftp->getDirList($path);
	break;

case "FILE":
	$files = $ftp->getFileList($path);
	break;

default:
	handleError('"type"정의 되어있지 않습니다.');
	break;
}

if (count($files) > 0) {
	unset($type);
	foreach ($files as $k=>$row) {
		$type[$k] = $row[$sort];
	}
	array_multisort($type, SORT_ASC, $files);
}

echo json_encode(array(
	'success' => true,
	'data' => $files
));
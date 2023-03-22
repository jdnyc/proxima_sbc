<?php
require_once('ExFTP.class.php');

$node = $_POST['node'];

$sites = parse_ini_file('../config/site.info.php', true);
$nodes = array();

if ($node == 'root') {
	foreach ($sites as $site_name => $site_info) {
		$nodes[] = array(
			'text' => $site_name,
			'id' => $site_name
		);
	}
}
else {
	$arrPaths = explode('/', $node);
	if (count($arrPaths) == 1) {
		$site = $arrPaths[0];
		$path = '';
	}
	else {
		$site = current(array_slice(explode('/', $node), 0, 1));
		$path = implode('/', (array_slice(explode('/', $node), 1)));
	}
	$site_info = $sites[$site];

	try {
		$ftp = new ExFTP($site_info['host'], $site_info['port'], $site_info['id'], $site_info['password'], $site_info['path']);

		$files = $ftp->getDirList($path);
		foreach ($files as $file) {
			$nodes[] = array(
				'text'  => $file['name'],
				'id'	=> $node.'/'.$file['name']
			);
		}
	}
	catch (Exception $e) {
		echo $e->getMessage();
	}
}


echo json_encode($nodes);
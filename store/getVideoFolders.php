<?php
require_once "../vendor/autoload.php";

use Touki\FTP\FTP;
use Touki\FTP\FTPFactory;
use Touki\FTP\Model\Directory;
use Touki\FTP\Model\File;
use Touki\FTP\Connection\Connection;
use Touki\FTP\FTPWrapper;

$destinations = array(
	"A" => array(
		"host" => "192.168.60.42",
		"port" => "21",
		"login" => "mxfmovie",
		"password" => ""
	),
	"B" => array(
		"host" => "192.168.60.44",
		"port" => "21",
		"login" => "mxfmovie",
		"password" => ""
	)
);

try {
	$destination = $_POST['destination'];
	$folders = array();

	$host = $destinations[$destination]['host'];
	$port = $destinations[$destination]['port'];
	$login = $destinations[$destination]['login'];
	$password = $destinations[$destination]['password'];

	$connection = new Connection($host, $login, $password, $port);
	$connection->open();

	$factory = new FTPFactory;
	$ftp = $factory->build($connection);
	$_folders = $ftp->findDirectories(new Directory('/MXF'));
	foreach ($_folders as $folder) {
		 $folder_name = array_pop(explode('/', $folder->getRealpath()));
		// $folder_name = $folder->getRealpath();

		array_push($folders, array('folder' => $folder_name));
	}

	echo json_encode(array(
		"success" => true,
		"data" => $folders
	));

} catch (Excpetion $e) {
	echo $e->getMessage();
}

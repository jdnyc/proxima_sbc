<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php'); 

use \Proxima\core\Response;

abstract class FtpMode
{
    const Passive = 'passive';
    const Active = 'active';
}

$data = [
    'type' => 'ftp',
    'host' => '10.26.101.52',
    'port' => 21,
    'username' => 'downloader',
    'password' => 'downloader',
    'mode' => FtpMode::Passive
];

Response::echoJson($data);

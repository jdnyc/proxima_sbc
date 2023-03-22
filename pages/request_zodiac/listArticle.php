<?php

use Proxima\core\View;

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
fn_checkAuthPermission($_SESSION);

$scriptPath = 'pages/request_zodiac/listArticle.js';
$script = View::getScriptData($scriptPath);
echo $script;

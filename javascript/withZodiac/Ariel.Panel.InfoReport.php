<?php

use Proxima\core\View;

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');
session_start();

$scriptPath = 'javascript/withZodiac/Ariel.Panel.InfoReport.js';
$script = View::getScriptData($scriptPath);

echo $script;

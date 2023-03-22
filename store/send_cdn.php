<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/bisUtil.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/pages/bis/bis.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/cuesheet/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');

$content_id = $_POST['content_id'];

$ud_content_id = MetaDataClass::getUserDefineId($content_id);

$contents =  MetaDataClass::getMetaFieldInfo('usr', $ud_content_id);

$logger->addInfo('aa', $contents);

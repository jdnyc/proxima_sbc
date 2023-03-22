<?php
// stop
//$v_command = "net stop IMAGE_ARCHIVE
//net start IMAGE_ARCHIVE";

/*
db_host : db_host,
db_port : db_port,
db_name : db_name,
db_user : db_user,
db_user_pw : db_user_pw,
db_driver : db_driver,
db_service_id:db_service_id

<DB_USER>proxima_sglt8</DB_USER>
<DB_USER_PW>proxima_sglt8</DB_USER_PW>
<DB_HOST>127.0.0.1</DB_HOST>
<DB_PORT>1521</DB_PORT>
<DB_NAME>proxima</DB_NAME>
<DB_SID>orcl</DB_SID>
*/

$doc = simplexml_load_file($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml');
$doc->items->DB_USER = $_POST['db_user'];
$doc->items->DB_USER_PW = $_POST['db_user_pw'];
$doc->items->DB_HOST = $_POST['db_host'];
$doc->items->DB_PORT = $_POST['db_port'];
$doc->items->DB_NAME = $_POST['db_name'];
$doc->items->DB_SID = $_POST['db_service_id'];

file_put_contents($_SERVER['DOCUMENT_ROOT'].'/lib/config.SYSTEM.xml', $doc->asXML());

$v_command = "restart.bat";
//run command
$v_return = shell_exec($v_command);
var_dump($v_return);
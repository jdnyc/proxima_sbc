<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$mtrl_id = $_REQUEST['mtrl_id'];

$mode = 'GetMtrlInfo';
$data = $mtrl_id;
require_once($_SERVER['DOCUMENT_ROOT'].'/interface/app/client/common.php');

$include_return = json_decode($include_return, true);
$data = $include_return[0]['data'];

$fields = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='".UD_NDS."'");

?>
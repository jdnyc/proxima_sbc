<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");

$query = "select value from bc_html_config where type='monitor_interval'";
$time = $db->queryOne($query);
//이 시간동안 새로운 정보가 안들어오면 연결에 문제가 있는 것으로 판단
define('TIMECHECK', $time*10/1000);
?>
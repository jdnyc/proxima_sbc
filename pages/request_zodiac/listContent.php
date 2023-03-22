<?php

use Proxima\core\View;

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
fn_checkAuthPermission($_SESSION);
////////////////////////////////////////////////////
//
//  2010.10.03 수정
//  한화면 보기시 meta_table_id값이 정의되지 않아 기능수행 안됨.
//  해결: store의 필드안에 meta_table_id 추가. line: 42.
//  작성자 : 박정근, 김성민
////////////////////////////////////////////////////
$thumb_width = $db->queryOne("select value from bc_html_config where type='thumb_width'");
$thumb_height = $db->queryOne("select value from bc_html_config where type='thumb_height'");
$list_width = $db->queryOne("select value from bc_html_config where type='list_width'");
$list_height = $db->queryOne("select value from bc_html_config where type='list_height'");
$tab_id = $_POST['tab_id'];

$labelwidth = 120;

$scriptPath = 'pages/request_zodiac/listContent.js';
$variables = [
	'$tab_id' => $tab_id,
	'$labelwidth' => $labelwidth,
	'$thumb_width' => $thumb_width,
	'$thumb_height' => $thumb_height,
	'MOVIE' => MOVIE,
	'IMAGE' => IMAGE,
	'SEQUENCE' => SEQUENCE,
	'SOUND' => SOUND,
	'DOCUMENT' => DOCUMENT,
	'CONFIG_QTIP_WIDTH' => CONFIG_QTIP_WIDTH
];

$script = View::getScriptData($scriptPath, $variables);
echo $script;

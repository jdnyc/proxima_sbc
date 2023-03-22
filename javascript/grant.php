<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();

$user = $_SESSION['user'];

$grants = $db->queryAll("SELECT C.GROUP_GRANT, C.MEMBER_GROUP_ID, C.UD_CONTENT_ID
                           FROM BC_MEMBER A, BC_MEMBER_GROUP_MEMBER B, BC_GRANT C
                          WHERE A.USER_ID='{$user['user_id']}'
                            AND A.MEMBER_ID=B.MEMBER_ID
                            AND B.MEMBER_GROUP_ID=C.MEMBER_GROUP_ID");

//콘텐츠 섬네일 크기 설정 값
if(empty($html_config_arr)) {
    $html_config = $db->queryAll("select * from bc_html_config");
    $html_config_arr = array();
    foreach($html_config as $hcon) {
        $html_config_arr[$hcon['type']] = $hcon['value'];
    }
}
$thumb_width = $html_config_arr['thumb_width'];
$thumb_height = $html_config_arr['thumb_height'];
$list_width = $html_config_arr['list_width'];
$list_height = $html_config_arr['list_height'];

//쿼터 알림값
$quota = $db->queryOne("select code from bc_code where code_type_id = 233");

?>

Ext.ns('Ariel');

MOVIE			= <?=MOVIE?>;
SOUND			= <?=SOUND?>;
IMAGE			= <?=IMAGE?>;
DOCUMENT		= <?=DOCUMENT?>;

//콘텐츠 목록 설정값
Ariel.thumb_width		= <?=$thumb_width?>;
Ariel.thumb_height		= <?=$thumb_height?>;
Ariel.list_width		= <?=$list_width?>;
Ariel.list_height		= <?=$list_height?>;

Ariel.streamer_addr		= '<?=STREAMER_ADDR.'/vod'?>';
// Ariel.WEB_PATH			= '<?=WEB_PATH?>';
Ariel.WEB_PATH = 'http://<?=STREAM_SERVER_IP?><?=LOCAL_LOWRES_ROOT?>';

Ariel.quota				= '<?=$quota?>';

Ariel.archive_use_yn	= '<?=ARCHIVE_USE_YN?>';

<?php

use Proxima\core\Session;
$_grant = array();
foreach ($grants as $grant) {
    array_push($_grant, $grant['ud_content_id']. ": '" . $grant['group_grant'] . "'");
}
echo "Ariel.my = {" . join(',', $_grant) . "}";
?>

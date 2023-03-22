<?php

require __DIR__ . '/vendor/autoload.php';

use \Proxima\core\Session;

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/out.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

// temp 사용자로 들어오면 화면이 깨짐...
if (strtolower($_SESSION['user']['user_id']) == 'temp') {
    header('Location: http://' . SERVER_HOST . '/index.php');
    exit;
}


$appVer = config('app')['ver'];

$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$user_email = $_SESSION['user']['user_email'];

$nowDateTime = date('YmdHis');

//media 와 cg 모듈 구분

$check_pw = $arr_sys_code['check_password_yn']['use_yn'];
$session_time_limit = $arr_sys_code['session_time_limit']['ref1'];
$check_lang = $_SESSION['user']['lang'];

$user_option = $db->queryRow("
    SELECT  top_menu_mode, slide_thumbnail_size, first_page, slide_summary_size, show_content_subcat_yn
    FROM        bc_member_option
    WHERE   member_id = (
        SELECT  member_id
        FROM        bc_member
        WHERE   user_id =  '" . $user_id . "' and del_yn!='Y'
    )
");
$first_page = trim($user_option['first_page']);
$firstPageIndex = 0;
if ($first_page === 'media') {
    $firstPageIndex = 1;
}
$show_content_subcat_yn = trim($user_option['show_content_subcat_yn']);

/**
  agent 접속 관련 부분 추가
  2016 . 08 22
  by hkh
 */

$hide_menu_flag = "false";

$adobe_plugin = array(
    'isPluginUse' => 'false',
    'isAgentnm' => '',
    PREMIERE_AGENT_NM => array(
        "plugin_flag" => 'false',
        "menu_hide_flag" => 'false',
        "ud_content_id" => 0
    ),
    PHOTOSHOP_AGENT_NM => array(
        "plugin_flag" => 'false',
        "menu_hide_flag" => 'false',
        "ud_content_id" => 0,
        "bs_content_id" => 0
    )
);

try {

    if ($_REQUEST['agent'] == PREMIERE_AGENT_NM) {
        $peremiere_plugin_use_yn = $arr_sys_code['premiere_plugin_use_yn']['use_yn'];
        if ($peremiere_plugin_use_yn != 'Y') {
            throw new Exception(_text('MSG02501'));
        } else {
            //걸정 관련 파일 확인
            //메뉴 보이게 할것인가?
            $adobe_plugin[PREMIERE_AGENT_NM]['plugin_flag'] = 'true';
            $adobe_plugin[isAgentnm] = PREMIERE_AGENT_NM;

            $premiere_menu_yn       = $arr_sys_code['premiere_plugin_use_yn']['ref1'];
            $premiere_ud_content_id = $arr_sys_code['premiere_plugin_use_yn']['ref2'];

            $adobe_plugin[PREMIERE_AGENT_NM]['ud_content_id'] = $premiere_ud_content_id;

            if ($premiere_menu_yn == 'N') {
                //$premiere_menu_hide_flag = 'true';
                $adobe_plugin[PREMIERE_AGENT_NM]['menu_hide_flag'] = 'true';
                $hide_menu_flag  = 'true';
            }

            $adobe_plugin['isPluginUse'] = 'true';
        }
    } else if ($_REQUEST['agent'] == PHOTOSHOP_AGENT_NM) {
        $photoshop_plugin_use_yn = $arr_sys_code['photoshop_plugin_use_yn']['use_yn'];
        if ($photoshop_plugin_use_yn != 'Y') {
            throw new Exception(_text('MSG02501'));
        } else {
            //걸정 관련 파일 확인
            //메뉴 보이게 할것인가?
            $adobe_plugin[PHOTOSHOP_AGENT_NM]['plugin_flag'] = 'true';
            $adobe_plugin[isAgentnm] = PHOTOSHOP_AGENT_NM;
            //$photoshop_plugin_flag = 'true';
            $photoshop_menu_yn       = $arr_sys_code['photoshop_plugin_use_yn']['ref1'];
            $photoshop_ud_content_id = $arr_sys_code['photoshop_plugin_use_yn']['ref2'];

            $adobe_plugin[PHOTOSHOP_AGENT_NM]['ud_content_id'] = $premiere_ud_content_id;
            $adobe_plugin[PHOTOSHOP_AGENT_NM]['bs_content_id'] = $arr_sys_code['photoshop_plugin_use_yn']['ref5'];

            if ($photoshop_menu_yn == 'N') {
                //$photoshop_menu_hide_flag = 'true';
                $adobe_plugin[PHOTOSHOP_AGENT_NM]['menu_hide_flag'] = 'true';
                $hide_menu_flag  = 'true';
            }

            $adobe_plugin['isPluginUse'] = 'true';
        }
    }
} catch (Exception $e) {
    die($e->getmessage());
}


$headerClassName = '';
$loadingMessage = _text('MN02538');
$faviconPath = 'css/images/logo/proxima_favicon.ico'; // default

$useMedeis = $arr_sys_code['use_product_name_medeis']['use_yn'] == 'Y';

$topMenuMode = $user_option['top_menu_mode'];
if (empty($topMenuMode)) {
    $topMenuMode = 'B';
}

// main logo image
$mainLogoImagePath = getMainLogoImagePath($topMenuMode);

// logo image path and loading message
if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CssManager')) {
    // custom
    $mainLogoImagePath = \ProximaCustom\core\CssManager::getMainLogoPath($topMenuMode);
    $mainLogoImageStyle = \ProximaCustom\core\CssManager::getMainLogoStyle($topMenuMode);
    $getTopMenuStyle = \ProximaCustom\core\CssManager::getTopMenuStyle($topMenuMode);
    $loadingMessage = LOADING_MESSAGE;
    $faviconPath = '/custom/ktv-nps/images/faviconV2.ico';
} else {
    // proxima
    $faviconPath = 'css/images/logo/proxima_favicon.ico';
    $loadingMessage = _text('MN02538');
}

// header class name
if ($hide_menu_flag == 'true') {
    $headerClassName = getHeaderClassName('');
} else {
    $headerClassName = getHeaderClassName($topMenuMode);
}

/*로그인시 현재 시간에 보여야 될 공지사항이 있는지 확인하는 부분 추가 - 2017.12.27 Alex */
/*$showNoticeId = $db->queryOne("
SELECT 
NOTICE.NOTICE_ID
FROM
BC_NOTICE NOTICE        
LEFT OUTER JOIN BC_MEMBER M 
ON M.USER_ID = NOTICE.FROM_USER_ID     
WHERE  NOTICE.NOTICE_START <= '$nowDateTime' AND NOTICE.NOTICE_END >= '$nowDateTime'  
AND NOTICE.NOTICE_ID IN (
SELECT NOTICE_ID FROM  BC_NOTICE WHERE NOTICE_TYPE = 'all'
UNION ALL
SELECT  N.NOTICE_ID FROM BC_NOTICE N, BC_NOTICE_RECIPIENTS R, BC_MEMBER_GROUP G
WHERE   G.MEMBER_GROUP_ID = R.MEMBER_GROUP_ID
AND     R.NOTICE_ID = N.NOTICE_ID
AND     R.MEMBER_GROUP_ID IN (".join(',', $_SESSION['user']['groups']).")
UNION ALL
SELECT  N.NOTICE_ID FROM  BC_NOTICE N, BC_NOTICE_RECIPIENTS R, BC_MEMBER M WHERE   M.MEMBER_ID = R.MEMBER_ID AND  R.NOTICE_ID = N.NOTICE_ID AND  M.USER_ID = '$user_id'
)
AND     NOTICE.NOTICE_POPUP_AT = 'Y'
ORDER BY  NOTICE.CREATED_DATE DESC 
");*/

function getHeaderClassName($topMenuMode)
{
    if (empty($topMenuMode)) {
        return 'header_hide_mode';
    } else if ($topMenuMode == 'S') {
        return 'header_small_mode';
    } else {
        return 'header_big_mode';
    }
}

function getMainLogoImagePath($topMenuMode)
{
    if ($topMenuMode == 'S') {
        return '/css/images/logo/proxima_logo_small_mode.gif';
    } else {
        return '/css/images/logo/proxima_logo_big_mode.png';
    }
}

?>
<!DOCTYPE html>
<html>

<head>

    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
    <meta http-equiv="X-UA-Compatible" content="IE=11" />
    <title><?php echo Session::get('app_title'); ?></title>
    <link rel="SHORTCUT ICON" href="<?php echo $faviconPath; ?>" />
    <link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
    <link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/Portal.css" />
    <link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/MultiSelect.css" />
    <link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/ProgressColumn.css" />
    <link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/css/ColumnHeaderGroup.css" />
    <link rel="stylesheet" type="text/css" href="/lib/extjs/examples/ux/treegrid/treegrid.css" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="/videojs/video-js.css">
    <script type="text/javascript" src="/javascript/script.js"></script>

    <link rel="stylesheet" type="text/css" href="/js/jquery-ui-1.11.4/jquery-ui.min.css" />
    <link rel="stylesheet" type="text/css" href="/css/MaterialDesign-Webfont/css/materialdesignicons.min.css" />

    <link rel="stylesheet" type="text/css" href="/css/proxima3.css?_ver=20191224001" />

    <link rel="stylesheet" type="text/css" href="/javascript/colorfield/Ext.ux.ColorField.css" />

    <link href="/videojs/videojs-markers-0.5.0/dist/videojs.markers.css" rel="stylesheet">
    <link href="/videojs/videojs-logo-overlay.css" rel="stylesheet">

    <!-- Common Styles for the examples -->
    <link rel="stylesheet" type="text/css" href="/lib/extjs/examples/shared/examples.css" />


    <!--Custom Style loading-->
    <?php
    //Custom Style loading        
    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CssManager')) {
        $styles = \ProximaCustom\core\CssManager::getCustomStyles();
        foreach ($styles as $style) {
            echo $style;
        }
    }
    ?>
</head>

<body>
    <div id="loading-mask"></div>
    <div id="loading">
        <span class="to-loader-logo loading-indicators">
            <?php echo $loadingMessage; ?>
        </span>
        <div class="to-loader" style="text-align:center;">
            <div id='loading-msg'></div>
            <svg width="60px" height="60px" viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
                <path class="to-loader-circlebg" fill="none" stroke="#dddddd" stroke-width="4" stroke-linecap="round" d="M40,10C57.351,10,71,23.649,71,40.5S57.351,71,40.5,71 S10,57.351,10,40.5S23.649,10,40.5,10z" />
                <path id='to-loader-circle' fill="none" stroke="#11b3c5" stroke-width="4" stroke-linecap="round" stroke-dashoffset="192.61" stroke-dasharray="192.61 192.61" d="M40,10C57.351,10,71,23.649,71,40.5S57.351,71,40.5,71 S10,57.351,10,40.5S23.649,10,40.5,10z" />
            </svg>
        </div>
    </div>

    <!--현이롤오버관련시작-->
    <script type="text/javascript">
        var dev_flag = true;
        var total_jsload_count = 63; //50;//66;
        var jsload_count = 0;

        var UPLOAD_URL = '<?php echo UPLOAD_URL; ?>';

        var now = new Date().getTime();

        function loader_view(page) {
            jsload_count++;

            var loadCircle = document.getElementById('to-loader-circle');
            var scripts = document.getElementsByTagName('script');
            var strokeLength = loadCircle.getTotalLength();
            //console.log(jsload_count);
            var percent = (jsload_count / total_jsload_count);
            loadCircle.setAttribute('stroke-dashoffset', (1 - percent) * strokeLength);
            if (percent === 1) {
                setTimeout(function() {
                    document.getElementById('loading').className = document.getElementById('loading').className + ' loaded';
                }, 0);
                setTimeout(function() {
                    document.getElementById('loading-mask').className = document.getElementById('loading-mask').className + ' loaded';
                }, 100);
                setTimeout(function() {
                    document.getElementById('loading-mask').parentNode.removeChild(document.getElementById('loading-mask'));
                    document.getElementById('loading').parentNode.removeChild(document.getElementById('loading'));
                }, 1000);
            }
        }
    </script>
    <!--현이롤오버관련끝-->
    <script type="text/javascript" src="/javascript/over_roll_function.js"></script>
    <script type="text/javascript">
        document.getElementById('loading-msg').innerHTML = 'Loading Core API...';
    </script>
    <script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
    <script type="text/javascript">
        document.getElementById('loading-msg').innerHTML = 'Loading UI Components...';
        loader_view('ext-base.js');
    </script>
    <script type="text/javascript" src="/lib/extjs/ext-all-debug.js"></script>
    <script type="text/javascript">
        document.getElementById('loading-msg').innerHTML = 'Initializing...';
        loader_view('ext-all-debug.js');
    </script>
    <script type="text/javascript" src="/javascript/lang.php"></script>
    <script>
        loader_view('lang.php');
    </script>

    <script type="text/javascript" src="/javascript/common.js?v=<?=$appVer?>"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/grant.php"></script>
    <script>
        loader_view();
    </script>

    <script type="text/javascript" src="/lib/extjs/examples/ux/MultiSelect.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/ItemSelector.js"></script>
    <script>
        loader_view();
    </script>

    <script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridSorter.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridColumnResizer.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridNodeUI.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridLoader.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGridColumns.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/treegrid/TreeGrid.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/ColumnHeaderGroup.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/BufferView.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/ProgressColumn.js"></script>
    <script>
        loader_view();
    </script>

    <script type="text/javascript" src="/lib/extjs/examples/shared/examples.js"></script><!-- EXAMPLES -->
    <script>
        loader_view();
    </script>

    <script type="text/javascript" src="/javascript/awesomeuploader/Ext.ux.AwesomeUploader.js"></script>
    <script type="text/javascript" src="/javascript/awesomeuploader/Ext.ux.AwesomeUploaderLocalization.js"></script>
    <script type="text/javascript" src="/javascript/awesomeuploader/Ext.ux.form.FileUploadField.js"></script>
    <script type="text/javascript" src="/javascript/awesomeuploader/Ext.ux.XHRUpload.js"></script>
    <script type="text/javascript" src="/javascript/awesomeuploader/swfupload.js"></script>
    <script type="text/javascript" src="/javascript/extjs.plugins/Ext.ux.plugins.TabStripContainer.js"></script>

    <!-- // 그룹관리 관련 RowExpand 추가 -->
    <script type="text/javascript" src="/lib/extjs/examples/ux/RowExpander.js"></script>
    <script>
        loader_view('RowExpander.js');
    </script>
    <script type="text/javascript" src="/lib/extjs/examples/ux/CheckColumn.js"></script>
    <script>
        loader_view('CheckColumn.js');
    </script>

    <script type="text/javascript" src="js/jquery.min.js"></script>
    <script type="text/javascript" src="js/jquery-ui-1.11.4/jquery-ui.min.js"></script>
    <script type="text/javascript" src="js/datepicker.js"></script>

    <!--Lodash 라이브러리-->
    <script type="text/javascript" src="/lib/lodash/lodash.min.js"></script>

    <!-- 데이터 사전 -->

    <!--<script type="text/javascript" src="/custom/ktv-nps/js/glossary/searchField.js"></script><script>loader_view('searchField.js');</script> -->
    <!-- <script type="text/javascript" src="/custom/ktv-nps/js/glossary/inputFormWindow.js"></script><script>loader_view('inputFormWindow.js');</script> -->
    <!--<script type="text/javascript" src="/custom/ktv-nps/js/glossary/domainSelectWindow.js"></script><script>loader_view('domainSelectWindow.js');</script>-->
    <!-- <script type="text/javascript" src="/custom/ktv-nps/js/glossary/fieldSelectWindow.js"></script><script>loader_view('fieldSelectWindow.js');</script>-->
    <!--<script type="text/javascript" src="/custom/ktv-nps/js/glossary/codeSelectWindow.js"></script><script>loader_view('fieldSelectWindow.js');</script>-->

    <!-- DashBoard -->

    <!-- <script type="text/javascript" src='/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Url.js' ,></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/archiveManagement/Ariel.archiveManagement.UrlSet.js' ,></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/glossary/Ariel.glossary.UrlSet.js' ,></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Notice.js'></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Monitor.js'></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Request.js'></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Review.js'></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Home.js'></script>
    <script type="text/javascript" src='/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Storage.js'></script> -->



    <!--컴포넌트 -->
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.user.modifiy.js"></script>
    <script>
        loader_view('Ariel.user.modifiy.js');
    </script>
    <script type="text/javascript" src="/javascript/component/button/Ariel.IconButton.js"></script>
    <script>
        loader_view('Ariel.IconButton.js');
    </script>
    <script type="text/javascript" src="/javascript/component/form/select/Ariel.form.ComboBox.js"></script>
    <script>
        loader_view('Ariel.form.ComboBox.js');
    </script>

    <script type="text/javascript" src="/javascript/ext.ux/dd.js"></script>
    <script>
        loader_view('dd.js');
    </script>
    <script type="text/javascript" src="/javascript/functions.js"></script>
    <script>
        loader_view('functions.js');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.PageSizer.js"></script>
    <script>
        loader_view('Ext.ux.grid.PageSizer.js');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/categoryContextMenu.php?agent=<?= $_REQUEST['agent'] ?>"></script>
    <script>
        loader_view('categoryContextMenu.php');
    </script>

    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.TreeCombo.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ext.ariel.ContentList.php"></script>
    <script>
        loader_view('Ext.ariel.ContentList.php');
    </script>
    <script type="text/javascript" src='/custom/ktv-nps/js/custom/Custom.ContentList.js'></script>
    <script>
        loader_view('Custom.ContentList.js');
    </script>
      <script type="text/javascript" src='/custom/ktv-nps/js/custom/Custom.RadioDay.js'></script>
    <script>
        loader_view('Custom.RadioDay.js');
    </script>
        <script type="text/javascript" src='/custom/ktv-nps/js/custom/Custom.MediaListGrid.js?v=1'></script>
    <script>
        loader_view('Custom.MediaListGrid.js');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.ExplorerView.js"></script>
    <script>
        loader_view('Ext.ux.grid.ExplorerView.js');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.Main.php"></script>
    <script>
        loader_view('Ariel.Nps.Main.php');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.Media.php?agent=<?= $_REQUEST['agent'] ?>"></script>
    <script>
        loader_view('Ariel.Nps.Media.php');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.WorkManagement.php"></script>
    <script>
        loader_view('Ariel.Nps.WorkManagement.php');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.Glossary.js"></script>
    <script>
        loader_view('Ariel.Nps.Glossary.js');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.ArchiveManagement.js"></script>
    <script>
        loader_view('Ariel.Nps.ArchiveManagement.js');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.Program.js"></script><!-- 2019-01-31 프로그램 메뉴 추가 -->

    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.Statistic.php"></script>
    <script>
        loader_view('Ariel.Nps.Statistic.php');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.CueSheet.php"></script>
    <script>
        loader_view('Ariel.Nps.CueSheet.php');
    </script>


    <script type="text/javascript" src="/javascript/ext.ux/Ariel.LoudnessLog.php"></script>
    <script>
        loader_view('Ariel.LoudnessLog.php');
    </script>

    <script type="text/javascript" src="/javascript/ext.ux/Ariel.QuailityCheckLog.php"></script>
    <script>
        loader_view('Ariel.QuailityCheckLog.php');
    </script>
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.user.modifiy.js"></script>
    <script>
        loader_view('Ariel.task.Monitor.js');
    </script>
    <script type="text/javascript" src="/store/metadata/Ariel.Nps.QC.php"></script>
    <script>
        loader_view('Ariel.Nps.QC.php');
    </script>

    <?php
    if (INTERWORK_ZODIAC == 'Y') {
        echo '
        <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.CheckRequest.js"></script><script>loader_view(\'Ariel.Nps.CheckRequest.js\');</script><!-- 2015-10-19 proxima_zodiac 메뉴 추가 -->
        <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.InfoReport.js"></script><script>loader_view(\'Ariel.Nps.InfoReport.js\');</script><!-- 2015-10-19 proxima_zodiac 메뉴 추가 -->                    ';
    }
    ?>

    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.SystemManagement.php"></script>
    <script>
        loader_view('Ariel.Nps.SystemManagement.php');
    </script><!-- 2016-01-25 개발자 시스템 메뉴 추가 -->
    <!-- 파일 업로더 -->
    <script type="text/javascript" src="/javascript/ext.ux/Ariel.Nps.FileUploadWindow.php"></script>
    <script>
        loader_view('Ariel.Nps.FileUploadWindow.php');
    </script>

    <!--Custom Menu Pages -->
    <?php
    //Custom Menu Pages        
    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MenuItemManager')) {
        $pageScripts = \ProximaCustom\core\MenuItemManager::getCustomPageScripts();
        foreach ($pageScripts as $pageScript) {
            echo $pageScript;
        }
    }
    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ScriptManager')) {
        $scripts = \ProximaCustom\core\ScriptManager::getCustomScripts();
        foreach ($scripts as $script) {
            echo $script;
        }
    }

    ?>

    <!-- Panel-->
    <script type="text/javascript" src="/javascript/component/Ariel.Panel.Main.js"></script>
    <script>
        loader_view('Ariel.Panel.Main.js');
    </script>
    <script type="text/javascript" src="/javascript/component/Ariel.Panel.Main.Center.js"></script>
    <script>
        loader_view('Ariel.Panel.Main.Center.js');
    </script>
    <script type="text/javascript" src="/javascript/component/Ariel.Panel.Monitor.php"></script>
    <script>
        loader_view('Ariel.Panel.Monitor.php');
    </script>
    <script type="text/javascript" src="/javascript/component/Ariel.panel.archive.Result.js"></script>
    <script>
        loader_view('Ariel.panel.archive.Result.js');
    </script>
    <script type="text/javascript" src="/javascript/component/Ariel.panel.review.Request.js"></script>
    <script>
        loader_view('Ariel.panel.review.Request.js');
    </script>
    <script type="text/javascript" src="/javascript/component/Ariel.panel.review.Result.js"></script>
    <script>
        loader_view('Ariel.panel.review.Result.js');
    </script>

    <?php
    if (INTERWORK_ZODIAC == 'Y') {
        echo '
        <script type="text/javascript" src="/javascript/withZodiac/Ariel.Panel.InfoReport.php"></script><script>loader_view(\'Ariel.Panel.InfoReport.php\');</script><!-- 2015-10-30 proxima_zodiac 보도정보 패널 xtype : \'infoReport\'-->
        <script type="text/javascript" src="/javascript/withZodiac/Ariel.Panel.InfoReportQ.php"></script><script>loader_view(\'Ariel.Panel.InfoReportQ.php\');</script><!-- 2015-11-26 proxima_zodiac 보도정보 큐시트패널 xtype : \'infoReport\'-->
        <script type="text/javascript" src="/javascript/withZodiac/Ariel.Panel.ListContent.js"></script><script>loader_view(\'Ariel.Panel.ListContent.js\');</script><!-- 2015-11-04 proxima_zodiac 비디오/그래픽 탭패널 -->
                    ';
    }
    ?>

    <!-- Menu -->
    <script type="text/javascript" src="/javascript/component/menu/Ariel.menu.Review.js"></script>
    <script>
        loader_view('Ariel.menu.Review.js');
    </script>

    <!-- form -->
    <script type="text/javascript" src="/javascript/component/form/Ariel.form.review.Accept.js"></script>
    <script>
        loader_view('Ariel.form.review.Accept.js');
    </script>
    <script type="text/javascript" src="/javascript/component/form/Ariel.form.review.Reject.js"></script>
    <script>
        loader_view('Ariel.form.review.Reject.js');
    </script>
    <script type="text/javascript" src="/javascript/component/form/Ariel.form.review.Request.js"></script>
    <script>
        loader_view('Ariel.form.review.Request.js');
    </script>
    <script type="text/javascript" src="/javascript/component/form/Ariel.form.review.Detail.js"></script>
    <script>
        loader_view('Ariel.form.review.Detail.js');
    </script>
    <script type="text/javascript" src="/javascript/component/form/Ariel.form.Workflow.js"></script>
    <script>
        loader_view('Ariel.form.Workflow.js');
    </script>
    <!--        <script type="text/javascript" src="/javascript/component/form/field/Ariel.form.field.Combo.js"></script>-->

    <!-- Window -->
    <script type="text/javascript" src="/javascript/component/window/Ariel.window.review.Accept.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/component/window/Ariel.window.review.Reject.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/component/window/Ariel.window.review.Request.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/component/window/Ariel.window.review.Detail.js"></script>
    <script>
        loader_view();
    </script>
    <!--        <script type="text/javascript" src="/javascript/component/window/Ariel.window.Workflow.js"></script>-->

    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.netbox.InputTextMask.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/moment.js"></script>
    <script>
        loader_view();
    </script>
    <script type="text/javascript" src="/javascript/lodash.min.js"></script>
    <script>
        loader_view();
    </script>
        <!-- 
    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.ProximaWindow.js"></script>
    <script>
        loader_view();
    </script>--><!-- Proxima window -->
        <!-- 
    <script type="text/javascript" src="/javascript/ext.ux/Ext.ux.ProximaMsgBox.js"></script>
    <script>
        loader_view();
    </script>--><!-- Proxima MSG BOX -->

    <!-- FlashNet 관련 -->
    <script type="text/javascript" src="/javascript/request.js">
    </script>
    <script>
        loader_view();
    </script>

    <script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-<?= $check_lang ?>.js"></script>
    <script>
        loader_view();
    </script>

    <script type="text/javascript" src="/javascript/extjs.plugins/Ext.us.PanelCollapsedTitle.js"></script>
    <script>
        loader_view();
    </script>
    <!-- 카테고리 숨김시 제목 노출 기능 -->



    <!-- Color Field -->

    <script src="/javascript/colorfield/Ext.ux.ColorField.js" type="text/javascript"></script>


    <!-- JSZip -->
    <script src="/javascript/jszip/jszip.js" type="text/javascript"></script>
    <script src="/javascript/jszip/jszip-utils.js" type="text/javascript"></script>
    <script src="/javascript/jszip/FileSaver.js" type="text/javascript"></script>

    <script src="/javascript/ext.ux/Ariel.QuailityCheckLog.php" type="text/javascript"></script>

    <!-- VideoJs -->
    <script src="/videojs/video.js"></script>
    <script src='/videojs/js/videojs_thumbnail.js'></script>
    <script type="text/javascript" src="/videojs/videojs.hotkeys.min.js"></script>
    <script type="text/javascript" src="/videojs/videojs-markers-0.5.0/src/videojs.markers.js"></script>
    <script type="text/javascript" src="/videojs/videojs-logo-overlay.js"></script>

    <script type="text/javascript">
        Ext.ns('Ariel');

        //자바스크립트용 버전값 설정
        Ariel.versioning.setVer('<?=$appVer?>');

        var global_detail;
        var current_focus = null;
        var advanceSearchWin = null;
        var cuesheetSearchWin = null;
        var global_webupload_win_opened = false;
        var v_file_list = [];
        // global
        _env = 'development';

        /**
    AME 연동 부분 추가
    Premiere plugin 시    
*/


        <?php
        // 사용자 정보에서 노출되면 안되는 항목 숨김   
        $passwordFieldHidden = 'false';
        $emailFieldHidden = 'false';
        $phoneFieldHidden = 'false';
        $languageFieldHidden = 'false';
        if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\UserInfoCustom')) {
            $passwordFieldHidden = \ProximaCustom\core\UserInfoCustom::PasswordFieldVisible() ? 'false' : 'true';
            $emailFieldHidden = \ProximaCustom\core\UserInfoCustom::EmailFieldVisible() ? 'false' : 'true';
            $phoneFieldHidden = \ProximaCustom\core\UserInfoCustom::PhoneFieldVisible() ? 'false' : 'true';
            $languageFieldHidden = \ProximaCustom\core\UserInfoCustom::LanguageFieldVisible() ? 'false' : 'true';
        }

        echo "var passwordFieldHidden = {$passwordFieldHidden},\n
emailFieldHidden = {$emailFieldHidden},\n
phoneFieldHidden = {$phoneFieldHidden},\n
languageFieldHidden = {$languageFieldHidden};";
        ?>

        Ext.BLANK_IMAGE_URL = '/lib/extjs/resources/images/default/s.gif';

        Ext.isAdobePlugin = <?= $adobe_plugin['isPluginUse'] ?>;
        Ext.isAdobeAgent = "<?= $adobe_plugin['isAgentnm'] ?>";
        Ext.isHideMenu = <?= $hide_menu_flag ?>;
        Ext.isPremiere = <?= $adobe_plugin[PREMIERE_AGENT_NM]['plugin_flag'] ?>;
        Ext.isPremiereHideMenu = <?= $adobe_plugin[PREMIERE_AGENT_NM]['menu_hide_flag'] ?>;
        Ext.isPremiereUserid = '<?= $user_id ?>';
        Ext.isPremierelang = '<?= $check_lang ?>';
        Ext.isPremiereSession_id = '<?= session_id() ?>';
        Ext.isPremiereUdContent_id = <?= $adobe_plugin[PREMIERE_AGENT_NM]['ud_content_id'] ?>;

        Ext.isPhotoshop = <?= $adobe_plugin[PHOTOSHOP_AGENT_NM]['plugin_flag'] ?>;
        Ext.isPhotoshopBsContent_id = <?= $adobe_plugin[PHOTOSHOP_AGENT_NM]['bs_content_id'] ?>;

        //* NPS 메인페이지 함수 , 상단메뉴, 좌측메뉴 모듈 2012-08-23 by 이성용
        // src="/javascript/ext.ux/Ariel.Nps.Main.php

        // 세션 체크
        var session_expire_time = time() + <?= $session_time_limit * 60 ?>;
        var session_user_id = '<?= $user_id ?>';
        var session_super_admin = '<?= $_SESSION['user']['super_admin'] ?>';
        var session_prevent_duplicate_login = '<?= $_SESSION['user']['prevent_duplicate_login'] ?>';
        var session_checker;
        var session_checker_id;
        var is_long_time_ajax_working = 'N'; //if Y, then block sessionChecker

        var thumbSlider = "";
        var summarySlider = "";



        Ext.onReady(function() {

            // 30초 주기로 세션 종료 체크.
            //session_checker_id = setInterval('sessionChecker()', 30 * 1000);

            // 메인 데이터
            var firstPageIndex = <?php echo $firstPageIndex; ?>;

            Ext.QuickTips.init();
            Ext.apply(Ext.QuickTips.getQuickTip(), {
                showDelay: 50,
                dismissDelay: 15000,
                shadow: false
            });

            thumbSlider = new Ext.FormPanel({
                //renderTo: 'thumb_slider',
                id: 'form_panel_thumb_slider',
                layout: 'fit',
                hidden: true,
                bodyStyle: 'border:0px',
                items: [{
                    xtype: 'slider',
                    minValue: 0,
                    maxValue: 100,
                    name: 'test',
                    value: 0,
                    //value:25,
                    id: 'grid_thumb_slider',
                    width: 150,
                    increment: 1,
                    stateful: false,
                    listeners: {
                        change: function(e, nv, ov) {
                            var x = $(".x-grid3-row.ux-explorerview-large-icon-row");
                            //var ad_width = (280 -(100-nv));

                            var range_start = 150;
                            var range_end = 380;
                            var h_range_start = 85;
                            var h_range_end = 214;
                            var ad_width = (range_start + (range_end - range_start) * nv / 100);
                            var ad_height = (h_range_start + (h_range_end - h_range_start) * nv / 100);

                            for (var i = 0; i < x.length; i++) {
                                $(x[i]).height("100%");
                                $(x[i]).width(ad_width + 'px');
                            }

                            var height = parseInt((ad_width / 16) * 10);

                            $(".thumb_img").css("max-width", (ad_width - 7) + "px");
                            $(".thumb_img").css("max-height", ad_height + "px");
                            $(".thumb_img_box").css("width", ad_width + "px");
                            $(".thumb_img_box").css("height", ad_height + "px");


                        },
                        changecomplete: function(slider, newValue, thumb) {
                            Ext.Ajax.request({
                                url: '/store/user/user_option/slide_thumb_size.php',
                                params: {
                                    slide_thumb_value: newValue
                                },
                                callback: function(options, success, response) {}
                            });
                        },
                        render: function(self) {
                            self.setValue(<?= $user_option['slide_thumbnail_size'] ?>);
                        }
                    },
                    change_image_size: function() {
                        var x = $(".x-grid3-row.ux-explorerview-large-icon-row");
                        var nv = Ext.getCmp('grid_thumb_slider').getValue();
                        //var ad_width = (280 -(100-nv));
                        var range_start = 150;
                        var range_end = 380;
                        var h_range_start = 85;
                        var h_range_end = 214;
                        var ad_width = (range_start + (range_end - range_start) * nv / 100);
                        var ad_height = (h_range_start + (h_range_end - h_range_start) * nv / 100);

                        for (var i = 0; i < x.length; i++) {
                            $(x[i]).height("100%");
                            $(x[i]).width(ad_width + 'px');
                        }

                        var height = parseInt((ad_width / 16) * 10);

                        $(".thumb_img").css("max-width", (ad_width - 7) + "px");
                        $(".thumb_img").css("max-height", ad_height + "px");
                        $(".thumb_img_box").css("width", ad_width + "px");
                        $(".thumb_img_box").css("height", ad_height + "px");
                    }
                }]

            });

            summarySlider = new Ext.FormPanel({
                //renderTo: 'summary_slider',
                id: 'form_panel_summary_slider',
                layout: 'fit',
                bodyStyle: 'border:0px',
                hidden: true,
                items: [{
                    xtype: 'slider',
                    minValue: 0,
                    maxValue: 100,
                    id: 'grid_summary_slider',
                    width: 150,
                    increment: 1,
                    stateful: true,
                    listeners: {
                        change: function(e, nv, ov) {
                            var x = $(".x-grid3-row.ux-explorerview-detailed-icon-row");
                            //var ad_width = (280 -(100-nv));

                            var range_start = 312;
                            var range_end = 624;

                            var h_range_start = 85;
                            var h_range_end = 214;
                            var ad_width = (range_start + (range_end - range_start) * nv / 100);
                            var ad_height = (h_range_start + (h_range_end - h_range_start) * nv / 100);

                            for (var i = 0; i < x.length; i++) {
                                $(x[i]).height("100%");
                                $(x[i]).width(ad_width + 'px');
                            }

                            var height = parseInt((ad_width / 16) * 10);

                            $(".thumb_img").css("max-width", (ad_width - 7) + "px");
                            $(".thumb_img").css("max-height", ad_height + "px");
                            $(".thumb_img_box").css("width", ad_width + "px");
                            $(".thumb_img_box").css("height", ad_height + "px");


                        },
                        changecomplete: function(slider, newValue, thumb) {
                            Ext.Ajax.request({
                                url: '/store/user/user_option/slide_summary_size.php',
                                params: {
                                    slide_summary_value: newValue
                                },
                                callback: function(options, success, response) {}
                            });
                        },
                        render: function(self) {
                            //self.setValue(0);
                            self.setValue(<?= $user_option['slide_summary_size'] ?>);

                        }
                    },
                    change_image_size: function() {

                        var x = $(".x-grid3-row.ux-explorerview-detailed-icon-row");
                        var nv = Ext.getCmp('grid_summary_slider').getValue();
                        //var ad_width = (280 -(100-nv));
                        var range_start = 312;
                        var range_end = 624;

                        // var h_range_start = 85;
                        // var h_range_end = 214;
                        var ad_width = (range_start + (range_end - range_start) * nv / 100);
                        // var ad_height = (h_range_start +(h_range_end- h_range_start)*nv/100);

                        for (var i = 0; i < x.length; i++) {
                            // $(x[i]).height("100%");
                            $(x[i]).width(ad_width + 'px');
                        }

                        // var height = parseInt((ad_width/16)*10);

                        // $(".thumb_img").css("max-width",(ad_width-7)+"px");
                        // $(".thumb_img").css("max-height",ad_height+"px");
                        // $(".thumb_img_box").css("width",ad_width+"px");
                        // $(".thumb_img_box").css("height",ad_height+"px");
                    }
                }]

            });


            var view = new Ext.Viewport({
                layout: 'border',
                boxMinWidth: 700,
                items: [{
                        boxMinWidth: 700,
                        region: 'north',
                        <?php if ($hide_menu_flag == 'true') { ?>
                            height: 0,
                        <?php } else if ($user_option['top_menu_mode'] == 'S') { ?>
                            height: 45,
                        <?php } else { ?>
                            height: 70,
                        <?php } ?>
                        baseCls: 'bg_main_top_gif'
                    }, {
                        boxMinWidth: 700,
                        minWidth: 800,
                        region: 'center',
                        id: 'nps_center',
                        layout: 'card',
                        border: false,
                        activeItem: firstPageIndex,
                        //0 : home // 1 : search // 2 : statistic // 3 : system // 4 : request(zodiac) // 5 : interwork zodiac // 6 : statistic // 7 : system_dev
                        items: [
                            // {
                            //     //0 : home
                            //     xtype: 'mainpanel',
                            //     id: 'main_card_home'
                            // },
                            new Ariel.Nps.DashBoard({
                                id: 'main_card_home'
                            }),
                            new Ariel.Nps.Media({
                                //1 : search
                                id: 'main_card_search'
                            }),
                            new Ariel.Nps.Statistic({
                                // 2 : statistic
                                id: 'main_card_statistics'
                            }),
                            new Ariel.Nps.WorkManagement({
                                // 3 : system
                                id: 'main_card_system'
                            }),
                            new Ariel.Nps.Program({
                                // 4 : program
                                id: 'main_card_program'
                            }),

                            // Custom card import                
                            <?php
                            //Custom Menu Pages        
                            if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MenuItemManager')) {
                                \ProximaCustom\core\MenuItemManager::getCustomPageCards();
                            }

                            ?>

                            <?php
                            // harris
                            if ($arr_sys_code['interwork_harris']['use_yn'] == 'Y') {
                                echo "
                                    new Ariel.Nps.HarrisManagement({
                                        id: 'main_card_harris'
                                    }),
                                ";
                            }
                            ?>
                            <?php
                            if (INTERWORK_ZODIAC == 'Y') {
                                //2015-10-19 proxima_zodiac 메뉴 추가/ 4 : request(zodiac)
                                // 5 : interwork zodiac
                                echo "new Ariel.Nps.CheckRequest({
                                    id: 'main_card_zodiac_request'
                                }),
                                {
                                    xtype : 'infoReport',
                                    id: 'main_card_zodiac_report'
                                },";
                                //2015-10-30  proxima_zodiac 메뉴 추가
                            }
                            ?>

                            new Ariel.Nps.SystemManagement({
                                // 7 : system_dev
                                id: 'main_card_configuration'
                            }),
                            new Ariel.Nps.Glossary({
                                id: 'main_card_glossary'
                            }),
                            new Ariel.Nps.ArchiveManagement({
                                id: 'main_card_archiveManagement'
                            }),
                            new Ariel.task.Monitor.Tab({
                                id: 'main_card_monitor',
                                listeners: {
                                    activate: function(panel) {
                                        //메뉴 선택시 동작
                                        panel._onShow();
                                    }
                                }
                            }),
                            new Ariel.Das.ArcManage({
                                id: 'main_card_archManage'
                            }),
                            // new Ariel.IngestSchedule({
                            //     id: 'main_card_ingest',
                            //     listeners: {
                            //         activate: function(panel) {
                            //             //메뉴 선택시 동작
                            //             panel._onShow();
                            //         }
                            //     }
                            // })
                        ],
                        listeners: {
                            afterlayout: function(self, layout) {
                                if (Ext.isAir) {
                                    try {
                                        if (layout.activeItem.id !== 'main-card-search') {
                                            airFunRemoveFilePath('all');
                                        }
                                    } catch (e) {}
                                }

                                //TopMenuToggle('TopImage-home');
                            }
                        }
                    }
                    <?php
                    // custom component in main layout
                    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MainComponentCustom')) {
                        \ProximaCustom\core\MainComponentCustom::renderCustomComponents();
                    }
                    ?>
                ],
                listeners: {
                    afterrender: function(self) {
                        /*
                            Premiere Plugin or Other first page Type check 
                        */

                        var first_page = '<?= $first_page ?>';

                        if (Ext.isAdobeAgent) {
                            TopMenuFunc('', 'TopImage-media');
                            Ext.getCmp('tab_warp').setActiveTab(0);
                        } else if (first_page == 'media') {
                            TopMenuFunc('', 'TopImage-media');
                            Ext.getCmp('tab_warp').setActiveTab(0);
                            if ('<?=env(USE_NOTICE_POP_UP)?>') {
                                var row_notice_id = '<?=$showNoticeId ?>';
                                if (row_notice_id != '' && Ext.util.Cookies.get('notice_popup_cookie_' + row_notice_id) != 'N') {
                                    noticePopup(row_notice_id);
                                }
                            }

                        } else {
                            TopMenuFunc('', 'TopImage-home');
                        }
                    }
                }
            });

            var hideMask = function() {
                Ext.get('loading').remove();
                Ext.fly('loading-mask').fadeOut({
                    remove: true
                });
            }

            //hideMask.defer(250);

            //공통 키이벤트 2014-09-22
            var KeyMap = new Ext.KeyMap(document,
                [{
                    key: [83],
                    alt: true,
                    stopEvent: true,
                    fn: function(e) {
                        //ctrl s
                        if (!Ext.isEmpty(Ariel.RoughCutWindow) && !Ext.isEmpty(Ext.getCmp('tc_toolbar'))) {
                            //러프컷프리뷰노트 입력
                            Ext.getCmp('tc_toolbar').SaveTC();
                        }
                    }
                }, {
                    key: [73],
                    alt: true,
                    stopEvent: true,
                    fn: function(e) {
                        //ctrl i
                        if (!Ext.isEmpty(Ariel.RoughCutWindow) && !Ext.isEmpty(Ext.getCmp('tc_toolbar'))) {
                            //러프컷프리뷰노트 입력
                            Ext.getCmp('tc_toolbar').setInTC();
                        }
                    }
                }, {
                    key: [79],
                    alt: true,
                    stopEvent: true,
                    fn: function(e) {
                        //ctrl o
                        if (!Ext.isEmpty(Ariel.RoughCutWindow) && !Ext.isEmpty(Ext.getCmp('tc_toolbar'))) {
                            //러프컷프리뷰노트 입력
                            Ext.getCmp('tc_toolbar').setOutTC();
                        }
                    }
                }, {
                    key: [80],
                    alt: true,
                    stopEvent: true,
                    fn: function(e) {
                        //엔터
                        if (!Ext.isEmpty(Ariel.RoughCutWindow) && !Ext.isEmpty(Ext.getCmp('tc_toolbar'))) {
                            //러프컷프리뷰노트 입력
                            Ext.getCmp('tc_toolbar').setAddTC();
                        }
                    }
                }, {
                    key: [68],
                    alt: true,
                    stopEvent: true,
                    fn: function(e) {
                        //ctrl d
                        if (!Ext.isEmpty(Ariel.RoughCutWindow) && !Ext.isEmpty(Ext.getCmp('tc_toolbar'))) {
                            //러프컷프리뷰노트 입력
                            Ext.getCmp('tc_toolbar').DelTC();
                        }
                    }
                }, {
                    key: [67],
                    alt: true,
                    stopEvent: true,
                    fn: function(e) {
                        //ctrl c
                    }
                }]);

            // 콘텐츠 목록 탭에서만 전체선택 기능을 사용하기 위함
            var KeyMapTabWrap = new Ext.KeyMap("tab_warp",
                [{
                    key: [65],
                    ctrl: true,
                    stopEvent: true,
                    fn: function(e) {

                        //ctrl a
                        if (Ext.getCmp('nps_center').getLayout().activeItem.id == "main_card_search" && !Ext.isEmpty(Ext.getCmp('tab_warp').getActiveTab().get(0))) {
                            var contentGrid = Ext.getCmp('tab_warp').getActiveTab().get(0);
                            var sm = contentGrid.getSelectionModel();
                            sm.selectAll();
                            var selectedRowCount = sm.getCount();
                            var selectedCountField = contentGrid.getBottomToolbar().find('itemId', 'selectedCount')[0];

                            selectedCountField.setValue(selectedRowCount + '&nbsp' + _text('MN01996')); //MN01996 개 선택됨
                        }
                    }
                }]);

        });
    </script>




    <!-- 2017.11.22 Flash 플레이어 사용시 주석 해제 하여 사용 -->
    <!-- <script type="text/javascript" src="/flash/flowplayer/flowplayer-3.2.4.min.js"></script> -->

    <div id="notify-wrapper">
        <span id="notify" class="server-success" style="display:none;opacity: 0.0113659;"><span id="notify-msg"></span></span>
    </div>
    <header class="<?php echo $headerClassName; ?>">
        <div class="logo_box">
            <img src="<?php echo $mainLogoImagePath; ?>" <?php echo $mainLogoImageStyle ?> />
        </div>

        <ul <?php echo $getTopMenuStyle ?>>
            <?php
            $result = createTopMenu_main($_SESSION, $_GET);
            echo $result;
            ?>
        </ul>

        <?php
        if ($_SESSION['user']['user_id'] != 'temp') {
            ?>
            <div class="logout">

                <!-- <div onclick="show_user_modifiy_information('<?= $_SESSION['user']['user_id'] ?>')" title="<?= _text('MN00189') ?>" style="float:left; margin-top:0px;padding: 0px 10px 0px 0px;border-right: 1px solid;"><b><?= $_SESSION['user']['KOR_NM'] ?></b></div> -->
                <div onclick="showUserModifiyInformation()" title="<?= _text('MN00189') ?>" style="float:left; margin-top:0px;padding: 0px 10px 0px 0px;border-right: 1px solid;"><b><?= $_SESSION['user']['KOR_NM'] ?></b></div>
                <button type="button" onClick="logout()" ext:qtip="<?= _text('MN00013') ?>"><i class="fa fa-power-off" aria-hidden="true"></i></button>
            </div>
        <?php
        } else {
            echo '';
        }
        ?>
    </header>

    <script type="text/javascript">
        function doInfoDownload(value) {

            window.open('http://<?= convertIP($_SERVER['REMOTE_ADDR']) ?>/store/http_download.php?path=' + value);
        }

        function TopMenuToggle(toggle_menu) {
            var menu_img_array = new Array("TopImage-home",
                "TopImage-media",
                "TopImage-tmmonitor",
                "TopImage-statistics",
                "TopImage-system",
                "TopImage-request",
                "TopImage-info_report",
                "TopImage-statistics_",
                "TopImage-system_dev",
                "TopImage-harris",
                "TopImage-glossary",
                "TopImage-archiveManagement",
                "TopImage-program",
                "TopImage-monitor",
                "TopImage-archManage",
                "TopImage-ingest"); //2015-12-07 proxima_zodiac 의뢰, 보도정보, 통계 추가            
            <?php
            //Custom Top menu image
            if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MenuItemManager')) {
                $menuItems = \ProximaCustom\core\MenuItemManager::getCustomMenuItems();
                foreach ($menuItems as $menuItem) {
                    echo 'menu_img_array.push("TopImage-' . $menuItem['menuId'] . '");';
                }
            }

            ?>

            for (var i = 0; i < menu_img_array.length; i++) {
                if (toggle_menu == menu_img_array[i]) {
                    var tar = Ext.get(Ext.query('a[name=' + menu_img_array[i] + ']')[0]);
                    if (tar) {
                        tar.setStyle('color', '#00a7dc');
                    }

                    var tar = Ext.get(Ext.query('span[name=arrow_' + menu_img_array[i] + ']')[0]);
                    if (tar) {
                        tar.addClass('menu_arrow');
                    }
                } else {
                    var tar = Ext.get(Ext.query('a[name=' + menu_img_array[i] + ']')[0]);
                    if (tar) {
                        tar.setStyle('color', '');
                    }

                    var tar = Ext.get(Ext.query('span[name=arrow_' + menu_img_array[i] + ']')[0]);
                    if (tar) {
                        tar.removeClass('menu_arrow');
                    }
                }
            }
        }

        function TopMenuFunc(that, menu) {
            //메뉴이동시 상세검색창 닫기
            closeAdvancedSearchWin();
            var main_center = Ext.getCmp('nps_center').getLayout();
            thumbSlider.hide();
            switch (menu) {
                case 'TopImage-home':
                    afterMenuChange('main_card_home');
                    TopMenuToggle(menu);
                    //active시 스토어 로드되도록 수정 2014-12-26
                    if (!Ext.isEmpty(Ext.getCmp('tab_warp'))) {
                        // Ext.getCmp('tab_warp').setActiveTab(0);
                    }
                    break;
                case 'TopImage-media':
                    afterMenuChange('main_card_search');
                    TopMenuToggle(menu);
                    thumbSlider.show();
                    break;
                case 'TopImage-statistics':
                    afterMenuChange('main_card_statistics');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-system':
                    afterMenuChange('main_card_system');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-request': //2015-10-19 proxima_zodiac  메뉴 추가
                    afterMenuChange('main_card_zodiac_request');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-harris':
                    afterMenuChange('main_card_harris');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-info_report': //2015-10-30 proxima_zodiac  메뉴 추가
                    afterMenuChange('main_card_zodiac_report');
                    TopMenuToggle(menu);
                    /** 기사인지 큐시트 인지 선택하는 부분 */
                    var tabList = Ext.getCmp('tab_list');
                    if (!Ext.isEmpty(tabList) && !tabList.initialized) {
                        tabList.setActiveTab(0);
                    }

                    /** 동영상인지 그래픽인지 선택하는 부분 */
                    var tabContentList = Ext.getCmp('tab_content_list');
                    if (!Ext.isEmpty(tabContentList) && !tabContentList.initialize) {
                        Ext.getCmp('tab_content_list').setActiveTab(0);
                    }
                    break;
                case 'TopImage-system_dev': //2016-01-25 super admin  메뉴 추가
                    afterMenuChange('main_card_configuration');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-taskmonitor':
                    // main_center.setActiveItem(10);
                    afterMenuChange('main_card_monitor');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-glossary':
                    afterMenuChange('main_card_glossary');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-archiveManagement':
                    afterMenuChange('main_card_archiveManagement');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-program':
                    afterMenuChange('main_card_program');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-monitor':
                    // main_center.setActiveItem(10);
                    afterMenuChange('main_card_monitor');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-archManage':
                    // main_center.setActiveItem(10);
                    afterMenuChange('main_card_archManage');
                    TopMenuToggle(menu);
                    break;
                case 'TopImage-ingest':
                    // main_center.setActiveItem(10);
                    afterMenuChange('main_card_ingest');
                    TopMenuToggle(menu);
                    break;

                    <?php
                    //Custom Top menu cases
                    if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MenuItemManager')) {
                        \ProximaCustom\core\MenuItemManager::getCustomTopMenuFuncCases();
                    }

                    ?>
            }
        }
        /*
        로그아웃 버튼을 눌렀을 경우 수행하는 함수
        */
        function logout() {
            Ext.Msg.show({
                icon: Ext.Msg.QUESTION,
                //>>title: '확인',
                title: '<?= _text('MN00024') ?>',
                //>> msg: ' 님 로그아웃 하시겠습니까?',
                msg: '<?= $_SESSION['user']['KOR_NM'] . '(' . $_SESSION['user']['user_id'] . '), ' . _text('MSG00002') ?>',
                buttons: Ext.Msg.OKCANCEL,
                fn: function(btnId, text, opts) {
                    if (btnId == 'cancel') return;

                    Ext.Ajax.request({
                        url: '/store/logout.php',
                        callback: function(opts, success, response) {
                            if (success) {
                                try {
                                    var r = Ext.decode(response.responseText);
                                    if (r.success) {
                                        if (Ext.isAdobeAgent) {
                                            window.location = '/?agent=' + Ext.isAdobeAgent;
                                        } else {
                                            window.location = '/';
                                        }
                                    } else {
                                        //>>Ext.Msg.alert('오류', r.msg);
                                        Ext.Msg.alert(_text('MN00022'), r.msg);
                                    }
                                } catch (e) {
                                    //>>Ext.Msg.alert('오류', e+'<br />'+response.responseText);
                                    Ext.Msg.alert('<?= _text('MN00022') ?>', e + '<br />' + response.responseText);
                                }
                            } else {
                                //>>Ext.Msg.alert('오류', response.statusText);
                                Ext.Msg.alert('<?= _text('MN00022') ?>', response.statusText);
                            }
                        }
                    })
                }
            });
        }

        function sub_story_board_selection(el) {

            var t = Ext.get(el);
            t.parent('#images-view').select('.thumb-wrap-disable').removeClass('sb-view-selected');
            t.parent('.template_container').select('.thumb-wrap-disable').addClass('sb-view-selected');
            var images_view_el = Ext.get('images-view').select('.sb-view-selected').elements;
            var images_view_el_length = images_view_el.length;

            var host = window.location.host;
            var ori_ext = 'xml';
            var root_path = '<?= ATTACH_ROOT ?>';
            var filename = images_view_el[0].getAttribute('xml_path');
            var path = root_path + '/' + filename;
            var edl_path = 'application/gmsdd:{"medias":["' + path + '"]}';

            for (i = 0; i < images_view_el_length; i++) {
                var myEventHandler = function(evt) {
                    evt.dataTransfer.setData("DownloadURL", edl_path);
                }
                images_view_el[i].addEventListener("dragstart", myEventHandler, false);
            }
        }

        function sub_story_board_item_selection(el, frame, frameRate) {

            var t = Ext.get(el);
            t.parent('#images-view').select('.thumb-wrap-disable').removeClass('sb-view-selected');
            t.parent('.template_container').select('.thumb-wrap-disable').addClass('sb-view-selected');
            var images_view = Ext.getCmp('images-view');
            var images_view_el = Ext.get('images-view').select('.sb-view-selected').elements;
            var images_view_el_length = images_view_el.length;
            var sec = frame / frameRate;
            // console.log('this', el);
            // console.log('images_view', images_view);
            // console.log('images_view_el', images_view_el);
            // console.log('frame', frame);
            // console.log('frameRate', frameRate);
            var player3 = videojs(document.getElementById('player3'), {}, function() {});
            player3.currentTime(sec);


        }

        /*
          premiere 연동시 해당 스크립트를 가져온다.
        */
        if (Ext.isPremiere) {
            var head = document.getElementsByTagName('head')[0];
            script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = '/javascript/adobe_plugin/premiere_plugin.js';
            head.appendChild(script);
        } else if (Ext.isPhotoshop) {
            var head = document.getElementsByTagName('head')[0];
            script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = '/javascript/adobe_plugin/photoshop_plugin.js';
            head.appendChild(script);
        }

        // 영상매핑 페이지에 비디오 Grid 우클릭 부조전송
        function transmissionAction(contentId, channel ) {
            var contentIds=[];
            var notAllowSend = false;
            var regCompleteContentExists = false;
            var msg = '';
            contentIds.push( contentId );

            var sel = {
                content_list : Ext.encode(contentIds),
                channel: channel
            };
                                    

            Ext.Ajax.request({
                url: '/custom/ktv-nps/store/check_overwrite.php',
                params: sel,
                callback: function(opt, success, response) {
                
                    var res = Ext.decode(response.responseText);
                    if(res.success) {
                        var msg = '선택한 전송처로 전송작업을 시작합니다.';
                        var btn_type = Ext.Msg.OKCANCEL;
                        if(!Ext.isEmpty(res.msg)) {
                            msg = res.msg;
                            btn_type = Ext.Msg.YESNOCANCEL;
                        }
                        Ext.Msg.show({
                            title: '확인',
                            msg: msg,
                            modal: true,
                            icon: Ext.MessageBox.QUESTION,
                            buttons: btn_type,
                            fn: function(btnId) {
                                if(btnId=='yes' || btnId=='ok') {
                                    //전체 content_id 작업시작
                                    Ext.Ajax.request({
                                        url: '/custom/ktv-nps/store/start_transfer_task.php',
                                        params: sel,
                                        callback: function(opt, success, response) {
                                            var res = Ext.decode(response.responseText);
                                            if(res.success) {
                                                //MSG00057 전송이 시작되었습니다
                                                Ext.Msg.alert( _text('MN00003'), _text('MSG00057'));                                                
                                            } else {
                                                Ext.Msg.alert( _text('MN00003'), res.msg);
                                            }
                                        }
                                    });
                                } else if(btnId=='no') {
                                    //중복 아닌 content_id만 작업시작
                                    sel.content_list = Ext.encode(res.accept_content_ids);
                                    Ext.Ajax.request({
                                        url: '/custom/ktv-nps/store/start_transfer_task.php',
                                        params: sel,
                                        callback: function(opt, success, response) {
                                            var res = Ext.decode(response.responseText);
                                            if(res.success) {
                                                //MSG00057 전송이 시작되었습니다
                                                Ext.Msg.alert( _text('MN00003'), _text('MSG00057'));                                               
                                            } else {
                                                Ext.Msg.alert( _text('MN00003'), res.msg);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    } else {
                        Ext.Msg.alert( _text('MN00003'), res.msg);
                    }
                }
            })
    }
    </script>
</body>

</html>
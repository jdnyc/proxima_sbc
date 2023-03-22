<?php
session_start();

$user_id = $_SESSION['user']['user_id'];
$user_name = $_SESSION['user']['KOR_NM'];

require "./inc/testCheck.php";
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$loc = ($loc) ? $loc : "0";

$scroll = $_GET[scroll];

if(!strpos($_SERVER['PHP_SELF'],'login_form.php'))
{
	if (!$_SESSION['user']['user_id'] || $_SESSION['user']['user_id'] == 'temp')
	{
?>
		<script type="text/javascript">
		location.href="login_form.php";
		</script>
<?php
	}
	else
	{
		$now = time(); // checking the time now when home page starts
		include "./check_session.php";
	}
}

if( !preg_match('/(iPad)/i', $_SERVER['HTTP_USER_AGENT']) && preg_match('/(iPhone|Mobile|UP.Browser|Android|BlackBerry|Windows CE|Nokia|webOS|Opera Mini|SonyEricsson|opera mobi|Windows Phone|IEMobile|POLARIS)/i', $_SERVER['HTTP_USER_AGENT']) )
{
} else {
}


if(defined('CUSTOM_ROOT') && defined('APP_TITLE')) {
	$appTitle = APP_TITLE;
} else if($use_product_name_medeis == 'Y') {
	$appTitle = _text('MN02537') . '::' . _text('MN00090');
} else {
	$appTitle = _text('MN00092') . '::' . _text('MN00090');
}
// 현재 로그인되어 있는 아이디 : $_SESSION['user']['user_id']
?>

<!DOCTYPE HTML>
<html lang="ko-kr" class="<?=$index?>">
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<title><?=$appTitle?></title>
<link rel="SHORTCUT ICON" href="../css/images/logo/Ariel.ico"/>
<meta http-equiv="imagetoolbar" content="no">
<!-- <meta name="viewport" content="user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0"/> -->
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0,user-scalable=no,user-scalable=0" />
 <link rel="shortcut icon" sizes="57x57" href="./img/icon_home-1.jpg"/>
 <link rel="apple-touch-icon" sizes="57x57" href="./img/icon_home-1.jpg"/>
 <link rel="apple-touch-icon-precomposed" sizes="57x57" href="./img/icon_home-1.jpg"/>

<link rel="stylesheet" type="text/css" href="./css/default.php" media="all" /><!-- css -->
<link rel="stylesheet" type="text/css" href="./css/notice.php" media="all" /><!-- css -->

<link rel="stylesheet" href="./resources/jquery.mobile-1.3.2.min.css" /><!-- mobile 추가 -->

<link rel="stylesheet" href="./css/dtree.css" /><!-- dtree for category tree -->

<link rel="stylesheet" type="text/css" href="./css/font-awesome.css">
<link rel="stylesheet" type="text/css" href="./css/font-awesome.min.css">

<script type="text/javascript" src="./js/jquery-1.10.2.min.js"></script><!-- jquery -->

<script type="text/javascript" src="./resources/jquery.mobile-1.3.2.min.js"></script><!-- hjquery mobile -->


<!-- include these files Exif 관련 -->
<script type="text/javascript" src="./js/binaryajax.js"></script>
<script type="text/javascript" src="./js/imageinfo.js" ></script>
<!-- optionally include exif.js for Exif support -->
<script type="text/javascript" src="./js/exif.js" ></script>
<script type="text/javascript" src="./js/jquery.exif.js" ></script>

<!-- optionally include jquery-ui.min.js for date picker support -->
<script type="text/javascript" src="./js/jquery-ui.min.js" ></script>

<!-- jQueryRotate.js -->
<script type="text/javascript" src="./js/jQueryRotate.js" ></script>

<!-- dtree.js for category tree menu-->
<script type="text/javascript" src="./js/dtree.js" ></script>



<script type="text/javascript" src="./js/jquery.cookie.js"></script>
<script type="text/javascript" src="./js/jquery.body.scroll.js"></script>

<script type="text/javascript" src="/javascript/lang.js"></script>

<script>
	var icon_image = '';
	$(document).ready(function(){
		var width_sc = $(document).width();
		if( width_sc> 799 )
		{
			$('#img_video').attr('src','/custom/cjos/public/images/logo-small.png');
		}
		else
		{
			$('#img_video').attr('src','/custom/cjos/public/images/logo-small.png');
		}
	});
	$(document).bind("mobileinit",function() { // Your jQuery commands go here before the mobile reference
		$.mobile.ajaxLinksEnabled = false;
		$.mobile.ajaxFormsEnabled = false;
		$.mobile.ajaxEnabled = false;
		//$.event.special.tap.tapholdThreshold = 100000000,
        //$.event.special.swipe.durationThreshold = 999;
	});

	function absorbEvent_(event) {
      var e = event || window.event;
      e.preventDefault && e.preventDefault();
      e.stopPropagation && e.stopPropagation();
      e.cancelBubble = true;
      e.returnValue = false;
      return false;
    }

    function preventLongPressMenu(node) {
      //node.ontouchstart = absorbEvent_;
      //node.ontouchmove = absorbEvent_;
      //node.ontouchend = absorbEvent_;
     // node.ontouchcancel = absorbEvent_;
    }

    function init() {
     // preventLongPressMenu(document.getElementById('img_thumb'));
    }

	function scrollWin()
	{
		var lo_scroll = '<?=$scroll?>';
		if( lo_scroll == '' )
		{
			window.scrollTo(0,0);
		}
		else
		{
			window.scrollTo(0,'<?=$scroll?>');
		}
	}
</script>

<script src="./resources/jquery.mobile-1.3.2.min.js"></script><!-- mobile 추가 -->
<script src="./js/jquery.validate.min.js"></script>

<script type="text/javascript" src="js/json2.js"></script>
<script type="text/javascript" src="js/default.js"></script>

<style>
/*.ui-btn-text {text-shadow:0 0 0 rgba(0,0,0,0.0);}*/
</style>
</head>
<body onload="scrollWin()" class="whole_page" >

<div id="tmp_id_win" style="display:none;">
	<iframe src="" width="600" height="100" frameborder="0" name="ok_frame" id="ok_frame"></iframe>
</div>

<?php if($site_test){ ?>
	<script type="text/javascript">
	function ok_frame_chk(){
		if(document.getElementById('tmp_id_win')){
			document.getElementById('tmp_id_win').style.display = 'block';
		}
		if(document.getElementById('ok_frame')){
			document.getElementById('ok_frame').style.height = '100px';
			document.getElementById('ok_frame').style.width = '600px';
		}
	}
	</script>
	<div id="id_win" style="position:absolute;;  ">
	<a href="javascript:ok_frame_chk()">　　　.</a>
	</div>
<?php } ?>


<?php if($index != true)
	{
?>
	<div data-role="page" data-theme="d" class="viewport" id="page_mobile"><!-- page -->
		<div data-role="header" class="header_nav" data-position="fixed" data-tap-toggle="false" >
			<div data-role="navbar" class="custom-navbar" data-theme="d">
				<ul id="nav_header">
				<li id="list_btn_header">
					<a id="list_content_button_header" href="#left-panel">
						<span class="glyphicon glyphicon-list-alt"></span>
						<img src="img/list.png" class="icon_header"/>
					</a>
				</li>
				<li><a href="" id="video_nav" class="ui-btn-active">
					<img src="/css/h_img/proxima3_.png" class="icon_header" id="img_video"/>
				</a></li>
				<li><a href=""  data-shadow="false" id="btn_notice_page"><?=_text('MN00144')?>
					<img class="icon_header"  id="img_star"/>
				</a></li>
				<li><a href="#" class="btnLogout" id="btn_logout"><?=_text('MN00013')?>
					<img src="img/logout.png" class="img_btnLogout"/>
				</a></li>

				</ul>
			</div>
		</div>
		
		<div data-role="popup" id="confirm_login"  class="popup_alert_out" data-dismissible="false">
			<div class="popup_alert_in">
				<p><?=$_SESSION['user']['KOR_NM'].'('.$_SESSION['user']['user_id'].'), '._text('MSG00002')?></p>
				<div style="text-align:center;">
					<span style="margin-left:auto; margin-right:auto;">
						<div data-role="button" id="btn_y" data-inline="true">Ok</div>
						<div data-role="button" id="btn_n" data-inline="true">Cancel</div>
					</span>
				</div>
			</div>
		</div>
	<div data-role="content" class="page_content" ><!-- content -->
	
	<script type="text/javascript">
	jQuery(function($){
		$(window).bind("pageshow", function(event) {
			if (event.originalEvent.persisted) {
				//document.location.reload();
			}
		});
		$(window).bind('load', function() {
		//modal
			showModal_s();
		});

		$('#btn_logout').on('click', function(){
			$('.popup_alert_out').css('width', $(window).width());
			$('#confirm_login').popup("open");
			$('.popup_alert_in').css('width', '90%');
		});

		$('#btn_y').on('click', function(){
			parent.location.href="./json/login_out.php";
			$('#confirm_login').popup('close');
		});
		$('#btn_n').on('click', function(){
			//$('#video_nav').addClass('ui-btn-active');
			$('#btn_logout').removeClass('ui-btn-active');
			$('#confirm_login').popup('close');
		});
	});
</script>
<?php } ?>

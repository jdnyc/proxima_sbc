<?php
$index = "pagelogin";
include "_head.php";

$test_browser = $_SERVER['HTTP_USER_AGENT'];
if(strstr($_SERVER['HTTP_USER_AGENT'],'Android'))
{
	$padding_top = '0em';
	$padding_top_t = '10em';
	$padding_top_t_h = '2em';
}
else
{
	$padding_top = '0em';
	$padding_top_t = '13em';
	$padding_top_t_h = '2em';
}

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

if (empty($_SESSION['user'])) {
	$_SESSION['user'] = array(
			'user_id' => 'temp',
			'is_admin' => 'N',
			'groups' => array(
					//ADMIN_GROUP,
					//CHANNEL_GROUP
			)
	);
}

$flag = $_REQUEST['flag'];
?>

<style>
#page_mobile {
	background: #33383e;
}
.test_window {color:green;}
.content_login {padding:.5em;width:90% !important;margin-left:auto !important; margin-right:auto !important;}
.contents_login {width:100%;padding-top:0;}
.ebs_ci_l {width:100%;display:table-cell;}
.image_ci_l {width:100%;}
.input_login {width:80%;}
.img_login {
	
}
.text_login>.ui-input-text {
	background: #293037;
}

.blank_login{
	height:15px;
}
@media only screen and (min-width : 320px) and (orientation : portrait) {/*스마트폰 세로*/
	.test_window {color:red;}
	.content_login {padding:.5em;width:90% !important;margin-left:auto !important; margin-right:auto !important;}
	.contents_login {width:100%;padding-top:<?=$padding_top?>;}
	.ebs_ci_l {width:100%;display:table-cell;}
	.image_ci_l {width:100%;}
	.input_login {width:80%;}
	.img_login {width:120px;}
	.blank_login{height:30px;}
}
@media only screen and (min-width : 480px)and (orientation : landscape) {/*스마트폰 가로*/
	.test_window {color:yellow;}
	.content_login {padding:.5em;width:300px !important;margin-left:auto; margin-right:auto;}
	.contents_login {width:100%;padding-top:0;}
	.ebs_ci_l {width:100%;display:table-cell;}
	.image_ci_l {width:100%;}
	.input_login {width:80%;}
	.img_login {width:120px;}
}

@media only screen and (min-width : 768px) and (max-width : 1024px) and (orientation : portrait)   {/*태블릿 세로*/
	.test_window {color:blue;}
	.content_login {padding:.5em;width:100% !important;margin-left:auto; margin-right:auto;}
	.contents_login {width:100%;padding-top:<?=$padding_top_t?>;}
	.ebs_ci_l {width:100%;display:table-cell;}
	.image_ci_l {width:100%;}
	.input_login {width:80%;}
	.img_login {width:250px;}
}
@media only screen and (min-width : 768px) and (max-width : 1600px) and (orientation : landscape)  {/*태블릿 가로*/
	.test_window {color:purple;}
	.content_login {padding:.5em;width:100% !important;margin-left:auto; margin-right:auto;}
	.contents_login {width:100%;padding-top:<?=$padding_top_t_h?>;}
	.ebs_ci_l {width:100%;display:table-cell;}
	.image_ci_l {width:100%;}
	.input_login {width:80%;}
	.img_login {width:300px;}
}
.ui-btn-inner {
	background:none;
	border-top-width:0px !important;
}
.form_login .ui-btn-up-d {border:none !important;background:none;}
.ui-checkbox{left:-15px;}
.ui-submit .ui-btn-inner {margin:0;padding:0;text-align:right;border:none;}
.input_login .ui-btn-inner {margin:0;padding:0;text-align:left;}
.ui-checkbox input{display:none;}

.ui-btn-hover-d{
	border:0px solid yellow  !important;
	background: none !important;
	color: #fff !important;
}
.gemini-text{
	color : #fff;
	font-size: .777em; 
	padding-bottom: 10px;
}
 .ui-body-d, .ui-overlay-d,.ui-btn-inner, .ui-input-text {
 	text-shadow:0 0 0  !important;
 }
.submit_login_new{
	height:30px;
	background:#15a4fa;
	line-height:2;
}
 
</style>

<div data-role="page" data-theme="d" class="viewport" id="page_mobile"><!-- page -->
<?php
	include "alert_s.php";
?>
<div  class="pageLogin" >
<div data-role="content" class="content_login">
	<table class="contents_login">
		<tr>
			<td style="text-align:center;"><img style="width:70%;" src="/custom/cjos/public/images/logo-large.png" class="image_ci_l"/></td>
		</tr>
		<tr>
			<td ><!-- onsubmit="return login_chk()" -->
				<div class="blank_login"></div>
				<form action="./json/login_exec.php" name="login_form" id="fomr_login" method="post"  target="ok_frame" class="form_login">
					<div class="input_login">
						<!-- <label for="userName" style="font-size: 17px;color:white;padding-left: 15px;"><i class="fa fa-lock" aria-hidden="true"></i></label> -->
						
						<div class="text_login"><input type="text" name="userName" id="userName" placeholder="<?=_text('MN00210')?>" value="<?=$_COOKIE[userName]?>"/></div>

						<!-- <label for="password">PASSWORD</label> -->
						<div class="text_login"><input type="password" name="password" id="password" placeholder="<?=_text('MN00185')?>" /></div>

						<label for="idSaveCheck">Remember me</label>
						<input type="checkbox" data-shadow="false"  name="idSaveCheck" id="idSaveCheck"  <?php if($_COOKIE[userName]) echo "checked='checked'"; ?>/>
						<input type="hidden" name="flag" id="flag" value="<?=$flag?>"/>
					</div>
					<div class="input_login" >
						<div class="submit_login_new">
							<button type="submit"  id="btn_login" value="Login" data-shadow="false" data-corners="false"  ><center><font color="#fff">Login</font></center></button>
						</div>
					</div>
				</form>
			</td>
		</tr>
		<tr >
			<td style="text-align:center;">
				<div class="blank_login"></div>
				<img src="./img/login_geminisoft.png" width="90px"; height="25px" style="margin-top: 0px;"/>
				<div class="gemini-text">Copyright © 2016 Geminisoft Co., Ltd. <br>All rights reserved.</div>
			</td>
		</tr>
	</table>
	<div data-role="popup" id="confirm_login"  class="popup_alert_out" data-dismissible="false">
	<div class="popup_alert_in">
		<p>Infomation notice</p>
		<p>Warning: 3G data</p>
		<div style="text-align:center;">
			<span style="margin-left:auto; margin-right:auto;">
				<div data-role="button" id="btn_y" data-inline="true">Yes</div>
				<div data-role="button" id="btn_n" data-inline="true">Cancel</div>
			</span>
		</div>
	</div>
</div>
</div>
</div>
</div><!-- page -->
<script type="text/javascript">
jQuery.validator.setDefaults({
	onkeyup:false,
	onclick:false,
	onfocusout:false,
	showErrors:function(errorMap, errorList){
		if(errorList[0])
		{
			var caption = $(errorList[0].element).attr('caption') || $(errorList[0].element).attr('name');
			alert(errorList[0].message);
		}
	}
});
jQuery(function($){
	$(window).bind('load', function() {
		showModal_s();
	})

	$(document).ready(function(){
		//필수값 체크
		$('#fomr_login').validate({
			//debug: true,
			rules:{
				userName:{required:true},//id
				password:{required:true}//password
			},
			messages:{
				userName:{required:"아이디를 입력해주세요."},//id
				password:{required:"비밀번호를 입력해주세요."}//password
			},
			submitHandler: function(form) {
				form.submit();
				$('#confirm_login').popup('close');
			}
		})
	})
})
</script>

<?php
include "_foot.php";
?>

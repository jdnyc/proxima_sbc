<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');

use Api\Application;
use \Proxima\core\Session;
use \Proxima\core\CustomHelper;

Session::init();

// CPS에서 바로가기로 오는 경우 세션 생성
$userId = $_REQUEST['user_id'] ?? null;
$token = $_REQUEST['token'] ?? null;
if ($userId !== null && $token !== null) {
	$token = base64_decode($token);
	if ($token === config('api_key')) {
		$container = Application::container();
		$userService = new \Api\Services\UserService($container);

		$user = $userService->findByUserId($userId);
		if ($user !== null) {
			$auth = $container->get('auth');
			$auth->setUser($userId);
			$groupIds = $user->groups->pluck('member_group_id')->toArray();
			// 레거시 호환용 세션변수
			$userSession = [
				'user_id' => $userId,
				'is_admin' => bool_to_yn($user->hasAdminGroup()),
				'KOR_NM' => $user->user_nm,
				'user_email' => $user->email,
				'phone' =>  $user->phone,
				'groups' => $groupIds,
				'lang' => $user->lang,
				'super_admin' => $userService->isSuperAdmin($password),
				'user_pass' => hash('sha512', $password)
			];
			Session::set('user', $userSession);
			header('Location: /main.php', true, 301);
			die();
		} else {
			header('Location: /', true, 301);
			die();
		}
	} else {
		header('Location: /', true, 301);
		die();
	}
}

$lang_default_info = getCodeInfo('lang_default');
$lang_default = empty($lang_default_info[0]['code']) ? 'en' : $lang_default_info[0]['code'];

if (empty($_SESSION['user'])) {
	$_SESSION['user'] = array(
		'user_id' => 'temp',
		'is_admin' => 'N',
		'lang' => $lang_default,
		'groups' => array(
			//ADMIN_GROUP,
			//CHANNEL_GROUP
		)
	);
}


//어디서 페이지를 호출했는지에 대한 구분 2013-01-31 이성용
$flag       = $_REQUEST['flag'] ?? null;
$user_id    = $_REQUEST['user_id'] ?? null;
$direct     = $_REQUEST['direct'] ?? null;
$agent      = $_REQUEST['agent'] ?? null;
$agent      = strtolower($agent);
$mode_a     = empty($_REQUEST['mode']) ? '' : '?mode=pgsql';
$mode       = empty($_REQUEST['mode']) ? '' : 'pgsql';

$muser_id = $_REQUEST['muser_id'] ?? null;

if ($direct && !empty($muser_id)) {
	$user_id = $muser_id;
}

if ($_SESSION['user']['user_id'] != 'temp' && $flag == '') {
	//echo "<script type=\"text/javascript\">window.location=\"browse.php\"</script>";
	if ($agent) {
		echo "<script type=\"text/javascript\">window.location=\"main.php?agent=" . $agent . "\"</script>";
	} else {
		echo "<script type=\"text/javascript\">window.location=\"main.php" . $mode_a . "\"</script>";
	}
}


function rtn_mobile_chk()
{
	// 모바일 기종(배열 순서 중요, 대소문자 구분 안함)
	$ary_m = array("iPhone", "iPod", "IPad", "Android", "Blackberry", "SymbianOS|SCH-M\d+", "Opera Mini", "Windows CE", "Nokia", "Sony", "Samsung", "LGTelecom", "SKT", "Mobile", "Phone");
	for ($i = 0; $i < count($ary_m); $i++) {
		if (preg_match("/$ary_m[$i]/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
			return $ary_m[$i];
			break;
		}
	}
	return "PC";
}

//임시 주석 모바일 화면 정리 전까지
$chk_m = rtn_mobile_chk();
// if ($chk_m == "PC") { } else {
// 	echo "<script type=\"text/javascript\">window.location=\"m\"</script>";
// }


$appTitle = 'Proxima MAM';


if (defined('CUSTOM_ROOT') && defined('APP_TITLE')) {
    $appTitle = APP_TITLE;
    $faviconPath = '/custom/ktv-nps/images/faviconV2.ico';
} else {
	$appTitle = _text('MN00092') . '::' . _text('MN00090');
}

Session::set('app_title', $appTitle);
/*
<!--[if lt IE 9]>
  <script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

  <!--[if lt IE 9]>
  <script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script>
  <![endif]-->
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=11" />
    <title><?php echo $appTitle; ?></title>
    <link rel="SHORTCUT ICON" href="<?php echo $faviconPath; ?>" />
	<script src="/javascript/lang.php" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
	<script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/lib/extjs/ext-all.js"></script>
	<script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-ko.js"></script>

	<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/xtheme-gray.css" />

	<link rel="stylesheet" type="text/css" href="/css/style.css?_ver=20191216001" />
	<script type="text/javascript" src="/javascript/script.js"></script>
	<link rel="stylesheet" type="text/css" href="/css/font-awesome.min.css">

	<!--Custom Style loading-->
	<?php
	//Custom Style loading        
	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CssManager')) {
		$style = \ProximaCustom\core\CssManager::getLoginLogoStyle();
		echo $style;
	}


	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\ScriptManager')) {
		$scripts = \ProximaCustom\core\ScriptManager::getCustomScripts(false, ['Ariel.Nps.DashBoard', 'Ariel.Das.ArcManage', 'Ariel.task.Monitor']);
		foreach ($scripts as $name => $script) {
			echo $script;
		}
	}

	?>

	<script type="text/javascript">
		var intervalStatus = 0;
		function login(userName, password, flag,authNumber) {

			var payload = {
				user_name: userName,
				password: password,
				flag: flag,
				auth_number:authNumber,
				limit_time:this.limitTime
            };

			Ext.Ajax.request({
				url: '/api/v1/auth/login',
				method: 'POST',
				jsonData: payload,
				success: function(response, opts) {
					var r = Ext.decode(response.responseText);
					$authPhone = r.auth_phone;
					if($authPhone){
						window.location = '/' + r.redirection;  
					}else{
						authPhone();      
					}
					
				}, 
				failure: function(response, opts) {
					var r = Ext.decode(response.responseText);
                    if( r.code == 'not_found'){
                        Ext.Msg.alert('로그인 실패', "아이디 또는 비밀번호를 다시 확인하세요.");
                    }else{
                        Ext.Msg.alert('로그인 실패', r.msg);
                    }
				},
			})
		}

		function checkLogin() {

			var loginData = {
				flag: '<?= $flag ?>',
				mode: '<?= $mode ?>',
				agent: '<?= $agent ?>'
			};
			loginData.userName = Ext.get('login-id').getValue();
			loginData.password = Ext.get('login-pw').getValue();

			<?php
			if (CustomHelper::customMethodExists('\ProximaCustom\core\ViewCustom', 'renderScript')) {
				\ProximaCustom\core\ViewCustom::renderScript('AddLoginData');
			}
			?>

			Ext.Ajax.request({
				url: '/store/login_ok.php',
				params: loginData,
				callback: function(opts, success, response) {
					if (success) {
						try {
							var r = Ext.decode(response.responseText);
							if (r.success) {
								if (r.passchk) {
									Ext.Msg.show({
										title: _text('MN00024'), //MN00024'확인',
										msg: _text('MSG00008'), //암호가 설정되어 있지 않습니다. 마이페이지로 이동합니다
										icon: Ext.Msg.INFO,
										buttons: Ext.Msg.OK,
										fn: function(btnId) {
											window.location = 'pages/mypage/index.php';
										}
									});
								} else {
									// 에어브라우저에서 로그인시 바로 미디어 검색페이지로
									// if (Ext.isAir) {
									// window.location = '/browse.php?media=true';
									// } else {
									window.location = '/' + r.redirection;
									// }
								}
							} else {
								Ext.Msg.show({
									title: _text('MN00024'), //MN00024'확인',
									msg: r.msg,
									icon: Ext.Msg.INFO,
									buttons: Ext.Msg.OK,
									fn: function(btnId) {
										Ext.get('login-id').focus(250);
									}
								});
							}
						} catch (e) {
							Ext.Msg.alert(e.title, e.message);
						}
					} else {
						Ext.Msg.alert(_text('MN01098'), response.statusText); //'서버 오류'
					}
				}
			});

			return false;
		}

		function AutoLogin() {
			Ext.Ajax.request({
				url: '/store/login_ok.php',
				params: {
					userName: '<?= $user_id ?>',
					direct: '<?= $direct ?>',
					flag: '<?= $flag ?>',
					agent: '<?= $agent ?>'
				},
				callback: function(opts, success, response) {
					if (success) {
						try {
							var r = Ext.decode(response.responseText);
							if (r.success) {
								window.location = '/' + r.redirection;
							} else {
								Ext.Msg.show({
									title: _text('MN00024'), //MN00024'확인',
									msg: r.msg,
									icon: Ext.Msg.INFO,
									buttons: Ext.Msg.OK,
									fn: function(btnId, text, opts) {
										Ext.get('login-id').focus(250);
									}
								});
							}
						} catch (e) {
							Ext.Msg.alert(e['title'], e['message']);
						}

					} else {
						//MN01098 '서버 오류'
						Ext.Msg.alert(_text('MN01098'), response.statusText);
					}
				}
			});
			return false;
		}

		function SignUp() {
			var components = [
				'/custom/ktv-nps/javascript/ext.ux/Custom.SignUpWindow.js'
			];

			Ext.Loader.load(components, function(r) {
				new Custom.SignUpWindow({

				}).show();
			});
		}

		// sms 인증 관련 함수
		function setTimer(){
			var time = Number(this.limitTime);
			if(time >= 0){
				var h = parseInt(time/3600);
				var m = parseInt((time%3600)/60);
				var s = time%60;
				this.limitTime = time-1;
				document.getElementById('timer').innerHTML = String().concat((h<10)?String().concat('0',h):h,':',(m<10)?String().concat('0',m):m,':',(s<10)?String().concat('0',s):s);
				
				if(time == 0){
					document.getElementById('time_over_msg').style.display = "block";
				}
			}
			else{
				clearInterval( this.t );
				this.intervalStatus = 0;
			}
		}
		function authPhone(){
			this.limitTime = 180;
			
			document.getElementById('auth-phone').style.display='block';
			document.getElementById('auth-user').style.display='none';
			document.getElementById('time_over_msg').style.display = "none";
			
			if(this.intervalStatus != this.t ){
				this.t = setInterval(function(){setTimer()}, 1000 );
				this.intervalStatus = 	this.t;	
			}
		}
		function authRe(){
			this.limitTime = 180;
			document.getElementById('time_over_msg').style.display = "none";

			$userId = Ext.get('login-id').getValue();

			// 인증번호 재전송
			Ext.Ajax.request({
				url:'/api/v1/auth/number-re-send',
				method:'post',
				params:{
					user_id:$userId
				},
				callback:function(opts,success,response){
					var r = Ext.decode(response.responseText);
					if(r.success){
						Ext.Msg.alert('알림', '인증번호가 재발송되었습니다.');
						
						if(this.intervalStatus != this.t ){
							this.t = setInterval(function(){setTimer()}, 1000 );
							this.intervalStatus = 	this.t;	
						}
					}
				}
			})
		}

		function inNumber(){
			if(event.keyCode<48 || event.keyCode>57){
			  event.returnValue=false;
		  	}
			
			var authNumber = Ext.get('auth-number').getValue();
			var authNumberChangeString = authNumber.toString();
            if(authNumberChangeString.length >= 4){
				event.returnValue=false;
			};
		}	

		Ext.onReady(function() {

			<?php
			if ($direct) {
				echo "AutoLogin();";
			}
			?>

			Ext.get('login-id').focus();
			Ext.get('login-id').on('keydown', function(e, t, o) {
				if (e.getKey() == e.ENTER) {
					e.stopEvent();
					checkLogin(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>');
				}
			});
			Ext.get('login-pw').on('keydown', function(e, t, o) {
				if (e.getKey() == e.ENTER) {
					e.stopEvent();
					checkLogin(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>');
				}
			});
			Ext.get('login-submit').on('click', function(e, t, o) {
				checkLogin(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>');
			});

			// 내부 클라이언트 IP에서 접근 시 [사용자 ID 신청]
			var checkInternalIp = '<?php echo checkInternalIp(); ?>';
			if (checkInternalIp) {
				Ext.get('sign-up').hide();
			} else {
				Ext.get('sign-up').on('click', function(e, t, o) {
					SignUp();
				});
				Ext.get('sign-up').show();
			}

			// 핸드폰 인증번호 체크 관련
			Ext.get('auth-cancel').on('click',function(e,t,o){
				document.getElementById('auth-phone').style.display='none';
				document.getElementById('auth-user').style.display='block';
				document.getElementById('time_over_msg').style.display = "none";
			});
			Ext.get('auth-re').on('click',function(e,t,o){
				authRe();
			});
			Ext.get('auth-number').on('keydown',function(e,t,o){
				if (e.getKey() == e.ENTER) {
					e.stopEvent();
					checkLogin(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>', Ext.get('auth-number').getValue());
				}
            });
			Ext.get('auth-check').on('click',function(e,t,o){

				checkLogin(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>', Ext.get('auth-number').getValue());
			});
		});
	</script>

</head>

<body class="centerbox1">
	<div id="login">
		<div class="loginbox">

			<div class="proxima_logo_image_login_form"></div>

				<?php
				if (CustomHelper::customMethodExists('\ProximaCustom\core\ViewCustom', 'render')) {
					\ProximaCustom\core\ViewCustom::render('login_top');
				}
				?>
				<div id="auth-user">
					<div id="login-box" class="form-container">
						<div class="input-container">
							<input type="text" name="" id="login-id" class="id" placeholder="User ID" />
							<input type="password" name="" id="login-pw" class="pass" placeholder="Password" />
							<div class="login-notice"></div>
							<button id="sign-up">
								<span>사용자 ID신청</span>
							</button>
						</div>
						<button id="login-submit">
							<span>Login</span>
						</button>
					</div>
				</div>
				
				<div id="auth-phone" style="display:none;">
					<div class="form-container">
						<table style="	height:40px;
										border-spacing: 10px;
  										border-collapse: separate;">
							<tr>
								<td>
									<input style="	width:250px;
													background-color: #1f1f1f;
													border: 1px solid #000000;
													font-size: 1rem;
													height:40px;
                                                    color: #ffffff;" type="text" name="auth-number" id="auth-number" onkeypress="inNumber();" placeholder="인증번호" />
									
								</td>
								<td>
									<button style="background-color: #00AEEF;
												   font-size: 1.5rem;
												   border: none;
												   border-radius: 6px;
												   width:100px;
												   height:40px;
												   cursor:pointer;
												   color: white;" id="auth-check" >인증</button>
								</td>
							</tr>
							<tr>
								<td>
								</td>
								<td>
									<button style="background-color: #00AEEF;
												   font-size: 1.5rem;
												   border: none;
												   border-radius: 6px;
												   width:100px;
												   height:40px;
												   cursor:pointer;
												   color: white;" id="auth-cancel">취소</button>
								</td>
							</tr>
							<tr>
								<td>
									<div id="tmp_authnum_display">
										<table>
											<tr style="font-family:Maigun Gothic; font-size:14px; color:#FFF;">						
												<td style="width:110px;">
													인증 대기 시간 :
												</td>
												<td style="width:55px; align:center">
													<span id="timer">00:00:00</span>
												</td>
											</tr>
										</table>
									</div>
								</td>
								<td>
									<button style="background-color: #00AEEF;
												   font-size: 1.5rem;
												   border: none;
												   border-radius: 6px;
												   width:100px;
												   height:40px;
												   cursor:pointer;
												   color: white;" id="auth-re">재발급</button>
								</td>
							</tr>
							<tr>
								<td colspan=2>
									<span id="time_over_msg" style="display:none; color:#DD4800; font-size:12pt;">인증번호 시간이 만료되었습니다. 인증번호를 재발송 하시기 바랍니다.</span>
								</td>
							</tr>
						</table>
					</div>
				</div>
				<?php
				if (CustomHelper::customMethodExists('\ProximaCustom\core\ViewCustom', 'render')) {
					\ProximaCustom\core\ViewCustom::render('login_bottom');
				}
				?>

			<dl>
				<!-- <dt><img src="css/images/login_geminisoft.png" width="90px"; height="25px" style="margin-top: 0px;"/></dt>
            <dd>Copyright © 2016 Geminisoft Co., Ltd. / All rights reserved.	<span><?= $agent ?></span></dd> -->
			</dl>
		</div>
	</div>
	<footer class="login-footer">
		<div class="footer-logo" />
		<div style="text-align: right">
			<div class="footer-content">해상도 : 1920x1080(권장), 1600x900(최소) &nbsp;&nbsp;&nbsp;&nbsp; 문의 : 044-204-8353 &nbsp;&nbsp;&nbsp;&nbsp; Copyright ⓒ KTV NPS, All Right Reserved.</div>
		</div>
		<!-- <div class="copyright">Copyright ⓒ KTV NPS, All Right Reserved.</div> -->
	</footer>
</body>

</html>
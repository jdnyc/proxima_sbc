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
$flag = $_REQUEST['flag'];
$user_id = $_REQUEST['user_id'];
$direct = $_REQUEST['direct'];
$mode_a = empty($_REQUEST['mode']) ? '' : '?mode=pgsql';
$mode = empty($_REQUEST['mode']) ? '' : 'pgsql';

// 추가 agent 부분  by 2016-08-22 by hkh 플러그인 연계
$agent =  $_REQUEST['agent'] ? strtolower($_REQUEST['agent']) : '';


if ($direct && !empty($_REQUEST['muser_id'])) {
	$user_id = $_REQUEST['muser_id'];
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
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title><?php echo $appTitle; ?></title>
	<script src="/javascript/lang.php" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/ext-all.css" />
	<script type="text/javascript" src="/lib/extjs/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/lib/extjs/ext-all.js"></script>
	<script type="text/javascript" src="/lib/extjs/src/locale/ext-lang-ko.js"></script>

	<link rel="stylesheet" type="text/css" href="/lib/extjs/resources/css/xtheme-gray.css" />

	<link rel="stylesheet" type="text/css" href="/css/style.css" />
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
		foreach ($scripts as $script) {
			echo $script;
		}
	}

	?>

	<script type="text/javascript">
		function login(userName, password, flag) {
			var payload = {
				user_name: userName,
				password: password,
				flag: flag
			};


			Ext.Ajax.request({
				url: '/api/v1/auth/login',
				method: 'POST',
				jsonData: payload,
				success: function(response, opts) {
					var r = Ext.decode(response.responseText);
					window.location = '/' + r.redirection;
				},
				failure: function(response, opts) {
					var r = Ext.decode(response.responseText);
					if (r.code == 'not_found') {
						Ext.Msg.alert('로그인 실패', "아이디 또는 비밀번호를 다시 확인하세요.");
					} else {
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
					login(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>');
				}
			});
			Ext.get('login-pw').on('keydown', function(e, t, o) {
				if (e.getKey() == e.ENTER) {
					e.stopEvent();
					login(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>');
				}
			});
			Ext.get('login-submit').on('click', function(e, t, o) {
				login(Ext.get('login-id').getValue(), Ext.get('login-pw').getValue(), '<?= $flag ?>');
			});

			Ext.get('sign-up').on('click', function(e, t, o) {
				SignUp();
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

			<div class="form-container">
				<div class="input-container">
					<input type="text" name="" id="login-id" class="id" placeholder="User ID" />
					<input type="password" name="" id="login-pw" class="pass" placeholder="Password" />
					<button id="sign-up">
						<span>사용자 ID신청</span>
					</button>
				</div>
				<button id="login-submit">
					<span>Login</span>
				</button>
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
</body>

</html>
<?php

use Proxima\core\View;
use Proxima\core\Session;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
Session::init();
fn_checkAuthPermission($_SESSION);

$user = Session::get('user');
$isAdmin = in_array(ADMIN_GROUP, $user['groups']) || $user['is_admin'] == 'Y';

$scriptPath = 'pages/menu/config/user/user.js';
$variables = [
	'$is_hidden_delete_button' => !$isAdmin
];

$script = View::getScriptData($scriptPath, $variables);
echo $script;

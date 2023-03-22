<?php
session_start();
header("Content-type: application/json; charset=UTF-8");


require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');

$user_id = $db->escape(trim($_REQUEST['user_id']));
$password_0 =  trim($_REQUEST['password_0']);
$password_1 =  trim($_REQUEST['password_1']);
$password_2 =  trim($_REQUEST['password_2']);
$new_ori_password = $_REQUEST['password_1'];
$email = trim($_REQUEST['email']);
$phone = trim($_REQUEST['phone']);
$user_ori_password = trim($_REQUEST['password_1']);
$lang = $_REQUEST['lang'];
$user_menu_mode = $_REQUEST['user_menu_mode'];
$user_action_icon_slide = $_REQUEST['action_icon_slide'];
$first_page = $_REQUEST['first_page'];


try {
	if (empty($user_id)) throw new Exception(_text('MSG00125'));

	// 사용자 정보 필드에서 비밀번호 필드를 사용안하면 정합성 체크도 하지 않는다.
	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\UserInfoCustom')) {
		if (\ProximaCustom\core\UserInfoCustom::PasswordFieldVisible()) {
			if (empty($password_1)) throw new Exception(_text('MSG00125'));
			if (empty($password_1) || empty($password_2) || empty($email)) throw new Exception(_text('MSG00125'));
		}
	} else {
		if (empty($user_id) || empty($password_1)) throw new Exception(_text('MSG00125'));
		if (empty($password_1) || empty($password_2) || empty($email)) throw new Exception(_text('MSG00125'));
	}

	//
	if ($password_1 != $password_2) throw new Exception(_test('MSG00097'));

	$current_time = date('YmdHis');
	$member_info = $db->queryRow("select member_id,user_id,password,phone,email,salt_key from bc_member where user_id='$user_id' and del_yn = 'N'");
	$current_password = hash('sha512', $password_0 . $member_info['salt_key']);
	$new_password = hash('sha512', $password_1 . $member_info['salt_key']);

	$current_password = hash('sha512', $password_0);
	$new_password = hash('sha512', $password_1);

	if ($member_info['password'] != $current_password) throw new Exception('현재 비밀번호가 일치 하지 않습니다.');

	// 사용자 정보 필드에서 사용안하는 필드는 업데이트도 하지 않는다
	if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\UserInfoCustom')) {
		$data = array(
			'PASSWORD_CHANGE_DATE' => $current_time
		);

		if (\ProximaCustom\core\UserInfoCustom::PasswordFieldVisible()) {
			$data[] = [
				'password' => $new_password
			];
		}

		if (\ProximaCustom\core\UserInfoCustom::EmailFieldVisible()) {
			$data[] = [
				'email' => $email
			];
		}

		if (\ProximaCustom\core\UserInfoCustom::PhoneFieldVisible()) {
			$data[] = [
				'phone'		=>	$phone
			];
		}

		if (\ProximaCustom\core\UserInfoCustom::LanguageFieldVisible()) {
			$data[] = [
				'lang'		=>	$lang
			];
		}
	} else {
		$data = array(
			'email'		=> $email,
			'phone'		=>	$phone,
			'password' =>	$new_password,
			'lang'	=>	 $lang,
			'PASSWORD_CHANGE_DATE' => $current_time
		);
	}

	$result = $db->update('bc_member', $data, "user_id = '$user_id'");

	$data = array(
		'TOP_MENU_MODE'	=> $user_menu_mode,
		'ACTION_ICON_SLIDE_YN'	=> $user_action_icon_slide,
		'FIRST_PAGE' => $first_page
	);

	$member_id = $member_info['member_id'];
	$hasMemberOption = $db->queryOne("SELECT COUNT(MEMBER_ID) FROM BC_MEMBER_OPTION WHERE MEMBER_ID = '$member_id'");

	if ($hasMemberOption > 0) {
		$result = $db->update('BC_MEMBER_OPTION', $data, "MEMBER_ID = $member_id");
	} else {
		$data['member_id'] = $member_id;
		$result = $db->insert('BC_MEMBER_OPTION', $data);
	}

	//	$result = $mdb->exec("update bc_member set email='$email',phone='$phone', password='$password_1',ori_password='$user_ori_password' where user_id='$user_id'");

	//	$user = $mdb->queryRow("select email, user_nm from bc_member where user_id='$user_id' ");



	$msg = _text('MSG02033');

	/*	if( update_user_info($member_info) != 'true' )
	{
		$msg .= '<br />DASì™€ ë�™ê¸°í™”ì—� ì‹¤íŒ¨í•˜ì˜€ìŠµë‹ˆë‹¤.';
	}
	else
	{
		$msg .= '<br />DASì™€ ë�™ê¸°í™” ë�˜ì—ˆìŠµë‹ˆë‹¤.';
	}

	$result_email = $member_info['email'];
*/

	echo json_encode(array(
		'success' => true,
		'msg' => $msg,
		'test' => $test
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

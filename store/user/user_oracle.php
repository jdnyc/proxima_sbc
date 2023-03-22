<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/Zodiac.class.php');

$update_user_id = $_SESSION['user']['user_id'];
//require_once('../oracle_connect.php');
$empno = $db->escape($_REQUEST['userId']);
$ori_pw = $_REQUEST['pw'];
$pw = $_REQUEST['pw'];
$name = $_REQUEST['name'];
$dept_nm = $_REQUEST['dept_nm'];
$job_position = $_REQUEST['job_position'];
$expired_date = $_REQUEST['expired_date'];
$groups = explode(',', $_REQUEST['groups']);
$email = $_REQUEST['email'];
$phone = $_REQUEST['phone'];
$lang = $_REQUEST['lang'];
$top_menu_mode = $_REQUEST['top_menu_mode'];
$action_icon_slide_yn = $_REQUEST['action_icon_slide_yn'];

try {

	switch ($_POST['action']) {
        case 'add':
        
			if ($arr_sys_code['max_allow_users']['use_yn'] == 'Y') {
				//If max user limitted, alert.
				$user_count = $db->queryOne("SELECT COUNT(*) FROM BC_MEMBER");
				if ($user_count >= $arr_sys_code['max_allow_users']['ref1']) {
					//The maximum allowable number of users exceed.
					throw new Exception(_text('MSG02164') . "(" . $user_count . "/" . $arr_sys_code['max_allow_users']['ref1'] . ")");
				}
            }
            $container = app()->getContainer();
            $userService = new \Api\Services\UserService($container);
          
            $bisService = new \Api\Services\BisCommonService($container);


            $user = new stdClass();
            $user->member_id        = $memberId;
            $user->user_id          = $_REQUEST['user_id'];
            $user->password         = $_REQUEST['password_1'];
            $user->user_nm          = $name;
            $user->dept_nm          = $dept_nm;
            $user->expired_date     = $expired_date;
            $user->created_date     = $createdDate;       
            $user->phone            = $phone;
            $user->email            = $email;
            $user->lang             = $lang;           
            $user->groups           = $_REQUEST['groups'];
            $userCollection = $userService->create($user);
           
            if( !$userCollection ){
                throw new Exception(_text('MSG02090'));
            }

            $userId = $user->user_id;
            $userRealName = $user->user_nm;
            $passwordHash = $userService->encryptPassword($password);

            $sessionConfig = config('session');
            $mode = $sessionConfig['driver'];        
            //dump($passwordHash);
            if($mode == 'sso'){
                $ssoClient = $container->get('sso_admin');        
                $ssoEmail = empty($email) ? '-' : $email;
                $ssoHpNo = empty($phone) ? '-' : $phone;
                $result = $ssoClient->createUser($userId, $passwordHash, $userRealName, $ssoEmail, $ssoHpNo);   
                
            }
            
            //조디악 동기화
            if( config('zodiac')['linkage'] ){
                $userService->syncUserZodiac($userId);
            }

            //bis 연동
            if( config('bis')['user'] ){            
                $r  = $bisService->createUser( $user );
            }

            //od 동기화
            if( config('od')['linkage'] ){
                $folderAuth = new \ProximaCustom\core\FolderAuthManager();
                $folderAuth->createUserFromOD($userId, $userRealName, $userCollection->member_id, $password );
            }



			//보도정보에 사용자 정보 전달
			// $zodiac = new Zodiac();

			// $zodiac->userManage(array(
			// 		'action'			=>	'add',
			// 		'user_id'			=>	$empno,
			// 		'user_nm'			=>	$name,
			// 		'password'			=>	$pw,
			// 		'interPhone'		=>	'',
			// 		'homePhone'			=>	'',
			// 		'handPhone'			=>	$phone,
			// 		'email'				=>	$email,
			// 		'rmk'				=>	$note,
			// 		'update_user_id'	=>	$update_user_id
			// ));
			break;

		case 'edit': //그룹 수정
			$member_id = $_POST['member_id_'];

			$groups = explode(',', $_REQUEST['groups']);

			foreach ($member_id as $member) {
				$r = $db->exec("delete from bc_member_group_member where member_id=" . $member . " ");

				foreach ($groups as $group_id) {
					$chk_member_id = $db->queryOne("select member_group_id from bc_member_group where member_group_id='{$group_id}' and is_admin='Y'");
					if ($chk_member_id) {
						$update_admin = $db->exec("update bc_member set is_admin='Y' where member_id={$member}");
					} else {
						$update_admin = $db->exec("update bc_member set is_admin='' where member_id={$member}");
					}
					$r = $db->exec("insert into bc_member_group_member values ($member, $group_id)");
				}
			}
			break;

		case 'edit_new':
			//비밀번호까지 받는 정보수정
			$member_id = $_POST['member_id'];

			$cur_pw = hash('sha512', $_POST['cur_pw']);
			$ori_pw = $db->queryOne("
						SELECT PASSWORD
						FROM BC_MEMBER
						WHERE MEMBER_ID = '$member_id'
					");
			if ($ori_pw != $cur_pw) {
				//비밀번호가 일치하지 않습니다
				throw new Exception(_text("MSG00100"));
			}

			$update_query = "update bc_member
				set	user_id='$empno',
					user_nm = '$name',
					dept_nm = '$dept_nm',
					password = '$pw'
				where member_id = '$member_id'";
			$r = $db->exec($update_query);
			break;

        case 'edit_1':
            //현재 수정
            $empno = $_REQUEST['userId'];
			$name = $_REQUEST['name'];
			$dept_nm = $_REQUEST['dept_nm'];
			$email = $_REQUEST['email'];

			$occu_kind = $_REQUEST['occu_kind'];
			$job_rank = $_REQUEST['job_rank'];
			$job_position = $_REQUEST['job_position'];
			$job_duty = $_REQUEST['job_duty'];
			$breake = $_REQUEST['breake'];
			$phone = $_REQUEST['phone'];
			$dep_tel_num = $_REQUEST['dep_tel_num'];

			$member_id = $_POST['member_id'];
            $groups = explode(',', $_REQUEST['groups']);
            
            $userService = new \Api\Services\UserService($app->getContainer());

            $user = new stdClass();        
            $user->user_id          = $_REQUEST['userId'];
            $user->password         = $_REQUEST['password_1'];
            $user->user_nm          = $name;
            $user->dept_nm          = $dept_nm;              
            $user->phone            = $phone;
            $user->email            = $email;        
            $user->groups           = $_REQUEST['groups'];
            $userCollection = $userService->update($member_id, $user);
           
            if( !$userCollection ){
                throw new Exception(_text('MSG02090'));
            }       


			break;

		case 'edit_2':
			$empno = $_REQUEST['userId'];
			$pw = hash('sha512', $_REQUEST['pw']);
			$name = $_REQUEST['name'];
			$dept_nm = $_REQUEST['dept_nm'];
			$email = $_REQUEST['email'];

			//$denied = $_REQUEST['denied'];
			//$limit_date=$_REQUEST['limit_date'];
			//$last_login = $_REQUEST['last_login'];
			//$is_admin = $_REQUEST['is_admin'];
			//$status = $_REQUEST['status'];
			$occu_kind = $_REQUEST['occu_kind'];
			$job_rank = $_REQUEST['job_rank'];
			$job_position = $_REQUEST['job_position'];
			$job_duty = $_REQUEST['job_duty'];
			$breake = $_REQUEST['breake'];
			$phone = $_REQUEST['phone'];
			//$home = $_REQUEST['home'];
			//$usr_loan_qty = $_REQUEST['usr_loan_qty'];
			$hired_date = $_REQUEST['hired_date'];
			$dep_tel_num = $_REQUEST['dep_tel_num'];
			//$retire_date = $_REQUEST['retire_date'];


			if ($breake == '재직') {
				$breake = 'C';
			} else if ($breake == '퇴직') {
				$breake = 'T';
			} else if ($breake == '휴직') {
				$breake = 'H';
			} else {
				$breake = '';
			}

			if ($phone == '--') {
				$phone = NULL;
			}

			$edit_user = "update bc_member set  email='$email', phone='$phone' where user_id='$empno'";

			$r = $db->exec($edit_user);

			$member_info = array(
				'user_id' => $empno,
				'email' => $email,
				'phone' => $phone
			);

			if (update_user_info($member_info) != 'true') { }

			break;

			//		case 'edit':
			//			$member_id = $_POST['member_id'];
			//
			//			$update_query = "update bc_member set user_id='$empno', user_nm = '$name', dept_nm = '$dept_nm', job_position = '$job_position' , email = '$email' where member_id = '$member_id'";
			//			$r = $db->exec($update_query);
			//
			//			$reset_group = $db->exec("delete bc_member_group_member where member_id = $member_id");
			//
			//			$db->exec("update bc_member set is_admin='N' where user_id='$empno'");
			//			foreach($groups as $group_id)
			//			{
			//				if ($group_id == ADMIN_GROUP)
			//				{
			//					$db->exec("update bc_member set is_admin='Y' where user_id='$empno'");
			//				}
			//
			//				$r = $db->exec("insert into bc_member_group_member values ($member_id, $group_id)");
			//			}
			//
			//		break;

		default:
			//throw new Exception('action 값이 없습니다.');MSG01022
			throw new Exception(_text('MSG01022'));
			break;
	}

	die(json_encode(array(
		'success' => true,
		'q' => $add_user,
		'q_option' => $option
	)));
} catch (Exception $e) {
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'q' => $db->last_query
	)));
}

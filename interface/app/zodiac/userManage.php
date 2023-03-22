<?php

$server->register('userManage',
	array(
		'action' => 'xsd:string',
		'loginID' => 'xsd:string',
		'realName' => 'xsd:string',
		'password' => 'xsd:string',
		'email' => 'xsd:string',
		'handPhone' => 'xsd:string',
		'homePhone' => 'xsd:string',
		'interPhone' => 'xsd:string',
		'comments' => 'xsd:string'
	),
	array(
		'success' => 'xsd:string',
		'msg' => 'xsd:string',
		'code' => 'xsd:string'
	),
	$namespace,
	$namespace.'#userManage',
	'rpc',
	'encoded',
	'userManage'
);

function userManage($action, $loginID, $realName, $password, $email, $handPhone, $homePhone, $interPhone, $comments) {
	global $db, $server;

	try{
		$response = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<Response />');

		$cur_date = date('YmdHis');
		$comments = $db->escape($comments);

		switch($action) {
			case 'add' :
				// if( is_null($loginID) || empty($realName) || empty($password) ){
				// 	throw new Exception ('invalid request', 101 );
				// }
				// //시퀀스를 사용하지않고 멤버아이디값의 최대값에 +1을 한다. 2011.01.11 김성민
				// $member_id = ($db->queryOne("SELECT MAX(MEMBER_ID) AS MAX_MEMBER_ID FROM BC_MEMBER"))+1;

				// $r = $db->queryOne("SELECT COUNT(*) FROM BC_MEMBER WHERE USER_ID = '$loginID'");
				// if($r > 0) throw new Exception('동일한 아이디가 존재 합니다.');

				// $sha_password = hash('sha512', $password);

				// $r = $db->exec("
				// 		INSERT INTO BC_MEMBER
				// 			(MEMBER_ID, USER_ID, PASSWORD, USER_NM, CREATED_DATE, EMAIL, PHONE, DEP_TEL_NUM, MEMBER_NOTE)
				// 		VALUES
				// 			('$member_id', '$loginID', '$sha_password', '$realName', '$cur_date', '$email', '$handPhone', '$interPhone', '".$comments."')
				// 	");

				// $default_group = $db->queryRow("SELECT MEMBER_GROUP_ID, MEMBER_GROUP_NAME FROM BC_MEMBER_GROUP WHERE IS_DEFAULT = 'Y'");

				// $r = $db->exec("
				// 		INSERT INTO BC_MEMBER_GROUP_MEMBER
				// 			(MEMBER_ID, MEMBER_GROUP_ID)
				// 		VALUES
				// 			(".$member_id.", ".$default_group['member_group_id'].")
				// 	");

				// //$success = 'true';
				// //$msg = '사용자 추가에 성공했습니다';

				// // AD 연동 하는 부분 추가해야됨

				// //1. ldap으로 사용자 추가
				// //2. ldap으로 사용자 그룹 매핑
				// require_once($_SERVER['DOCUMENT_ROOT'].'/store/ldap/ldap.class.php');
				// $ldap = new Ldap();

				// $ldap_result = $ldap->add_user($loginID, $password, $realName, $email, $handPhone, '', 'Y', $default_group['member_group_name']);
				
                $success = 'true';
                $msg = '사용자 추가에 성공했습니다';
				
			break;
			case 'update' :
				if( is_null($loginID) || empty($realName) ){
					throw new Exception ('invalid request', 101 );
				}
				$member_id = $db->queryOne("SELECT MEMBER_ID FROM BC_MEMBER WHERE USER_ID = '$loginID'");
                if(empty($member_id)) throw new Exception('해당 사용자가 존재하지 않습니다.');
                
                $container = app()->getContainer();
                $contentService = new \Api\Services\ContentService($container);
                $userService = new \Api\Services\UserService($container);
                $bisService = new \Api\Services\BisCommonService($container);

                                //sso 연동
                $sessionConfig = config('session');
                $mode = $sessionConfig['driver'];        
                $userId = trim($loginID);
                $password = trim($password);                    
      
                $user = $userService->findByUserId($userId);        

        
                if ($mode == 'sso') {
                    $encUserId = $userService->encryptUserId($userId);        
                    $ssoClient = $container->get('sso_admin');                     
                    $ssoEmail = empty($email) ? '-' : $email;
                    $ssoHpNo = empty($handPhone) ? '-' : $handPhone;
                    if( !empty($password) ){
                            $passwordHash = $userService->encryptPassword($password);
                    }else{
                            $passwordHash = $user->password;
                            //파라미터에 없는경우 업데이트 제외
                            $user->password = null;
                    }        
                    $result = $ssoClient->updateUser($encUserId, $passwordHash, $realName, $ssoEmail, $ssoHpNo);                 
                }
                    
                if( !empty($password) ){
                    $passwordHash = $userService->encryptPassword($password);        
                    //cms 수정
                    $userService->updatePassword($userId, $password);

                    //bis 연동
                    if (config('bis')['user']) {
                        $r  = $bisService->changePassword($userId, $password);
                    }
                        
                    //od 동기화
                    if (config('od')['linkage']) {
                        $folderAuth = new \ProximaCustom\core\FolderAuthManager();
                        $folderAuth->changePasswordFromOD($userId, $password);
                    }
                }

                if (!empty($email)) {
                    $user->email = $email;
                }

                if (!empty($handPhone)) {
                    $user->phone = $handPhone;
                }
                if (!empty($realName)) {
                    $user->user_nm = $realName;
                }
                if (!empty($deptNm)) {
                    $user->dept_nm = $deptNm;
                }
                
                if (!empty($expiredDate)) {
                    $user->expired_date = $expiredDate;
                }
                $user->groups = null;
                $user = $userService->update($user->member_id, $user);
    
                //bis 연동
                if( config('bis')['user'] ){            
                    $r  = $bisService->updateUser( $user );
                }   
                
                $success = 'true';
                $msg = '사용자 정보 수정에 성공했습니다';
				// AD 연동 하는 부분 추가해야됨
				//$success = 'true';
				//$msg = '사용자 정보 수정에 성공했습니다';

				// require_once($_SERVER['DOCUMENT_ROOT'].'/store/ldap/ldap.class.php');
				// $ldap = new Ldap();

				// $user_group_nm = "";
				// $user_group_nm_arr = array();
				// $member_groups = $db->queryAll("
				// 	SELECT	G.MEMBER_GROUP_ID
				// 			, M.MEMBER_GROUP_NAME
				// 	FROM	BC_MEMBER M
				// 			, BC_MEMBER_GROUP G
				// 			, BC_MEMBER_GROUP_MEMBER MGM
				// 	WHERE	M.USER_ID = 'admin'
				// 	AND		M.MEMBER_ID = MGM.MEMBER_ID
				// 	AND		MGM.MEMBER_GROUP_ID = G.MEMBER_GROUP_ID
				// ");

				// if(!empty($member_groups)){
				// 	foreach($member_groups as $member_group){
				// 		array_push($user_group_nm_arr, $member_group['member_group_name']);
				// 	}
				// }
				// if(!empty($user_group_nm_arr)){
				// 	$user_group_nm = implode('!@#', $user_group_nm_arr);
				// }

				// $ldap_result = $ldap->modify_user($loginID, $realName, $email, $handPhone, '', 'Y', $user_group_nm, $user_group_nm);
				// if($ldap_result['success'] == true){
				// 	if(!empty($password)){
				// 		$ldap->modify_user_pwd($loginID, $password);
				// 	}

				// 	$success = 'true';
				// 	$msg = '사용자 정보 수정에 성공했습니다';
				// }
				// else{
				// 	$success = 'false';
				// 	$msg = $ldap_result['msg'];
				// }
			break;
			case 'del' :
				if( is_null($loginID) ){
					throw new Exception ('invalid request', 101 );
				}

				$member_id = $db->queryOne("SELECT MEMBER_ID FROM BC_MEMBER WHERE USER_ID = '$loginID'");

				if(empty($member_id)) throw new Exception('해당 사용자가 존재하지 않습니다.');

				// $r = $db->exec("
				// 		DELETE FROM BC_MEMBER_GROUP_MEMBER WHERE MEMBER_ID = '$member_id'
				// 	");

				// $r = $db->exec("
				// 		DELETE FROM BC_MEMBER WHERE MEMBER_ID = '$member_id'
				// 	");
				// // AD 연동 하는 부분 추가해야됨
				// //$success = 'true';
				// //$msg = '사용자 정보삭제에 성공했습니다';
				// require_once($_SERVER['DOCUMENT_ROOT'].'/store/ldap/ldap.class.php');
				// $ldap = new Ldap();

				// $ldap_result = $ldap->delete_user($loginID);
				// if($ldap_result['success'] == true){
				// 	$success = 'true';
				// 	$msg = '사용자 정보 삭제에 성공했습니다';
				// }
				// else{
				// 	$success = 'false';
				// 	$msg = $ldap_result['msg'];
				// }

			break;
			default :
				throw new Exception ('invalid action', 101);
			break;
		}

		return array(
				'success' => $success,
				'msg' => $msg
		);
	} catch(Exception $e) {
		$msg = $e->getMessage();
		$code = $e->getCode();
		$success = 'false';

		return array(
				'success' => $success,
				'msg' => $msg
		);
	}
}
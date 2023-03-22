<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
// user_id user_password

try {
	// 비밀번호 변경
	$cur_date		= date('YmdHis');
	$user_id		= $db->escape(trim($_REQUEST['user_id']));
	$user_password	= trim($_REQUEST['user_password']);
    $user_ori_password	= trim($_REQUEST['user_password']);


    $userService = new \Api\Services\UserService($app->getContainer()); 
    $bisService = new \Api\Services\BisCommonService($app->getContainer()); 
    $userCollection = $userService->updatePassword($user_id, $user_password);   
    if( !$userCollection ){
        throw new Exception('비밀번호가 변경에 실패하였습니다.');
    }
        //조디악 동기화
    if( config('zodiac')['linkage'] ){
        $r = $userService->syncUserZodiac($user_id);
    }

    if( config('session')['driver'] == 'sso' ){
        $r = $userService->syncUserSSO($user_id);
    }

    
    //bis 연동
    if (config('bis')['user']) {
        $r  = $bisService->changePassword($user_id, $user_password);
    }
        
    //od 동기화
    if (config('od')['linkage']) {
        $folderAuth = new \ProximaCustom\core\FolderAuthManager();
        $folderAuth->changePasswordFromOD($user_id, $user_password);
    }

	$msg = '변경되었습니다';
	echo json_encode(array(
		'success' => true,
		'msg' => $msg
	));
} catch (Exception $e) {
	echo json_encode(array(
		'success' => false,
		'msg'	=> $e->getMessage()
	));
}

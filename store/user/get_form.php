<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Zodiac.class.php');

if($_POST['action'] == 'del'){
    //보도정보에 사용자 삭제 정보 전달
    

   
	$update_user_id = $_SESSION['user']['user_id'];
	$userId = $db->queryOne("
						SELECT	USER_ID
						FROM	BC_MEMBER
						WHERE	MEMBER_ID = ".$_POST['member_id']
                    );
    $userService = new \Api\Services\UserService($app->getContainer());

    $userService->delete($userId);
    
    //sso 싱크
    $userService->syncUserSSO($userId);

    //조디악 동기화
    if( config('zodiac')['linkage'] ){
        $userService->syncUserZodiac($userId);
    }

	die(json_encode(array(
		'success' => true
	)));
}

$groups = $db->queryAll("select * from bc_member_group order by member_group_name");
switch($_POST['action']){
	case 'add':
		//>>$window_title = "'사용자 추가'";
		//>>$button_text = "'추가'";
		//>>$wait_msg = "'사용자 추가 중입니다...'";
		$window_title = "'"._text('MN00126')."'";
		$button_text = _text('MN00033');
		$wait_msg = "'"._text('MSG00152')."'";
        $user['lang'] = 'ko';
        
        echo json_encode(array(
            'success'       => true,
            'window_title'  => $window_title,
            'button_text'   => $button_text,
            'wait_msg'      => $wait_msg,
            'user'          => $user,
            'groups'        => $groups,
            'action'        => $_POST['action']
        ));
	break;

	case 'edit':
		//>>$window_title = "'사용자 수정'";MN00193
		//>>$button_text = "'변경'";
		//>>$wait_msg = "'변경 중입니다...'";
		$window_title = "'"._text('MN00193')."'";
		$button_text = _text('MN00043');
		$wait_msg = "'"._text('MSG00153')."'";
		$user = $db->queryRow("
											SELECT a.*, b.top_menu_mode, b.action_icon_slide_yn
											FROM bc_member a
											LEFT OUTER JOIN bc_member_option b ON a.member_id = b.member_id
											WHERE a.member_id='{$_POST['member_id']}'
										");
		if(empty($user)){
			//>>die("'" . $_POST['user_id'] . "'을 찾을수 없습니다");MSG00154
			die("'" . $_POST['user_id'] . "'을 찾을수 없습니다");
		}
		//2016-10-26 전화번호 입력방식 변경
		$phone = $user['phone'];
		if( preg_match('/-/', $phone) ){
			$phone0 = explode('-', $phone);
			$phone1 = $phone0[0];
			$phone2 = $phone0[1];
			$phone3 = $phone0[2];
		}else{
			if( $phone != '' ){
				if( strlen($phone) <= 10  ){
					$phone1 = substr($phone, 0, 3);
					$phone2 = substr($phone, 3, 3);
					$phone3 = substr($phone, 6, 4);
				}else{
					$phone1 = substr($phone, 0, 3);
					$phone2 = substr($phone, 3, 4);
					$phone3 = substr($phone, 7, 4);
				}
			}
		}

		$in_groups = $db->queryall("select g.member_group_name from bc_member m, bc_member_group g, bc_member_group_member gm " .
										"where m.member_id={$_POST['member_id']} " .
										"and m.member_id=gm.member_id " .
										"and gm.member_group_id=g.member_group_id");
		$group_member = array();
		foreach($in_groups as $in_group){
			array_push($group_member, $in_group['member_group_name']);
        }
        
        echo json_encode(array(
            'success'       => true,
            'window_title'  => $window_title,
            'button_text'   => $button_text,
            'wait_msg'      => $wait_msg,
            'user'          => $user,
            'groups'        => $groups,
            'groups_member' => $group_member,
            'action'        => $_POST['action']
        ));
	break;
        

    }
?>
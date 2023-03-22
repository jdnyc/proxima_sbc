<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');
require_once(TEMP_ROOT.'/lib/config.php');
require_once(TEMP_ROOT.'/migration/mig_functions.php');//마이그레이션용 함수


$GLOBALS['flag'] = '1';

$content_type_id = '506';
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
	$old_nps = new Database('ebsnps', 'ebsnps', '10.10.10.171/ebsmamdb');
	

	$query = "select c.*, p.path from CATEGORIES c, path_mapping p where c.id=p.category_id and c.PARENT_ID=0 order by c.ID ";

	$before_categories = $old_nps->queryAll($query);
	//as is 카테고리 목록
	foreach($before_categories as $be_category)
	{
		$category_id = $be_category['id'];

		$is_exist_category = $db->queryRow("select * from bc_category where category_id='$category_id' ");

		$cp_mapping_info = $old_nps->queryAll("select * from CATEGORY_PROGCD_MAPPING where category_id='$category_id' " );

		if( empty($is_exist_category ) )
		{//신 NPS에 카테고리가 없을떄
			//카테고리 생성

			insertCategory($be_category);

			$member_group_id = insertPath_mapping($be_category);

			
			//멤버 그룹 생성
			//그룹 권한 주기
			//유저 매핑 확인후 그룹 매핑
			//카테고리 프로그램코드 매핑
			
		}
		else
		{//있다면 
			//패스정보 확인
			$is_path = $db->queryRow("select * from path_mapping where category_id='$category_id' ");

			if( empty($is_path) )
			{
				//패스 추가
				$member_group_id = insertPath_mapping($be_category);
			}
			else
			{
				//그룹 확인
				if( empty($is_path['member_group_id']) )
				{
					//그룹 추가
					$member_group_id = insertMember_group($be_category);
				}
				else
				{
					$member_group_id = $is_path['member_group_id'];
				}
			}
			

		}

		if(empty($member_group_id))
		{
			throw new Exception('그룹정보 오류');
		}

		$userList = $old_nps->queryAll("select * from user_mapping where category_id='$category_id' " );

		udateUserMapping($category_id,$member_group_id, $userList);

		

		///프로그램코드 매핑 업데이트
		updateCategoryProgcd($cp_mapping_info);
	}

	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);



	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;	
}

function udateUserMapping($category_id,$member_group_id, $userList)
{	
	global $db;
	global $flag;

	foreach($userList as $user)
	{	
		$user_id = $user['user_id'];
		$member_id = $db->queryOne("select member_id from bc_member where user_id='$user_id'");
		if( !empty($member_id) )
		{
			$um = $db->queryOne("select category_id from user_mapping where category_id='$category_id' and user_id='$user_id' ");
			if(empty($um) )
			{
				$exec_q1 = "insert into user_mapping ( CATEGORY_ID, USER_ID ) values ( '$category_id' ,  '$user_id' )";
				
				$r = ExecQuery($flag , $exec_q1);

				$exec_q2 = "insert into bc_member_group_member ( member_id, member_group_id ) values('$member_id', '$member_group_id')";
				$r = ExecQuery($flag , $exec_q2);
			}			
		}
	}
}

function updateCategoryProgcd($cp_mapping_info)
{
	global $db;
	global $flag;
	foreach($cp_mapping_info as $cp_mapping)
	{
		$category_id = $cp_mapping['category_id'];
		$medcd = $cp_mapping['medcd'];
		$progparntcd = $cp_mapping['progparntcd'];
		$prognm = $db->escape($cp_mapping['prognm']);
		$brodstymd = $cp_mapping['brodstymd'];
		$brodendymd = $cp_mapping['brodendymd'];
		$formbaseymd = $cp_mapping['formbaseymd'];
		$progcd = $cp_mapping['progcd'];

		$check = $db->queryOne("select progcd from CATEGORY_PROGCD_MAPPING where category_id='$category_id' and medcd='$medcd' and progcd='$progcd' and formbaseymd='$formbaseymd' ");
		if(empty($check) )
		{		
			$exec_q = "insert into CATEGORY_PROGCD_MAPPING (CATEGORY_ID,MEDCD,PROGPARNTCD,PROGNM,BRODSTYMD,BRODENDYMD,FORMBASEYMD,PROGCD) values ('$category_id','$medcd','$progparntcd','$prognm','$brodstymd','$brodendymd','$formbaseymd','$progcd' ) ";
			$r = ExecQuery($flag , $exec_q);
		}
	}
}

function insertCategory($be_category)
{
	global $db;
	global $flag;

	$category_id = $be_category['id'];
	$parent_id = $be_category['parent_id'];
	$category_title = $db->escape($be_category['title']);
	$code = $be_category['code'];
	$show_order = $be_category['sort'];
	$no_children = $be_category['is_leaf'];

	$exec_q = "insert into bc_category (CATEGORY_ID,PARENT_ID,CATEGORY_TITLE,CODE,SHOW_ORDER,NO_CHILDREN) values ('$category_id', '$parent_id','$category_title','$code','$show_order','$no_children') ";
	$r = ExecQuery($flag , $exec_q);
	
	return $category_id;
}

function insertPath_mapping($be_category)
{
	global $db;
	global $flag;

	$category_id = $be_category['id'];

	$path_info = $be_category['path'];
	
	$is_exist_path = $db->queryRow("select * from path_mapping where category_id='$category_id' ");
	
	if( empty($is_exist_path) )
	{//패스정보가 업을때

		$exec_q = "insert into path_mapping (category_id, path, member_group_id) values ($category_id,'$path_info', '$member_group_id')";
		$r = ExecQuery($flag , $exec_q);
		$member_group_id = insertMember_group($be_category);
	}
	else
	{//패스정보는 있음
		//그룹이 매핑되어 있는지 확인
		if( empty( $is_exist_path['member_group_id'] ) )
		{
			//그룹정보 신규 추가
			$member_group_id = insertMember_group($be_category);

		}
		else
		{
			$member_group_id = $is_exist_path['member_group_id'];
		}
	}

	return $member_group_id;
}

function insertMember_group($be_category)
{
	//멤버그룹 신규 생성

	global $db;
	global $flag;

	global $CG_LIST;
	$category_id = $be_category['id'];

	$category_name = $be_category['title'];
		//그룹 추가
	$created_time = date("YmdHis");
	$allow_login = 'Y';
	$member_group_id = getSequence('SEQ_MEMBER_GROUP_ID');
	$group_name = $category_name.'(제작그룹)';
	$description = $group_name.' 입니다.';

	$insert_q = "insert into BC_MEMBER_GROUP (MEMBER_GROUP_ID, MEMBER_GROUP_NAME, IS_DEFAULT, IS_ADMIN, DESCRIPTION, CREATED_DATE, ALLOW_LOGIN ) values ('$member_group_id', '$group_name' ,'N','N','$description', '$created_time', '$allow_login' )";
	
	$r = ExecQuery($flag , $insert_q);
	


	$path_info_update = "update path_mapping set MEMBER_GROUP_ID='$member_group_id' where category_id='$category_id' ";
	$r = ExecQuery($flag , $path_info_update);
	
	$ud_content_list = $db->queryAll("select ud_content_id from bc_ud_content order by show_order");

	foreach($ud_content_list as $ud_content)
	{
		$ud_content_id = $ud_content['ud_content_id'];

		if( in_array($ud_content_id , $CG_LIST) ) continue;	//CG쪽은 패스

		$category_group_grant = '2';//카테고리 권한 값 //읽기 수정 이동
		$group_grant = '15'; //콘텐츠 권한 값 // 읽기 수정 삭제 다운로드

		$category_full_path = '/0/'.$category_id;

		$is_exist_category_grant = $db->queryOne("select count(*) from BC_CATEGORY_GRANT where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and category_id='$category_id'");

		if($is_exist_category_grant)
		{
			//그룹 카테고리 권한 추가
			$update_q = "update BC_CATEGORY_GRANT set GROUP_GRANT='$category_group_grant' , CATEGORY_FULL_PATH='$category_full_path' where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and category_id='$category_id' ";
			$r = ExecQuery($flag , $update_q);
			
		}
		else
		{
			//그룹 카테고리 권한 추가
			$insert_q = "insert into BC_CATEGORY_GRANT ( UD_CONTENT_ID , MEMBER_GROUP_ID, CATEGORY_ID, GROUP_GRANT, CATEGORY_FULL_PATH) values ('$ud_content_id', '$member_group_id' ,'$category_id','$category_group_grant','$category_full_path' )";
			$r = ExecQuery($flag , $insert_q);
			
		}

		$is_exist_grant = $db->queryOne("select count(*) from BC_GRANT where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and GRANT_TYPE='content_grant' ");

		if($is_exist_grant)
		{
								//그룹 권한 추가
			$update_q = "update BC_GRANT set GROUP_GRANT='$group_grant' where member_group_id='$member_group_id' and ud_content_id='$ud_content_id' and GRANT_TYPE='content_grant' )";
			$r = ExecQuery($flag , $update_q);
		
		}
		else
		{
			//그룹 권한 추가
			$insert_q = "insert into BC_GRANT (UD_CONTENT_ID , MEMBER_GROUP_ID, GRANT_TYPE, GROUP_GRANT ) values ('$ud_content_id', '$member_group_id' ,'content_grant','$group_grant' )";
			$r = ExecQuery($flag , $insert_q);
			
		}
	}

	return $member_group_id;

}

?>
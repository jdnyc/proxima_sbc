<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

/*
 TAPE Libray 관리 리스트 가져오기
 전체 보기
 승인대기 .. - R STORAGE 만 등록된 데이터들
 완료     .. - TAPE으로 등록된 자료


*/

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date =		$_POST['start_date'];
$e_date =		$_POST['end_date'];

$delete_combo = $_POST['delete_combo'];
$mtrl_id = $_POST['mtrl_id'];
$search_feild  = $_POST['search_feild'];

$genre_category = trim($_POST['genre_category']);
if(empty($genre_category)
	|| $genre_category == '0'
	|| $genre_category == '0/' )
{
	$genre_category_query = '';
}
else
{
	$category_id = array_pop( explode('>', $genre_category) );
	if( is_numeric($category_id) )
	{
		$genre_category_query = " and c.category_full_path like '%/".$category_id."%' ";
	}
	else
	{
		$genre_category_query = '';
	}
}

if(empty($start)){
    $start = 0;
}
if(empty($limit)){
    $limit = 100;
}

$mappingMediaType = array(
	original => "<font color=green>".'On-line'."</font>",
	nearline => "<font color=green>".'Near-line'."</font>"
);

$mappingDeleteFlag = array(
	//NULL =>	"<font color=gray>".'니어라인에 존재하지 않음'."<font color=red>".'(삭제대상 아님)',
	ARCHIVE_QUEUE		=>	"<font color=gray><b>".'승인대기'."</font>",
	ARCHIVE_ERROR		=>	"<font color=orange><b>".'실패'."</font>",
	ARCHIVE_COMPLETE	=>	"<font color=blue><b>".'성공'."</font>",
	ARCHIVE_ACCEPT		=>	"<font color=green><b>".'TAPE화 승인'."</font>",
	ARCHIVE_DELETE		=>	"<font color=red><b>".'삭제'."</font>",
	ARCHIVE_REQUEST_DELETE_ACCEPT =>"<font color=green><b>".'삭제승인'."</font>",
	ARCHIVE_REQUEST_DELETE => "<font color=red><b>".'삭제요청'."</font>",
	150          		=>	"<font color=red><b>".'삭제'."</font>",
	'delete'			=>	"<font color=red><b>".'삭제'."</font>"
);

$mappingMetaTable = array(
	UD_NDS =>	'NDS 아카이브',
	UD_PDS =>	'PDS 아카이브'
);

//현재 유저의 content 테이블 불러오기
try
{
	$action = $_POST['action'];
	if($action == "전체보기")
	{
		$action = 'all';
	}
	$ud_content = $_POST['ud_content'];
	if($ud_content == "전체")
	{
		$ud_content = 'all';
	}
	
	switch($action){
		case 'all':
			$action_query = "";
			break;
		case '':
			$action_query = " and c.archive_status is null ";
			break;
		case '150':
			$action_query = " and ( c.archive_status ='".$action."' or c.archive_status ='delete' ) ";
			break;
		default :
			//그 외 action 들
			$action_query = " and c.archive_status ='".$action."' ";
			break;
	}

	switch($ud_content){
		case 'all':
			$ud_content_query = "";
			break;
		default :
			//그 외 ud_content 들
			$ud_content_query = " and c.ud_content_id ='".$ud_content."' ";
			break;
	}

	if( !empty($mtrl_id) )
	{
		$mtrl_id = $db->escape($mtrl_id);

		switch($search_feild)
		{
			case '2':
				$mtrl_id_query = " and (cdr.REQUEST_USER_ID like '%".$mtrl_id."%' or cdr.REQUEST_USER_NM like '%".$mtrl_id."%') ";
			break;

			case '3':
				$mtrl_id_query = " and (cdr.AUTH_USER_ID like '%".$mtrl_id."%' or cdr.AUTH_USER_NM like '%".$mtrl_id."%') ";
			break;

			default :
				$mtrl_id_query = " and (cci.mtrl_id like '%".$mtrl_id."%' or c.title like '%".$mtrl_id."%') ";
			break;
		}		
	}
	
	$date_query = "and cci.arc_time >= '".$s_date."' and cci.arc_time <= '".$e_date."'";

	$db->setLimit($limit,$start);
	
//	$query = "select c.*, cci.arc_time, cci.asset_id, cci.mtrl_id, ce.arc_period, ce.ori_del_method, m.path
//				from bc_content c, content_code_info cci, bc_category_env ce, bc_media m
//			   where c.content_id=cci.content_id
//				 and c.content_id=m.content_id
//				 and c.category_id=ce.category_id
//				 and m.media_type='original'
//				 and ce.arc_method in ('A', 'M')
//				 and c.content_id is not null
//				 and c.is_deleted != 'Y'
//			 ".$genre_category_query.$action_query.$date_query.$ud_content_query.$mtrl_id_query;
/*
	$query = "select c.*, cci.arc_time, cci.asset_id, cci.mtrl_id, cci.ud_system, ce.arc_period, ce.ori_del_method, m.path
				from bc_content c, content_code_info cci, bc_category_env ce, bc_media m
			   where c.content_id=cci.content_id
				 and c.category_id=ce.category_id
				 and c.content_id=m.content_id
				 and ce.arc_method in ('A', 'M')
				 and c.content_id is not null
				 and c.status='".CONTENT_STATUS_REG_READY."'
				 and c.is_deleted != 'Y'
				 and m.media_type='original'
			 ".$genre_category_query.$action_query.$date_query.$ud_content_query.$mtrl_id_query;
*/
	$query = "
				SELECT     c.CONTENT_ID         as CONTENT_ID
						  ,c.UD_CONTENT_ID      as UD_CONTENT_ID
						  ,c.TITLE              as TITLE
						  ,c.ARCHIVE_STATUS     as ARCHIVE_STATUS
						  ,c.ARCHIVE_DATE       as ARCHIVE_DATE
						  ,c.CREATED_DATE       as CREATED_DATE
						  ,cci.MTRL_ID          as MTRL_ID
						  ,cci.ARC_TIME         as ARC_TIME
						  ,env.ORI_DEL_METHOD   as ORI_DEL_METHOD  
						  ,env.ORI_DEL_PERIOD   as ORI_DEL_PERIOD 
						  ,ori_bm.FLAG          as ORI_BM_FLAG
						  ,ori_bm.PATH          as ORI_PATH
						 -- ,archive_bm.FLAG      as ARCHIVE_BM_FLAG
						 -- ,archive_bm.PATH      as ARCHIVE_PATH
						  ,cdr.REQUEST_COMNT    as REQUEST_COMNT
						  ,cdr.REQUEST_USER_ID  as REQUEST_USER_ID
						  ,cdr.REQUEST_DATE     as REQUEST_DATE
						  ,cdr.REQUEST_USER_NM  as REQUEST_USER_NM
						  ,cdr.AUTH_COMNT       as AUTH_COMNT
						  ,cdr.AUTH_USER_ID     as AUTH_USER_ID
						  ,cdr.AUTH_DATE        as AUTH_DATE
						  ,cdr.AUTH_USER_NM     as AUTH_USER_NM
						  ,cdr.REQUEST_ID       as REQUEST_ID 						
						  ,NVL2(cci.UD_SYSTEM,cci.UD_SYSTEM,cci.SRC_DEVICE_ID) as UD_SYSTEM

				FROM       BC_CONTENT c
						  ,CONTENT_CODE_INFO cci
						  ,BC_CATEGORY_ENV env
						  ,(  
							  SELECT  * 
							  FROM    BC_MEDIA 
							  WHERE   MEDIA_TYPE = 'original' 
							) ori_bm
						 -- ,(  
						--	  SELECT  * 
						--	  FROM    BC_MEDIA 
						--	  WHERE   MEDIA_TYPE = 'archive'
						--	) archive_bm
						   ,(
							   SELECT  MAX(MEDIA_ID) as media_id
									  ,MAX(CONTENT_ID) as content_id    
									  ,MAX(REQUEST_COMNT) as REQUEST_COMNT
									  ,MAX(REQUEST_USER_ID) as REQUEST_USER_ID
									  ,MAX((SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = REQUEST_USER_ID)) as REQUEST_USER_NM
									  ,MAX(REQUEST_DATE) as REQUEST_DATE
									  ,MAX(AUTH_COMNT) as AUTH_COMNT
									  ,MAX(AUTH_USER_ID) as AUTH_USER_ID
									  ,MAX((SELECT USER_NM FROM BC_MEMBER WHERE USER_ID = AUTH_USER_ID)) as AUTH_USER_NM
									  ,MAX(AUTH_DATE) as AUTH_DATE
									  ,MAX(REQUEST_ID) as REQUEST_ID
							   FROM   CHA_DELETE_REQUEST 
							   WHERE  REQUEST_TYPE = 'T' 
							   GROUP BY MEDIA_ID

							) cdr
							
				WHERE         c.CONTENT_ID  = cci.CONTENT_ID      
						  AND c.UD_CONTENT_ID in(454,455)
						  AND c.STATUS      = 2
						  AND c.IS_DELETED  = 'N'
						  AND c.CATEGORY_ID = env.CATEGORY_ID(+)  
						  AND c.CONTENT_ID  = ori_bm.CONTENT_ID --file original 은 무조건 있다는 아니다.. NPS에서 ARCHIVE 요청시에는 MEDIA TABLE 에 ARCHIVE 만 있을 수도 있음
						 -- AND c.CONTENT_ID  = archive_bm.CONTENT_ID(+)
						  AND c.CONTENT_ID  = cdr.CONTENT_ID(+)".$genre_category_query.$action_query.$date_query.$ud_content_query.$mtrl_id_query;

	$results = $db->queryAll($query." order by cci.arc_time, cci.mtrl_id");

	$total_query = "select count(*) from (".$query.") t1";
	$total = $db->queryOne($total_query);

	$data = array();
	$cur_date = date('YmdHis');
	foreach($results as $i => $result){
		$exp_period = $result['arc_period'];

		$exp_date = '지정안됨';

		if($result['ori_del_method'] == 'M')
		{
			$exp_date = '수동';
		}
		else if($result['ori_del_method'] == 'A')
		{
			$exp_date = date('Y-m-d', strtotime($result['created_date'].'+ '.$result['ori_del_period'].'days'));
		}

		if($result['request_user_nm'])
		{
			$request_user_info = $result['request_user_nm']."(".$result['request_user_id'].")";
		}

		if($result['auth_user_nm'])
		{
			$auth_user_info = $result['auth_user_nm']."(".$result['auth_user_id'].")";
		}


//		//만료일이 지난 자료 중 아직 아카이브 상태값이 null인 경우는 승인대기로 올라감
//		if( $exp_date < $cur_date
//		 && trim($result['archive_status']) == '' )
//		{
//			$flag_nm = $mappingDeleteFlag[ trim($result['archive_status']) ]; 
//			$flag = trim($result['archive_status']);
//		}
//		//만료일이 지나지 않고 아카이브 상태값이 null 인 경우. 일반 자료
//		else if( trim($result['archive_status']) == '' )
//		{
//			$flag_nm = '';
//			$flag = 'common';
//		}
//		//그 외에는 상태값에 따라 표시
//		else
//		{
//			$flag_nm = $mappingDeleteFlag[ trim($result['archive_status']) ];
//			$flag = trim($result['archive_status']);
//		}

//수동으로 Tape아카이브 시키는 페이지이므로 만료일 상관없이 상태는 그대로 보여주기
		$flag_nm = $mappingDeleteFlag[ trim($result['archive_status']) ];
		$flag = trim($result['archive_status']);
		
		if($result['archive_status'] == '')
		{
			$result['path'] = $result['ori_path'];
		}
		else 
		{
			 $result['path'] = $result['archive_path'];
		}
		
		$result['path'] = $result['ori_path'];

		$conv_file_name = array_pop( explode('/', $result['path']) );

	
		if(!is_numeric($result['ud_system']))
		{	
			$device_id = $result['ud_system'];		

			if($device_id == 'NVIDEO' || $device_id == 'TAPENVIDEO' ) {				
				$ud_system = UD_SYS_NDS_GH;
			}else if($device_id == 'PVIDEO' || $device_id == 'TAPEPVIDEO' ) {				
				$ud_system = UD_SYS_PDS_GH;
			}else if($device_id == 'ARCHIVE01') {
				$ud_system = UD_SYS_R_GH;
			}
			
			$results[$i]['ud_system'] = $ud_system;
		}

		$results[$i]['flag'] = $flag;
		$results[$i]['flag_nm'] = $flag_nm;
		$results[$i]['contentType'] = $mappingMetaTable[$result['ud_content_id']];
		$results[$i]['path'] = $conv_file_name;
		$results[$i]['exp_date'] = $exp_date;

		$results[$i]['request_user_info'] = $request_user_info;
		$results[$i]['auth_user_info'] = $auth_user_info;
		$results[$i]['request_date'] =$result['request_date'];
		$results[$i]['auth_date'] = $result['auth_date'];
		$results[$i]['request_comment'] = $result['request_comnt'];
		$results[$i]['auth_comment'] = $result['auth_comnt'];
		$results[$i]['request_id'] = $result['request_id'];
		
	}
	$data = array(
		'success'	=> true,
		'data'		=> $results,
		'total_list'		=> $total,
		'query' => $query,
		'temp' => $tt
	);

	echo json_encode($data);	

}
catch (Exception $e)
{
	$data = array(
		'success'	=> false,
		'msg'		=> $e->getMessage(),
		'query' => $db->last_query
	);

	echo json_encode($data);
}
?>

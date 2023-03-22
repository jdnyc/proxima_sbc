<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$limit =		$_POST['limit'];
$start =		$_POST['start'];
$s_date =		$_POST['start_date'];
$e_date =		$_POST['end_date'];
$arc_s_date =		$_POST['arc_start_date'];
$arc_e_date =		$_POST['arc_end_date'];

$delete_combo = $_POST['delete_combo'];
$mtrl_id = $_POST['mtrl_id'];
$search_feild  = $_POST['search_feild'];


if(empty($limit)){
    $limit = 100;
}

$mappingMediaType = array(
	original => "<font color=green>".'On-line'."</font>",
	nearline => "<font color=green>".'Near-line'."</font>"
);

$mappingDeleteFlag = array(
	DEL_MEDIA_COMPLETE_FLAG		=>	"<font color=blue><b>".'삭제 완료'."</font>",
	DEL_MEDIA_ERROR_FLAG		=>	"<font color=red><b>".'삭제 실패'."</font>",
	DEL_MEDIA_REQUEST_FLAG		=>  "<font color=orange><b>".'삭제 요청'."</font>",
	DEL_MEDIA_ADMIN_APPROVE_FLAG => "<font color=green><b>".'삭제 승인'."</font>"
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
			$action_query = " ";
			break;
		case '': //삭제대기
			$action_query = " AND ( m.ori_flag is null AND m.archive_flag is null AND c.archive_status = 'complete' ) ";
			break;
		case 'DMC':
			$action_query = " AND m.ori_flag ='".$action."' AND m.archive_flag is null ";
			break;
		default :
			//그 외 action 들
			$action_query = " AND m.ori_flag ='".$action."' ";
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
	
	$date_query = "and m.created_date >= '".$s_date."' and m.created_date <= '".$e_date."'";
	$arc_date_query = "and cci.arc_time >= '".$arc_s_date."' and cci.arc_time <= '".$arc_e_date."'";

	$db->setLimit($limit,$start);
	/*
		select m.*, ori_m.flag as ori_flag, c.title, c.ud_content_id, cci.mtrl_id, cci.asset_id, cci.arc_time, ce.ori_del_period, ce.ori_del_method
		from bc_content c, bc_media m, content_code_info cci, bc_category_env ce,
          (SELECt e.flag, x.content_id
           FROM  
                (SELECT * FROM BC_MEDIA WHERE MEDIA_TYPE = 'original') e,                      
                (SELECT * FROM BC_MEDIA WHERE MEDIA_TYPE = 'archive') x
           WHERE e.content_id = x.content_id) ori_m
		where c.content_id=m.content_id
		  and c.category_id=ce.category_id
		  and c.content_id=cci.content_id
		  and c.content_id=ori_m.content_id
		  and ce.ori_del_method in ('A', 'M')
		 
	*/
	/*
	$query = "select m.*, ori_m.flag as ori_flag, c.title, c.ud_content_id, cci.mtrl_id, cci.asset_id, cci.arc_time, ce.ori_del_period, ce.ori_del_method
		from bc_content c, bc_media m, content_code_info cci, bc_category_env ce,
			(select * from bc_media where media_type='original') ori_m
		where c.content_id=m.content_id
		  and c.category_id=ce.category_id
		  and c.content_id=cci.content_id
		  and c.content_id=ori_m.content_id
		  and ce.ori_del_method in ('A', 'M')
		  --and c.is_deleted != 'Y'
		  and m.media_type='archive'
		 ".$action_query.$date_query.$arc_date_query.$ud_content_query.$mtrl_id_query;

		 */
	$query = "
				SELECT       m.ori_flag as ori_flag
							,m.archive_flag as archive_flag
							,m.CREATED_DATE 
							,c.TITLE
							,c.UD_CONTENT_ID
							,c.ARCHIVE_STATUS
							,cci.mtrl_id
							,cci.ASSET_ID
							,cci.ARC_TIME
							,ce.ORI_DEL_PERIOD
							,ce.ORI_DEL_METHOD
						    ,cdr.REQUEST_COMNT
						    ,cdr.REQUEST_USER_ID
						    ,cdr.REQUEST_DATE
						    ,cdr.REQUEST_USER_NM
						    ,cdr.AUTH_COMNT
					  	    ,cdr.AUTH_USER_ID
						    ,cdr.AUTH_DATE
						    ,cdr.AUTH_USER_NM
							,cdr.REQUEST_ID
							,c.CONTENT_ID							

				FROM		 BC_CONTENT c          
							,CONTENT_CODE_INFO cci
							,BC_CATEGORY_ENV ce
							,(

							   SELECt  decode(e.status, 1,'DMC',0,e.FLAG) as ori_flag
									  ,decode(x.status, 1,'DMC',0,x.FLAG) as archive_flag									
									  ,x.CONTENT_ID
									  ,x.CREATED_DATE
							   FROM  
									  (SELECT * FROM BC_MEDIA WHERE MEDIA_TYPE = 'original') e,                      
									  (SELECT * FROM BC_MEDIA WHERE MEDIA_TYPE = 'archive') x
							   WHERE  e.content_id = x.content_id

							 ) m
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

								 WHERE  REQUEST_TYPE = 'R'    
								 
								 GROUP BY MEDIA_ID

							   ) cdr

				WHERE           c.CONTENT_ID  = m.CONTENT_ID
							AND c.CATEGORY_ID = ce.CATEGORY_ID
							AND c.CONTENT_ID  = cci.CONTENT_ID
							AND c.CONTENT_ID  = m.CONTENT_ID
							--AND ce.ORI_DEL_METHOD in ('A','M')
                            AND c.CONTENT_ID  = cdr.CONTENT_ID(+)".$action_query.$date_query.$arc_date_query.$ud_content_query.$mtrl_id_query;

	//Tape완료일자로 정렬
	//$results = $db->queryAll($query." order by m.created_date");
	//승인일자로 정렬
	//print_r($query);
	$results = $db->queryAll($query." order by cci.arc_time");
	

	$total_query = "select count(*) from (".$query.") t1";
	$total = $db->queryOne($total_query);

	$data = array();
	$cur_date = date('YmdHis');
	foreach($results as $result){	
		//Archive스토리지 삭제 정보는 media_type='original'로 검색해야 함 = ori_flag값	
		
		
		if($result['ori_flag'] == '' &&  $result['archive_flag'] == '' && $result['archive_status'] == 'complete')
		{
			$flag_nm = "<font color=gray><b>".'삭제 대기'."</font>";
		}
		else if($result['ori_flag'] == DEL_MEDIA_COMPLETE_FLAG && $result['archive_flag'] == '' && $result['archive_status'] == 'complete')
		{
			$flag_nm = $mappingDeleteFlag[DEL_MEDIA_COMPLETE_FLAG];
		}
		else if($result['archive_status'] == 'complete' && $result['ori_flag'] == DEL_MEDIA_COMPLETE_FLAG)
		{
			$flag_nm = "<font color=blue><b>".'삭제 완료'."</font>";
		}
		else 
		{
			$flag_nm = $mappingDeleteFlag[trim($result['ori_flag'])];
		}

	

		$del_exp_date = '지정안됨';

		if($result['ori_del_method'] == 'M')
		{
			$del_exp_date = '수동';
		}
		else if($result['ori_del_method'] == 'A')
		{
			$del_exp_date = date('Y-m-d', strtotime($result['created_date'].'+ '.$result['ori_del_period'].'days'));
		}

		if($result['request_user_nm'])
		{
			$request_user_info = $result['request_user_nm']."(".$result['request_user_id'].")";
		}

		if($result['auth_user_nm'])
		{
			$auth_user_info = $result['auth_user_nm']."(".$result['auth_user_id'].")";
		}
		
		array_push($data,array(
			'flag' =>			trim($result['ori_flag']),
			'flag_nm' =>		$flag_nm,
			'content_id'=>		$result['content_id'],
			'title'	=>			$result['title'],
			'ud_content_id'=>	$result['ud_content_id'],
			'contentType'=>		$mappingMetaTable[$result['ud_content_id']],
			'mtrl_id'=>			$result['mtrl_id'],
			'asset_id'=>		$result['asset_id'],
			'created_date'=>	$result['created_date'],
			'arc_time'=>		$result['arc_time'],
			'del_exp_date'=>	$del_exp_date,
			'request_user_info'=> $request_user_info,
			'auth_user_info'=>$auth_user_info,
			'request_date'=>$result['request_date'],
			'auth_date'=>$result['auth_date'],
			'request_comment'=>$result['request_comnt'],
			'auth_comment'=>$result['auth_comnt'],
			'request_id' =>$result['request_id']
		));
	}

	$arr_info_msg = getStoragePolicyInfo();
	$info = $arr_info_msg['info1_2'];
	$info2 = $arr_info_msg['info2_2'];

	$data = array(
		'success'	=> true,
		'data'		=> $data,
		'total_list'		=> $total,
		'query' => $query,
		'temp' => $tt,
		'info' => $info,
		'info2' => $info2
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

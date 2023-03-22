<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
//require_once($_SERVER['DOCUMENT_ROOT'].'/ebs_info.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/mssql_connection.php');
$user_id = $_SESSION['user']['user_id'];
$category_id = $_POST['category_id'];
try
{
		//print_r($_POST);
		$search_field = $_POST['search_field'];
		$search_value = $_POST['search_value'];
		$limit = $_POST['limit'];
		$start = $_POST['start'];
		if(empty($limit)){
			$limit = 200;
		}

		if( !empty($_POST['brodendymd']) )
		{
			$brodstymd = $_POST['brodstymd'];
			$brodendymd = $_POST['brodendymd'];

			$where = " where t.brodymd between $brodstymd and $brodendymd";

		}
		else
		{
			$where = "";
		}

		if(true)
		{

			if( is_null($category_id) )
			{
				$uminfo = $db->queryRow("select * from user_mapping where user_id='$user_id' order by category_id");

				if(empty($uminfo)) throw new Exception('매핑정보가 없습니다.');

				$category_id = $uminfo['category_id'];
			}

			$whereinfo = $db->queryAll("select * from CATEGORY_PROGCD_MAPPING where category_id='$category_id'");

			$medcd = $whereinfo['medcd'];
			$formbaseymd = $whereinfo['formbaseymd'];
			$progcd = $whereinfo['progcd'];
			$prognm = $whereinfo['prognm'];

			$query_array = array();

			if( !empty($whereinfo) )
			{

				foreach($whereinfo as $info)
				{
					$category_id = $info['category_id'];
					$medcd = $info['medcd'];
					$progparntcd = $info['progparntcd'];
					$progcd = $info['progcd'];
					$prognm = $info['prognm'];
					$formbaseymd = $info['formbaseymd'];
					$brodstymd = $info['brodstymd'];
					$brodendymd = $info['brodendymd'];

					$forquery = " (
					select
						tm2.*,
						tb1.korname,
						(select kordepnm from TBPAO01 where depcd=tb1.depcd ) dept_nm
					from
						tbbf002 tf2,
						tbbma02 tm2,
						tbpae01 tb1
					where
						tm2.pdempno=tb1.empno
					and tm2.medcd=tf2.medcd
					and tf2.progcd=tm2.progcd
					and tf2.formbaseymd=tm2.formbaseymd
					and tf2.brodgu='001'
					and tf2.medcd='$medcd'
					and tf2.formbaseymd='$formbaseymd'
					and tf2.progcd='$progcd') ";


					array_push($query_array , $forquery );
				}

				$query = join(' union all ', $query_array);
				$query = "select * from ( $query  ) t";
				$query .= $where;
				$order = " order by t.brodymd ,t.subprogcd  desc";

				$total = $db_ms->queryOne("select count(*) from ( $query ) cnt");
				$db_ms->setLimit($limit,$start);
				$res = $db_ms->queryAll($query.$order);

				foreach($res as $key => $val )
				{
					$res[$key]['comb_cd'] = $val['medcd'].'-'.$val['progcd'].'-'.$val['formbaseymd'].'-'.$val['subprogcd'];
				}
			}
			else
			{
				$res = array();
			}



		}
		else
		{
			if ($search_field === 'bd_plan_dt')	// 방송일자
			{
				$brodYmdTmp=explode('-',$search_value);
				$brodYmd=$brodYmdTmp[0].$brodYmdTmp[1].$brodYmdTmp[2];
				$addWhere=" and a.BrodYmd = '{$brodYmd}'";

				$query = "select ".
					 "a.mk_no, ".
					 "a.mk_item, ".
					 "to_char(a.mk_str_dt, 'yyyy-mm-dd') mk_str_dt, ".
					 "to_char(a.bd_plan_dt, 'yyyy-mm-dd') bd_plan_dt, ".
					 "(select kor_nm from EAI.tperinfo_if where empno = a.pd_empno) pd_nm, ".
					 "to_char(a.ins_dtm, 'yyyy-mm-dd') reg_date ".
					 "from ".
					 "EAI.tmakebdcost_if a ".
					 "where to_char(a.bd_plan_dt, 'yyyy-mm-dd') = :search_value";
			}
			else if($search_field === 'pd_nm')	// 담당PD명
			{
				$addWhere=" and b.KorName like '%{$search_value}%'";

				$query = "select ".
					 "a.mk_no, ".
					 "a.mk_item, ".
					 "to_char(a.mk_str_dt, 'yyyy-mm-dd') mk_str_dt, ".
					 "to_char(a.bd_plan_dt, 'yyyy-mm-dd') bd_plan_dt, ".
					 "b.kor_nm pd_nm, ".
					 "to_char(a.ins_dtm, 'yyyy-mm-dd') reg_date ".
					 "from ".
					 "EAI.tmakebdcost_if a, EAI.tperinfo_if b ".
					 "where a.pd_empno = b.empno and b.kor_nm = :search_value";
			}
			else if($search_field === 'pro_nm')	// 프로그램명
			{
				$addWhere=" and a.ProgNm like '%{$search_value}%'";
			}


			$db_ms->setLimit($limit,$start);
			$proQuery=getProg($addWhere);
			$proTotalQuery=getProgTotal($addWhere);
			//$res = $db_ms->queryAll($proQuery);
			$total = $db_ms->queryOne($proTotalQuery);

		}

		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'data' => $res
		));
}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}
?>

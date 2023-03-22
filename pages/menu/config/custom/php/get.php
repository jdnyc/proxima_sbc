<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try 
{
	switch($_REQUEST['action']) 
	{
		case 'bc_ud_content':
			get_meta_table();
		break;

		case 'table_field':
			get_custom_field($_POST['ud_content_id']);
			break;

		case 'table_list':
			get_table_list();
			break;

		case 'content_type_list':
			get_content_type_list();
		break;
		
		//2011.12.06 콘텐츠 자신 삭제일 불러오기
		case 'content_del_date_list':
			get_content_del_date_list();
		break;
		
		case 'contents_del_date_list':
			get_contents_del_date_list();
		break;
		
		case 'file_del_date_list':
			get_file_del_date_list();
		break;

		// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
		case 'container_list':
			get_container_list($_POST['ud_content_id']);
		break;

		default:
			echo json_encode(array(
				'success' => false,
				'msg' => 'no more action 액션이 정의 되어있지 않습니다.'
			));
		break;
	}
}
catch (Exception $e)
{
	echo $db->last_query;
}

function get_file_del_date_list()
{
	global $db;
	
	$query = "select * from bc_code where code_type_id in (select id from bc_code_type ct where ct.code = 'FLDDDT')";
	$all = $db->queryAll($query);
	
	echo json_encode(array(
		'success' => true,
		'data' => $all	
	));		
}

//2011.12.06 콘텐츠 자신 삭제일 불러오기
function get_content_del_date_list()
{
	global $db;
	
	$query = "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCSDDT')";
	$all = $db->queryAll($query);
	
	echo json_encode(array(
		'success' => true,
		'data' => $all	
	));	
}

function get_contents_del_date_list()
{
	global $db;
	
	$query = "select * from bc_code where code_type_id  in (select id from bc_code_type ct where ct.code = 'UCDDDT')";
	$all = $db->queryAll($query);
	
	echo json_encode(array(
		'success' => true,
		'data' => $all	
	));	
}

function get_meta_table() 
{
    global $db;
    
    $query = "select bsc.bs_content_title ori_content_idx, bsc.bs_content_id, bsc.bs_content_title, udc.ud_content_id, 
		      udc.ud_content_title, udc.allowed_extension, udc.description, udc.show_order, udc.expired_date as content_expire_date , udc.con_expire_date as contents_expire_date
		      ,(select name from (select * from bc_code where code_type_id in (select id from bc_code_type where code= 'UCSDDT')) z where z.code = udc.expired_date) e_date
              ,(select name from (select * from bc_code where code_type_id in (select id from bc_code_type where code= 'UCDDDT')) z where z.code = udc.con_expire_date) se_date	
			   from bc_bs_content bsc, bc_ud_content udc, bc_category_mapping cm
			   where bsc.bs_content_id=udc.bs_content_id 
               and udc.ud_content_id=cm.ud_content_id(+)
               order by udc.show_order";

    $all = $db->queryAll($query);
    
    for($i=0;$i<count($all) ; $i++)
    {
		$all[$i][show_contents_expire_date] = check_limit_code_to_date($all[$i][contents_expire_date]);    	 
    }  
        
    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

function get_custom_field($meta_table_id) 
{
    global $db;

	// 2010-11-08 order by sort 수정 (컨테이너 추가 by CONOZ)
	//$all = $db->queryAll('select * from meta_field where meta_table_id = ' . $meta_table_id . ' order by sort');
//    $all = $db->queryAll('select * from meta_field where meta_table_id = ' . $meta_table_id . ' order by sort');

	$ud_content_id = $_POST['ud_content_id'];

	//쿼리 변경 by 이성용 2011-05-23
	 $all = $db->queryAll("select usrmf.*, cf.usr_meta_field_title as container_name 
							from bc_usr_meta_field usrmf, 
								(select usr_meta_field_title, usr_meta_field_id from bc_usr_meta_field where ud_content_id=$ud_content_id and usr_meta_field_type='container') cf 
							where usrmf.ud_content_id=$ud_content_id 
							--and usrmf.container_id(+)=cf.usr_meta_field_id 
							and usrmf.container_id=cf.usr_meta_field_id(+) 
							order by usrmf.show_order");


	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
/*	$dataTmp=array();
	foreach($all as $key=>$val){
		$dataTmp[$key]['meta_table_id']=$val['meta_table_id'];
		$dataTmp[$key]['meta_field_id']=$val['meta_field_id'];
		$dataTmp[$key]['sort']=$val['sort'];
		$dataTmp[$key]['name']=$val['name'];
		$dataTmp[$key]['container_id']=$val['container_id'];
		$dataTmp[$key]['type']=$val['type'];
		$dataTmp[$key]['is_required']=$val['is_required'];
		$dataTmp[$key]['editable']=$val['editable'];
		$dataTmp[$key]['default_value']=$val['default_value'];
		$dataTmp[$key]['is_show']=$val['is_show'];
		$dataTmp[$key]['search_allow']=$val['search_allow'];

		if( $val['container_id'] && $val['depth'] != '0')
		{
			$container_name = $db->queryOne("select name from meta_field where meta_table_id={$meta_table_id} and type='container' and meta_field_id='{$val['container_id']}'");

			$dataTmp[$key]['container_name']=$container_name;
		}
		else
		{
			$dataTmp[$key]['container_name']="";
		}
	}
*/

	foreach($all as $key=>$val)
	{
		if($val['type'] == 'container')
		{
			$all[$key]['container_name'] = "";
		}
	}

    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all//$dataTmp
    ));
}

function check_limit_code_to_date($code)
{
	global $db;
	$query = "select name from bc_code bc where code='$code' and bc.code_type_id in (select id from bc_code_type where code='UCDDDT')";
	$re = $db->queryOne($query);
	
	return $re;
}

function get_table_list() 
{
    global $db;

    $all = $db->queryAll('select ud_content_id, ud_content_title from bc_ud_content order by show_order');

    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

function get_content_type_list()
{
	global $db;

	$all = $db->queryAll("select * from bc_bs_content order by show_order");

    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
function get_container_list($ud_content_id) 
{
    global $db;

    $all = $db->queryAll("select usr_meta_field_id as container_id, usr_meta_field_title 
							from bc_usr_meta_field 
							where ud_content_id = '$ud_content_id' 
							and usr_meta_field_type='container' 
							and depth <= 1 
							order by show_order");

    echo json_encode(array(
        'success' => true,
        'total' => count($all),
        'data' => $all
    ));
}

?>
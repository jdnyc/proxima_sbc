<?php
//11-11-01 승수. bc_ud_content 테이블에 만료기간 expire_date필드 추가
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once('common.php');

switch($_POST['action']) {
    case 'edit_table':      	
         edit_table();
	    break;
    
    case 'field':
        edit_field();
	break;
	
    case 'sort_field':
    	sort_field($_POST['records']);
    break;
    
    default: 
        echo json_encode(array(
            'success' => true,
            'msg' => 'Empty Action(' . $_POST['action'] . ')'
        ));
    break;
}


function edit_table()
{
    global $db;
	$bs_content_id = $_POST['ori_content_idx']; // ud_content_id_value
	$ud_content_id = $_POST['ud_content_id'];
	$ud_content_title = $_POST['ud_content_title'];  
	$show_order = $_POST['show_order'];
	$allowed_extension = $_POST['allowed_extension'];
    $description = $_POST['description'];
	//$bs_content_id = $_POST['bs_content_id'];
	//$expire_date = $_POST['year']*365 + $_POST['month']*30 + $_POST['day'];
	$use_common_category = $_POST['use_common_category'];
	
	//print_r($_POST);
	
	$content_expire_date = check_limit_date($_POST['expired_date']); 
	
	$expire = array();
	$data =json_decode($_POST['expire']);
    
    foreach($data as $key => $v)
    {
    	$expire[$key]=$v;    	
    }    
    
    $contents_expire_date = $expire['con_expired_date']; 
    $curtime = date('YmdHis');
    
    // 우선 먼저 삭제 //
            	
	$query = "update bc_ud_content set 
			  show_order = '$show_order', 
			  ud_content_title='$ud_content_title', 
			  allowed_extension='$allowed_extension', 
			  description='$description', 
			  bs_content_id=$bs_content_id, 
			  expired_date='$content_expire_date' , 
			  con_expire_date='$contents_expire_date' 
			  where ud_content_id=$ud_content_id";
	
	$db->exec($query);
	
	$query = "delete from bc_ud_content_delete_info where ud_content_id = '$ud_content_id'";
	$db->exec($query); 	
	
	$query = "insert into bc_ud_content_delete_info (ud_content_id, type_code, date_code, code_type) values ($ud_content_id,'$_POST[expired_date]','$_POST[expired_date]','UCSDDT')";
	$db->exec($query);
	
	$query = "insert into bc_ud_content_delete_info (ud_content_id, type_code, date_code, code_type) values ($ud_content_id,'$expire[con_expired_date]','$expire[con_expired_date]','UCDDDT')";
	$db->exec($query);
	
 	$query = "select * from bc_code where code_type_id in (select id from bc_code_type ct where ct.code = 'FLDLNM')";
    $filetype_code = $db->queryAll($query);     
  
    foreach($filetype_code as $f_code)
    {
    	$ck_name = 'del_'.$f_code['code'].'_checkbox';
    	$dt_name = 'del_'.$f_code['code'].'_date';    	
    	
    	if(!strcmp("on",$expire[$ck_name])) // exist
    	{    	
    		if($expire[$dt_name])
    		{
    			$query = "insert into bc_ud_content_delete_info (ud_content_id, type_code, date_code, code_type) values ($ud_content_id ,'$f_code[code]','$expire[$dt_name]','FLDLNM')";   
    			//echo("<br>$query<br>"); 			   			
    			$db->exec($query);
    		}    		
    	}    	
    }
//카테고리 권한 관리 로 설정 변경 후 주석 by 이성용
//	if ($use_common_category == 'Y')
//	{
//		$category_id = $db->queryOne('select category_id from bc_category_mapping where ud_content_id=' . $ud_content_id);
//
//		$db->exec('delete from bc_category_mapping where ud_content_id=' . $ud_content_id);
//
//		// 부모를 잃은 카테고리가 생김
//		// deleteChildrenCategory($category_id)
//		$db->exec('delete from bc_category where category_id=' . $category_id);
//	}
//	else
//	{
//		$category_id = $db->queryOne("select category_id from bc_category_mapping where ud_content_id=".$ud_content_id);
//		if (!is_null($category_id))
//		{
//			$db->exec("update bc_category set category_title='$ud_content_title' where parent_id=$category_id");
//		}
//		else
//		{
//			addExclusiveCategory($ud_content_id, $ud_content_title);
//		}
//	}

    echo json_encode(array(
        'success' => true,
        'msg' => _text('MN02178')//'완료'
    ));
}


function edit_field() 
{
    global $db;

	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
    $container_id		= !empty($_POST['container_id'])		? $_POST['container_id']	: '';

	$usr_meta_field_id		= $_POST['usr_meta_field_id'];
	$ud_content_id			= $_POST['ud_content_id'];
	$usr_meta_field_title	= $_POST['usr_meta_field_title'];
	$usr_meta_field_type	= $_POST['usr_meta_field_type'];
	$is_required		= !empty($_POST['is_required'])		? $_POST['is_required']	: 0;
	$is_editable		= !empty($_POST['is_editable'])		? $_POST['is_editable']		: 0;
	$is_show			= !empty($_POST['is_show'])			? $_POST['is_show']		: 0;
	$is_search_reg		= !empty($_POST['is_search_reg'])	? $_POST['is_search_reg']	: 0;
	$default_value		= !empty($_POST['default_value'])	? $_POST['default_value']: '';

    $is_exists = $db->queryOne("select count(*) from bc_usr_meta_field where ud_content_id=$ud_content_id and usr_meta_field_id != $usr_meta_field_id and usr_meta_field_title = '$usr_meta_field_title'");
	if($is_exists > 0) _print(_text('MSG02116'));//존재하는 메타데이터 이름 입니다.
	
	if (empty($usr_meta_field_title))		_print(_text('MSG01051').'(' . $usr_meta_field_title . ')');//메타데이터 명이 존재 하지않습니다.
	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
	$changeContainer=false;
	if($container_id)
	{
		if($usr_meta_field_type == 'container')
		{
			// 컨테이너가 다른 컨테이너로 바뀌게 되면 
			$pre_container_id=$db->queryOne("select container_id from bc_usr_meta_field where usr_meta_field_id=$usr_meta_field_id");
			if($container_id != $pre_container_id)
			{
				if($container_id == $ud_content_id)
				{
					// 컨테이너가 다시 독립 컨테이너로
					$depth=0;
				}
				else
				{
					// 자식 들도 모두 바뀐 컨테이너로 이동
					$depth=$db->queryOne("select depth from bc_usr_meta_field where usr_meta_field_id='{$container_id}'");
					if($depth > 0)
					{
						_print(_text('MSG01053'));//'2차 컨테이너 안에는 컨테이너외 다른 입력형식들만 추가하실수 있습니다.'
					}
					$depth += 1;
					$changeContainer=true;
				}
			}
			else
			{
				# container_id 의 depth가 0이 아닐경우에는 type을 container로 할수 없습니다.
				$depth=$db->queryOne("select depth from bc_usr_meta_field where usr_meta_field_id=$container_id");
				if($depth > 0)
				{
					_print(_text('MSG01053'));//'2차 컨테이너 안에는 컨테이너외 다른 입력형식들만 추가하실수 있습니다.'
				}
				//$depth=$depth+1;
			}
		}
		else
		{
			$depth=$db->queryOne("select depth from bc_usr_meta_field where usr_meta_field_id=$container_id");
			$depth += 1;
		}
	}
	else
	{
		if($usr_meta_field_type != 'container')
		{
            //'컨테이너를 선택하시거나 입력형식을 컨테이너로 선택해 주십시오.
			_print(_text('MSG01054').'(' . $type . ')');
		}
		else
		{
			$container_id=$usr_meta_field_id;
			$depth=0;
		}
	}

	// 2010-11-08 container_id, depth 추가 (컨테이너 추가 by CONOZ)
	$r = $db->exec("update bc_usr_meta_field set " .
						"usr_meta_field_title	='$usr_meta_field_title', " .
						"usr_meta_field_type	='$usr_meta_field_type', " .
						"is_required	='$is_required', " .
						"is_editable	='$is_editable', " .
						"default_value	='$default_value', " .
						"is_show		='$is_show', " .
						"container_id	='$container_id', " .
						"depth			='$depth', " .
						"is_search_reg	='$is_search_reg' " .
					"where usr_meta_field_id=".$usr_meta_field_id);
	

	// 2010-11-08 추가 (컨테이너 추가 by CONOZ)
	if($changeContainer == true)
	{
		$r = $db->exec("update bc_usr_meta_field set container_id='$container_id', depth='$depth' where container_id=".$meta_field_id);
	}

    echo json_encode(array(
        'success' => true,
        'msg' =>  '(' .$db->last_query . ')'
    ));
}
function sort_field($records) 
{
	global $db;
	
	$records = json_decode($records);
	foreach ($records as $record) 
	{
		$r = $db->exec("update {$record->table} set show_order = {$record->sort} where {$record->id_field} = {$record->id_value}");
	}

	echo json_encode(array(
        'success' => true,
        'msg' => _text('MN02178')//'완료's
    ));
	
}
function _print($msg){
	echo json_encode(array(
		'success' => false,
		'msg' => $msg
	));
	exit;
}

function check_media_expire_date($ud_content_id,$file_type)
{
	global $db;
	//content_id 사용시
	/*$query ="
	select bicmd.* from bc_ud_content_media_del bicmd
	where bicmd.ud_content_id in (select ud_content_id from bc_content where content_id = $content_id)";
		*/
	//ud_content_id 사용시	
	$query = "select * from bc_ud_content_delete_info where ud_content_id = $ud_content_id";		
	$data = $db->queryAll($query);
	
	foreach($data as $d => $da)
	{
		$type = $da[type_code];
		$code = $da[date_code];
		
		if(strstr($file_type,$type))
		{
			return check_limit_date($code);
		}					
	}
	
	return check_limit_date('100_y');
}

	function check_limit_date($limit)
    {   	
   	  $limit = explode("_",$limit);
   	  $str = '';
   	  
   	  switch($limit[1]){
   	  	case 'y' :
   	  		if($limit[0]>26)
   	  		{ //26년이상 계산이 안됨
   	  			$cur_year_date = date('Y');
   	  			$cur_date = date('mdHis');   	  			
   	  			$year = $limit[0]+$cur_year_date;   	  		
   	  			return $year.$cur_date;	
   	  		}else 
   	  		{
   	  			$str = '+'.$limit[0].' year';
   	  		}		
   	  		break;
   	  		   	  			
   	  	case 'm' :
   	  		$str = '+'.$limit[0].' month';
   	  		break;
   	  		
   	  	case 'd' :
   	  		$str = '+'.$limit[0].' day';
   	  		break;
   	  		
   	  	case 'h' :
   	  		$str = '+'.$limit[0].' hours';
   	  		break;
   	  	
   	  	case 'i' :
   	  		$str = '+'.$limit[0].' minutes';
   	  		break;
   	  	
   	  	case 's' :
   	  		$str = '+'.$limit[0].' seconds';
   	  		break;
   	  		
   	  	default :
   	  		//그 이외 값은 999년 으로 처리   	  			  		
   	  		return $limit[0];
   	  	break;
     }
   
     return date('YmdHis',strtotime($str));
   }
?>
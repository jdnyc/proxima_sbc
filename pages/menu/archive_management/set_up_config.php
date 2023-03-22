<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

global $db;
$type = $_REQUEST['type'];
$category_id = $_REQUEST['category_id'];
$post_method = $_REQUEST['method_nm'];
$post_period = $_REQUEST['period'];
$user_id = $_REQUEST['user_id'];
$master = $_REQUEST['master'];
$target_category_id = $_REQUEST['target_category_id'];
$post_period = preg_replace("/[^0-9]/","",$post_period);


try{
    switch($post_method)
    {
        case '자동' :
            $method = 'A';
        break;
        case '수동' :
            $method = 'M';
        break;
        case '미지정' :
            $method = 'N';
        break;
    }
    switch($method)
    {
        case 'A' :
            $period = $post_period;
        break;
        case 'M' :
            $period = '';
        break;
        case 'N' :
            $period = '';
            $method = '';
        break;
    }
    
    $cur_time = date('YmdHis');    

    $query = "select category_id from bc_category start with category_id = $category_id connect by prior category_id = parent_id";
    $categories = $db->queryAll($query);


    foreach($categories as $category)
    {
        $c_id = $category['category_id'];
        $query = "select count(*) from bc_category_env where category_id=$c_id";
        $is_category = $db->queryOne($query);
        if($is_category == 0)
        {
            //type에 따라 아카이브, 삭제, 자동폐기값을 신규 추가
            if($type == 'archive')
            {
                $query = "insert into bc_category_env (category_id, arc_method, arc_period, edit_date, edit_user_id)
                            values($c_id, '$method', '$period', '$cur_time', '$user_id')";
            }
			else if($type == 'accept')
            {
                 $query = "insert into bc_category_env (category_id, acpt_method, acpt_period, edit_date, edit_user_id)
                        values($c_id, '$method', '$period', '$cur_time', '$user_id')";
            }
            else if($type == 'delete')
            {
                 $query = "insert into bc_category_env (category_id, del_method, del_period, edit_date, edit_user_id)
                        values($c_id, '$method', '$period', '$cur_time', '$user_id')";
            }
			else if($type == 'ori_del')
            {
				if($period < 3)
				{//채널A, Archive스토리지삭제는 최소 3일로 제한
					$period = 3;
				}
                 $query = "insert into bc_category_env (category_id, ori_del_method, ori_del_period, edit_date, edit_user_id)
                        values($c_id, '$method', '$period', '$cur_time', '$user_id')";
            }
            else if($type == 'abrogate')
            {
                 $query = "insert into bc_category_env (category_id, abr_method, abr_period, edit_date, edit_user_id)
                        values($c_id, '$method', '$period', '$cur_time', '$user_id')";
            }
            else if($type == 'restore')
            {
                 $query = "insert into bc_category_env (category_id, res_method, res_period, edit_date, edit_user_id)
                        values($c_id, '$method', '$period', '$cur_time', '$user_id')";
            }
            else if($type == 'master')
            {
                 $query = "insert into bc_category_env (category_id, is_master, tr_category, edit_date, edit_user_id)
                        values($c_id, '$master', '$target_category_id', '$cur_time', '$user_id')";
            }

            $db->exec($query);
        }
        else
        {
            //type에 따라 아카이브, 삭제, 자동폐기값을 업데이트
            if($type == 'archive')
            {
                $query = "update bc_category_env 
                             set arc_method = '$method', arc_period = '$period', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
			else if($type == 'accept')
            {
                $query = "update bc_category_env 
                             set acpt_method = '$method', acpt_period = '$period', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
            else if($type == 'delete')
            {
                $query = "update bc_category_env 
                             set del_method = '$method', del_period = '$period', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
			else if($type == 'ori_del')
            {
				if($period < 3)
				{//채널A, Archive스토리지삭제는 최소 3일로 제한
					$period = 3;
				}
                $query = "update bc_category_env 
                             set ori_del_method = '$method', ori_del_period = '$period', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
            else if($type == 'abrogate')
            {
                $query = "update bc_category_env 
                             set abr_method = '$method', abr_period = '$period', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
            else if($type == 'restore')
            {
                $query = "update bc_category_env 
                             set res_method = '$method', res_period = '$period', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
            else if($type == 'master')
            {
                $query = "update bc_category_env 
                             set is_master = '$master', tr_category = '$target_category_id', edit_date = '$cur_time', edit_user_id = '$user_id'
                           where category_id = $c_id";
            }
            $db->exec($query);
        }
    }
    
    echo json_encode(array(
            'success' => true,
			'query' => $query,
            'msg' => '수정이 완료되었습니다'
    ));
    
}
catch(Exception $e)
{
        $msg = $e->getMessage();
        
        echo json_encode(array(
            'success' => false,
            'msg' => $msg
    ));
        
} 
?>

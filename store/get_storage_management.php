<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

/* 사용자 ID를 받아서 해당 사용자가 포함된 
 * 제작그룹의 스토리지 정보를 얻어오는 페이지
 * 2014.07.08 임찬모 작성
 */

$user_id = $_SESSION['user']['user_id'];

try
{
    // 필요정보 : 카테고리명(category_title), 경로(path), 카테고리ID(category_id), 구분(type), 할당량(quota), 사용량(useage)
        if(($user_id = 'admin') && ( $_SESSION['user']['is_admin'] == 'Y' )) {
            $query = "select p.*, ca.category_title 
                        from bc_category ca, path_mapping p
                        where ca.category_id = p.category_id
                        order by ca.category_title";
        } else {
            $query = "select p.*, ca.category_title 
                        from bc_category ca, path_mapping p, user_mapping um
                        where um.user_id = '$user_id' 
                            and um.category_id = p.category_id 
                            and ca.category_id = p.category_id
                        order by ca.category_title";
        }
        
        $storages = $db->queryAll($query);
        
        foreach ($storages as $storage)
	{
                $usable = $storage['quota'] - $storage['usage'];
                
		$data[] = array(
			'category_id'   =>  $storage['category_id'],
                        'category_title'    =>  $storage['category_title'],
                        'type'  =>  $storage['type'],
                        'path'  =>  $storage['path'],
                        'quota' =>  $storage['quota'],
                        'usage'    => $storage['usage'],
                        'usable'    => $usable,
			'usable_percentage'    => round(($usable / $storage['quota']) * 100, 2)
		);
	}
        
        echo json_encode(array(
		'success' => true,
		'data' => $data
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
	exit;
}
?>

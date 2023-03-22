<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
fn_checkAuthPermission($_SESSION);

$data = array();

try {


	if($_POST['type'] == 'notice'){
		foreach( $_POST as $field=>$value ){
			switch($field){
				case 'dir':
					$_dir = $value;
				break;
				case 'sort':
					$_sort = $value;
				break;
				case 'limit':
					$_limit = empty($value) ? 20 : $value;
				break;
				case 'start':
					$_start = empty($value) ? 0 : $value;
				break;
				case 'search_value':
					if( empty($value) ){
					}else{
						$search_field = $_POST['search_field'];
						if( $search_field == 's_created_time' ){
							$search_value = date('Ymd', strtotime($value))."%";
						}else{
							$search_value = "%".$value."%";
						}
						$_where = "WHERE	".strtoupper($search_field)." LIKE '".$search_value."' ";
					}
				break;
			}
		}
		$query_group = "
			SELECT	*
			FROM		BC_MEMBER_GROUP
			".$_where."
			ORDER	BY	 CREATED_DATE ASC
		";
		$rows = $db->queryAll($query_group);
	}else{
		$rows = $db->queryAll("select * from bc_member_group order by {$_POST['sort']} {$_POST['dir']}");
	}


	foreach($rows as $row)
	{
            $member_group_id = $row['member_group_id'];
            $query = "select bm.* from bc_member bm, bc_member_group_member bgm where bm.member_id = bgm.member_id and bgm.member_group_id = $member_group_id";
            $members = $db->queryAll($query);
            $group_members = array();
            foreach($members as $member) {
                array_push($group_members, $member);
            }
            $row['members']=$group_members;
				
			if (array_key_exists('parent_group_id',$row) && $row['parent_group_id'] != ''){
				$parent_group_id = $row['parent_group_id'];
				$group_parent = $db->queryRow("select * from bc_member_group where member_group_id = $parent_group_id");
				$row['parent_group_name'] = $group_parent['member_group_name'];
			}
            array_push($data, $row);
	}

    echo json_encode(array(
		'success' => true,
		'total' => count($rows),
		'query' => $query_group,
		'data' => $data
	));
}
catch(Exception $e){
	switch($e->getCode()){
		case ERROR_QUERY:
			die(json_encode(array(
				'success' => false,
				'msg' => $e->getMessage().'('.$db->last_query.')'
			)));
		break;
	}
}
?>
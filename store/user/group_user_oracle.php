<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
//require_once('../oracle_connect.php');
$group_id = $_REQUEST['member_group_id'];
$members = $_REQUEST['members'];
$del_records = array();
$add_records = array();
$old_members = array();

try {
        $members = array_filter($members);
    
        $old_group_members = $db->queryAll("select member_id from bc_member_group_member where member_group_id = $group_id");
        
        $old_group_members = array_filter($old_group_members );
        
        foreach($old_group_members as $old_group_member) {
                $old_member = $old_group_member['member_id'];
                array_push($old_members, $old_member);
        }
        // 기존 그룹 사용자가 있을경우
        if(count($old_members) > 0 ) {
            // 신규 그룹 사용자 array가 있을 경우
            if(count($members) > 0 ) {
                foreach($members as $member) {
                    $is_exist = in_array($member, $old_members);
                    if(!$is_exist) {
                        array_push($add_records, $member);
                    }
                }
                
                foreach($old_members as $old_member) {
                        $is_exist = in_array($old_member, $members);
                        if(!$is_exist) {
                            array_push($del_records, $old_member);
                        }
                }
            } // 신규 그룹 사용자 array가 비어있을 경우 기존 그룹 사용자는 삭제처리
            else {
                foreach($old_members as $old_member) {
                        array_push($del_records, $old_member);
                }
            }
        } // 기존 그룹 사용자가 없을 경우
        else {
            // 신규 그룹 사용자 array가 있을 경우에는 전부 insert 처리
            if(count($members) > 0 ) {
                foreach($members as $member) {
                    array_push($add_records, $member);
                }
            }
        }
        
        // 추가,삭제 대상 array 중 빈값은 제외시킴
        $add_records = array_filter($add_records);
        $del_records = array_filter($del_records);
        
//         추가 대상은 DB에 insert , 삭제대상은 DB에서 delete 처리
        if(count($add_records) > 0 ) {
            foreach($add_records as $add_record) {
                $db -> exec("insert into bc_member_group_member values ($add_record, $group_id)");
            }
        }
        if(count($del_records) > 0 ) {
            foreach($del_records as $del_record) {
                $query = "delete from bc_member_group_member where member_id = $del_record and member_group_id = $group_id";
                $db -> exec($query);
            }
        }
        
	die(json_encode(array(
		'success' => true
	)));
}
catch(Exception $e){
	die(json_encode(array(
		'success' => false,
		'msg' => $e->getMessage(),
		'q' => $db->last_query
	)));
}
?>
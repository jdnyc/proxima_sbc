<?php
/**
 * 전송시 Harris서버로 전송하는 작업이면 중복여부를 체크한다.
 * Harris서버 중에서도 관리되는 서버(채널운영실, 부조)만 해당된다.
 */
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] .'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] .'/lib/functions.php';

try
{
    $content_id_arr = json_decode( $_POST['content_list'] );
    $accept_content_ids = array();
    $denine_content_ids = array();
    $msg_arr = array();
    // foreach($content_id_arr as $content_id) {
    //     foreach($_POST as $k => $v) {
    //         if($k == 'content_list') continue;
    //         if(!in_array($k, array('ftp_transfer_GWR_A','ftp_transfer_GWR_B'))) continue;//Harris전송 워크플로우가 아니면 체크하지 않는다.
    //         //Harris테이블과 bc_content의 title을 비교
    //         $xid = $db->queryOne("select xid from harris where ariel_uid = $content_id and mam_ingest is not null");
    //         if(!empty($xid)) {
    //             $title = $db->queryOne("select title from bc_content where content_id = $content_id");
                
    //             if($xid == $title) {
    //                 $msg_arr[] = '"'.$title.'" 은(는) 중복된 콘텐츠 입니다. 덮어씌워집니다.';
    //                 $denine_content_ids[] = $content_id;
    //             } else {	
    //                 $msg_arr[] = '"'.$title.'" 은(는) 제목이 다른 동일 콘텐츠 입니다.';
    //                 $denine_content_ids[] = $content_id;
    //             }
    //         } else {
    //             $accept_content_ids[] = $content_id;
    //         }
    //     }
    // }

    /**
     * 중복인 content_id와 아닌 content_id를 나눠서 리턴하고, 사용자의 결정에 따라 모든 content_id를 전송할지
     * 중복을 제외한 content_id를 전송할지 결정된다.
     */
    if(empty($accept_content_ids) && empty($denine_content_ids)) {
        $accept_content_ids_text = $_POST['content_list'];
        $msg_text = '';
    } else {
        $accept_content_ids_text = '["'.implode('","', $accept_content_ids).'"]';
        $denine_content_ids_text = '["'.implode('","', $denine_content_ids).'"]';
        $msg_arr[] = '중복자료도 다시한번 전송하시겠습니까?';
        $msg_text = implode('<br />', $msg_arr);
    }

    echo json_encode(array(
		'success' => true,
        'accept_content_ids' => $accept_content_ids_text,
        'denine_content_ids' => $denine_content_ids_text,
		'msg' => $msg_text
	));
}
catch(Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage() . '(' . $db->last_query . ')'
	));
}
?>
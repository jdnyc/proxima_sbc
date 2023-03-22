<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

try {
    $user_id            = $_SESSION['user']['user_id'];
    $action             = $_REQUEST['action'];
    $broad_date         = date('Ymd000000', strtotime($_REQUEST['broad_date']));
    $cuesheet_id        = $_REQUEST['cuesheet_id'];
    $cuesheet_type      = $_REQUEST['cuesheet_type'];
    $cuesheet_title     = $_REQUEST['cuesheet_title'];
    $subcontrol_room    = $_REQUEST['subcontrol_room'];
    $prog_nm            = $_REQUEST['prog_nm'];
    $prog_id            = $_REQUEST['prog_id'];
    $epsd_no		    = $_REQUEST['epsd_no'];
    $trff_no		    = $_REQUEST['trff_no'];
    $trff_seq	    	= $_REQUEST['trff_seq'];
    $trff_ymd		    = $_REQUEST['trff_ymd'];
    $contents           = json_decode($_REQUEST['contents'], true);
    $modified_date      = date('YmdHis');
    $dellist            = $_REQUEST['dellist'];


    // prog_id 로 prog_nm을 조회
    //$prog_nm = $db->queryOne("select ca.category_title from path_mapping pm, bc_category ca where pm.category_id = ca.category_id and pm.path = '$prog_id'");

    $created_date = date('YmdHis');
    switch($action) {
        case 'add' :
                $new_cuesheet_id = getSequence('SEQ_BC_CUESHEET_ID');
                $db->insert('BC_CUESHEET', array(
                    'CUESHEET_ID' => $new_cuesheet_id,
                    'CUESHEET_TITLE' => $cuesheet_title,
                    'BROAD_DATE' => $broad_date,
                    'CREATED_DATE' => $created_date,
                    'USER_ID' => $user_id,
                    'TYPE' => $cuesheet_type,
                    'SUBCONTROL_ROOM' => $subcontrol_room,
                    'CREATE_SYSTEM' => 'CMS',
                    'PROG_ID' => $prog_id,
                    'PROG_NM' => $prog_nm,
                    'EPSD_NO' => $epsd_no,
                    'TRFF_NO' => $trff_no,
                    'TRFF_SEQ' => $trff_seq,
                    'TRFF_YMD' => $trff_ymd
                ));
                $msg = '큐시트가 추가되었습니다';
            break;
        case 'edit' :
            $is_reset_task = ($_POST['is_reset_task'] == 'true') ? true : false;

            $query = "update bc_cuesheet
                        set cuesheet_title = '$cuesheet_title', broad_date = '$broad_date', subcontrol_room='$subcontrol_room', prog_id = '$prog_id', prog_nm = '$prog_nm',
							epsd_no = '$epsd_no', trff_no = '$trff_no', trff_seq = '$trff_seq', trff_ymd = '$trff_ymd', modified_date='$created_date'
                        where cuesheet_id = '$cuesheet_id'";
            $db->exec($query);

            if ($is_reset_task) {
                $query = "update bc_cuesheet_content set task_id=null where cuesheet_id=" . $cuesheet_id;
                $db->exec($query);
            }

            $msg = '큐시트가 수정되었습니다';
            break;

        case 'del' :
		    // 큐시트 삭제시 해당 큐시트에 추가된 아이템들도 전부 삭제
            $db->exec("delete from bc_cuesheet where cuesheet_id = '$cuesheet_id'");
		    $db->exec("delete from bc_cuesheet_content where content_id = '$cuesheet_id'");
            $msg = '큐시트가 삭제되었습니다';
            break;

        case 'add-items' :
                $show_order = $db->queryOne("select max(show_order) from bc_cuesheet_content where cuesheet_id = $cuesheet_id");
                if (empty($show_order)) {
                    $show_order = 0;
                }

                foreach ($contents as $content) {
                        $cuesheet_content_id = getSequence('SEQ_BC_CUESHEET_CONTENT_ID');
                        $content_id = $content['id'];
                        $title = $content['title'];
                        $show_order += 1;

                    $db->insert('BC_CUESHEET_CONTENT', array(
                        'CUESHEET_ID' => $cuesheet_id,
                        'SHOW_ORDER' => $show_order,
                        'TITLE' => $title,
                        'CONTENT_ID' => $content_id,
                        'CUESHEET_CONTENT_ID' => $cuesheet_content_id
                    ));
                }
                $msg = '큐시트에 콘텐츠가 추가되었습니다';
            break;
        case 'del-items' :
            $cuesheet_items = json_decode($_POST['cuesheet_items'], true);
            foreach ($cuesheet_items as $entry) {
                $db->exec("delete from bc_cuesheet_content where cuesheet_id=$cuesheet_id and cuesheet_content_id = ".$entry['id']);
            }
            $msg = '큐시트에서 콘텐츠가 삭제되었습니다';
            break;

        case 'sort_field':
            $records = $_POST['records'];
            $records = json_decode($records);


            foreach ($records as $record) {
                    $db->exec("update {$record->table} set show_order = {$record->sort} where {$record->id_field} = {$record->id_value}");
            }
            $msg = '재정렬 완료';

            break;

        case 'save-control':
            $datas = $_POST['datas'];
            $datas = json_decode($datas);
            foreach ($datas as $data) {
                    $cuesheet_c_id = $data->cuesheet_content_id;
            $control = $data->control;

            $db->exec("update bc_cuesheet_content set control = '$control' where cuesheet_content_id = '$cuesheet_c_id'");
            }
            $msg = '제어항목 저장 완료';

            break;
        case 'save-items':
            $datas = $_POST['datas'];
            $datas = json_decode($datas);
            $dellist = $_POST['dellist'];
            $dellist = json_decode($dellist);
            // 삭제된 항목이 있을 경우 삭제처리
            if(count($dellist) > 0) {
                foreach($dellist as $del) {
                    $query = "delete from bc_cuesheet_content where cuesheet_content_id = '$del'";
                    $db->exec($query);
                }
            }           
            // 신규추가 및 수정된 항목 처리
            // 삭제된 데이터 이외의 데이터들에 대해서 show_order가 빠지는 부분이 없도록 하기 위해서 record 순서대로 show_order를 잡아주기 위해 추가
            $idx = 1;
            
            foreach($datas as $data) {
                $cuesheet_content_id = $data->cuesheet_content_id;
                $cuesheet_id = $data->cuesheet_id;
                $title = $data->title;
                $content_id = $data->content_id;
                $control = $data->control;
                if(!empty($cuesheet_content_id)) {
                    // 수정
                    $query = "update bc_cuesheet_content set show_order = '$idx', control = '$control' where cuesheet_content_id = '$cuesheet_content_id'";
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/cuesheet_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] update query ===> '.$query."\r\n", FILE_APPEND);
                    $db->exec($query);
                } else {
                    // 신규
                    $cuesheet_content_id = getSequence('SEQ_BC_CUESHEET_CONTENT_ID');
                    $query = "insert into bc_cuesheet_content (cuesheet_id, show_order, title, content_id, cuesheet_content_id, control)  values ('$cuesheet_id', '$idx' , '$title','$content_id', '$cuesheet_content_id', '$control')";
                    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/cuesheet_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] add query ===> '.$query."\r\n", FILE_APPEND);
                    $db->exec($query);
                }
                
                $idx = $idx + 1;
            }
        break;
    }

    if ( ! empty($cuesheet_id)) {
        $db->exec("UPDATE BC_CUESHEET SET MODIFIED_DATE = '$modified_date' where cuesheet_id = $cuesheet_id");
    }

    echo json_encode(array(
        'success' => true,
        'msg' => $msg,
        'modified_date' => $modified_date
    ));
} catch (Exception $e) {
    die(json_encode( array(
            'success' => false,
            'msg' => $e->getMessage()
    )));
}
?>

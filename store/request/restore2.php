<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');


file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/restore2_test_' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" .date('Y-m-d H:i:s') . '] ' . print_r($_REQUEST, true)."\r\n", FILE_APPEND);
//2014.01.08 수정(임찬모)
//뉴스 : SD는 2013.12.17 21시 기준 / HD 중 YTN 및 하위 폴더는 2014.01.02 10시20분 기준 / HD YTN 이외는 2014.01.08 00시 기준으로 mxf/mov 리스토어 방식이 나뉨
//2013.04.04 수정(임찬모)
//컨텐츠 유형별로 워크플로우 채널 분리
//2013.12.16 수정(임찬모)
//SD 영상 워크플로우 채널 분리

$channel = 'sgl_restore';
$job_priority = 1;
$user_id = $_SESSION['user']['user_id'];
$ask_admin = false;
$insert_task = new TaskManager($db);
//soap 통신을 위한 데이터
$wsdl = "http://10.0.10.37:8080/soap/IWebService";
$ns = "http://tempuri.org/";

$now = date('YmdHis');
file_put_contents($_SERVER['DOCUMENT_ROOT'] . '/log/restore2_test_' . date('Ymd') . '.log', $_SERVER['REMOTE_ADDR'] . "\t[" .date('Y-m-d H:i:s') . '] ' . print_r($data,true)."\r\n", FILE_APPEND);
foreach ($data as $content_id) {
    //UNIQUE ID를 알아내서 soap으로 소산되었는지 여부 판단
    $full_path =$db->queryOne("select path from bc_media where content_id = '$content_id' and media_type = 'original'");
    $arr_full_path = explode('/', $full_path);
    $filename = array_pop($arr_full_path);
    $filename_arr = explode('.', $filename);
    $unique_id = $filename_arr[0];
    
    $client = new soapclient($wsdl);
    $client->soap_defencoding = 'UTF-8';
    $client->decode_utf8 = false;
    
    $err = $client->getError();
    if ($err) {
        echo '<p><b>Error: ' . $err . '</b></p>';
            exit;
    }

    $response = $client->call('getFlashNetStatus', array('strUID' => $unique_id), $ns);

    $request = simplexml_load_string($response);
    //$request = new SimpleXMLElement($response);
file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/restore2_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').']'.print_r($response,true)."\r\n", FILE_APPEND);
    $success = $request->result['success'];

    if($success) {
//        $is_online = false;
        $is_online = true;
        $off_volume = '';
        foreach ($request->data->record as $item) {
            $status = $item->status;
//            if($status == 'ONLINE') {
//                $is_online = true;
//            } else if($status == 'OFFLINE') {
//                $off_volume = $item->volume;
//            }
            if($status == 'OFFLINE') {
                $is_online = false;
                $off_volume = $item->volume;
                break;
            } else if($status == 'ONLINE') {
                $is_online = true;
            }
        }
        // TEST는 둘 중 하나라도 OFFLINE이면 소산된 걸로 간주해서 진행
        // TAPE 둘 중 하나라도 ONLINE이면 리스토어 진행, 둘다 OFFLINE 일 경우에는 소산된 상태이므로 사용자에게는 message 뿌려주고 소산리스트에 추가
        if($is_online) {
            $query = "select ud_content_id, category_id, archive_date from bc_content where content_id = '$content_id'";
            $content_info = $db->queryRow($query);

            $check_ud_content = $content_info['ud_content_id'];
            $category_id = $content_info['category_id'];
            $archive_date = $content_info['archive_date'];

            if($content_id < 43709 && $check_ud_content == '358' && $user_id != 'alex2207' && $user_id != 'bkjeong' && $user_id != 'reidar') {
                $ask_admin = true;
            } else {
                if($check_ud_content == '358') {
                        $channel = 'nps_restore';
                } else if($check_ud_content == '334') {
                        $channel = 'news_restore';
                } else if($check_ud_content == '374') {
                    if($category_id == '362' || $category_id == '434') {
                        if($archive_date > '20140102102000') {
                            $channel = 'tape_hd_restore';
                        } else {
                            $channel = 'tape_restore';
                        }
                    } else if($archive_date > '20140108000000') {
                        $channel = 'tape_hd_restore';
                    } else {
                        $channel = 'tape_restore';
                    }
                } else if($check_ud_content == '202') {
                    if($archive_date > '20131217210000') {
                        $channel = 'tape_sd_restore';
                    } else {
                        $channel = 'tape_restore';
                    }
                } else {
                        $channel = 'sgl_restore';
                }
                $insert_task->set_priority(200);
                $insert_task->start_task_workflow($content_id, $channel, $user_id);
                }
            } else {
                $query = "select title from bc_content where content_id = '$content_id'";
                $title = $db->queryOne($query);
                $query = "select user_nm from bc_member where user_id = '$user_id'";
                $user_nm = $db->queryOne($query);
                $query = "insert into bc_archive_dissipation (content_id, unique_id, title, tape_number,  reg_user_id, reg_user_nm, created_date) 
                                values ('$content_id', '$unique_id', '$title', '$off_volume', '$user_id', '$user_nm($user_id)', '$now')";
                $db->exec($query);
                $ask_admin = true;
            }
    } else {
        echo json_encode(array(
                        'success' => false,
                        'msg' => '아카이브 되지 않은 파일입니다'
                    ));
        exit;
    }  
}

if($ask_admin) {
    echo json_encode(array(
        'success' => true,
        'msg' => '소산된 자료는 관리자에게 문의 바랍니다'
    ));
} else {
    echo json_encode(array(
            'success' => true,
            'msg' => '리스토어 요청이 완료되었습니다.'
    ));
}
?>

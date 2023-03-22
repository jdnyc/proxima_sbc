<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//자료이관시 등록 함수
function data_transfer_register($old_content_id)
{
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] register==>true'." \r\n", FILE_APPEND);
    global $db;
    //컨텐츠ID값이 중복일 경우 가장 최근 것을 찾아서 등록
    $query = "select max(transfer_count) from bc_data_transfer where old_content_id = $old_content_id and request_data = 'N'";
    $max_transfer_count = $db->queryOne($query);
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] max_count==>'.$max_transfer_count." \r\n", FILE_APPEND);
    //bc_data_transfer 테이블에서 필요한 정보를 다 가져옴
    $query = "select * from bc_data_transfer where old_content_id = $old_content_id and transfer_count = $max_transfer_count and request_data = 'N'";
    $transfer_infos = $db->queryRow($query);
    $user_id =$transfer_infos['reg_user_id'];
    $ud_content_id = $transfer_infos['target_ud_content_id'];
    $bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id = $ud_content_id");
    $new_category_id = $transfer_infos['target_category'];
    $file = $transfer_infos['file_name'];
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] transfer_info'.print_r($transfer_infos, true)." \r\n", FILE_APPEND);
    //기존 데이터의 타이틀을 가져옴
    $query = "select title from bc_content where content_id = $old_content_id";
    $title = $db->queryOne($query);
    
    //신규 컨텐츠 등록을 위한 데이터값
    $content_id = getSequence('SEQ_CONTENT_ID');
    $status_value = 2;

    $cur_time = date('YmdHis');
    $expire_date = '99991231235959';

    //카테고리 획득
    $category_full_path = '/0'.getCategoryFullPath($new_category_id);

    $query = "insert into bc_content (category_id, category_full_path, bs_content_id, ud_content_id, ".
                                                                                    "content_id, title, reg_user_id, expired_date, created_date, status) ".
                 "values ('$new_category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', ".
                                                                                    "'$content_id', '$title', '$user_id', '$expire_date', '$cur_time', $status_value)";
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query===>'.$query." \r\n", FILE_APPEND);
    $db->exec($query);

    //뉴스(HD,SD), 외신영상
    if ($ud_content_id=='202' || $ud_content_id == '374') {
            $workflow_channel = 'data_reg_transfer_tape';
    }
    //뉴스편집영상
    else if ($ud_content_id=='334') {   
             $workflow_channel = 'data_reg_transfer_news';
    }
    //인제스트
    else if ($ud_content_id=='314') {
             $workflow_channel = 'data_reg_transfer_ingest';
    }
    //제작(NPS)
    else if($ud_content_id=='358') {
            $workflow_channel = 'data_reg_transfer_nps';
    }
    
    //통계를 위해서 신규 등록된 컨텐츠 ID 값을 업데이트
    $query = "update bc_data_transfer set new_content_id = $content_id where old_content_id = $old_content_id and request_data = 'N' and transfer_count = $max_transfer_count";
    $db->exec($query);
    
    $task_mgr = new TaskManager($db);
    $task_mgr->insert_task_query_outside_data($content_id, $workflow_channel, 1, $user_id, $file);
    //메타데이트 등록을 위한 함수
    change_meta_for_datatransfer($old_content_id, $content_id, 'transfer');
    return true;
}

//자료요청시 등록 함수
function data_request_register($old_content_id, $request_count, $task_id)
{
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] register==>true'." \r\n", FILE_APPEND);
    global $db;
    //bc_data_transfer 테이블에서 필요한 정보를 다 가져옴
    $query = "select * from bc_data_transfer where old_content_id = $old_content_id and transfer_count = $request_count and request_data = 'R'";
    $transfer_infos = $db->queryRow($query);
    $user_id =$transfer_infos['reg_user_id'];
    $ud_content_id = $transfer_infos['target_ud_content_id'];
    $bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id = $ud_content_id");
    $new_category_id = $transfer_infos['target_category'];
    $file = $transfer_infos['file_name'];
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] request_info'.print_r($transfer_infos, true)." \r\n", FILE_APPEND);
    //기존 데이터의 타이틀을 가져옴
    //제목에 ''가 들어갈 수 있어서 escape함수를 사용하여 변경
    $query = "select title from bc_content where content_id = $old_content_id";
    $title = $db->queryOne($query);
    $title = $db->escape($title);
    
    //우선 순위 변경시 변경된 우선순위를 따라가도록 하기 위해 기존 우선순위를 조회
    $query = "select priority from bc_task where task_id = $task_id";
    $task_priority = $db->queryOne($query);
    if(empty($task_priority))
    {
        $task_priority = 300;
    }
    
    //신규 컨텐츠 등록을 위한 데이터값
    $content_id = getSequence('SEQ_CONTENT_ID');
    $status_value = 2;

    $cur_time = date('YmdHis');
    $expire_date = '99991231235959';

    //카테고리 획득
    $category_full_path = '/0'.getCategoryFullPath($new_category_id);

    $query = "insert into bc_content (category_id, category_full_path, bs_content_id, ud_content_id, ".
                                                                                    "content_id, title, reg_user_id, expired_date, created_date, status) ".
                 "values ('$new_category_id', '$category_full_path', '$bs_content_id', '$ud_content_id', ".
                                                                                    "'$content_id', '$title', '$user_id', '$expire_date', '$cur_time', $status_value)";
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_request_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query===>'.$query." \r\n", FILE_APPEND);
    $db->exec($query);
    
    $query = "select code from bc_code where name = 'news_group'";
    $news_groups = $db->queryOne($query);
    $news_group_array = explode(",", $news_groups);
    foreach($news_group_array as $news)
    {
        if($ud_content_id == $news)
        {
            $workflow_channel = 'data_reg_request_news';

            break;
        }
        else
        {
             $workflow_channel = 'data_reg_request_nps';
        }
    }
    
    //통계를 위해서 신규 등록된 컨텐츠 ID 값을 업데이트
    $query = "update bc_data_transfer set new_content_id = $content_id where old_content_id = $old_content_id and request_data = 'R' and transfer_count = $request_count";
    $db->exec($query);
    
    
    $task_mgr = new TaskManager($db);
    $task_mgr->set_priority($task_priority);
    $task_mgr->insert_task_query_outside_data($content_id, $workflow_channel, 1, $user_id, $file);
    //메타데이트 등록을 위한 함수
    change_meta_for_datatransfer($old_content_id, $content_id, 'transfer');
    return true;
}

//기존 자료의 메타값을 이관 후의 UD_CONTENT_ID값에 맞게 변경 및 신규등록
function change_meta_for_datatransfer($old_content_id, $new_content_id, $type)
{
    global $db;
    if($new_content_id == 0)
    {
        $query = "select ori_ud_content_id, target_ud_content_id from bc_data_transfer where old_content_id = $old_content_id";
    }
    else
    {
        $query = "select ori_ud_content_id, target_ud_content_id from bc_data_transfer where old_content_id = $old_content_id and new_content_id = $new_content_id";
    }
    $ud_content_id = $db->queryRow($query);
    $old_ud_content_id = $ud_content_id['ori_ud_content_id'];
    $new_ud_content_id = $ud_content_id['target_ud_content_id'];
    
    $query = "select usr_meta_field_id, usr_meta_field_title from bc_usr_meta_field where ud_content_id = $new_ud_content_id";
    $new_meta_field = $db->queryAll($query);
    //type이 change이면 단순 변경, transfer 이면 신규등록
    if($type == 'change' && $new_content_id == 0)
    {
        $query = "select usr_meta_field_id, usr_meta_field_title from bc_usr_meta_field where ud_content_id = $old_ud_content_id";
        $old_meta_field = $db->queryAll($query);
        
         foreach ($old_meta_field as $old)
            {
                $old_meta_id = $old['usr_meta_field_id'];
                $old_meta_title = $old['usr_meta_field_title'];

                for ($i=0; $i < count($new_meta_field); $i++)
                {
                   $new_meta_id = $new_meta_field[$i]['usr_meta_field_id'];
                    $new_meta_title = $new_meta_field[$i]['usr_meta_field_title'];

                    //기존 ud_content_id의 메타와 이관될 카테고리내의 메타 타이틀 값을 비교하여 같은 값이 있으면
                    //기존 메타 아이디 값을 새로운 메타 아이디 값으로 변경해줌
                    if ($old_meta_title == $new_meta_title)
                    {
                        $query = "update bc_usr_meta_value 
                                     set ud_content_id = $new_ud_content_id, usr_meta_field_id = $new_meta_id 
                                   where content_id= $old_content_id and usr_meta_field_id = $old_meta_id";
                        $db->exec($query);
                    }
                }
            }        
    }
    else if ($type == 'transfer')
    {
        $query = "select usr_meta_field_id, usr_meta_field_title from bc_usr_meta_field where ud_content_id = $old_ud_content_id and usr_meta_field_type != 'container'";
        $old_meta_field = $db->queryAll($query);
        
         foreach ($old_meta_field as $old)
            {
                $old_meta_id = $old['usr_meta_field_id'];
                $old_meta_title = $old['usr_meta_field_title'];

                for ($i=0; $i < count($new_meta_field); $i++)
                {
                    $new_meta_id = $new_meta_field[$i]['usr_meta_field_id'];
                    $new_meta_title = $new_meta_field[$i]['usr_meta_field_title'];
                    //신규등록을 위해서 usr_meta_value_id 값을 시퀀스값으로 가져옴
                    //기존 ud_content_id의 메타와 이관될 카테고리내의 메타 타이틀 값을 비교하여 같은 값이 있으면
                    //기존 메타 아이디 값을 새로운 메타 아이디 값으로 변경해줌
                    if ($old_meta_title == $new_meta_title)
                    {
                        $query = "select TO_CHAR(usr_meta_value) AS usr_meta_value from bc_usr_meta_value where usr_meta_field_id = $old_meta_id and content_id = $old_content_id";
                        $old_usr_meta_value = $db->queryOne($query);
                        $old_usr_meta_value = $db->escape($old_usr_meta_value);
                        $query ="insert into bc_usr_meta_value (content_id, ud_content_id, usr_meta_field_id,  usr_meta_value)
                                    values ($new_content_id, $new_ud_content_id, $new_meta_id,'$old_usr_meta_value')";
                        $db->exec($query);
                    }
                }
		
		//뉴스(촬영일자) <-> 제작(방영일) 간에 데이터 입력이 가능하도록 수정
		//제작에서 뉴스 자료요청일 경우
		if($old_meta_id == 897 || $old_meta_id == 795 || $old_meta_id == 1111) {// 뉴스(HD/SD/외신) 일 경우에는 제작쪽에 넣는걸로
		    $query = "select TO_CHAR(usr_meta_value) AS usr_meta_value from bc_usr_meta_value where usr_meta_field_id = $old_meta_id and content_id = $old_content_id";
		    $old_usr_meta_value = $db->queryOne($query);
		    $old_usr_meta_value = $db->escape($old_usr_meta_value);
		    $query ="insert into bc_usr_meta_value (content_id, ud_content_id, usr_meta_field_id,  usr_meta_value)
                                    values ($new_content_id, $new_ud_content_id, 995,'$old_usr_meta_value')";
		    $db->exec($query);
		}
		//뉴스에서 제작 자료 요청일 경우
		if($old_meta_id == 995) {// 뉴스(HD/SD/외신) 일 경우에는 제작쪽에 넣는걸로
		    $query = "select TO_CHAR(usr_meta_value) AS usr_meta_value from bc_usr_meta_value where usr_meta_field_id = $old_meta_id and content_id = $old_content_id";
		    $old_usr_meta_value = $db->queryOne($query);
		    $old_usr_meta_value = $db->escape($old_usr_meta_value);
		    $query ="insert into bc_usr_meta_value (content_id, ud_content_id, usr_meta_field_id,  usr_meta_value)
                                    values ($new_content_id, $new_ud_content_id, 897,'$old_usr_meta_value')";
		    $db->exec($query);
		}
            }
    }
    return true;
}

//리스토어 후 자료 이관, 자료 요청을 구분하여 작업을 추가하는 함수 (2013.06.19 임찬모)
function after_restore_task($content_id, $user_id)
{
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 4444 content_id===>'.$content_id."\r\n", FILE_APPEND);
    global $db;
    //bc_media의 delted_date 항목에 값이 있으면 작업 진행이 안되서 값이 있을 경우 없애도록 처리
    $query = "update bc_media set status = 0, deleted_date = ''  where content_id = $content_id and media_type = 'original' and status = 1 and deleted_date is not null";
    $db->exec($query);
    
    $query = "select request_data, ori_ud_content_id from bc_data_transfer where old_content_id = $content_id and request_restore = 'Y'";
    $data_info = $db->queryRow($query);
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 5555'."\r\n", FILE_APPEND);
    $is_request = $data_info['request_data'];
    $old_ud_content_id = $data_info['ori_ud_content_id'];
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 6666==>'.print_r($data_info, true)."\r\n", FILE_APPEND);
    //자료이관 작업
    if($is_request == 'N')
    {
        switch($old_ud_content_id){
            case 334 :
                $workflow_channel = 'data_transfer_news';
            break;
            case 202 :
                $workflow_channel = 'data_transfer_tape';
            break;
            case 374 :
                $workflow_channel = 'data_transfer_tape';
            break;
            case 394 :
                $workflow_channel = 'data_transfer_tape';
            break;
            case 314 :
                $workflow_channel = 'data_transfer_ingest';
            break;
            case 358 :
                $workflow_channel = 'data_transfer_nps';
            break;
        }
    }
    //자료요청 작업
    else if($is_request == 'R')
    {
        $query = "select code from bc_code where name = 'news_group'";
        $news_groups = $db->queryOne($query);
        $news_group_array = explode(",", $news_groups);
        foreach($news_group_array as $news)
        {
            if($old_ud_content_id == $news)
            {
                $workflow_channel = 'data_request_nps';
                break;
            }
            else
            {
                $workflow_channel = 'data_request_news';
            }
        }
        
        //리스토어 완료되었기때문에 request_restore 를 N으로 업데이트
        $query = "update bc_data_transfer set request_restore = 'N' where old_content_id = $content_id and request_data = 'R' and request_restore = 'Y'";
        $db->exec($query);
    }
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 7777 channel'.$workflow_channel."\r\n", FILE_APPEND);
    $file = $db->queryOne("select path from bc_media where content_id = $content_id and upper(media_type) = 'ORIGINAL'");
    $task_mgr = new TaskManager($db);
    file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/data_transfer_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 8888 content_id===>'.$content_id."\r\n", FILE_APPEND);
    $task_mgr->insert_task_query_outside_data($content_id, $workflow_channel, 1, $user_id, $file);
}


?>

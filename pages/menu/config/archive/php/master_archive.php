<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/nusoap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
        
global $db;
//MASTER 로 설정된 카테고리를 검색
$query = "select category_id from bc_category_env where lower(is_master) = 'on'";
$categories = $db->queryAll($query);

//각각의 카테고리에 대해서 작업을 진행
foreach($categories as $category)
{
    $category_id = $category['category_id'];
    //해당 카테고리 ID값을 갖는 컨텐츠 중에 삭제와 아카이브가 안된 파일의 컨텐츠 ID와 ud_content_id 값을 검색
    $query = "select content_id, ud_content_id, title, bs_content_id from bc_content where category_id = '$category_id' and is_deleted = 'N' and archive_date is null order by content_id asc";
    $contents = $db->queryAll($query);
   
    //검색된 컨텐츠 각각에 대해서 다음 작업 진행
    foreach($contents as $content)
    {
        $content_id = $content['content_id'];
        $ud_content_id = $content['ud_content_id'];
        $bs_content_id = $content['bs_content_id'];
        $title = $content['title'];
        // mtrl_typ 값을 가져옴 -> 해당 영상이 프로그램 영상인지 운행소재 영상인지를 구분하기 위해
        $query = "select to_char(t1.usr_meta_value) as usr_meta_value from bc_usr_meta_value t1, bc_usr_meta_field t2
                   where t1.content_id = $content_id and t2.ud_content_id = $ud_content_id and t2.usr_meta_field_title = 'BIS 구분'
                     and t1.ud_content_id = t2.ud_content_id and t1.usr_meta_field_id = t2.usr_meta_field_id";
        $bis_typ = trim($db->queryOne($query));
        //로그용
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/master_archive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id===> '.$content_id.' BIS 구분 ===> '.$bis_typ."\r\n", FILE_APPEND); 
        
        if($bis_typ == '프로그램' || $bis_typ == 'PGM')
        {
            $mtrl_typ = 'PGM';
        }
        else if($bis_typ == '운행소재' || $bis_typ == 'FLL')
        {
            $mtrl_typ = 'FLL';
        }
                   
        $wsdl = "http://121.134.112.223:5000/CXF/services/CisServicePort?wsdl";

        $client = new soapclient($wsdl);
        $client->soap_defencoding = 'UTF-8';
        $client->decode_utf8 = false;

        $err = $client->getError();
        if ($err) {
            echo '<p><b>Error: ' . $err . '</b></p>';
                exit;
        }

        $ns = "http://service.cxf.cis.com/";

        $params = array(
            'mam_id' => $content_id,
            'mtrl_typ'  => $mtrl_typ
        );

        $response = $client->call('IF_BIS_400', array('param' => $params), $ns);
        
        //CNF_CLF는 방송여부에 대한 결과값 ( Y : 확정(방송) , N : 미처리(대기), X : 취소(반려))
        $cnf_clf = $response['cnf_clf'];  
        
        file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/master_archive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id===> '.$content_id.' cnf_clf ===> '.$cnf_clf."\r\n", FILE_APPEND);            
        
        if($cnf_clf == 'Y')
        {
            //원본 영상의 카테고리 값을 가져옴
            $query = "select category_id from bc_content where content_id = $content_id";
            $ori_category_id = $db->queryOne($query);
            //위에서 얻은 원본의 카테고리 값을 토대로 BC_CATEGORY_ENV에 지정된 폴더(TR_CATEGORY) 값을 가져옴
            $query = "select tr_category from bc_category_env where category_id = $ori_category_id";
            $tr_category = $db->queryOne($query);
            //TARGET 카테고리 ID값이 있는경우에 한해서 카테고리 이동 그 이외의 경우에는 다른 동작 안함
            if(!empty($tr_category))
            {
                $category_full_path = '/0'.getCategoryFullPath($tr_category);
                file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/master_archive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id===> '.$content_id.' target_category ===> '.$tr_category.' category_full_path ===> '.$category_full_path."\r\n", FILE_APPEND);
                //TR_CATEGORY 값으로 BC_CONTENT의 카테고리 값을 업데이트
                $query = "update bc_content set category_id = $tr_category, category_full_path = '$category_full_path' where content_id = $content_id";
                $db->exec($query);               
            }
            else
            {
                //로그용
                file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/master_archive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id===> '.$content_id.' target_category is null'."\r\n", FILE_APPEND);
            }
       }
        else if($cnf_clf == 'N')
        {
            //N 인 경우에는 아직 아무런 액션이 취해지지 않은 것이기 때문에 대기
            continue;
        }
        else if($cnf_clf == 'X')
        {
            //수정등의 이유로 반려된 영상은 expired_date를 업데이트 한 후 설정된 기간에 따라 삭제됨(설정은 톰캣쪽에서)
            $cur_time = date('YmdHis');
            $query = "update bc_content set expired_date = '$cur_time', is_deleted = 'Y' where content_id = $content_id";
            $db->exec($query);
            //삭제에 대한 정보를 BC_LOG 테이블에 입력
            $description = "BIS 구분에 의한 자동 폐기";
            $cur_datetime = date('YmdHis');
            $log_id = getNextSequence();
            $query = "insert into bc_log 
                            (log_id, action, user_id, bs_content_id, link_table_id, ud_content_id, created_date, description )
                        values 
                            ($log_id, 'bis_delete', 'System', '$bs_content_id' , '$content_id', '$ud_content_id' , '$cur_datetime', '$description')";
            file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/master_archive_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] content_id===> '.$content_id.' query ===> '.$query."\r\n", FILE_APPEND);
            $db->exec($query);
        }
    }
}


function post_send($url, $metadatas) {

    $session = curl_init();

    curl_setopt($session, CURLOPT_HEADER,         false);

    //curl_setopt($session, CURLOPT_HTTPHEADER,     $header);

    curl_setopt($session, CURLOPT_URL,            $url);

    curl_setopt($session, CURLOPT_POSTFIELDS,     join('&', $metadatas));

    curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($session, CURLOPT_POST,           1);


$response = curl_exec($session);

curl_close($session);

return $response;
}
?>

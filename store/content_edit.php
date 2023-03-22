<?php
use \Proxima\core\Request;
use \Proxima\core\Session;
use Api\Types\DefinedGroups;
use Api\Types\ContentStatusType;

require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/searchengine/solr/searcher.class.php');
Session::init();
// session_start();





$user_id = $_SESSION['user']['user_id'];

try {
    if (empty($user_id) || $user_id == 'temp') throw new Exception("로그인해주세요.");
    $content_id = $_POST['k_content_id'];
    $ud_content_id = $_POST['k_ud_content_id'];
    $title = $_POST['k_title'];
    $container_id = $_POST['k_meta_field_id'];
    $modified_time = date('YmdHis');

    $k_type2_1 = $_POST['k_type2_1'];
    $k_type3_1 = $_POST['k_type3_1'];
    $k_type3_2 = $_POST['k_type3_2'];
    $k_type3_3 = $_POST['k_type3_3'];
    $k_type4_1 = $_POST['k_type4_1'];
    $k_type5_1 = $_POST['k_type5_1'];
    $k_type6_1 = $_POST['k_type6_1'];
    $k_type7_1 = $_POST['k_type7_1'];

    //  20160216 hoang.nv : get old value for history edit metadata
    $query = "SELECT * 
            FROM 
                bc_usr_meta_field 
            WHERE 
                ud_content_id='{$ud_content_id}' 
                AND container_id IS NOT NULL 
                AND depth=0 ORDER BY show_order";
    $containerList = $db->queryAll($query);
    $container_array = array();
    foreach ($containerList as $container_key => $container) {
        
        // 컨테이너 아이디
        $container_id_tmp = $container['container_id'];
        // 컨테이너 명
        $container_title = addslashes($container['usr_meta_field_title']);
        //$rsFields = content_meta_value_list( $content_id, $content['ud_content_id'] , $container_id_tmp );
        $rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr' , $ud_content_id, $container_id_tmp, $content_id);
        // /**
        //  * $rsFields는 아래 형태임
        // Array
        // (
        //     [0] => Array
        //         (
        //             [ud_content_id] => 1
        //             [usr_meta_field_id] => 105
        //             [show_order] => 3
        //             [usr_meta_field_title] => 방송일시
        //             [usr_meta_field_type] => broad_datetime
        //             [is_required] => 0
        //             [is_editable] => 1
        //             [is_show] => 1
        //             [is_search_reg] => 1
        //             [default_value] => 
        //             [container_id] => 91
        //             [depth] => 1
        //             [meta_group_type] => 
        //             [desciption] => 
        //             [smef_dm_code] => 
        //             [summary_field_cd] => 0
        //             [usr_meta_field_code] => BROAD_DATETIME
        //             [is_social] => 0
        //             [num_line] => 0
        //             [value] => 20171122000000
        //         )
        // )
        //  */     
        foreach ($rsFields as $f) {
            // if($f['type'] == 'listview')
            // {
            //     $listview = getMetaMultiXML($content_id);
            // }
            array_push($container_array, $f);
        }
    }
    $container_array_old_value = array();
    // 기존 메타데이터 container_array_old_value에 백업 해놓음
    foreach ($container_array as $index => $value) {
        //기존 V3의 경우 usr_meta_field_code를 썼으나 현재는 usr_meta_field_code를 쓰므로 해당 부분 수정
        //2018.01.11 Alex
        $container_array_old_value[strtolower($value['usr_meta_field_code'])] = $value['value'];
    }

    //특수문자 제거 타이틀
    $dis_title = preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}\s]/i", "", $_POST['k_title']);

    //업데이트전의 콘텐츠의 타이틀을 얻어 온다.
    $content_info = $db->queryRow("SELECT c.*, m.path, m.delete_date
                                     FROM bc_content c,bc_media m
                                    WHERE c.content_id=m.content_id
                                      AND media_type='original'
                                      AND c.content_id='$content_id'");
    $container_array_old_value['k_title'] = $content_info['title'];
    //카테고리는 변경 전 후 필드명이 다르므로 따로 가자..k_category_id/ c_category_id
	if($_POST['k_category_id'] != $_POST['c_category_id'])
	{
		$container_array_old_value['c_category_id'] = $_POST['k_category_id'];
	}
    //print_r($container_array_old_value); exit;
    $fieldKey = array();
    $fieldValue = array();

    // 필드 목록 배열
    $metaFieldInfo = MetaDataClass::getMetaFieldInfo('usr', $ud_content_id);    
    
    // 테이블명 구하기
    $tablename = MetaDataClass::getTableName('usr', $ud_content_id);
    
    // POST로 넘어온 값들중에 실제 사용자 메타에 대한 배열 얻기(k_content_id 같은 값 제외)
    $metaValues = MetaDataClass::getDefValueRender('usr', $ud_content_id, $_POST);

    //미디어 생성자 아이디
    $registerUserId = MetaDataClass::registerUserId($content_id);
    // 관리자 이면 Y 아니면 빈값 또는 널
    $isAdmin = Session::getUser('is_admin');
    $userId = Session::getUser('user_id');

    //오픈전 기능제한
    // if (class_exists('\Api\Services\ContentService') ) {
    //     $contentService = new \Api\Services\ContentService(app()->getContainer());
    //     $statusMeta = $contentService->findStatusMeta($content_id);
    //     $contentUsrMeta = $contentService->findContentUsrMeta($content_id);
        
    //     //ASIS 이관 메타데이터 
    //     if( !empty($statusMeta->bfe_video_id) || !empty($contentUsrMeta->hmpg_cntnts_id) || !empty($contentUsrMeta->ehistry_id)  ){
    //         throw new Exception("이관 콘텐츠는 오픈전까지 수정기능이 제한됩니다.");
    //     }
    // }

    $groups = getGroups($user_id);

    
        //권한 제어 삭제 2020-01-07
    // if( $registerUserId === $userId  || $isAdmin === 'Y' || in_array( DefinedGroups::INGEST_GROUP, $groups )  || in_array( DefinedGroups::META_GROUP, $groups ) ){   
    //     //  //수정 권한 제어
    //     // if( $content_info['status'] == ContentStatusType::COMPLETE ){
    //     //     //승인 콘텐츠
    //     //     //관리자 권한만 수정 가능
    //     //     if( $isAdmin != 'Y' || in_array('14', $groups ) ){
    //     //         throw new Exception("승인된 콘텐츠는 수정기능이 제한됩니다.");
    //     //     }
    //     // }        
    // }else{
    //     throw new Exception("등록자 또는 관리자만이 수정할 수 있습니다.");
    // }
    

    foreach ($metaValues as $k => $v) {
        array_push($fieldKey, $k);
        array_push($fieldValue, $v);
    }

    // 이 페이지에서는 업데이트만 하면 됨. Insert를 해야되는 상황이면 이미 오류 콘텐츠이다.

    $content_update_field_array = array();

    //수정일자 기본추가
    $content_update_field_array['last_modified_date'] = $modified_time;

    if ( ! is_null($_POST['c_category_id']) && !strstr($_POST['c_category_id'], 'xnode')) {
        $category_id = empty($_POST['c_category_id']) ? 0 : $_POST['c_category_id'];
        $category_full_path = empty($category_id) ? '/0' : getCategoryFullPath($_POST['c_category_id']);
        $content_update_field_array ['category_id'] = $category_id;
        $content_update_field_array ['category_full_path'] = $category_full_path;
    }

    if ( ! is_null($_POST['k_title'])) {
        $title = $_POST['k_title'];
        //$title = preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}\s]/i", "", $title);		//특수 문자 제거
        $content_update_field_array['title'] = $title;
    }

    if (!is_null($_POST['c_expired_date'])) {
        $expired_date = empty($_POST['c_expired_date']) ? '9999-12-31' : $_POST['c_expired_date'];
        $expired_date = date('YmdHis', strtotime($expired_date));
        $content_update_field_array ['expired_date'] = $expired_date;
    }

    $content_update_field_array ['updated_at'] = date('YmdHis');
    $content_update_field_array ['updated_user_id'] = $user_id;

    if (class_exists('\Api\Services\ContentService') && class_exists('\Api\Services\ApiJobService') ) {
        $contentService = new \Api\Services\ContentService(app()->getContainer());
        $user = auth()->user();
        $contentService->updateUsingArray($content_id, $content_update_field_array, [], [], $metaValues , $user);
    }else{
        $query = $db->update("bc_content", $content_update_field_array, " content_id=$content_id ");
      
        $query = $db->UpdateQuery($tablename, $fieldKey, $fieldValue, " usr_content_id=$content_id ");

        $r = $db->exec($query);
    }

    $easyCertiBaseURL = env('EASYCERTI_API_URL');
    $easyCertiClient = new \Api\Modules\EasyCertiClient($easyCertiBaseURL);
    $contentUsrMeta = $easyCertiClient->getMetadata($content_id);

    $param = $easyCertiClient->makeEasyCertiArg($contentUsrMeta, $user_id);

    $response = $easyCertiClient->postPersonalInformationDetection($param);
    $easyCertiData = json_decode($response->getContents(), true);

    if (isset($easyCertiData['privacy_summary']['IsPriv']) && $easyCertiData['privacy_summary']['IsPriv'] == '1') {
        //# 검출내역이 있을 시, INDVDLINFO_AT = 'Y'
        $insert = $easyCertiClient->saveINDVDLINFO($easyCertiData['privacy_detail']['content']['content_list'], $content_id);
    } else {
        //# 검출내역이 없을 시, INDVDLINFO_AT = 'N', INDEVELINFO 테이블 row 값 null 처리
        $easyCertiClient->makeINDVDLINFORowDataNull($content_id);
    }

    // custom metadata save logic after metadata saved.
    if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
        $postItem = Request::post('usr_item_list');
        // if( !empty($postItem) ) {
        //     $items = json_decode($postItem, true);             
        //     \ProximaCustom\models\metadata\Item::saveItems($content_id, $items);
        // }

        //\ProximaCustom\core\MetadataManager::actionAfterSaveMetadata($content_id);
    }    

    //    $tap_name = $db->queryOne("select usr_meta_field_title from bc_usr_meta_field where usr_meta_field_id='$container_id' ");
    $arr_temp = array('content_info' => $content_info, 'content_update_field_array' => $content_update_field_array, 'fieldKey' => $fieldKey, 'fieldValue' => $fieldValue);
    $description = '['.json_encode($content_info) . ', ' . json_encode($content_update_field_array). ', ' . json_encode($fieldKey) . ' , '. json_encode($fieldValue).']';

    $meta_changed_log_inserted = 'N';
    $bc_log_id = '';
    foreach ( $_POST as $k=>$v ){

        
        //2019-03-08 이승수. 메타변경이력에 날짜형식이 14자리로 남으니 여기서 수정해줌.
        if(strcmp(trim($container_array_old_value[$k]),$v) && array_key_exists($k, $container_array_old_value))
        {
            $old_v = $container_array_old_value[$k];
			
            $detail_log_id = getSequence('SEQ_BC_LOG_DETAIL_ID');
            
            //2019-03-08 이승수. 메타변경이력에 날짜형식이 14자리로 남으니 여기서 수정해줌.
            foreach($container_array as $f) {
                if('usr_'.strtolower($f['usr_meta_field_code']) == $k && $f['usr_meta_field_type'] == 'datefield') {
                    if(!empty($v)) {
                        $v = date('Y-m-d', strtotime($v));
                    }
                    if(!empty($old_v)) {
                        $old_v = date('Y-m-d', strtotime($old_v));
                    }
                }
            }

            $dateArray = [
                'embg_relis_dt',
                'brdcst_de',
                'prod_de'
            ];
            
            if(in_array($k,$dateArray)){
                if($v == date('Y-m-d',strtotime($old_v))) continue;
            }else{
                //2019-03-08 이승수. 날짜형식은 POST로 넘어온값과 DB의 값 형식이 다를 수 있으므로 위에서 같은 양식으로 맞춘 후 다시 판단
                if($v == $old_v) continue;
            }

            //2019-03-11 이승수. BC_LOG에는 한번만 넣음
            if($meta_changed_log_inserted == 'N') {
                $meta_changed_log_inserted = 'Y';
                insertLog('edit', $user_id, $content_id, '메타수정됨');
                $bc_log_id_q = "SELECT log_id
                            FROM  bc_log
                            WHERE action = 'edit'
                            AND content_id = {$content_id}
                            AND ud_content_id = {$ud_content_id}
                            ORDER BY created_date DESC";
                $bc_log_id = $db->queryOne($bc_log_id_q);

                // bc_content_status의 수정횟수
                $reviewQuery = "SELECT  edit_count
                                FROM    bc_content_status
                                WHERE   content_id = {$content_id}
                                ";
                $reviews = $db->queryAll($reviewQuery);
                if(!empty($reviews)) {
                    // 콘텐츠 등록 승인의 승인 횟수
                    $editCount = $reviews[0]['edit_count'];
                    if($editCount === null) {
                        $editCount = 0;
                    } 
                    $newCount = $editCount + 1;
                    $db->exec("UPDATE bc_content_status
                                    SET edit_count = {$newCount}
                                    WHERE content_id = {$content_id}");
                }
            }
			if($k == 'c_category_id')
			{
				$old_full_path = getCategoryFullPath($old_v);
				$old_v = getCategoryPathTitle(substr($old_full_path, 2), ' > ');
				$new_full_path = getCategoryFullPath($v);
				$v = getCategoryPathTitle(substr($new_full_path, 2), ' > ');
				//echo $container_array_old_value[$k]."<br/>"; 
			}
            $insert_data = array(
                'DETAIL_LOG_ID' => $detail_log_id,
                'LOG_ID' => $bc_log_id,
                'ACTION' => 'edit',
                'USR_META_FIELD_CODE' => $k,
                'NEW_CONTENTS' => utf8_strcut($v,1000),
                'OLD_CONTENTS' => utf8_strcut($old_v,1000)
            );
            $r =  $db->insert('BC_LOG_DETAIL', $insert_data);
        }
    }

    searchUpdate($content_id);


    //외부 배포용 스케줄 등록
    if (class_exists('\Api\Services\ContentService') && class_exists('\Api\Services\ApiJobService') ) {
        $contentService = new \Api\Services\ContentService(app()->getContainer());
        $apiJobService = new \Api\Services\ApiJobService(app()->getContainer());
        $contentMap = $contentService->getContentForPush($content_id);
        if ($contentMap) {
            $apiJobService->createApiJob('Api\Services\ContentService', 'update', $contentMap, $content_id);
        }
    }

    echo json_encode(array(
        'success' => true,
        'msg' => $msg
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}


function EditContentCode($POST)
{
    global $db;

    $content_id = $POST['k_content_id'];//	4431818

    $formbaseymd = $POST['k_formbaseymd'];
    $subprogcd = $POST['k_subprogcd'];
    $progcd = $POST['k_progcd'];
    $brodymd = $POST['k_brodymd'];
    $medcd = $POST['k_medcd'];

    $check = $db->queryOne("select content_id from content_code_info where content_id='$content_id'");

    if (empty($check)) {
        $content_update_field_array = array();
        $content_update_value_array = array();
        $content_update_field_array[] = " content_id ";
        $content_update_value_array[] = " '$content_id' ";

        if (!empty($formbaseymd)) {
            $content_update_field_array[] = " formbaseymd ";
            $content_update_value_array[] = " '$formbaseymd' ";
        }

        if (!empty($subprogcd)) {
            $content_update_field_array[] = " subprogcd ";
            $content_update_value_array[] = " '$subprogcd' ";
        }

        if (!empty($progcd)) {
            $content_update_field_array[] = " progcd ";
            $content_update_value_array[] = " '$progcd' ";
        }

        if (!empty($brodymd)) {
            $content_update_field_array[] = " brodymd ";
            $content_update_value_array[] = " '$brodymd' ";
        }

        if (!empty($medcd)) {
            $content_update_field_array[] = " medcd ";
            $content_update_value_array[] = " '$medcd' ";
        }

        $r = $db->exec("insert into content_code_info ( " . join(',', $content_update_field_array) . " ) values ( " . join(',', $content_update_value_array) . " )");
    } else {

        $content_update_field_array = array();
        $content_update_field_array[] = " content_id='$content_id' ";

        if (!empty($formbaseymd)) {
            $content_update_field_array[] = " formbaseymd='$formbaseymd' ";
        }

        if (!empty($subprogcd)) {
            $content_update_field_array[] = " subprogcd='$subprogcd' ";
        }

        if (!empty($progcd)) {
            $content_update_field_array[] = " progcd='$progcd' ";
        }

        if (!empty($brodymd)) {
            $content_update_field_array[] = " brodymd='$brodymd' ";
        }

        if (!empty($medcd)) {
            $content_update_field_array[] = " medcd='$medcd' ";
        }

        $r = $db->exec("update content_code_info set " . join(',', $content_update_field_array) . " where content_id='$content_id' ");
    }
}

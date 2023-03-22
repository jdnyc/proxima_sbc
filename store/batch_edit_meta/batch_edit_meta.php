<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/db.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/searchengine/solr/searcher.class.php');

use \Proxima\core\Request;

$user_id = $_SESSION['user']['user_id'];

try {

    $ud_content_id = $_POST['ud_content_id'];
    $content_ids = json_decode($_POST['content_ids'], true);
    $modified_time = date('YmdHis');

    foreach ($content_ids as $content_id) {

        $containerList = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='{$ud_content_id}' and container_id is not null and depth=0 order by show_order");
        $container_array = array();
        foreach ($containerList as $container_key => $container) {
            // 컨테이너 아이디
            $container_id_tmp = $container['container_id'];
            // 컨테이너 명
            $container_title = addslashes($container['usr_meta_field_title']);
            //$rsFields = content_meta_value_list( $content_id, $content['ud_content_id'] , $container_id_tmp );
            $rsFields =  MetaDataClass::getFieldValueforContaierInfo('usr' , $ud_content_id, $container_id_tmp, $content_id);

            /**
            * $rsFields는 아래 형태임
            Array
            (
                [0] => Array
                    (
                        [ud_content_id] => 1
                        [usr_meta_field_id] => 105
                        [show_order] => 3
                        [usr_meta_field_title] => 방송일시
                        [usr_meta_field_type] => broad_datetime
                        [is_required] => 0
                        [is_editable] => 1
                        [is_show] => 1
                        [is_search_reg] => 1
                        [default_value] => 
                        [container_id] => 91
                        [depth] => 1
                        [meta_group_type] => 
                        [desciption] => 
                        [smef_dm_code] => 
                        [summary_field_cd] => 0
                        [usr_meta_field_code] => BROAD_DATETIME
                        [is_social] => 0
                        [num_line] => 0
                        [value] => 20171122000000
                    )
            )
            */              
            foreach ($rsFields as $f) {
                if($f['type'] == 'listview')
                {
                    $listview = getMetaMultiXML($content_id);
                }
                array_push($container_array, $f);
            }
        }
        $container_array_old_value = array();
        // 기존 메타데이터 container_array_old_value에 백업 해놓음
        foreach ($container_array as $index => $value) {
            $container_array_old_value[$value['usr_meta_field_code']] = $value['value'];
        }

        //특수문자 제거 타이틀
        $dis_title = preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}\s]/i", "", $_POST['k_title']);

        //업데이트전의 콘텐츠의 타이틀을 얻어 온다.
        $content_info = $db->queryRow("SELECT c.*, coalesce(m.status, '0') media_status, m.path, m.delete_date
                                            FROM view_content c,bc_media m
                                        WHERE c.content_id=m.content_id
                                            AND media_type='original'
                                            AND c.content_id='$content_id'");
        $container_array_old_value['k_title'] = $content_info['title'];
        $is_update_user_meta = false;

        foreach ($_POST as $k => $v) {
            $v = $db->escape($v);
            if (preg_match('/^k\_|^c\_/', $k) || strstr($k, 'ext')) continue;

            // meta_field 가 날짜형식일경우 14자리로 변환 by 이성용 2011-2-7
            if($k != "content_ids" && $k != "ud_content_id" && $k != "k_title" && $k != "c_category_id"){
                $is_update_user_meta = true;
                $query = "SELECT 
                            usr_meta_field_type 
                        FROM 
                            bc_usr_meta_field 
                        WHERE 
                            usr_meta_field_code='$k' ";
                $date_field_chk = $db->queryOne($query);
            }

            if ($date_field_chk == 'datefield') {
                if (!empty($v)) {
                    $v = date('YmdHis', strtotime($v));
                }
            }

            if ($date_field_chk == 'checkbox') {
                if (!empty($v)) {
                    $v = '1';
                }
            }
        }

        if($is_update_user_meta){
            
            $fieldKey = array();
            $fieldValue = array();

            // 필드 목록 배열
            $metaFieldInfo = MetaDataClass::getMetaFieldInfo('usr', $ud_content_id);
            
            // 테이블명 구하기
            $tablename = MetaDataClass::getTableName('usr', $ud_content_id);

            // POST로 넘어온 값들중에 실제 사용자 메타에 대한 배열 얻기(k_content_id 같은 값 제외)
            $metaValues = MetaDataClass::getDefValueRender('usr', $ud_content_id, $_POST);
            foreach ($metaValues as $k => $v) {                
                array_push($fieldKey, $k);
                array_push($fieldValue, $v);
            }

            // 이 페이지에서는 업데이트만 하면 됨. Insert를 해야되는 상황이면 이미 오류 콘텐츠이다.
            $query = $db->UpdateQuery($tablename, $fieldKey, $fieldValue, " usr_content_id=$content_id ");            
            
            $r = $db->exec($query);
        }
        $content_update_field_array = array();

        //수정일자 기본추가
        $content_update_field_array[] = " last_modified_date='$modified_time' ";
    	
        if ( ! is_null($_POST['c_category_id']) && !strstr($_POST['c_category_id'], 'xnode')) {
            $category_id = empty($_POST['c_category_id']) ? 0 : $_POST['c_category_id'];
            $category_full_path = empty($category_id) ? '/0' : getCategoryFullPath($_POST['c_category_id']);
            $content_update_field_array [] = " category_id='$category_id' ";
            $content_update_field_array [] = " category_full_path='$category_full_path' ";
        }

        if ( ! is_null($_POST['k_title'])) {
            $title = $db->escape($_POST['k_title']);
            //$title = preg_replace("/[#\&\+%@=\/\\\:;,\.'\"\^`~|\!\?\*$#<>\[\]\{\}\s]/i", "", $title);		//특수 문자 제거
            $content_update_field_array[] = " title='$title' ";
        }

        if (!is_null($_POST['c_expired_date'])) {
            $expired_date = empty($_POST['c_expired_date']) ? '9999-12-31' : $_POST['c_expired_date'];
            $expired_date = date('YmdHis', strtotime($expired_date));
            $content_update_field_array [] = " expired_date='$expired_date' ";
        }

        $update_content_query = "update bc_content set " . join(',', $content_update_field_array) . " where content_id='$content_id'";
        $r = $db->exec($update_content_query);

        // custom metadata save logic after metadata saved.
        if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\MetadataManager')) {
            $postItem = Request::post('usr_item_list');
            if($postItem != null) {
                $items = json_decode($postItem, true);    
                \ProximaCustom\models\metadata\Item::saveItems($content_id, $items);
            }
            
            \ProximaCustom\core\MetadataManager::actionAfterSaveMetadata($content_id);
        }

        $arr_temp = array('content_info' => $content_info, 'content_update_field_array' => $content_update_field_array, 'fieldKey' => $fieldKey, 'fieldValue' => $fieldValue);
        $description = '['.json_encode($content_info) . ', ' . json_encode($content_update_field_array). ', ' . json_encode($fieldKey) . ' , '. json_encode($fieldValue).']';
        foreach ( $_POST as $k=>$v ){
        	if(strcmp(trim($container_array_old_value[$k]),$v) && array_key_exists($k, $container_array_old_value))
        	{
        		insertLog('edit', $user_id, $content_id, $description);
        		break;
        	}
        	
        }

        $query_row = "SELECT  *
                FROM  bc_log
                WHERE action = 'edit'
                AND content_id = {$content_id}
                AND ud_content_id = {$ud_content_id}
                ORDER BY created_date DESC";
        $v_row = $db->queryOne($query_row);

        foreach ( $_POST as $k=>$v ){
            if(strcmp(trim($container_array_old_value[$k]),$v) && array_key_exists($k, $container_array_old_value))
                {
                    $detail_log_id = getSequence('SEQ_BC_LOG_DETAIL_ID');
    				
    				$insert_data = array(
    					'DETAIL_LOG_ID' => $detail_log_id,
    					'LOG_ID' => $v_row,
    					'ACTION' => 'edit',
    					'USR_META_FIELD_ID' => $k,
    					'NEW_CONTENTS' => $v,
    					'OLD_CONTENTS' => $container_array_old_value[$k]
    				);
    				$r =  $db->insert('BC_LOG_DETAIL', $insert_data);
                }
        }

        searchUpdate($content_id);

    }
    echo json_encode(array(
        'success' => true,
       'msg' => $msg,
    ));
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}

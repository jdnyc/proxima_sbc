<?php

use Api\Types\UdContentId;

/*
plugin register web
Content: The page that the application calls to register metadata in a json manner
filepath: path to the uploaded file
// type: Registration type
*/

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');

session_start();
try {
  
    $receive = $_REQUEST['jsondata'];
    $receive = str_replace("\\\\n", "\n", urldecode($receive));
    $receive = str_replace("\n", "\\n", $receive);
    $decodeData = json_decode(trim($receive) , true);
    
    // canUse 조건에 맞으면 공개여부 N 이 된다.
    if(canUse($decodeData)) 
        $decodeData['metadata'][0]['othbc_at'] = "N";
    
    if (!$decodeData) {
        throw new Exception('Decoding error');
    }
    
    
    $filepath = $db->escape($decodeData['original_filename']);
    $filepath_arr = explode(':', $filepath);

    $return_result = [];
    $count = count($filepath_arr);
    for ($i=0; $i < $count; $i++) {

        $metadatas = $decodeData['metadata'];
        if( is_array($metadatas) ){
            foreach($metadatas as $km => $kv){
                $metadatas[$km] = \Proxima\core\Unit::normalizeUtf8String($kv);
            }
        }else{
            $metadatas = \Proxima\core\Unit::normalizeUtf8String($metadatas);
        }
        
        $filepath = $db->escape($decodeData['filepath']);
        if (empty($filepath)) {
            $filepath = $db->escape($decodeData['filename']);
        }
        if (empty($filepath)) {
            $filepath = $db->escape($decodeData['original_filename']);
        }
        $filepath = \Proxima\core\Unit::normalizeUtf8String($filepath);
        
        
        $type = $decodeData['type'];
        $channel = $decodeData['channel'];
        $user_id = $decodeData['user_id'];
        $ud_content_id =  $decodeData['ud_content_id'];
        $flag = $decodeData['flag'];
        $server_ip = $decodeData['server_ip'];
        //Options for splitting metadata registration / operations
        $regist_type = $decodeData['regist_type'];
        $task_id = $decodeData['task_id'];
        $target_content_id = $decodeData['content_id'];
        $filePathInfo = new \Api\Core\FilePath($filepath);

        $filepath = str_replace('\\', '/', $filepath);
        $filepath = trim($filepath, '/');
        $filepath_array = explode('/', $filepath);
        $filename = array_pop($filepath_array);
        $filename_array = explode('.',$filename);
        $file_ext = array_pop($filename_array);

        

        // Data for registering sequence task
        $arr_sequence_info = array(
            'sequence_file' => $decodeData['sequence_file'],
            'sequence_count' => $decodeData['sequence_count'],
            'sequence_proxy_file' => $decodeData['sequence_proxy_file'],
            'sequence_file_size' => $decodeData['sequence_file_size'],
            'sequence_file_resolution' => $decodeData['sequence_file_resolution'],
            'sequence_proxy_file_size' => $decodeData['sequence_proxy_file_size'],
            'sequence_ext' => $file_ext
        );
        
        
        //SONCM make select multiple contents
        
        // Append filename or number to title
        // $title_suffix = trim(mb_convert_encoding($decodeData['title_suffix'], 'utf-8', 'utf-16le'));
        // $en = mb_detect_encoding($decodeData['title_suffix']);
        $title_suffix = trim($decodeData['title_suffix']);
        $title = trim($metadatas[0]['k_title']);
        // if ($decodeData['IsFileNameToTitle'] == 1) {
        //     $title = $title_suffix;
        // } else {
        //     $title = makeTitleWithSuffix($title, $title_suffix);
        // }
        $flag_arr = explode('?', $flag);
        $flag = $flag_arr[0];
        if($channel == '') $channel = $flag;
        $category_id = $metadatas[0]['c_category_id'];
        $topic_id = $metadatas[0]['k_topic_content_id'];
        if (empty($ud_content_id)) $ud_content_id =  $metadatas[0]['k_ud_content_id'];
        // Automatically generate moving material categories

        if($_SERVER['HTTP_HOST'] == 'nps.ktv.go.kr'){
            $channel = $channel.'_web';
        }
        $cur_time = date('YmdHis');
        //2015-11-19 bs_content_id  추가
        //$bs_content_id = MOVIE;
        $bs_content_id = $db->queryOne("SELECT BS_CONTENT_ID FROM BC_UD_CONTENT WHERE UD_CONTENT_ID = ".$ud_content_id." ");
        //Storage info by ud_content_id
        $storage_info = array();
        $arr_storage = $db->queryAll("
            SELECT	A.US_TYPE, B.*
            FROM	BC_UD_CONTENT_STORAGE A
                    FULL JOIN 
                    BC_STORAGE B
                    ON(A.STORAGE_ID=B.STORAGE_ID)
            WHERE	A.UD_CONTENT_ID=".$ud_content_id."
        ");
        foreach($arr_storage as $stor) {
            $storage_info[$stor['us_type']] = $stor['path'];
        }
        
        //$metaValues = getMetaValues($metadatas);
        $metaValues = MetaDataClass::getUserMetaValuesFromPost( $metadatas );
            //기본 데이터유형 변환
        $metaValues = MetaDataClass::getDefValueRender('usr' , $ud_content_id , $metaValues);
    
        
        $metaMultiValues = getMetaMultiValues($metadatas);
 
        $task = new TaskManager($db);
        // TODO 그룹정보에서 가져오도록 수정 필요
        if (in_array(GROUP_INGEST, getUserOfGroup($user_id))) {
            $task->set_priority(400);
        }
        //for make path
        $time_path = date("Y")."/".date("m")."/".date("d");
        // 타입이 지정이 안되있거나 메타데이터일 경우 신규 등록

        //콘텐츠 유형 확장자 체크
        if( in_array( $ud_content_id , [UdContentId::MASTER,UdContentId::NEWS]) && $filePathInfo->fileExt == 'mov' ){
            $channel = $channel.'_conv';
        }
      
        if (empty($regist_type) || $regist_type == 'meta') {
            if (!empty($target_content_id) && ( $flag == 'fcpx')){
                $content_id = $target_content_id;
            } else {
                $content_id = getSequence('SEQ_CONTENT_ID');
            }
            $group_type = 'I';
            $is_group = $decodeData['is_group'];
        
            if ($is_group == 'Y') {
                $group_type = 'C';
                $group_count = $decodeData['index'];
                //$parent_content_id = $decodeData['parent_id'];
                $filename_parent = $decodeData['filename_parent'];

                if ( ! empty($parent_content_id)) {
                    $parent_id = $content_id;
                } else {
                    $parent_id = $content_id;
                }
                if ($filepath_arr[$i] == $filename_parent ) {
                    $group_type = 'G';
                    $parent_content_id = $content_id;
                }
            }
         
            //KTV 미디어ID 발급 등록시 발급한다            
            $contentService = new \Api\Services\ContentService(app()->getContainer());
            $metaValues['media_id'] = $contentService->getMediaId($bs_content_id);
          
        
            $manageNoShotList = new  Api\Support\Custom\Material\ShotList($metadatas[0]);
            $manageNoShotList->saveShotList($content_id);
         

                
            $statusMeta = [];
            //주조 전송
            if( !empty($metadatas[0]['k_send_to_main']) ){
                $statusMeta['mcr_trnsmis_sttus'] = 'request';
            }
            //부조 전송
            if( !empty($metadatas[0]['k_send_to_sub']) && !empty($metadatas[0]['k_send_to_sub_news'])) {
                $statusMeta['scr_trnsmis_sttus'] = 'request';
                $statusMeta['scr_news_trnsmis_sttus'] = 'request';
                $statusMeta['scr_trnsmis_ty'] = 'all';
            } else if( !empty($metadatas[0]['k_send_to_sub']) && empty($metadatas[0]['k_send_to_sub_news'])) {
                $statusMeta['scr_trnsmis_sttus'] = 'request';
                $statusMeta['scr_trnsmis_ty'] = 'ab';
            } else if( empty($metadatas[0]['k_send_to_sub']) && !empty($metadatas[0]['k_send_to_sub_news'])) {
                $statusMeta['scr_news_trnsmis_sttus'] = 'request';
                $statusMeta['scr_trnsmis_ty'] = 'news';
            }
            //확인
            if( !empty($metadatas[0]['k_qc_confirm']) ){
                $statusMeta['qc_cnfrmr'] = $user_id;     
                $statusMeta['qc_cnfirm_at'] = 1;     
            }

            if( !empty($metadatas[0]['k_archv_trget_at']) ){
                $statusMeta['archv_trget_at'] = $metadatas[0]['k_archv_trget_at'];  
            }

            $contentMeta = [
                'category_id' => $category_id,         
                'bs_content_id' => $bs_content_id,
                'ud_content_id' => $ud_content_id,
                'content_id' => $content_id,
                'title' => $title,
                'reg_user_id' => $user_id,        
                'is_group' => $group_type
            ];

            if ($group_type == 'C') {
                $contentMeta['parent_content_id'] = $parent_content_id;
            }else if( $group_type == 'G' ){
                $contentMeta['group_count'] = $parent_content_id;
            }

            // $statusMeta에 사용금지 데이터
            if($metaValues['use_prhibt_at'] == 'Y') {
                $statusMeta['use_prhibt_set_dt'] = date("YmdHis");
                $statusMeta['use_prhibt_set_user_id'] = $user_id;
                $statusMeta['use_prhibt_set_resn'] = '사용금지설정-'.$metaValues['use_prhibt_cn'];
            }

            $content = $contentService->createUsingArray($contentMeta, $statusMeta, [], $metaValues );

            if($metaValues['use_prhibt_at'] =='Y'){
                $description = '사용금지설정-'.$metaValues['use_prhibt_cn'];            
                $logData = [
                    'action' => 'edit',
                    'description' => $description,
                    'content_id' => $content->content_id,
                    'bs_content_id' => $content->bs_content_id,
                    'ud_content_id' => $content->ud_content_id
                ];
                $user = new \Api\Models\User();
                $user->user_id = $user_id;
                $logService = new \Api\Services\LogService(app()->getContainer());
                $r = $logService->create($logData, $user);
            }

            //insertContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id, $title , $user_id, $topic_id, $group_type, $group_count, $parent_content_id);
            
            //insertMetaValues($metaValues, $content_id, $ud_content_id);

            //원본 파일명 등록
            $sys_metafields = MetaDataClass::getFieldNametoIdMap('sys',$bs_content_id);
            $sysInfo = array();
            $ori_filename = $sys_metafields['sys_ORI_FILENAME'];
            $sysInfo[$ori_filename] = $db->escape($filename);
            InterfaceClass::insertSysMetaValus($sysInfo, $content_id, $bs_content_id);

            $workflowInfo = $task->getWorkflowInfo($channel, $content_id);

            if(in_array($workflowInfo['job_code'], array(ARIEL_TRANSFER_FS, ARIEL_TRANSFER_FTP, ARIEL_CATALOG, ARIEL_THUMBNAIL_CREATOR, 91 ))) {
                //Normal transfer job. Check first job of workflow. Include catalog job for sequence.
                //For SEQUENCE, need original, proxy path.(proxy file made by FileIngest)
                if($bs_content_id == SEQUENCE) {
                    $seq_medias = array(
                        array(
                            'type' => 'original',
                            'path' => $time_path.'/'.$content_id,
                            'ingestid' => $channel
                        ),
                        array(
                            'type' => 'proxy',
                            'path' => $time_path.'/'.$content_id.'/'.$content_id.'/Proxy',
                            'ingestid' => $channel
                        )
                    );
                    updateMediaMeta($seq_medias, $content_id);
                }
            } else {
                //Transfer by FileIngest. Job_code is 69, 89, ... etc.
                $task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $content_id.'.'.$file_ext);
                $task_list_info = $task->get_task_list(null);
                if($channel == 'fcpx'){
                    $fcp_map_r = $db->exec("
                                    INSERT INTO TB_FCP_MAP
                                        (CONTENT_ID, TASK_ID)
                                    VALUES
                                        ($content_id, $task_id)
                                ");
                }
                if ( ! empty($task_list_info)) {
                    $workflow = $db->queryRow("select USER_TASK_NAME,TASK_WORKFLOW_ID from bc_task_workflow where register = '$channel'");
                    $interface_id = $task->InsertInterface($workflow['user_task_name'], 'USER', $user_id, 'USER', $user_id, $content_id, 'regist', $workflow['task_workflow_id']);
                    foreach ($task_list_info as $list_info) {
                        $task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
                    }
                }
            }
        }
        if (empty($regist_type) || $regist_type == 'task') {
            if ( ! empty($target_content_id) && ( $regist_type == 'task')) {
                $content_id = $target_content_id;
            }
            // todo 세션 확인 필요
            if (empty($user_id)) {
                $user_id = 'system';
            }
            // 그룹이면 등록던 파일이름 등록
            if (isGroupContent($content_id)) {
                insertMediaMetadata($content_id, 'raw', $decodeData['original_filename'], $channel);
            }
            if($bs_content_id == SEQUENCE) {
                //SEQUENCE need BC_CONTENT / BC_MEDIA update.
                updateSequenceContent($content_id, $arr_sequence_info);
                //Also, system metadata update
                $sysMetaValues = array(
                    4802298 => $arr_sequence_info['sequence_file_resolution'], //RESOLUTION
                    4802299 => $arr_sequence_info['sequence_ext'] //IMAGE_FORMAT
                );
                MetaDataClass::insertSysMeta($sysMetaValues, $bs_content_id , $content_id );
                $task_id = $task->start_task_workflow($content_id, $channel, $user_id);
                $filename = $filepath;
            } else {
                if (empty($task_id)) {
                    $task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $filename);
                    $task_list_info = $task->get_task_list(null);
                    if ( ! empty($task_list_info)) {
                        $workflow = $db->queryRow("select USER_TASK_NAME,TASK_WORKFLOW_ID from bc_task_workflow where register = '$channel'");
                        $interface_id = $task->InsertInterface($workflow['user_task_name'], 'USER', $user_id, 'USER', $user_id, $content_id , 'regist', $workflow['task_workflow_id']);
                        foreach ($task_list_info as $list_info) {
                            $task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
                        }
                    }
                } else {
                    //if task_id exists, transfer by Client module. So mark as completed.
                    $task_info  = $db->queryRow("select t.media_id, t.task_id , t.assign_ip ,  t.type  from bc_task t where  t.task_id=$task_id");
                    $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><Request />');
                    $request->addChild("TaskID", $task_id );
                    $request->addChild("TypeCode", $task_info['type']);
                    $request->addChild("Progress", '100' );
                    $request->addChild("Status", 'complete' );
                    $request->addChild("Ip", $_SERVER['REMOTE_ADDR']);
                    $request->addChild("Log", 'Transfer by FileIngest end.');
                    $sendxml =  $request->asXML();

                    $task = new TaskManager($db);
                    $result = $task->Post_XML_Soket($_SERVER['HTTP_HOST'], '/workflow/update_task_status.php', $sendxml );
                    $result_content = substr( $result , strpos( $result, '<'));
                    $result_content_xml = InterfaceClass::checkSyntax($result_content);

                    if($result_content_xml[data]->Result != 'success') throw new Exception( $result_content_xml[data]->Result, 107);
                }
            }
        }

        // 시퀀스 파일(SxS, P2, 기타...) 등록시 접미어 적용
        // if ( ! empty($title_suffix) && ! empty($target_content_id)) {
        //     $db->exec("update bc_content set title='". $db->escape($title) . "' where content_id=".$target_content_id);
        // }

        if($bs_content_id == SEQUENCE) {
            $workflowInfo['source'] = $time_path."/".$content_id;
            $workflowInfo['source_proxy'] = $time_path.'/'.$content_id.'/'.$content_id.'/Proxy';
            $workflowInfo['source_root'] = $storage_info['highres'];
            $workflowInfo['source_root_proxy'] = $storage_info['lowres'];
        } else {
            $workflowInfo['source'] = '';
            $workflowInfo['source_proxy'] = '';
        }

        searchUpdate($content_id);

        array_push($return_result, array(
            'success' => true,
            'content_id' => $content_id,
            'channel' => $channel
        ));
    }//Soncm end for

    echo json_encode(array(
            'success' => true,
            'data' => $return_result
        ) );
} catch (Exception $e) {
    
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ) );
}

function insertContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id, $title, $user_id, $topic_id, $group_type, $group_count, $parent_content_id) {
    global $db;

    $category_full_path = getCategoryFullPath($category_id);
    $cur_time           = date('YmdHis');

    $expired_date = '99991231';

    //제목
    $title = trim($title);
    if (empty($title)){
        $title ='no title';
    }

    //2016-02-24 INSERT QUERY 수정
	$insert_data = array(
            'CATEGORY_ID' => $category_id,
            'CATEGORY_FULL_PATH' => $category_full_path,
            'BS_CONTENT_ID' => $bs_content_id,
            'UD_CONTENT_ID' => $ud_content_id,
            'CONTENT_ID' => $content_id,
            'TITLE' => $title,
            'REG_USER_ID' => $user_id,
            'CREATED_DATE' => $cur_time,
            'STATUS' => INGEST_READY,
            'EXPIRED_DATE' => $expired_date,
            'IS_GROUP' => $group_type
    );
    if( $group_type == 'C' ){
		$insert_data['PARENT_CONTENT_ID'] = $parent_content_id;
	}else if( $group_type == 'G' ){
		$insert_data['GROUP_COUNT'] = $parent_content_id;
	}

    $db->insert('BC_CONTENT', $insert_data);

    $action = 'regist';
    $description = 'web register';
    insertLog($action, $user_id, $content_id, $description);

    return $content_id;
}

function insertMediaMetadata($content_id, $type, $filename, $channel) {
    global $db;
    $db->insert('BC_MEDIA', array(
        'CONTENT_ID' => $content_id,
        'CREATED_DATE' => date('YmdHis'),
        'PATH' => $filename,
        'MEDIA_TYPE' => $type,
        'STORAGE_ID' => 0,
        'REG_TYPE' => $channel,
        'EXPIRED_DATE' => '99981231000000'
    ));
    

}

function insertBaseContentValue($content_id, $content_type_id) {
    global $db;

    //$r = $db->exec("delete from content_value where content_id=".$content_id);
    $system_fields = $db->queryAll("select * from BC_SYS_META_FIELD where BS_CONTENT_ID ='$content_type_id' order by SHOW_ORDER ");

    foreach($system_fields as $field)
    {

        $content_field_id   = $field['sys_meta_field_id'];
        $value              = '';
        //시작타임코드 강제로 01:00:00:00로 변경 fcp
        if($content_field_id == '6073034')
        {
            $value  = '00:00:00:00';
        }

        $r = $db->exec("insert into BC_SYS_META_VALUE (CONTENT_ID,SYS_META_FIELD_ID,SYS_META_VALUE) values('$content_id', '$content_field_id',  '$value')");

    }
    return true;
}

function  insertMetaValues($metaValues, $content_id, $meta_table_id ,$update = null ) {
    global $db;
    //$r = $db->exec("delete from meta_value where content_id=".$content_id);

    $fieldKey = array();
    $fieldValue = array();

    //필드 목록 배열
    $metaFieldInfo = MetaDataClass::getMetaFieldInfo ('usr' , $meta_table_id );

    //필드의 id => name
    $fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $meta_table_id );

    //테이블 명
    $tablename = MetaDataClass::getTableName('usr', $meta_table_id );

    //기본 데이터유형 변환
    $metaValues = MetaDataClass::getDefValueRender('usr' , $meta_table_id , $metaValues);
    
    foreach ($fieldNameMap as $usr_meta_field_id => $name ) {
        $name = strtolower($name);
        $value = $metaValues[$name];
        $value = $db->escape($value);
        array_push($fieldKey, $name );
        array_push($fieldValue, $value);
    }
    
    if (MetaDataClass::isNewMeta($table_type, $meta_table_id , $content_id)) {

        // 신규 등록
        array_push($fieldKey, 'usr_content_id' );
        array_push($fieldValue, $content_id );
		$insert_arr = array_combine($fieldKey,$fieldValue);
        $db->insert($tablename ,$insert_arr);
    } else {

        //업데이트
		$update_arr = array_combine($fieldKey,$fieldValue);
        $db->update($tablename ,$update_arr, "usr_content_id=$content_id" );
    }
    
    return true;
}

function insertContentCodeInfo($metaValues, $content_id,  $is_update = null )
{
    global $db;
    $medcd = $metaValues[0]['k_medcd'];
    $brodymd = $metaValues[0]['k_brodymd'];
    $formbaseymd = $metaValues[0]['k_formbaseymd'];
    $progcd = $metaValues[0]['k_progcd'];
    $subprogcd = $metaValues[0]['k_subprogcd'];

    $datagrade = $metavalues[0]['k_datagrade'];
    $storterm = $metavalues[0]['k_storterm'];

    if(!$is_update){        //신규일때

        //등록시 전송처 코드 입력 2013-02-15 이성용
        $register_type = 'E';//편집 전송 코드

        $r = $db->exec ("insert into CONTENT_CODE_INFO  (CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
    }else{
        //업데이트
        $r = $db->exec ("update CONTENT_CODE_INFO set medcd='$medcd',brodymd='$brodymd' where content_id='$content_id' ");
    }

    return true;
}

function insertContentCodeInfo2($metaValues, $content_id,  $is_update = null )
{
    global $db;
    $medcd = $metaValues[0]['k_medcd'];
    $brodymd = $metaValues[0]['k_brodymd'];
    $formbaseymd = $metaValues[0]['k_formbaseymd'];;
    $progcd = $metaValues[0]['k_progcd'];
    $subprogcd = $metaValues[0]['k_subprogcd'];

    $datagrade = $metavalues[0]['k_datagrade'];
    $storterm = $metavalues[0]['k_storterm'];

    if(!$is_update){        //신규일때

        //등록시 전송처 코드 입력 2013-02-15 이성용
        $register_type = 'I';//편집 전송 코드

        $r = $db->exec ("insert into CONTENT_CODE_INFO  (CONTENT_ID,MEDCD,PROGCD,SUBPROGCD,BRODYMD,FORMBASEYMD,DATAGRADE,STORTERM , REGISTER_TYPE ) values ('$content_id', '$medcd','$progcd','$subprogcd','$brodymd','$formbaseymd','$datagrade','$storterm' , '$register_type' )");
    }else{
        //업데이트
        $r = $db->exec ("update CONTENT_CODE_INFO set medcd='$medcd',brodymd='$brodymd' where content_id='$content_id' ");
    }

    return true;
}

function getMetaValues( $metadatas )
{
    $metaValues = array();
    foreach($metadatas as $metadata)
    {
        foreach($metadata as $key => $value)
        {
            if( strstr($key, 'usr_') ){
                $metaValues[$key] = $value;
            }
        }
    }
    return $metaValues;
}

function getMetaMultiValues($metadatas)
{

    foreach($metadatas as $metadata)
    {
        if( !empty($metadata['multi']) )
        {
            return $metadata['multi'];
        }
    }

    return array();
}


//들어온 메타데이터에서 인자로 넘오온 항목을 찾아서 값을 반환
function findUsrMetaValue($metadatas, $usr_meta_field_id)
{
    foreach($metadatas as $meta)
    {
        foreach($meta as $meta_field => $meta_value)
        {
            if($meta_field == $usr_meta_field_id)
            {
                return $meta_value;
            }
        }
    }
    return '';
}

function createMaterialCategory($params) {

    $name = $params['4778411'];
    $code = $params['4778410'];

    $category = isExistsCategory($code);

    if ( ! empty($category)) {
        return $category['category_id'];
    } else {
        return addCategory($name, $code);
    }
}

function isExistsCategory($code) {
    global $db;

    return $db->queryRow("select * from bc_category where code='$code'");
}

function addCategory($name, $code) {
    global $db;

    $category_id = getSequence('SEQ_BC_CATEGORY_ID');

    $db->exec("
        insert into BC_CATEGORY (CATEGORY_ID ,PARENT_ID, CATEGORY_TITLE, CODE, NO_CHILDREN)
        values ($category_id, -2, '$name', '$code', 1)
    ");

    return $category_id;
}

function makeTitleWithSuffix($title, $suffix) {
    if ( ! empty($title) && empty($suffix)) {
        $_title = $title;
    } else if ( ! empty($title) && ! empty($suffix)) {
        $_title = $title . '_' . $suffix;
    } else if (empty($title) && ! empty($suffix)) {
        $_title = $suffix;
    } else {
        $_title = 'No Title';
    }

    return $_title;
}

function getUserOfGroup($user_id) {
    global $db;

    $groups = array();

    $result = $db->queryAll("
        select b.member_group_id
          from bc_member a, bc_member_group_member b
        where a.user_id= '$user_id'
          and a.member_id=b.member_id
    ");

    foreach ($result as $item) {
        array_push($groups, $item['member_group_id']);
    }

    return $groups;
}

function isGroupContent($content_id) {
    global $db;

    $group_type = $db->queryOne("SELECT IS_GROUP FROM BC_CONTENT WHERE CONTENT_ID = ".$content_id);
    if ($group_type == 'G' || $group_type == 'C') {
        return true;
    } else {
        return false;
    }
}

function updateMediaMeta($medias, $content_id)
{
	global $db;

	$r = $db->exec("delete from bc_media where content_id=".$content_id);

	foreach ( $medias as $media => $value)
	{
		$media_id 	= getSequence('SEQ_MEDIA_ID');
		$type		= $value['type'];
		$path		= reConvertSpecialChar($value['path']);
		$filesize	= $value['filesize'];
		$register	= $value['ingestid'];
		$cur_time	= date('YmdHis');

		//스토리지 테이블에 각 타입의 대표 스토리지 정의 입력이 필요함.
		$storage_info 	= get_storage_info($type); // 함수에 하드코딩 되어잇슴.
		$storage_path 	= $storage_info['path'];
		$storage_id 	= 6;//$storage_info['storage_id'];

//		$insert_media_query = $db->exec("insert into bc_media ".
//				"(CONTENT_ID, MEDIA_ID, STORAGE_ID, MEDIA_TYPE, PATH, FILESIZE, CREATED_DATE, REG_TYPE, STATUS, DELETE_DATE, FLAG) ".
//			"values ".
//				"($content_id, $media_id, $storage_id, '$type', '$path', $filesize, '$cur_time', '$register', '0',null,null)");
		$data_arr = array(
			'CONTENT_ID' => $content_id,
			'MEDIA_ID' => $media_id,
			'STORAGE_ID' => $storage_id,
			'MEDIA_TYPE' => $type,
			'PATH' => $path,
			'FILESIZE' => $filesize,
			'CREATED_DATE' => $cur_time,
			'REG_TYPE' => $register,
			'STATUS' => 0,
			'DELETE_DATE' => '',
			'FLAG' => ''
		);
		$db->insert('BC_MEDIA', $data_arr);
	}
}

function updateSequenceContent ($content_id, $arr_sequence_info) {
	global $db;

	$sequence_file = $arr_sequence_info['sequence_file'];
	$sequence_count = $arr_sequence_info['sequence_count'];
	$sequence_proxy_file = $arr_sequence_info['sequence_proxy_file'];
	$sequence_file_size = $arr_sequence_info['sequence_file_size'];
	$sequence_file_resolution = $arr_sequence_info['sequence_file_resolution'];
	$sequence_proxy_file_size = $arr_sequence_info['sequence_proxy_file_size'];

	if($sequence_count == '') $sequence_count = 'null';
	if($sequence_file_size == '') $sequence_file_size = 'null';
	if($sequence_proxy_file_size == '') $sequence_proxy_file_size = 'null';

	//BC_CONTENT UPDATE
	$content_update_query = $db->exec("
								UPDATE	BC_CONTENT
								SET		IS_GROUP = 'G', GROUP_COUNT = $sequence_count
								WHERE	CONTENT_ID = $content_id
							");

	//BC_MEDIA UPDATE
	$medias = $db->queryAll("
					SELECT	MEDIA_TYPE, PATH
					FROM	BC_MEDIA
					WHERE	CONTENT_ID = $content_id
					AND		MEDIA_TYPE IN ('original', 'proxy')
				");

	foreach($medias as $media) {
		$media_type = $media['media_type'];
		$path = $media['path'];

		switch($media_type) {
			case 'original' :
				$ori_path = $path.'/'.$sequence_file;
				$db->exec("
						UPDATE	BC_MEDIA
						SET		PATH = '$ori_path',
								FILESIZE = $sequence_file_size
						WHERE	MEDIA_TYPE = '$media_type'
						AND		CONTENT_ID = $content_id
					");
			break;
			case 'proxy' : 
				$proxy_path = $path.'/'.$sequence_proxy_file;
				$db->exec("
						UPDATE	BC_MEDIA
						SET		PATH = '$proxy_path',
								FILESIZE = $sequence_proxy_file_size
						WHERE	MEDIA_TYPE = '$media_type'
						AND		CONTENT_ID = $content_id
					");
			break;
			default:
				//nothing
			break;
		}
	}
}
/**
 * 공개여부 기본 결정
 */
function canUse($meta){
    /**
     * brdcst_stle_se : 방송형태    구매코드-> B
     * use_prhibt_at : 사용금지여부
     * othbc_at : 공개여부
     * cpyrhtown : 저작권자
     * cpyrht_cn : 저작권 내용
     * 
     * 1. 방송형태가 구매이고
     * 2. 저작권이 없고
     * 3. 사용금지가 Y 일떄
     * 4. 등록 승인전 공개여부는 N 이 된다.
     */
    $metaArr = $meta['metadata'][0];
    if(($metaArr['brdcst_stle_se'] === "B") || ($metaArr['cpyrhtown'] === "") || ($metaArr['othbc_at'] === "Y")){
        // $metaArr['use_prhibt_at'] = "N";
        return true;
    };
    return false;
}
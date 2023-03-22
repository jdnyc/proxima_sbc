<?php
//set_time_limit(0);
session_start();

//내용 : 어플리케이션에서 json방식으로 메타데이터를 등록하기 위해 호출하는 페이지
//post data
//metadata : json인코딩 되어 있음
//filepath : 업로드 된 파일경로
//type: 등록유형

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php'); //2011.12.17 작업 매니저 클래스 추가
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');

try {

    $response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

    //web에서 post 로 등록시 2013-01-31 이성용
    if ( ! empty($_REQUEST['metadata'])) {

        $metadatas = json_decode(urldecode($_REQUEST['metadata']) , true);
        $filepath = $db->escape(urldecode($_REQUEST['filepath']));
        if(empty($filepath)){
            $filepath  = $db->escape(urldecode($_REQUEST['filename']));
        }
        $type = urldecode($_REQUEST['type']);
        $user_id = $_REQUEST['user_id'];
        $channel = $_REQUEST['channel'];
        $flag = $_REQUEST['flag'];
        $server_ip  = $_REQUEST['server_ip'];

        //메타데이터 등록 / 작업을 분할하기 위한 옵션
        $regist_type = $_REQUEST['inserttype'];
        $target_content_id = $_REQUEST['content_id'];
    } else {

        // 소켓통신 json 데이터
        $receive = file_get_contents('php://input');
//        $receive = urldecode($receive);
        $receive = str_replace("\\\\n", "\n", urldecode($receive));
        $receive = str_replace("\n", "\\n", $receive);

        $decodeData = json_decode(trim($receive) , true);
        if ( ! $decodeData) throw new Exception('디코딩 오류');

		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 1 decodeData ===> '.print_r($decodeData, true)."\r\n", FILE_APPEND);

        $metadatas = $decodeData['metadata'];
        $filepath = $db->escape($decodeData['filepath']);
        if (empty($filepath)) {
            $filepath = $db->escape($decodeData['filename']);
        }
        $type = $decodeData['type'];
        $channel = $decodeData['channel'];
        $user_id = $decodeData['user_id'];
        $ud_content_id =  $decodeData['ud_content_id'];
        $flag = $decodeData['flag'];
        $server_ip = $decodeData['server_ip'];

        //메타데이터 등록 / 작업을 분할하기 위한 옵션
        $regist_type = $decodeData['regist_type'];
        $task_id = $decodeData['task_id'];
        $target_content_id = $decodeData['content_id'];
    }

    // 제목에 파일명 또는 넘버링 덧붙이기
    // $title_suffix = trim(mb_convert_encoding($decodeData['title_suffix'], 'utf-8', 'utf-16le'));
    // $en = mb_detect_encoding($decodeData['title_suffix']);

    $title_suffix = trim($decodeData['title_suffix']);
    $title = trim($metadatas[0]['k_title']);

    if ($decodeData['IsFileNameToTitle'] == 1) {
        $title = $title_suffix;
    } else {
        $title = makeTitleWithSuffix($title, $title_suffix);
    }

	$channel = 'edius';

    $flag_arr = explode('?', $flag);
    $flag = $flag_arr[0];

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 2 flag ===> '.$flag.' | '.$channel."\r\n", FILE_APPEND);

    $filepath = str_replace('\\', '/', $filepath);
    $filepath = trim($filepath, '/');
    $filepath_array = explode('/', $filepath);
    $filename = array_pop($filepath_array);

    $category_id = $metadatas[0]['c_category_id'];
    $topic_id = $metadatas[0]['k_topic_content_id'];
    if (empty($ud_content_id)) $ud_content_id =  $metadatas[0]['k_ud_content_id'];

    // 운행소재 카테고리 자동 생성
    if ($ud_content_id == '4000290') {
        $category_id = createMaterialCategory($metadatas[0]);
    } else if ($ud_content_id == '4000287' && $topic_id == 'program') {
        $params = json_encode($metadatas[0]['program_info']);
        $outter_response = Requests::post('http://127.0.0.1/pages/menu/setting/save.php', array(), $params);
        $category_id = $outter_response->body;
    }


	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 3 flag ===> '.$flag.' | '.$channel."\r\n", FILE_APPEND);

    $cur_time = date('YmdHis');
	//2015-11-19 bs_content_id  추가
	//$bs_content_id = MOVIE;
    $bs_content_id = mapping_channel($ud_content_id, 'bs_content_id');

    if (empty($channel)) {
        $channel = 'mac_plugin';

        if ($flag == 'fcp2') {
            $channel = 'fcp_register';
        }

        if ( ! empty($server_ip)) {
            $channel .= '_'.$server_ip;
        }
    }

    $metaValues = getMetaValues($metadatas);
    $metaMultiValues = getMetaMultiValues($metadatas);


	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 4 flag ===> '.$flag.' | '.$channel."\r\n", FILE_APPEND);

    $task = new TaskManager($db);
    // TODO 그룹정보에서 가져오도록 수정 필요
    if (in_array(GROUP_INGEST, getUserOfGroup($user_id))) {
        $task->set_priority(400);
    }

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 5 flag ===> '.$flag.' | '.$channel."\r\n", FILE_APPEND);


    // 타입이 지정이 안되있거나 메타데이터일 경우 신규 등록
    if (empty($regist_type) || $regist_type == 'meta') {
        $content_id = getSequence('SEQ_CONTENT_ID');
        $group_type = 'I';
        $is_group = $decodeData['is_group'];
        if ($is_group == 'Y') {
            $group_type = 'C';
            $group_count = $decodeData['index'];
            $parent_content_id = $decodeData['parent_id'];
            if ( ! empty($parent_content_id)) {
                $parent_id = $parent_content_id;
            } else {
                $parent_id = $content_id;
            }
            if ($group_count == '1') {
                $group_type = 'G';
                $parent_content_id = $content_id;
            }
        }

       insertContent($metaValues, $content_id, $category_id, $bs_content_id, $ud_content_id, $title , $user_id, $topic_id, $group_type, $group_count, $parent_content_id);

        insertMetaValues($metaValues, $content_id, $ud_content_id);

        $workflowInfo = $task->getWorkflowInfo($channel, $content_id);
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

//        if (empty($task_id)) {
//            $task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $filename);
//            $task_list_info = $task->get_task_list(null);
//
//            if ( ! empty($task_list_info)) {
//                $workflow = $db->queryRow("select USER_TASK_NAME,TASK_WORKFLOW_ID from bc_task_workflow where register = '$channel'");
//                $interface_id = $task->InsertInterface($workflow['user_task_name'], 'USER', $user_id, 'USER', $user_id, $content_id , 'regist', $workflow['task_workflow_id']);
//                foreach ($task_list_info as $list_info) {
//                    $task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
//                }
//            }
//        }
    }

    // 시퀀스 파일(SxS, P2, 기타...) 등록시 접미어 적용
    if ( ! empty($title_suffix) && ! empty($target_content_id)) {
        $db->exec("update bc_content set title='". $db->escape($title) . "' where content_id=".$target_content_id);
    }

    if ( ! empty($_REQUEST['metadata'])) {
        $response_text = json_encode(array(
            'success' => true,
            'msg' => 'ok',
            'content_id' => $content_id,
            'task_id' => $task_id,
            'task_list_info' => $workflowInfo
        ));
    } else {

        // todo 제거 - 채널에이 작업을 위해 임시
        if (empty($task_id)) {
//            $task_id = '308003';
        }

        $response->result->addAttribute('success', 'true');
        $response->result->addAttribute('msg', 'ok');
        $response->result->addAttribute('content_id', $content_id);
        $response->result->addAttribute('task_id', $content_id);//여기 Task_id는 content_id로... plugin_updateStatus.php 에서 사용함
        $response->result->addChild('success', 'true' );
        $response->result->addChild('msg', 'ok');
        $response->result->addChild('content_id', $content_id);
        $response->result->addChild('parent_id', $parent_id);
        $response->result->addChild('task_id', $content_id);//여기 Task_id는 content_id로... plugin_updateStatus.php 에서 사용함
        $task_list_info = $response->result->addChild('task_list_info');

        if ( ! empty($workflowInfo)) {
            foreach ($workflowInfo as $key => $info) {
                $task_list_info->addChild($key, htmlspecialchars($info));
            }

            // todo 제거 - 채널에이 작업을 위해 임시
            $task_list_info->addChild('material_id', str_pad(rand(1, 100000), 5, "0", STR_PAD_LEFT));
        }

        $response_text = $response->asXML();
    }

    searchUpdate($content_id);

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 3 response_text ===> '.print_r($response_text, true)."\r\n", FILE_APPEND);

    echo $response_text;
} catch (Exception $e) {
    if ( ! empty($_REQUEST['metadata'])) {
        $response_text = json_encode(array(
            'success' => false,
            'msg' => $e->getMessage()
        ));
    }else{
        $response->result->addAttribute('success', 'false');
        $response->result->addAttribute('msg', $e->getMessage());
        $response->result->addChild('success', 'false' );
        $response->result->addChild('msg', $e->getMessage());

        $response_text = $response->asXML();
    }

    echo $response_text;
}

function insertContent($metaValues, $content_id, $category_id, $bs_content_id,
                            $ud_content_id, $title, $user_id, $topic_id, $group_type, $group_count, $parent_content_id) {
    global $db;

    $category_full_path = getCategoryFullPath($category_id);
    $cur_time           = date('YmdHis');

    $expired_date = '9999-12-31';

    //제목
    $title = trim($title);
    if (empty($title)){
        $title ='no title';
    }


	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] 3 response_text ===> '.$category_id.' | '.$category_full_path.' | '.$bs_content_id.' | '.$bs_content_id.' | '.$ud_content_id.' | '.$content_id.' | '.$title.' | '.$user_id.' | '.$cur_time.' | '.$cur_time.' | '.$expired_date.' | '.$group_count.' | '.$group_type.' | '.$parent_content_id.' | '.INGEST_READY.' | '.$category_id.' | '.$category_id.' | '."\r\n", FILE_APPEND);
	//$parent_content_id = '1';

    $db->insert('BC_CONTENT', array(
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
            'IS_GROUP' => $group_type,
            'GROUP_COUNT' => $group_count,
            'PARENT_CONTENT_ID' => $parent_content_id
    ));
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] after insert content ===> '."\r\n", FILE_APPEND);
    $action = 'regist';
    $description = 'nle register';
    insertLog($action, $user_id, $content_id, $description);

    return $content_id;
}

function insertMediaMetadata($content_id, $type, $filename, $channel) {
    global $db;
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before insert media ===> '."\r\n", FILE_APPEND);
    $db->insert('BC_MEDIA', array(
        'CONTENT_ID' => $content_id,
        'CREATED_DATE' => date('YmdHis'),
        'PATH' => $filename,
        'MEDIA_TYPE' => $type,
        'STORAGE_ID' => 0,
        'REG_TYPE' => $channel,
        'EXPIRED_DATE' => '99981231000000'
    ));

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] after insert media ===> '."\r\n", FILE_APPEND);
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
        /* 메타데이터 코드로 메타처리 되도록 변경 2019.01.27 skc
		$value = $metaValues[$usr_meta_field_id];
        */
		$v_temp = strtolower($fieldNameMap[$usr_meta_field_id]);
		$value = $metaValues[$v_temp];
        $value = $db->escape($value);
        array_push($fieldKey, $name );
        array_push($fieldValue, $value);
    }

    if (MetaDataClass::isNewMeta($table_type, $meta_table_id , $content_id)) {

        // 신규 등록
        array_push($fieldKey, 'usr_content_id' );
        array_push($fieldValue, $content_id );
        $query = $db->InsertQuery($tablename ,$fieldKey, $fieldValue);
    } else {

        //업데이트
        $query = $db->UpdateQuery($tablename ,$fieldKey, $fieldValue, "usr_content_id='$content_id'" );
    }
    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/json_register_edius'.date('Ymd').'.html', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').']'.$query."\n", FILE_APPEND);

    $db->exec($query);

    return true;
}

function insertContentCodeInfo($metaValues, $content_id,  $is_update = null )
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
            $metaValues[$key] = $value;
			/* 메타데이터 코드로 메타처리 되도록 변경 2019.01.27 skc
			if( is_numeric($key) ){
                $metaValues[$key] = $value;
            }*/
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

function mapping_channel($ud_content_id, $type=null){
	global $db;
	$chanel = '';

	$bs_content_id = $db->queryOne("SELECT BS_CONTENT_ID FROM BC_UD_CONTENT WHERE UD_CONTENT_ID = ".$ud_content_id." ");
	switch($bs_content_id){
		case 506:
			$channel = 'filer_movie';
		break;
		case 515:
			$channel = 'filer_audio';
		break;
		case 518:
			$channel = 'filer_image';
		break;
		case 57057:
			$channel = 'filer_doc';
		break;
	}

	if(empty($type)){
		return $channel;
	}else{
		return $bs_content_id;
	}


}
?>

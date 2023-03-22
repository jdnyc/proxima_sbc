<?php

use Api\Types\UdContentId;
/**
 * MAM등록 페이지.
 * 사용하는곳 : FileIngest
 */
session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');

try {

	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] php input ===> '.file_get_contents('php://input')."\r\n", FILE_APPEND);
@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] _REQUEST ===> '.print_r($_REQUEST, true)."\r\n", FILE_APPEND);

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
        //@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] urldecode receive ===> '.urldecode($receive)."\r\n", FILE_APPEND);
        // 기존 코드
        // $receive = str_replace("\\\\n", "\n", urldecode($receive));
        // $receive = str_replace("\n", "\\n", $receive);
        
        // 메타에 역슬래시가 들어오면 받을 수 있도록 수정
        $receive = urldecode($receive);
        // $receive = str_replace("\\\\n", "\n", $receive);
        // $receive = str_replace("\n", "\\n", $receive);
        // $receive = str_replace("\\", "\\\\",$receive);
        // $receive = str_replace("\\\\n", "\n", $receive);
        // $receive = str_replace("\n", "\\n", $receive);

		$decodeData = json_decode(trim($receive) , true);
		if ( ! $decodeData) throw new Exception('디코딩 오류');
		@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] php input Decode ===> '.print_r($decodeData, true)."\r\n", FILE_APPEND);
        $metadatas = $decodeData['metadata'];
                
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
		/*원본파일명을 넣기 위해서 추가 - 기존데이터는 아래쪽 소스에서 가공되기때문에 따로 뺐음 2018.01.31. Alex*/
		$org_filename = $decodeData['original_filename'];

		//메타데이터 등록 / 작업을 분할하기 위한 옵션
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

		//시퀀스 task 등록용 데이터
		$arr_sequence_info = array(
			'sequence_file' => $decodeData['sequence_file'],
			'sequence_count' => $decodeData['sequence_count'],
			'sequence_proxy_file' => $decodeData['sequence_proxy_file'],
			'sequence_file_size' => $decodeData['sequence_file_size'],
			'sequence_file_resolution' => $decodeData['sequence_file_resolution'],
			'sequence_proxy_file_size' => $decodeData['sequence_proxy_file_size'],
			'sequence_ext' => $file_ext
		);
	}

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] after if ===> '.$content_id.','.$regist_type."\r\n", FILE_APPEND);

	// 제목에 파일명 또는 넘버링 덧붙이기
	// $title_suffix = trim(mb_convert_encoding($decodeData['title_suffix'], 'utf-8', 'utf-16le'));
	// $en = mb_detect_encoding($decodeData['title_suffix']);

	$title_suffix = trim($decodeData['title_suffix']);
	$title = trim($metadatas[0]['k_title']);
	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before title ===> '.$content_id.','.$title."\r\n", FILE_APPEND);

	/**
	 * 추후 옵션으로 사용여부 처리 할 예정 2018.1.5 Alex
	 */

	// if ($decodeData['IsFileNameToTitle'] == 1) {
	//	 $title = $title_suffix;
	// } else {
	//	 $title = makeTitleWithSuffix($title, $title_suffix);
	// }

	$flag_arr = explode('?', $flag);
	$flag = $flag_arr[0];
	if($channel == '') $channel = $flag;

	$category_id = $metadatas[0]['c_category_id'];
	$topic_id = $metadatas[0]['k_topic_content_id'];
	if (empty($ud_content_id)) $ud_content_id =  $metadatas[0]['k_ud_content_id'];

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before Custom ===> '.$content_id.','.$regist_type."\r\n", FILE_APPEND);

	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\IngestCustom')) {		
		$category_id = \ProximaCustom\core\IngestCustom::getCategoryByUdContent($ud_content_id, $category_id);		
	}

	if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\IngestCustom')) {		
		$channel = \ProximaCustom\core\IngestCustom::getTaskChannelByUdContent($ud_content_id, $channel);		
	}	

	$cur_time = date('YmdHis');
	//2015-11-19 bs_content_id  추가
	//$bs_content_id = MOVIE;
	$bs_content_id = $db->queryOne("SELECT BS_CONTENT_ID FROM BC_UD_CONTENT WHERE UD_CONTENT_ID = ".$ud_content_id." ");

	//CJO, image가 아닌 그룹이면 파일명으로 맞춰줌. 단일등록으로도 바꿔줌.
	if($bs_content_id != IMAGE && $decodeData['is_group'] == 'Y') {
		// $original_filename = $decodeData['original_filename'];
		// $original_filename_arr = explode('.', $original_filename);
		// array_pop($original_filename_arr);
		// $title = join('.', $original_filename_arr);
	    $decodeData['is_group'] = 'N';
	}

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

    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before meta ===> '.$content_id.','.$regist_type."\r\n", FILE_APPEND);
    

    //콘텐츠 유형 확장자 체크
    if( in_array( $ud_content_id , [UdContentId::MASTER,UdContentId::NEWS]) && $filePathInfo->fileExt == 'mov' ){
        $channel = 'fileingest_conv';
    }

	// 타입이 지정이 안되있거나 메타데이터일 경우 신규 등록
	if (empty($regist_type) || $regist_type == 'meta') {
		if( ! empty($target_content_id) && ( $flag == 'fcpx')){
		   $content_id = $target_content_id;
		}else{
		   $content_id = getSequence('SEQ_CONTENT_ID');
        }

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


        //KTV 미디어ID 발급 등록시 발급한다            
        $contentService = new \Api\Services\ContentService(app()->getContainer());
        $metaValues['media_id'] = $contentService->getMediaId($bs_content_id);
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] insertContent title ===> '.$content_id.','.$title."\r\n", FILE_APPEND);
		
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

		$workflowInfo = $task->getWorkflowInfo($channel, $content_id);

		// @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] job_code ===> '.$workflowInfo['job_code']."\r\n", FILE_APPEND);
		// @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] bs_content_id ===> '.$bs_content_id."\r\n", FILE_APPEND);
		// @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] SEQUENCE ===> '.SEQUENCE."\r\n", FILE_APPEND);

		if(in_array($workflowInfo['job_code'], array(ARIEL_TRANSFER_FS, ARIEL_TRANSFER_FTP, ARIEL_CATALOG, ARIEL_THUMBNAIL_CREATOR, 91))) {
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
                //@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] seq_medias ===> '.$seq_medias."\r\n", FILE_APPEND);
				updateMediaMeta($seq_medias, $content_id);
			}
		} else {
			//Transfer by FileIngest. Job_code is 69, 89, ... etc.
			$task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $content_id.'.'.$file_ext);
			$task_list_info = $task->get_task_list(null);

			//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] task_id ===> '.$task_id."\r\n", FILE_APPEND);
			if($channel == 'fcpx'){
				//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/fcp_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] channel ===> '.$channel."\r\n", FILE_APPEND);

                $fcp_map_r = $db->exec("
								INSERT INTO TB_FCP_MAP
									(CONTENT_ID, TASK_ID)
								VALUES
									($content_id, $task_id)
							");
			}

			if ( ! empty($task_list_info)) {
				$workflow = $db->queryRow("select USER_TASK_NAME,TASK_WORKFLOW_ID from bc_task_workflow where register = '$channel'");
				$interface_id = $task->InsertInterface($workflow['user_task_name'], 'USER', $user_id, 'USER', $user_id, $content_id , 'regist', $workflow['task_workflow_id']);
				foreach ($task_list_info as $list_info) {
					$task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
				}
			}
        }
        
        //파일 매핑 연계                
        if( $flag == 'fcp' ){
            $fileQuery = \Api\Models\MapFile::where('file_key' , $filename);
            $fileInfo = $fileQuery->first();
            $fileInfo->content_id = $content_id;
            $fileInfo->channel = $channel;
            $fileInfo->save();
        }else if( $flag == 'fcpx' ){
           
        }
    }

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before task ===> '.$content_id.','.$regist_type."\r\n", FILE_APPEND);
	if (empty($regist_type) || $regist_type == 'task') {
		if ( ! empty($target_content_id) && ( $regist_type == 'task')) {
            $content_id = $target_content_id;            
        }
        
        

        //파일인제스트는 업로드 파일명이 콘텐츠 ID로 고정
        if( strstr($channel,'fileingest') ){
            $filename = $content_id.'.'.$filePathInfo->fileExt;
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

                            
                //중복 작업 요청시 처리
                $beforeMediaInfo = $db->queryRow("select * from bc_media where media_type='original' and content_id='$target_content_id'");
                if( !empty($beforeMediaInfo)){
                    //이미 미디어가 있는 경우 스킵처리
                    $logData = [
                        'action' => 'edit',
                        'description' => '중복 등록 요청 방지 처리',
                        'content_id' => $target_content_id,
                        'bs_content_id' => $bs_content_id,
                        'ud_content_id' => $ud_content_id
                    ];
                    $user = new \Api\Models\User();
                    $user->user_id = $user_id;
                    $logService = new \Api\Services\LogService(app()->getContainer());
                    $r = $logService->create($logData, $user);

                    $beforeTaskInfo = $db->queryRow("select * from bc_task where src_content_id='$target_content_id' order by task_id ");
                    if( !empty($beforeTaskInfo) ){                        
                        $r = $db->insert("bc_task_log",[
                            'task_id' => $beforeTaskInfo['task_id'],
                            'description' => '중복 등록 요청 방지 처리',
                            'creation_date' => date("YmdHis"),
                            'status' => 'error',
                            'progress' => 0
                        ] );                        
                    }
                }else{                    
                    $task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $filename);
                    $task_list_info = $task->get_task_list(null);
                }

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

				if($result_content_xml['data']->Result != 'success') throw new Exception( $result_content_xml['data']->Result, 107);
			}
        }
        
        //원본 파일명 등록
        $sys_metafields = MetaDataClass::getFieldNametoIdMap('sys',$bs_content_id);
        $sysInfo = array();
        $ori_filename = $sys_metafields['sys_ORI_FILENAME'];
        $sysInfo[$ori_filename] = $db->escape($org_filename);
		InterfaceClass::insertSysMetaValus($sysInfo, $content_id, $bs_content_id);
	}

	//FileIngest에서 취소로 넘어올 시 content_id를 넣어줌.
	if($regist_type == 'task_cancel') {
		$content_id = $target_content_id;
	}

	// CJO, 파일인제스트 등록시 suffix 쓰지않음
	// 시퀀스 파일(SxS, P2, 기타...) 등록시 접미어 적용
	// if ( ! empty($title_suffix) && ! empty($target_content_id)) {
	//	 $db->exec("update bc_content set title='". $db->escape($title) . "' where content_id=".$target_content_id);
	// }

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before seq ===> '.$content_id.','.$regist_type."\r\n", FILE_APPEND);

	if($bs_content_id == SEQUENCE) {
		$workflowInfo['source'] = $time_path."/".$content_id;
		$workflowInfo['source_proxy'] = $time_path.'/'.$content_id.'/'.$content_id.'/Proxy';
		$workflowInfo['source_root'] = $storage_info['highres'];
		$workflowInfo['source_root_proxy'] = $storage_info['lowres'];
//		$workflowInfo['source_root'] = '//192.168.1.150/Storage/highres';
//		$workflowInfo['source_root_proxy'] = '//192.168.1.150/Storage/lowres';
	} else {
		if($flag=='fileingest' && $regist_type == 'meta') {
			$workflowInfo = $db->queryRow("
									SELECT	TYPE, PATH AS SOURCE_ROOT, PATH_FOR_MAC AS SOURCE_ROOT_FOR_MAC, LOGIN_ID, LOGIN_PW,
											(SELECT SOURCE_OPT
											FROM BC_TASK_RULE
											WHERE TASK_RULE_ID = 16) AS SOURCE_OPT
									FROM	BC_STORAGE
									WHERE	STORAGE_ID = 106
							");
		}else if($decodeData['client_type'] == 'mac' && $regist_type == 'meta' ){
            
            $workflowInfo = $db->queryRow("
                    SELECT	TYPE, PATH AS SOURCE_ROOT, PATH_FOR_MAC AS SOURCE_ROOT_FOR_MAC, LOGIN_ID, LOGIN_PW,
                            (SELECT SOURCE_OPT
                            FROM BC_TASK_RULE
                            WHERE TASK_RULE_ID = 16) AS SOURCE_OPT
                    FROM	BC_STORAGE
                    WHERE	STORAGE_ID = 106
            ");
        }
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
//			$task_id = '308003';
		}

		$response->result->addAttribute('success', 'true');
		$response->result->addAttribute('msg', 'ok');
		$response->result->addAttribute('content_id', $content_id);
		$response->result->addAttribute('task_id', $task_id);
		$response->result->addChild('success', 'true' );
		$response->result->addChild('msg', 'ok');
		$response->result->addChild('content_id', $content_id);
		$response->result->addChild('parent_id', $parent_id);
		$response->result->addChild('task_id', $task_id);
		$task_list_info = $response->result->addChild('task_list_info');

		if ( ! empty($workflowInfo)) {
			foreach ($workflowInfo as $key => $info) {
				$task_list_info->addChild($key, htmlspecialchars($info));
			}

			// todo 제거 - 채널에이 작업을 위해 임시
			//$task_list_info->addChild('material_id', str_pad(rand(1, 100000), 5, "0", STR_PAD_LEFT));
			$task_list_info->addChild('material_id', $content_id);
		}

		$response_text = $response->asXML();

	}

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] before search ===> '.$content_id.','.$regist_type."\r\n", FILE_APPEND);

	searchUpdate($content_id);

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] response_text ===> '.$response_text."\r\n", FILE_APPEND);

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

	@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/plugin_register_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] ERROR response_text ===> '.$response_text."\r\n", FILE_APPEND);

	echo $response_text;
}

function insertContent($metaValues, $content_id, $category_id, $bs_content_id,
							$ud_content_id, $title, $user_id, $topic_id, $group_type, $group_count, $parent_content_id) {
	global $db;

	$category_full_path = getCategoryFullPath($category_id);
	$cur_time		   = date('YmdHis');

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
	$description = 'nle register';
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
		$value			  = '';
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

	//테이블 명
    $tablename = MetaDataClass::getTableName('usr', $meta_table_id );
    
       //필드의 id => name
    $fieldNameMap = MetaDataClass::getFieldIdtoNameMap('usr' , $meta_table_id );

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

function getMetaValues( $metadatas )
{
	$metaValues = array();
	foreach($metadatas as $metadata)
	{
		foreach($metadata as $key => $value)
		{
			$metaValues[$key] = $value;
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

?>

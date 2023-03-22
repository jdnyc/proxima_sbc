<?PHP
//2012.12.17 김형기 --> 새로운 작업 관리 클래스를 사용하도록 변경
require_once '../lib/config.php';
require_once '../lib/functions.php';
require_once 'lib/functions.php';
require_once 'lib/task_manager.php';
header("Content-type: text/xml");

$is_debug = false;
define('BC_TASK_TABLE', 'bc_task');
define('BC_TASK_LOG_TABLE', 'bc_task_log');

try {
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");
	$xml = file_get_contents('php://input');
	if (empty($xml)) throw new Exception(_text('MSG02097'));//Request is empty.
	_debug(basename(__FILE__), $xml);

	$xml = new SimpleXMLElement($xml);

	$task_action	= (String)$xml->GetTask['Action'];
	$task_type		= (String)$xml->GetTask['Type'];
  $channel		= (String)$xml->GetTask['Channel'];

	$request_ip = empty($channel) ?  $_SERVER['REMOTE_ADDR'] : $channel;

	$ip_cehck = $db->queryRow("select * from BC_MODULE_INFO where (MAIN_IP='$request_ip' or SUB_IP='$request_ip') and active='1'");
	_debug(basename(__FILE__), $db->last_query);
	if (empty($ip_cehck)) throw new Exception(_text('MSG02104').'('.$request_ip.')');//Unknown IP or MAC_ADDRESS

	$db->exec("UPDATE BC_MODULE_INFO SET LAST_ACCESS='".date('YmdHis')."' WHERE MAIN_IP='".$request_ip."'");
	$task_mgr = new TaskManager($db);
	if($task_type == ARIEL_TRANSFER_FTP){
		$task_mgr->updateQueueStatus($task_type);
	}
	$assign_task = check_parent_job($task_type, $ip_cehck['module_info_id']);
	_debug(basename(__FILE__), $db->last_query);

	if ($assign_task == false) throw new Exception(_text('MSG02105'), TASK_NOHAVEITEM);//Queued tasks does not exist.
	
	$rtn = $db->exec(sprintf("update ".BC_TASK_TABLE." set status='assigning', assign_ip='%s' where task_id=%d and status='queue'", $request_ip, $assign_task['task_id']));
	if ($db->affectedRows() <= 0 ) {
		throw new Exception(_text('MSG02105'), TASK_NOHAVEITEM);//Queued tasks does not exist.
	}

	_debug(basename(__FILE__), $rtn.' '.$db->last_query);

	//if harris FTP work exist...
	if($arr_sys_code['interwork_harris']['use_yn'] == 'Y' && $assign_task['type'] == ARIEL_TRANSFER_FTP) {

		// 여기서 해당 FTP 작업이 HARRIS로 가는지 파악 하여 맞는 경우 HARRIS ROUND ROBIN 모듈을 통해 변경 하도록 한다.
		$harris_ftp_check_q = "
			SELECT COUNT(*)
			FROM   HARRIS_FTP_LIST
			WHERE  STORAGE_ID in(".$assign_task['src_storage_id'].",".$assign_task['trg_storage_id'].")
		";

		$harris_ftp_check = $db->queryOne($harris_ftp_check_q);

		// HARRIS FTP 작업 이라면 
		if($harris_ftp_check>0){

			//만약 
			if(strstr(strtolower($assign_task['parameter']), 'source')) {
				
				$harris_info = getHarrisWorkStorage($assign_task['src_storage_id'], 'source');

				if(!empty($harris_info)){

					$ori_storage_id 			   = $assign_task['src_storage_id'];
					$assign_task['src_storage_id'] = $harris_info['storage_id'];
					$harris_src_path 			   = $harris_info['path'];	

					$ftp_max_cnt                   = $harris_info['conn_limit'];
					$now_storage_cnt               = $harris_info['cnt'];

					if($now_storage_cnt >= $ftp_max_cnt){
						// harris max cnt 문제시 다시 되돌려 assigning => queue 로 변경 

						$max_update_q = "
							UPDATE BC_TASK
							SET    STATUS = 'queue',
							       assign_ip = ''
							WHERE  TASK_ID = ".$assign_task['task_id']."
							       AND STATUS = 'assigning'
						";

						$db->exec($max_update_q);
						throw new Exception('Harris FTP server limit exceed(limit:'.$ftp_max_cnt.')');
					}else if($ori_storage_id != $harris_info['storage_id']){
						$db->exec("UPDATE BC_TASK SET SRC_STORAGE_ID='".$harris_info['storage_id']."' WHERE TASK_ID=".$assign_task['task_id']);
					}

				}		

            }
            //CJO, 업로드시는 에이전트에서 1건만 돌도록 해놨음.
            // else if(strstr(strtolower($assign_task['parameter']), 'target')) {

			// 	$harris_info = getHarrisWorkStorage($assign_task['trg_storage_id'], 'target');

			// 	if(!empty($harris_info)){

			// 		$ori_storage_id 			   = $assign_task['trg_storage_id'];
			// 		$assign_task['trg_storage_id'] = $harris_info['storage_id'];
			// 		$harris_trg_path 			   = $harris_info['path'];	

			// 		$ftp_max_cnt                   = $harris_info['conn_limit'];
			// 		$now_storage_cnt               = $harris_info['cnt'];

			// 		if($now_storage_cnt >= $ftp_max_cnt){
			// 			// harris max cnt 문제시 다시 되돌려 assigning => queue 로 변경 

			// 			$max_update_q = "
			// 				UPDATE BC_TASK
			// 				SET    STATUS = 'queue',
			// 				       assign_ip = ''
			// 				WHERE  TASK_ID = ".$assign_task['task_id']."
			// 				       AND STATUS = 'assigning'
			// 			";

			// 			$db->exec($max_update_q);
			// 			throw new Exception('Harris FTP server limit exceed(limit:'.$ftp_max_cnt.')');
			// 		}else if($ori_storage_id != $harris_info['storage_id']){
			// 			// 변경된 스토리지 아이디로 변경
			// 			$db->exec("UPDATE BC_TASK SET TRG_STORAGE_ID='".$harris_info['storage_id']."' WHERE TASK_ID=".$assign_task['task_id']);
			// 		}
			// 	}
			// }
		}

	}

	$assign_task['source'] = trim(str_replace('\\', '/', $assign_task['source']), '/');

	$task_source = $assign_task['source'];
	$result = $response->addChild('Result');
	$result->addAttribute('Action', 'assign');

	$result->addChild('TaskID',		$assign_task['task_id']);
	$result->addChild('TaskType', $assign_task['type']);

	//task_rule테이블과 storage테이블을 맵핑하여 경로를 가져옴.
	$task_rule_id = $assign_task['task_rule_id'];
	$workflow_rule_id = $assign_task['workflow_rule_id'];

	//수정일 : 2011.12.15
	//작성자 : 김형기
	//내용 : 워크플로우쪽 또 손대서 쿼리 바뀜
	if (empty($workflow_rule_id)) {

		// $path_mapping_query = "SELECT DISTINCT
		// 							(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = CAST(TR.SOURCE_PATH AS DOUBLE PRECISION)) SRC_PATH,
		// 							(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = CAST(TR.TARGET_PATH AS DOUBLE PRECISION)) TRG_PATH
		// 						FROM
		// 							(
		// 							SELECT MI.MODULE_INFO_ID, MI.NAME, MI.MAIN_IP, MI.SUB_IP, TA.TASK_RULE_ID
		// 							FROM BC_MODULE_INFO MI, BC_TASK_AVAILABLE TA
		// 							WHERE MI.MODULE_INFO_ID = TA.MODULE_INFO_ID AND MI.ACTIVE = '1'
		// 							) M, BC_TASK_RULE TR
		// 						WHERE
		// 						  TR.TASK_RULE_ID = {$task_rule_id}
		// 							AND (M.MAIN_IP = '{$request_ip}' OR M.SUB_IP = '{$request_ip}')";
	}else{

			$path_mapping_query =	"SELECT  distinct
								(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = CAST(TR.SOURCE_PATH AS DOUBLE PRECISION)) SRC_PATH,
								(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = CAST(TR.TARGET_PATH AS DOUBLE PRECISION)) TRG_PATH,
								(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = WR.SOURCE_PATH_ID) SRC_RULE_PATH,
								(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = WR.TARGET_PATH_ID) TRG_RULE_PATH
							FROM
								(
								SELECT MI.MODULE_INFO_ID, MI.NAME, MI.MAIN_IP, MI.SUB_IP, TA.TASK_RULE_ID
								FROM  BC_MODULE_INFO MI
									LEFT OUTER JOIN BC_TASK_AVAILABLE TA
									ON (MI.MODULE_INFO_ID = TA.MODULE_INFO_ID)
								WHERE MI.ACTIVE='1'
								) M, BC_TASK_RULE TR, BC_TASK_WORKFLOW_RULE WR
							WHERE
							  WR.TASK_RULE_ID=TR.TASK_RULE_ID
								AND TR.TASK_RULE_ID = {$task_rule_id}
								AND (M.MAIN_IP = '{$request_ip}' OR M.SUB_IP = '{$request_ip}')
								and WR.WORKFLOW_RULE_ID=$workflow_rule_id";
	}

	/*
//For speed, temp query
SELECT MI.MODULE_INFO_ID, MI.NAME, MI.MAIN_IP, MI.SUB_IP, TA.TASK_RULE_ID
FROM  BC_MODULE_INFO MI
    LEFT OUTER JOIN BC_TASK_AVAILABLE TA
    ON (MI.MODULE_INFO_ID = TA.MODULE_INFO_ID)
WHERE MI.ACTIVE='1'

//Previous query
SELECT MI.MODULE_INFO_ID, MI.NAME, MI.MAIN_IP, MI.SUB_IP, TA.TASK_RULE_ID
FROM BC_MODULE_INFO MI, BC_TASK_AVAILABLE TA
WHERE MI.MODULE_INFO_ID = TA.MODULE_INFO_ID AND MI.ACTIVE = '1'
	*/

	$task_root_path = $db->queryRow($path_mapping_query);

	_debug(basename(__FILE__), $task_root_path.' '.$db->last_query);

	if(empty($task_root_path))
	{
		throw new Exception(_text('MSG02106').'('.$request_ip.')');//Module information does not exist in this IP.
	}

	$assign_task['target'] = trim(str_replace('\\', '/', $assign_task['target']), '/');

	//룰쪽에 패스가 있을경우 매핑
	if( !empty($task_root_path['src_rule_path']) ){
		$task_root_path['src_path'] = $task_root_path['src_rule_path'];
	}

	if( !empty($task_root_path['trg_rule_path']) ){
		$task_root_path['trg_path'] = $task_root_path['trg_rule_path'];
	}

	//If harris path exists,
	if( !empty($harris_src_path) ) {
		$task_root_path['src_path'] = $harris_src_path;
	}
	if( !empty($harris_trg_path) ) {
		$task_root_path['trg_path'] = $harris_trg_path;
	}

	//추가 정보가 있을경우 루트 패스 업데이트 2014-02-07 이성용
    if( !is_null($assign_task['src_storage_id']) ){
        $src_storage_path_nm = $db->queryOne("SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = '{$assign_task['src_storage_id']}'");
        //if( !empty($src_storage_path_nm) ){
            $task_root_path['src_path'] = $src_storage_path_nm;
        //}
    }else{
        $task_root_path['src_path'] = null;
    }

    if( !is_null($assign_task['trg_storage_id']) ){
        $trg_storage_path_nm = $db->queryOne("SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = '{$assign_task['trg_storage_id']}'");
        //if( !empty($trg_storage_path_nm) ){
            $task_root_path['trg_path'] = $trg_storage_path_nm;
        //}
    }else{
        $task_root_path['trg_path'] = null;
    }

	$parameter = $assign_task['parameter'];

	//FTP작업일시.
	if ($task_type == ARIEL_TRANSFER_FTP) {

		//수정일 : 2011.12.28
		//작성자 : 김형기
		//내용 : 위에 안쓰는 코드 주석처리 하고 FTP전송일 때 경로 주는 부분 보강해서 추가
		//업로드 다운로드 구분 : parameter 첫번째 인자가 "source"이면 다운로드, "target"이면 업로드
		//다운로드 이면 : 소스경로에 루트경로를 주지 않고
		//업로드 이면 : 타겟경로에 루트경로를 주지 않는다.
		$arr_param = explode(' ', trim($assign_task['parameter']));
		if($arr_param[0] == '"target_ftp"') {
			$arr_param[0] = '"target"';
			$src_path_array = explode(':', $task_root_path['trg_path'] );

			$arr_param[1] = '"'.$src_path_array[0].':'.$src_path_array[1].':'.$src_path_array[2].'"';
			$arr_param[2] = '"'.$src_path_array[3].'"';
			$parameter = join(' ',$arr_param);

			$source = $result->addChild('SourceFile', checkFilePathNull( $task_root_path['src_path'].'/'.$task_source) );
			$target = $result->addChild('TargetPath', checkFilePathNull( convertSpecialChar($assign_task['target']) ) );
		}else if ($arr_param[0] == '"source"') {
			//다운로드
			$src_path_array = explode(':', $task_root_path['src_path'] );
			$arr_param[1] = $src_path_array[0];
			$arr_param[2] = $src_path_array[1];
			$parameter = join(' ',$arr_param);
			$source = $result->addChild('SourceFile', checkFilePathNull( $task_source ) );
			$target = $result->addChild('TargetPath', checkFilePathNull( $task_root_path['trg_path'].'/'.convertSpecialChar($assign_task['target']) ));
		} else if($arr_param[0] == '"target"' || $arr_param[0] == '"target_dir"') {

			// //업로드
			// $src_path_array = explode(':', $task_root_path['trg_path'] );
			// $port = array_pop($src_path_array);
			// $arr_param[1] = '"'.join(':',$src_path_array).'"';
			// $arr_param[2] = '"'.$port.'"';
            // $parameter = join(' ',$arr_param);


            //업로드
            //중간 경로 분리
            $task_target = $assign_task['target'];
            $trg_path_dir_array = explode('/', $task_root_path['trg_path'] );			
            //IP 정보
            $trg_path_ip = array_shift($trg_path_dir_array);
            if( !empty($trg_path_dir_array) ){
                $trg_path_dir = join('/', $trg_path_dir_array);
                $task_target = $trg_path_dir.'/'.ltrim($task_target,'/');
            }
            $src_path_array = explode(':', $trg_path_ip );
            $arr_param[1] = $src_path_array[0];
            $arr_param[2] = $src_path_array[1];
            $parameter = join(' ',$arr_param);

            if( !empty($task_root_path['src_path']) ){
                $task_source = $task_root_path['src_path'].'/'.$task_source;			
            }

			$source = $result->addChild('SourceFile', checkFilePathNull( convertSpecialChar($task_source) ) );
			$target = $result->addChild('TargetPath', checkFilePathNull( convertSpecialChar($task_target) ) );
		} else  {
			//예외 처리
			$source = $result->addChild('SourceFile', $task_source );
			$target = $result->addChild('TargetPath', $task_source );
		}
	} else if($task_type == ARIEL_OPENDIRECTORY ) {

		//그외 작업일시
		$source = $result->addChild('SourceFile', $task_source);
		$target = $result->addChild('TargetPath', $assign_task['target'] );
	} else if( $task_type == ARIEL_CATALOG ) {
		// Frame 단위로 뽑는 경우 기존 frame이라고 되어 있는 부분을 실제 frame 값으로 대체
		$arr_param = explode(' ', trim($assign_task['parameter']));
		if($arr_param[0] == '"6"' && $arr_param[1] == '"QCFrame"') {
			$src_content_id = $assign_task['src_content_id'];
			$timecodes = $db->queryAll("
							--SELECT	DISTINCT(QC.START_TC) AS TC
							SELECT	QC.START_TC AS TC, QUALITY_ID
							FROM	BC_MEDIA_QUALITY QC, BC_MEDIA M
							WHERE	M.MEDIA_TYPE = 'original'
							AND		M.MEDIA_ID = QC.MEDIA_ID
							AND		M.CONTENT_ID = $src_content_id
						");
			//추후 DB에서 조회하는 방식으로 변경 필요
			$qc_frame_rate = '29.97';
			$qc_frame_info = array();
		
			foreach($timecodes as $timecode) {
				$qc_frame = $timecode['tc'] * $qc_frame_rate;

				$qc_frame_info[] = (int)$qc_frame;
				$qc_res = $db->exec("
									UPDATE	BC_MEDIA_QUALITY
									SET		FRAME_NUM = ".(int)$qc_frame."
									WHERE	QUALITY_ID = ".$timecode['quality_id']
								);
			}
			$qc_frame_info_ = array_unique($qc_frame_info);

			$arr_param[1] = '"'.join(' ', $qc_frame_info_).'"';

			$parameter = join(' ',$arr_param);
		} else if($arr_param[0] == '"6"' && $arr_param[1] == '"LoudFrame"') {
			$src_content_id = $assign_task['src_content_id'];
			$loudness_infos = $db->queryAll("
								SELECT	*
								FROM	TB_LOUDNESS TL, TB_LOUDNESS_MEASUREMENT_LOG TLBL
								WHERE	TL.LOUDNESS_ID = TLBL.LOUDNESS_ID
								AND		TL.CONTENT_ID = $src_content_id
								AND		TLBL.STATUS = 'D'
								ORDER BY TLBL.LOUDNESS_MEASUREMENT_LOG_ID ASC
							");
			//추후 DB에서 조회하는 방식으로 변경 필요
			$loudness_frame_rate = '29.97';
			$loudness_frame_info = array();
			
			foreach($loudness_infos as $info) {
				$time_stamp = $info['timestamp'];
				$loudness_time_arr = explode(":", $time_stamp);
				$loudness_sec = $loudness_time_arr[0] * 3600 + $loudness_time_arr[1] * 60 + $loudness_time_arr[2];
				$loudness_frame = $loudness_sec * $loudness_frame_rate + $loudness_time_arr[3];
			
				$loudness_frame_info[] = (int)$loudness_frame;
				
				// loudness update
				$loudness_res = $db->exec("
									UPDATE	TB_LOUDNESS_MEASUREMENT_LOG
									SET		FRAME_NUM = ".(int)$loudness_frame."
									WHERE	LOUDNESS_MEASUREMENT_LOG_ID = ".$info['loudness_measurement_log_id']
								);
			}
			
			$arr_param[1] = '"'.join(' ', $loudness_frame_info).'"';
			
			$parameter = join(' ',$arr_param);
		}
		
		$source = $result->addChild('SourceFile', checkFilePathNull( $task_root_path['src_path'].'/'.convertSpecialChar($task_source) ));
		$target = $result->addChild('TargetPath', checkFilePathNull( $task_root_path['trg_path'].'/'.convertSpecialChar($assign_task['target']) ));
	} else {
        if( empty($assign_task['target']) ){
            $targetPath = checkFilePathNull( $task_root_path['trg_path'] );
        }else if( empty($task_root_path['trg_path']) ){
            $targetPath = checkFilePathNull( convertSpecialChar($assign_task['target']) );
        }else{
            $targetPath = checkFilePathNull( $task_root_path['trg_path'].'/'.convertSpecialChar($assign_task['target']) );
        }

        if(empty($task_root_path['src_path'])){
            $sourceTemp = checkFilePathNull( convertSpecialChar($task_source) );
        }else{
            $sourceTemp = checkFilePathNull( $task_root_path['src_path'].'/'.convertSpecialChar($task_source) );
        }
		$source = $result->addChild('SourceFile', checkFilePathNull( $sourceTemp ));
		$target = $result->addChild('TargetPath',  $targetPath);

		//$source = $result->addChild('SourceFile', $task_source);
	}

	if (empty($task_source)) {
		//소스경로가 없어서 실패되는 작업은 assigning 으로 남아 있으니 상태값 에러로 업데이트 추가. 2019-03-21
		if($assign_task['task_id'])
		{
			$db->exec("update bc_task set status = 'error' where task_id = ".$assign_task['task_id']);
		}
		//미디어삭제일경우 삭제테이블에도 상태값 업데이트.
		if ($task_type == ARIEL_DELETE_JOB) {
			$db->exec("update bc_delete_content set status = 'FAIL' where task_id = ".$assign_task['task_id']);
		}


		throw new Exception(_text('MSG02107'));//Source path does not exist.
	}

	$source->addAttribute('id', $assign_task['source_id']);
	$source->addAttribute('pw', $assign_task['source_pw']);

	// XML의 소스에 대한 태그는 여기까지...
	if ($task_type == 100) {
		$task_source_array = explode('/', $task_source);
		if (count($task_source_array) < 2) {
			throw new Exception(_text('MSG02109'));//Wrong source path
		}
	}

	if ( !in_array($task_type, [15,100,130,50] ) &&($arr_param[0] != '"delete"') && empty($assign_task['target'])) {
		//When FTP group send, allow empty target.
		if($assign_task['parameter'] != '"target_dir"') {
			throw new Exception(_text('MSG02108'));//Target path does not exist
		}
	}

	$target->addAttribute('id', $assign_task['target_id']);
	$target->addAttribute('pw', $assign_task['target_pw']);

	$info = $result->addChild('Info');
	$info->addChild('Parameter', $parameter );

	echo $response->asXML();

	_debug(basename(__FILE__), $response->asXML());
} catch (TaskException $e) {
	$task_id = $e->getTaskID();
	$task_log_msg = str_replace("'", "\'", $e->getMessage());
	$task_log_creation_datetime = date('YmdHis');

	$db->exec("update ".BC_TASK_TABLE." set status='error' where task_id=".$task_id);
	_debug(basename(__FILE__), $db->last_query);

	$db->exec("insert into bc_task_log (task_id, description, creation_date) values ($task_id, '$task_log_msg', '$task_log_creation_datetime')");
	_debug(basename(__FILE__), $db->last_query);

	_debug(basename(__FILE__), $task_log_msg);
} catch (Exception $e) {
    $task_log_creation_datetime = date('YmdHis');
	$response = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");
	if ($e->getCode() == TASK_NOHAVEITEM) {

		$result = $response->addChild('Result');
		$rtn = $response->asXML();
		echo $rtn;
	} else {
        if( !empty($task_id) ){
            $db->exec("update ".BC_TASK_TABLE." set status='error' where task_id=".$task_id);
            _debug(basename(__FILE__), $db->last_query);
    
            $db->exec("insert into bc_task_log (task_id, description, creation_date) values ($task_id, '$task_log_msg', '$task_log_creation_datetime')");
            _debug(basename(__FILE__), $db->last_query);
        }
		$result = $response->addChild('Result');
			$result->addAttribute('success', 'false');
			$result->addAttribute('msg', $e->getLine().':'.$e->getMessage());

		$rtn = $response->asXML();
		echo $rtn;
	}

	if (!strstr($rtn, _text('MSG02105'))) {//Queued tasks does not exist
		_debug(basename(__FILE__), $rtn.' '.$db->last_query);
	}
}

class TaskException extends Exception
{
	private $task_id;

	function __construct($msg, $task_id)
	{
		$this->task_id = $task_id;

		parent::__construct($msg);
	}

	function getTaskId()
	{
		return $this->task_id;
	}

}

function checkFilePathNull($filePath) {
	if($filePath == "/")
		return "";

	return $filePath;
}
?>
<?PHP
//작성일 : 2011.12.16
//작성자 : 김형기
//내용 : Task Rule에 대한 관리 기능을 하는 클래스
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);

$rootDir = dirname(dirname(__DIR__));

require_once($rootDir . DS .'lib' . DS . 'config.php');
require_once($rootDir . DS .'lib' . DS . 'functions.php');
require_once($rootDir . DS .'lib' . DS . 'ATS.class.php');
require_once($rootDir . DS .'lib' . DS . 'SGL.class.php');
require_once($rootDir . DS .'lib' . DS . 'SNS.class.php');
require_once($rootDir . DS .'workflow' . DS . 'lib' . DS . 'task_option_parser.php');
require_once($rootDir . DS .'lib' . DS . 'bisUtil.class.php');

class TaskManager
{
	private $db = null;
	private $arr_sys_code = null;
	
	private $priority = 300;

	private $source_media_id = null;
    private $target_media_id = null;
    
    private $force_source_media_id = null;
    private $force_target_media_id = null;

    private $src_file_id = 0;
    private $trg_file_id = 0;

    private $task_list = array();
    
    private $task_status = 'queue';

	//생성자
	public function __construct($db)
	{
		global $arr_sys_code;
		$this->db = $db;
		$this->arr_sys_code = $arr_sys_code;
    }
    
    public function setStatus($task_status)
	{
		$this->task_status = $task_status;
	}

	public function set_priority($priority)
	{
		if(empty($priority) || $priority < 0)
			return;

		$this->priority = $priority;
	}

	public function set_task_id($task_id)
	{
		$this->task_id = $task_id;
	}

	public function get_task_id()
	{
		return $this->task_id;
	}

	public function set_target_media_id($media_id)
	{
		$this->target_media_id = $media_id;
	}

	public function set_source_media_id($media_id)
	{
		$this->source_media_id = $media_id;
    }
        
    public function getTrgMediaId(){
        return $this->target_media_id;
    }
    public function getSrcMediaId(){
        return $this->source_media_id;
    }

	public function get_task_list($task_id)
	{
		if( !empty($task_id) ){
			return $this->task_list[$task_id];
		}else{
			return $this->task_list;
		}
    }
    
    public function getDB(){
        return $this->db;
    }

	public function getWorkflowInfo($channel, $content_id=null)
	{
		$bs_content_id = $this->db->queryOne("SELECT BS_CONTENT_ID FROM BC_CONTENT
			WHERE CONTENT_ID=".$content_id);

		$workflow_info = $this->db->queryRow("
			SELECT T2.SOURCE_PATH_ID, T2.TARGET_PATH_ID, T1.REGISTER, T3.PARAMETER, T3.SOURCE_OPT, T3.TARGET_OPT, T5.TYPE AS JOB_CODE, T4.TYPE, T4.PATH SOURCE_ROOT,
					T4.PATH_FOR_UNIX SOURCE_ROOT_FOR_MAC, T4.LOGIN_ID, T4.LOGIN_PW
			FROM	BC_TASK_WORKFLOW T1, BC_TASK_WORKFLOW_RULE T2, BC_TASK_RULE T3, BC_STORAGE T4, BC_TASK_TYPE T5
			WHERE 	T4.STORAGE_ID = T2.SOURCE_PATH_ID
			AND 	T3.TASK_RULE_ID = T2.TASK_RULE_ID
			AND		T3.TASK_TYPE_ID = T5.TASK_TYPE_ID
			AND 	T2.JOB_PRIORITY = 1
			AND 	T2.TASK_WORKFLOW_ID = T1.TASK_WORKFLOW_ID
			AND 	REGISTER='$channel'
			AND		(T1.BS_CONTENT_ID=".$bs_content_id." OR T1.BS_CONTENT_ID=0)
			ORDER BY T2.WORKFLOW_RULE_ID");
		if( empty($workflow_info) ){
			return false;
		}
		$source_opt_parser = new TaskOptionParser($this, 'source');
		$source_opt_parser->parseTaskOption($content_id, $workflow_info['source_opt']);
		$source = $source_opt_parser->getFullPath();
		$workflow_info['source'] = $source;
		$workflow_info['edius_file_path'] = $content_id.'.mxf';//NLE 플러그인용
		return $workflow_info;
	}

	public function InsertInterface($title, $from_type,$from_id,$target_type,$target_id, $base_content_id, $interface_work_type , $interface_workflow_id ){
		/*고유아이디
		인터페이스 명 대표 제목
		인터페이스 등록자 유형 USER ,GROUP
		인터페이스 등록자 user_id, group_id
		인터페이스 대상자 유형 USER , GROUP
		인터페이스 대상자 user_id, group_id
		인터페이스 기본 콘텐츠 ID content_id
		생성일자 YmdHis*/
		$id = getSequence('SEQ_INTERFACE_ID');
		$title = $this->db->escape($title);
		$r = $this->db->exec("insert into INTERFACE (INTERFACE_ID,INTERFACE_TITLE,INTERFACE_FROM_TYPE,INTERFACE_FROM_ID,	INTERFACE_TARGET_TYPE,INTERFACE_TARGET_ID,INTERFACE_BASE_CONTENT_ID,
		CREATE_DATE,INTERFACE_WORK_TYPE, INTERFACE_WORKFLOW_ID ) values($id,'$title','$from_type','$from_id',	'$target_type','$target_id',$base_content_id ,'".date("YmdHis")."','$interface_work_type', $interface_workflow_id )");
		return $id;
	}

	public function InsertInterfaceCH($interface_id, $i_channel, $i_type,$task_id,$content_id){
		/*인터페이스 고유 아이디 seq
		하위 인터페이스 고유 아이디 seq
		인터페이스 채널명 NPS,DAS,DMC
		인터페이스 유형 WORK,TASK,INTERFACE
		인터페이스 대상 ID task_id,nps_work_id,interface_id
		인터페이스 대상 콘텐츠 ID content_id
		생성일자 YmdHis*/
		$interface_ch_id = getSequence('SEQ_INTERFACE_CH_ID');
		$r = $this->db->exec("insert into INTERFACE_CH (INTERFACE_ID,INTERFACE_CH_ID,INTERFACE_CHANNEL,INTERFACE_TYPE,TARGET_ID,TARGET_CONTENT_ID,CREATE_DATE) values ($interface_id,$interface_ch_id,'$i_channel','$i_type',$task_id,$content_id,'".date("YmdHis")."') ");
		return $interface_ch_id;
	}

	private function ModifyParameter($content_id, $task_type, $parameter/*파라메터 값*/, $target_opt_parser , $arr_param_info = null/*수정될 파라메터 정보*/)
	{
		/* 예를들면 요런식으로 수정
		$p = '"in점" "out점" "C" "15555" "X"';
		$p = trim($p, '"');
		$arr = explode('" "', $p);
		//print_r($arr);
		$arr[0] = 0;
		$arr[1] = 0;
		$p = '"'.implode('" "', $arr).'"';
		*/

        if($arr_param_info == null)
            return $parameter;
            //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_param_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] arr_param_info ===> '."(".print_r($arr_param_info, true).")"."\r\n", FILE_APPEND);
        // pfr
        if( !empty($arr_param_info[0]['parameter']) ){
        
            $ret_param = $arr_param_info[0]['parameter'];
           
        }else if (is_numeric($arr_param_info[0]['value']) && is_numeric($arr_param_info[1]['value'])) {
            $ret_param = '"' . $arr_param_info[0]['value'] . '" "'. $arr_param_info[1]['value'] . '" ' .$parameter;
            //file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/test_param_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] param_info_pfr ===> '.$ret_param."(".print_r($arr_param_info, true).")"."\r\n", FILE_APPEND);
        }else {
            if($arr_param_info == null){
                return $parameter;
            }

            $parameter_array = $this->getParameterArray($parameter);
            if( !is_array($parameter_array) ) return $parameter;

            if( !empty($arr_param_info) ){            
                foreach($arr_param_info as $param_info){
                    if($param_info['index'] == null) continue;
                    $parameter_array[$param_info['index']] = '"'.$param_info['value'].'"';
                }
            }
            $ret_param = implode(" ", $parameter_array);
        }

        return $ret_param;
		
    }
    
    
    /**
     * Task 파라미터를 배열로 리턴
     * 형식에 맞지않으면 기존값
     *
     * @param [type] $parameter
     * @return void
     */
    public function getParameterArray($parameter){
        $returnVal = array();

        //""형식이 아닌경우
        $parameter = trim($parameter);
        $parameter_check = preg_split('/\s+/', $parameter);
        if( count($parameter_check) == 1 ){
        //쌍따옴표 없는 단일 파라미터 파싱 제외
        if( !strstr( $parameter, '"' ) ) return $parameter;
        }

        //쌍따옴표로 파싱
        preg_match_all('/([\"]).*?\1/', $parameter, $parameter_array);
        $parameter_array = $parameter_array[0];
        //쌍따옴표 형식이 아니면 리턴
        if( empty($parameter_array) ) return $parameter;

        $returnVal = $parameter_array;

        return $returnVal;
    }

	public function testDebug($msg, $filename = null)
	{
		if ($filename == null) {
			$filename = basename(__FILE__);
		}
		//날짜별로 log파일이 생성되게 변경.
        $log_file = $_SERVER['DOCUMENT_ROOT']. '/log' . '/' . substr($filename, 0, strrpos($filename, '.')) . '_' . date('Ymd') . '.log';
        $log_msg = $_SERVER['REMOTE_ADDR'] . '[' . date('Y-m-d H:i:s') . '] ' . $msg . chr(10);
        file_put_contents($log_file, $log_msg, FILE_APPEND);
	}

	//외부로부터 자료를 가져오는 작업은 파일 경로가 그때  그때 변경되기 때문에 따로 함수로 만듬.
	public function insert_task_query_outside_data($content_id, $channel, $job_priority, $task_user_id, $source_path/*full path*/, $target_path=null,$arr_param_info = null)
	{
		// $is_debug = true;
		//_debug("register_sequence", "insert_task_query_outside_data source_path :".$source_path);
		
		$arr_source_path = array('source_path' => $source_path, 'target_path' => $target_path);
		if (is_array($arr_param_info)) {
			$_arr_param_info = array(
				array_merge(
					$arr_source_path,
					$arr_param_info[0]
				),
				array_merge(
					$arr_param_info[1]
				)
			);
		} else {
			$_arr_param_info = array($arr_source_path);
		}

		return $this->start_task_workflow($content_id, $channel, $task_user_id, $_arr_param_info);
	}

	public function insert_task_query_OpenDirectory($content_id, $channel, $task_user_id, $arr_param_info )
	{
		return $this->start_task_workflow($content_id, $channel, $task_user_id, $arr_param_info );
	}


	/* 트리구조 워크플로우를 위한 시작 함수 정의 첫 작업부터 병렬실행 가능하게... */

	public function start_task_workflow($content_id, $channel, $task_user_id, $arr_param_info = null, $options = null)
	{

		//_debug("register_sequence", "start_task_workflow ===== START:");
		$query = "SELECT * FROM BC_TASK_WORKFLOW WHERE REGISTER = '$channel' AND TYPE != 'p'";
		$task_workflow_info = $this->db->queryRow($query);
		$task_workflow_id = $task_workflow_info['task_workflow_id'];
		$wf_bs_content_id = $task_workflow_info['bs_content_id'];
		
		$bc_content_info = $this->db->queryRow("select * from bc_content where content_id=".$content_id);
		$ct_bs_content_id = $bc_content_info['bs_content_id'];

		//throw new Exception('task_workflow에 등록되지 않은 인제스트 채널입니다.(채널:'.$channel.')');
		if(empty($task_workflow_id)) {
			//_debug("register_sequence", "task_workflow에 등록되지 않은 인제스트 채널입니다 ===== EXCPEION:");
			throw new Exception('('._text('MN01071').':'.$channel.')'._text('MSG02083'));//Workflow does not exist
		}

		if($wf_bs_content_id > 0) {
			$query = "SELECT * FROM BC_TASK_WORKFLOW
				WHERE REGISTER = '$channel' AND TYPE != 'p' AND BS_CONTENT_ID = ".$ct_bs_content_id;
			$task_workflow_info = $this->db->queryRow($query);
			$task_workflow_id = $task_workflow_info['task_workflow_id'];
			$wf_bs_content_id = $task_workflow_info['bs_content_id'];
			if(empty($task_workflow_info) || $wf_bs_content_id != $ct_bs_content_id) {
				throw new Exception('('._text('MN01071').':'.$channel.')'._text('MSG02084'));//Content type is wrong between workflow and content.
			}
		}

		$next_job_priority = 1;

		$next_query = "
						SELECT	TASK_RULE_ID ,
								TASK_RULE_PARANT_ID ,
								WORKFLOW_RULE_ID ,
								WORKFLOW_RULE_PARENT_ID
						FROM	BC_TASK_WORKFLOW_RULE
						WHERE	TASK_WORKFLOW_ID = $task_workflow_id
						AND		JOB_PRIORITY = $next_job_priority
						ORDER BY WORKFLOW_RULE_ID
					";
		//_debug("register_sequence", "next_query : ".$next_query);
		$get_next_job_infos = $this->db->queryAll($next_query);
		
		$root_task = '';
		
		//_debug("register_sequence", print_r($get_next_job_infos,true));
		
		foreach ($get_next_job_infos as $get_next_job_info) {

            //조건 작업 처리 
            if( !$this->conditionChecker( $content_id, null, 0, $get_next_job_info['workflow_rule_id'] ) ){
                //스킵용 작업상태 추가
                $new_task_id = $this->createTask($content_id, $channel, $next_job_priority, $task_user_id, $arr_param_info , $get_next_job_info['workflow_rule_id'] );
                continue;
            }

			if( !empty($root_task) ){
				$arr_param_info[0]['root_task'] = $root_task;
			}
			$temp_task_id = $this->insert_task_query($content_id, $channel, $next_job_priority, $task_user_id, $arr_param_info , $get_next_job_info['workflow_rule_id'], $options);

			//_debug("register_sequence", "temp_task_id *** : ".$temp_task_id);
			if($temp_task_id){
				$task_id = $temp_task_id;
				if(empty($root_task)) $root_task = $task_id;
			}
		}
		return $task_id;
	}

	public function insert_task_query($content_id, $channel, $job_priority, $task_user_id, $arr_param_info=null , $workflow_rule_id=null, $options = null)
	{
		//_debug("register_sequence", "insert_task_query FUNC  *** channel : ".$channel);
		$query = "SELECT * FROM BC_TASK_WORKFLOW WHERE REGISTER = '$channel' AND TYPE != 'p'";

		$task_workflow_info = $this->db->queryRow($query);
		$task_workflow_id = $task_workflow_info['task_workflow_id'];
		$wf_bs_content_id = $task_workflow_info['bs_content_id'];

		$bc_content_info = $this->db->queryRow("select * from bc_content where content_id=".$content_id);
		$ct_bs_content_id = $bc_content_info['bs_content_id'];

		//throw new Exception('task_workflow에 등록되지 않은 인제스트 채널입니다.(채널:'.$channel.')');
		if(empty($task_workflow_id)) {
			//_debug("register_sequence", "insert_task_query task_workflow에 등록되지 않은 인제스트 채널입니다: ".$channel);
			throw new Exception('('._text('MN01071').':'.$channel.')'._text('MSG02083'));//Workflow does not exist
		}

		if($wf_bs_content_id > 0) {
			$query = "SELECT * FROM BC_TASK_WORKFLOW
				WHERE REGISTER = '$channel' AND TYPE != 'p' AND BS_CONTENT_ID = ".$ct_bs_content_id;
			$task_workflow_info = $this->db->queryRow($query);
			$task_workflow_id = $task_workflow_info['task_workflow_id'];
			$wf_bs_content_id = $task_workflow_info['bs_content_id'];
			if(empty($task_workflow_info) || $wf_bs_content_id != $ct_bs_content_id) {
				throw new Exception('('._text('MN01071').':'.$channel.')'._text('MSG02084'));//Content type is wrong between workflow and content.
			}
		}

		$query = "
            select          
						r.*
			from 	bc_task_workflow_rule r
			where 	task_workflow_id = $task_workflow_id
			and 	workflow_rule_id = $workflow_rule_id
			and 	job_priority = {$job_priority}";
        $get_jobs = $this->db->queryRow($query);
        
        ///워크플로우 조회 끝

        //가변인경우 미디어ID 지정
        if ( !empty($arr_param_info) && !empty($arr_param_info[0]) && !empty($arr_param_info[0]['force_src_media_id'])) {
            $force_src_media_id = $arr_param_info[0]['force_src_media_id'];
            $this->set_source_media_id($force_src_media_id);
        }

		//AD OD 워크플로우 구분 2013-01-18 이성용
		//if( $this->checkStorageGroup($content_id, $get_jobs) ) return false;
		//$getStorageInfo = $this->getStorageInfo($content_id, $get_jobs);
		$query = "
			select tr.*, tt.type
			from 	bc_task_rule tr, bc_task_type tt
			where 	tr.task_type_id = tt.task_type_id
			and 	tr.task_rule_id = {$get_jobs['task_rule_id']}";
		//_debug("register_sequence", "insert_task_query query  ***2  : ".$query);
        $task_rule = $this->db->queryRow($query);
 
		//소스 옵션 분석
		$source_opt_parser = new TaskOptionParser($this, 'source', $channel, $arr_param_info, $task_rule, $get_jobs );

		if ($content_id != '') {
            $source_opt_parser->parseTaskOption($content_id, $task_rule['source_opt']);
            $src_storage_id = $source_opt_parser->getSrcStorageId();
        }
      
		$source_opt_arr = $source_opt_parser->getTaskOption();
		if($source_opt_arr['media_type'] == 'xml') {
			$storage_info = $this->db->queryRow("
				SELECT	*
				FROM	VIEW_UD_STORAGE
				WHERE	UD_CONTENT_ID = ".$bc_content_info['ud_content_id']."
				AND		US_TYPE='lowres'
			");

			$fullpath = $this->db->escape($source_opt_arr['full_path']);
			$meta_xml = $this->make_xml($content_id, $task_user_id, $channel, basename($fullpath));
			//for window path
			//$filename = $storage_info['path'].'/'.$fullpath;
			//for linux path
			$filename = $storage_info['path_for_unix'].'/'.$fullpath;
			
			$filename = iconv('utf-8','cp949',$filename);
			file_put_contents($filename, $meta_xml);
			$filesize = @filesize($filename);
			if(empty($filesize)) $filesize = 0;

			$this->db->exec("
				UPDATE	BC_MEDIA
				SET		FILESIZE=".$filesize."
						,CREATED_DATE='".date('YmdHis')."'
						,REG_TYPE='".$channel."'
				WHERE	MEDIA_ID=".$source_opt_arr['media_id']."
			");
		}


        //타겟 옵션 분석
        $target_opt_parser = new TaskOptionParser($this, 'target', $channel, $arr_param_info, $task_rule, $get_jobs);
		if ($content_id != '') {
            $target_opt_parser->parseTaskOption($content_id, $task_rule['target_opt'], $source_opt_parser->getMediaType() , $source_opt_parser->getMediaId() );
            $trg_storage_id = $target_opt_parser->getTrgStorageId();
        }
        //파서에서 storage id가 있으면 우선
        //그다음 bc_task_workflow_rule
        
        if( !empty($src_storage_id) ){
        }else if( $get_jobs['source_path_id'] ){
            $src_storage_id = $get_jobs['source_path_id'];
        }else{
            $src_storage_id = 'null';
        }
		$query = "select s.type , s.path , s.login_id, s.login_pw from bc_storage s where s.storage_id={$src_storage_id}";
        $source_login_info = $this->db->queryRow($query);
        

        if( !empty($trg_storage_id) ){
        }else if( $get_jobs['target_path_id'] ){
            $trg_storage_id = $get_jobs['target_path_id'];
        }else{
            $trg_storage_id = 'null';
        }
		$query = "select s.type , s.path , s.login_id, s.login_pw from bc_storage s where s.storage_id={$trg_storage_id}";
		$target_login_info = $this->db->queryRow($query);

        
		//소스 타겟 스토리지 루트패스 저장
		if( !empty($source_login_info) ){
			$source_opt_parser->setSourceRoot($source_login_info['path']);
		}

		if( !empty($target_login_info) ){
			$target_opt_parser->setTargetRoot($target_login_info['path']);
		}

		$task_type = $task_rule['type'];
		$parameter = $task_rule['parameter'];

        //옵션 매핑
		if ($arr_param_info != null) {
			if(!empty($arr_param_info[0]['root_task'])){
				$root_task = $arr_param_info[0]['root_task'];
            }

            if(!empty($arr_param_info[0]['priority'])){
                $priority = $arr_param_info[0]['priority'];
                if(!empty($priority)){
                    $this->set_priority($priority);
                }
            }

            //대상 파일 ID 입력
            if( !empty($arr_param_info[0]['trg_file_id']) ){
                $this->trg_file_id = $arr_param_info[0]['trg_file_id'];         
            }
            
            
            $parameter = $this->ModifyParameter($content_id, $task_type, $parameter, $target_opt_parser, $arr_param_info);
            
            
			if( $target_opt_parser->getMediaType() == 'pfr' ){// pfr 작업시 미디어정보에 인아웃 값 업데이트
				$vr_start = $target_opt_parser->vr_start;
				$vr_end = $target_opt_parser->vr_end;

				$pfr_media_id = $target_opt_parser->getMediaId();

				if( !is_null($vr_start) && !is_null($vr_end) && !is_null($pfr_media_id) ){
					$this->db->exec("update bc_media set vr_start=$vr_start, vr_end=$vr_end where media_id=$pfr_media_id");
				}
			}
		}

		$cur_time = date('YmdHis');
		$source = $source_opt_parser->getFullPath();
		$target = $target_opt_parser->getFullPath();
		if (is_array($options) && ! empty($options['change_target_path']))  {
			$target = $options['change_target_path'];
		}

		$source_media_id = $source_opt_parser->getMediaId();
        $target_media_id = $target_opt_parser->getMediaId();
        
        $media_id = $target_opt_parser->getMediaId();

		if( empty($media_id) ){
			$media_id = $source_opt_parser->getMediaId();
		}
		$task_id = getSequence('TASK_SEQ');
		switch($task_type)
		{
			//카탈로깅 작업
			//카탈로그 이미지는 bc_media테이블에 등록되지 않는다.
			case '10':
			break;
			case '11': //썸네일 생성
			case '15': //퀄리티 체크
			break;
			case '18': //Thumbnail Grid
				// under 200 sec, capture per 1 sec.
				// over 200 sec, capture maximum 200 pic.
				$content_info = $this->db->queryRow("select * from bc_content where content_id=".$content_id);
				$bs_content_id = $content_info['bs_content_id'];
				$content_sys_info = MetaDataClass::getValueInfo('sys', $bs_content_id , $content_id );
				$rt = $content_sys_info['sys_video_rt'];
				$h = substr($rt,0,2)*3600;
				$m = substr($rt,3,2)*60;
				$s = substr($rt,6,2);
				$total = $h+$m+$s;
				if($total < 200) {
					$div_sec = 1;
				} else {
					$div_sec = ceil($total/200);
				}
				$arr_param = explode(" ", $parameter);
				$arr_param[2] = '"'.$div_sec.'"';//In Thumbnail Grid parameter, 3rd option is divide sec.
				$parameter = implode(" ", $arr_param);
			break;
			case '20': //트랜스코딩
			break;
			case '22': //이미지 트랜스코딩
			break;

			case '30': //고해상도, 저해상도 리랩핑 (XDCAM mxf), 구간추출(고해상도: XDCAM mxf, 저해상도 : mp4)
			break;
			case '31': //MOV to MXF 리랩핑
			case '34': //필요정보 : 영상의 원본경로와 복사한 대상경로. 원본파일의 헤더값을 대상파일로 헤더값을 복사한다. (실시간 FTP 다운로드 후 실행 필요)
			case '35': //필요정보 : 체크할 원본 경로. 작업완료 후 Key 값을 로그값에 등록. 해당 키값의 관리가 필요하며, 이후 동일 영상인지의 체크를 해당 키
			case '60': //Transfer FS
//				//When group content, change source, target
//				if($bc_content_info['is_group'] == 'G') {
//					if(strstr($source, '/')) {
//						$filename = array_pop( explode('/', $source) ); //get filename
//						$source = str_replace($filename, '', $source); //group folder
//					} else {
//						$source = $content_id; //group folder
//					}
//
//					if(strstr($target, '/')) {
//						$filename = array_pop( explode('/', $target) ); //get filename
//						$target = str_replace($filename, '', $target); //group folder
//					} else {
//						$target = $content_id; //group folder
//					}
//				}
			break;
			case '36': //mov 헤더 작업시 이전 80작업의 소스경로를 참조해야됨..
				$task_rule_parant_id = $get_jobs['task_rule_parant_id'];
				$before_job_priority = $job_priority - 1 ;
				if( $before_job_priority > 0 ){
					$temp_source = $this->db->queryRow("select t.source from bc_task t, bc_media m where t.media_id=m.media_id and m.content_id=$content_id and t.task_rule_id=$task_rule_parant_id and t.job_priority=$before_job_priority");
					$source = $temp_source['source'];
				}
			break;

			case '70': //오디오 트랜스코딩
			break;
			case '80': //Transfer FTP
				//When group content, change source, target
				if($bc_content_info['is_group'] == 'G') {
					$parameter = str_replace('target', 'target_dir', $parameter);
					$parameter = str_replace('source', 'source_dir', $parameter);

					if(strstr($source, '/')) {
						//PHP5.4에서 지원안됨. $filename = array_pop( explode('/', $source) ); //get filename
						$source_arr = explode('/', $source);
						$filename = array_pop( $source_arr ); //get filename
						$source = str_replace($filename, '', $source); //group folder
					} else {
						$source = '/'; //group folder
					}

					if(strstr($target, '/')) {
						//PHP5.4에서 지원안됨. $filename = array_pop( explode('/', $target) ); //get filename
						$target_arr = explode('/', $target);
						$filename = array_pop( $target_arr ); //get filename
						$target = str_replace($filename, '', $target); //group folder
					} else {
						$target = '/'; //group folder
						$target = '/g_'.$bc_content_info['title']; //group folder
					}
				}
			break;
			case '100':
				//When group content, change source
				if($bc_content_info['is_group'] == 'G') {
					//PHP5.4에서 지원안됨. $filename = array_pop( explode('/', $source) ); //get filename
					$source_arr = explode('/', $source);
					$filename = array_pop( $source_arr ); //get filename
					
					$source = str_replace($filename, '', $source); //group folder
					$parameter = 'directory';
				}
			break;
//			case '110':
				//Duplicate with archive job(case ARCHIVE) at bottom.
//				$_media_id = $target_opt_parser->getMediaId();
//				$unique_id = $this->db->queryOne("SELECT PATH FROM BC_MEDIA WHERE MEDIA_ID=".$_media_id);
//				$unique_id = pathinfo($unique_id, PATHINFO_FILENAME);
//
//				$this->db->exec("insert into sgl_archive (unique_id, media_id, task_id) values ('$unique_id', $_media_id, $task_id)");
//
////				$query = "update bc_content set archive_date = '$cur_time' where content_id = $content_id";
//				$this->db->exec($query);
//			break;
			case ARCHIVE:
				if ( $this->arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ) {
					$_media_id = $target_opt_parser->getMediaId();
					$this->db->exec("insert into sgl_archive (unique_id, media_id, task_id) values ('$content_id', $_media_id, $task_id)");
				}
				
			break;
			case ARCHIVE_DELETE:
			case RESTORE:
				if ( $this->arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ) {
					$_media_id = $source_opt_parser->getMediaId();
					$this->db->exec("insert into sgl_archive (unique_id, media_id, task_id) values ('$content_id', $_media_id, $task_id)");
				}				
			break;
			case RESTORE_PFR;
				if ( $this->arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ) {
					$_media_id = $source_opt_parser->getMediaId();


					$this->db->exec("insert into sgl_archive (unique_id, media_id, task_id) values ('$content_id', $_media_id, $task_id)");
				}
				
			break;
						//ARCHIVE_LIST 확인 작업일 경우 sgl_archive 테이블에 값을 인풋
			case ARCHIVE_LIST:
				if ( $this->arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ) {
					$_media_id = $source_opt_parser->getMediaId();


					$this->db->exec("insert into sgl_archive (unique_id, media_id, task_id) values ('$content_id', $_media_id, $task_id)");
				}
				
			break;

			case SNS_SHARE:
				//BC_SOCIAL_TRANSFER STATUS(REQUEST, SUCCESS, FAIL)
				$is_sns_exists = $this->db->queryRow("
					SELECT	B.TYPE
							,A.*
					FROM	(      
							SELECT	*
							FROM	BC_SOCIAL_TRANSFER
							WHERE	CONTENT_ID=".$content_id."
							AND		SOCIAL_TYPE='".$parameter."'
							) A
							LEFT OUTER JOIN
							BC_TASK B
							ON(A.TASK_ID=B.TASK_ID)
				");
				//if register job and not fail, then exit.
				if($is_sns_exists['type'] == SNS_SHARE && $is_sns_exists['status'] != 'FAIL') {
					return;
				}
				//if delete job and not success, then exit.
				if($is_sns_exists['type'] == SNS_DELETE && $is_sns_exists['status'] != 'DELETED') {
					return;
				}
				if($is_sns_exists['sns_seq_no'] == '') {
					$sns = new SNS($this->db);
					$sns_title = $sns->getTitle($content_id);
					$sns_content_info = $sns->getContent($content_id);
					$sns_seq_no = getSequence('SEQ_BC_SOCIAL_TRANSFER_NO');
					$insert_data_sns = array(
						'sns_seq_no' => $sns_seq_no,
						'task_id' => $task_id,
						'content_id' => $content_id,
						'social_type' => $parameter,
						'title' => $sns_title,
						'content' => $sns_content_info['content_string'],
						'status' => 'REQUEST',
						'reg_user_id' => $task_user_id,
						'created_date' => $cur_time,
						'deleted_date' => '',
						'web_url1' => '',
						'web_url2' => ''
					);

					$this->db->insert('BC_SOCIAL_TRANSFER', $insert_data_sns);
				} else {
					$sns = new SNS($this->db);
					$sns_title = $sns->getTitle($content_id);
					$sns_content_info = $sns->getContent($content_id);
					$update_data_sns = array(
						'task_id' => $task_id,
						'title' => $sns_title,
						'content' => $sns_content_info['content_string'],
						'status' => 'REQUEST',
						'reg_user_id' => $task_user_id,
						'created_date' => $cur_time,
						'deleted_date' => '',
						'web_url1' => '',
						'web_url2' => ''
					);
					$update_data_sns_where = " sns_seq_no=".$is_sns_exists['sns_seq_no']." ";
					$this->db->update('BC_SOCIAL_TRANSFER', $update_data_sns, $update_data_sns_where);
				}
				
				//After insert task, sns upload
				$sns_upload = 'Y';
				
			break;
		}
		//_debug("register_sequence", "insert_task_query set_task_id TASK_ID : ".$task_id);
		$this->set_task_id($task_id);

		if(empty($root_task) || $root_task == ''){
			$root_task = $task_id;
		}

		if( $target_opt_parser->getMediaType() == 'out' ){
			$media_id = 0;
		}

		//2015-12-10 추가
		if( empty($target_media_id) ){
			$media_id =0;
		}
		//2016-07-25 for PGSQL, is null then make empty
		if( $source_media_id == 'null' ){
			$source_media_id = '';
		}
		// FTP 세션 제한 관련 추가_Proxima 소스 기준 수정하여 추가 2018.06.11 Alex
		// 교통방송의 경우 FTP만 제한있으므로 우선은 FTP일 경우에만 동작하도록 수정
		// true 일경우 pending으로 상태 변경
		if($task_type == ARIEL_TRANSFER_FTP) {
			if( $this->checkLimitStorage($task_type, $src_storage_id, $trg_storage_id)){
				$this->task_status = 'pending';	
            }
        }

        if (!empty($arr_param_info[0]['target_path'])) {
			$target = $arr_param_info[0]['target_path'];
        }

        if( $this->trg_file_id  && class_exists('\Api\Core\FilePath') && class_exists('\Api\Models\File') ){
            
            $filePathInfo = new \Api\Core\FilePath($target);
            $fileMeta = [
                'file_path'     => $filePathInfo->filePath,
                'file_name'     => $filePathInfo->filenameExt,
                'file_ext'      => $filePathInfo->fileExt
            ];
            $file = \Api\Models\File::find($this->trg_file_id);           
            $file = $file->update($fileMeta);
            
        }

		//2016-02-24 INSERT QUERY 수정
		$insert_data = array(
			'task_id' => $task_id,
			'media_id' => $media_id,
			'type' => $task_type,
			'status' => $this->task_status,
			'priority' => $this->priority,
			'source' => $source,
			'source_id' => $source_login_info['login_id'],
			'source_pw' => $source_login_info['login_pw'],
			'target' => $target,
			'target_id' => $target_login_info['login_id'],
			'target_pw' => $target_login_info['login_pw'],
			'parameter' => $parameter,
			'creation_datetime' => $cur_time,
			'destination' => $channel,
			'task_workflow_id' => $get_jobs['task_workflow_id'],
			'job_priority' => $get_jobs['job_priority'],
			'task_rule_id' => $get_jobs['task_rule_id'],
			'task_user_id' => $task_user_id,
			'root_task' => $root_task,
			'workflow_rule_id' => $get_jobs['workflow_rule_id'],
			'src_content_id' => $content_id,
			'src_media_id' => $source_media_id,
			'trg_media_id' => $target_media_id,
			'src_storage_id' => $src_storage_id,
			'trg_storage_id' => $trg_storage_id,
            'trg_file_id'   => $this->trg_file_id,
            'src_file_id'   => $this->src_file_id
		);

		//_debug("register_sequence", print_r($insert_data,true));

		$this->db->insert('BC_TASK', $insert_data);

		//_debug("register_sequence", " INSERT COMPLETE ********************************************");

		$this->task_list[$task_id] = array(
			'task_id' => $task_id,
			'type' => $task_type,
			'source' => $source,
			'target' => $target,
			'channel' => $channel,
			'content_id' => $content_id,
			'src_media_id' => $source_media_id,
			'trg_media_id' => $target_media_id,
			'src_storage_info' => $source_login_info,
			'trg_storage_info' => $target_login_info
		);

		//After insert task, sns upload
		if($sns_upload == 'Y') {
			$sns->upload($task_id);
		}

		return $task_id;
	}

	//워크 플로우 작업 흐름 상세 부분에서 조건에 따라 작업 등록  // 조건 체크, 일치시 true 불일치시 false 리턴
	//2011 - 12 - 26
	//이성용
	function conditionChecker( $content_id, $media_id, $task_id, $workflow_rule_id )//콘텐츠 아이디 , 이전 작업의 미디어 아이디 , 다음 작업 룰 아이디
	{
		global $db;

        if ($workflow_rule_id) {
            $task_rule_info = $db->queryRow("select * from BC_TASK_WORKFLOW_RULE tr where tr.workflow_rule_id = $workflow_rule_id ");
        }
        if ($content_id) {
            //콘텐츠 정보
            $content_info = $db->queryRow("select * from  BC_CONTENT c where c.content_id = $content_id ");
        }
        //이전 작업의 미디어 정보
        if ($media_id) {
            $media_info = $db->queryRow("select * from  BC_MEDIA m where m.media_id = $media_id ");
        }

        if ($task_id) {
            //이전 작업 정보
            $task_info = $db->queryRow("select * from  BC_TASK t where t.task_id = $task_id ");
        }

		$condition = $task_rule_info['condition'];//조건 정보

		if(empty($condition) || $condition == 'null')//조건 없을시 계속 진행
		{
			return true;
		}

		$arr_opt = explode('&', $condition);

		$b_ret = false;
		foreach($arr_opt as $opt)
		{
			list($key, $values) = explode('=', $opt);

			switch($key)
			{
				case 'bs_content_id':
					//시스템정의콘텐츠 아이디
					$arr_value = explode(';', $values);

					$bs_content_id_check = false;

					foreach($arr_value as $value)
					{
						if( $value == $content_info['bs_content_id'] ) $bs_content_id_check = true;
					}

					$b_ret = $bs_content_id_check;
				break;

				case 'ud_content_id':
					//사용자정의콘텐츠 아이디
					$arr_value = explode(';', $values);

					$ud_content_id_check = false;

					foreach($arr_value as $value)
					{
						if( $value == $content_info['ud_content_id'] ) $ud_content_id_check = true;
					}

					$b_ret = $ud_content_id_check;

				break;

				case 'reg_user_id':
					//콘텐츠등록자 아이디
					$arr_value = explode(';', $values);

					$reg_user_id_check = false;

					foreach($arr_value as $value)
					{
						if( $value == $content_info['reg_user_id'] ) $reg_user_id_check = true;
					}

					$b_ret = $reg_user_id_check;
				break;

				/*
				case 'task_user_id':
					//작업등록자 아이디

					$arr_value = explode(';', $values);

					$task_user_id_check = false;

					foreach($arr_value as $value)
					{
						if( $value == $task_info['task_user_id'] ) $task_user_id_check = true;
					}

					return $task_user_id_check;

				break;
				*/

				case 'req':
					//종속성, 워크플로우 룰 아이디 참조

					$arr_value = explode(';', $values);

					$req_check = false;

					foreach($arr_value as $value)
					{
						$before_task_workflow_rule = $db->queryRow("select * from BC_TASK_WORKFLOW_RULE where workflow_rule_id=$value");
						if( $before_task_workflow_rule['task_rule_id'] == $task_info['task_rule_id'] ) $req_check = true;
					}

					$b_ret = $req_check;

				break;

				case 'ext'://이전 작업의 미디어 정보의 확장자 체크
					$arr_full_path = explode('/', $media_info['path']);//패스 배열
					$filename = array_pop($arr_full_path);//파일이름
					$arr_filename = explode('.', $filename);//파일 배열
					$ext = array_pop($arr_filename);//확장자

					$arr_value = explode(';', $values);

					$ext_check = false;

					foreach($arr_value as $value)
					{
						if( $value == $ext ) $ext_check = true;
					}

					$b_ret = $ext_check;

				break;
			}
			if(!$b_ret)
				return false;

		}

		return true;
	}

	function add_next_job($task_id) {
		// 작업이 끝났을때 다음 작업이 있는지 체크하여 등록하기
		//--받은 태스크아이디의 잡프리어리티를 체크하여 다음잡이 있으면 태스크에 등록.
		//$get_task_info = $this->db->queryRow("select t.*, m.content_id, m.media_id from bc_task t, bc_media m where t.task_id = $task_id and m.media_id = t.media_id"); //완료한 작업의 job_priority를 구해와서..

		//Change, media id no need here. Also, if media id is 0(media_out), above query not work.
		$get_task_info = $this->db->queryRow("select t.*, t.src_content_id as content_id from bc_task t where t.task_id = $task_id");

		$this->update_content_status($get_task_info['content_id'], $get_task_info , 'task');
		if($get_task_info['task_id'] == '')	return;
		$next_job_priority = $get_task_info['job_priority'] + 1;  // 다음잡= 1을 더한값이 task_workflow_define에 있는지 체크.
        $root_task = $get_task_info['root_task'];
        $priority = $get_task_info['priority'];

		if($root_task != ''){
			$arr_root_task = array(
                'root_task' => $root_task,
                'priority' => $priority,
                'pre_task' => $task_id //이전작업의 정보를 가져오기 위해 추가
            );
			$arr_param_info = array($arr_root_task);
		}

		$channel = $get_task_info['destination'];

		// 2012.03.17 by 이성용
		//트리구조 워크플로우로 변경
		$next_query = " select
							task_rule_id ,TASK_RULE_PARANT_ID ,WORKFLOW_RULE_ID ,WORKFLOW_RULE_PARENT_ID
						 from
							BC_TASK_WORKFLOW_RULE
						 where
							task_workflow_id = $get_task_info[task_workflow_id]
						 and WORKFLOW_RULE_PARENT_ID=$get_task_info[workflow_rule_id] ";

        $get_next_job_infos = $this->db->queryAll($next_query);
        
        //2011.03.21 by 이성용
        //다음 작업이 없을시
		// if (empty($get_next_job_infos) || ( count($get_next_job_infos) == 0)) {
        //     //워크플로우 완료 체크
		// 	if( $this->isCompleteWorkflow($get_task_info[task_workflow_id] , $get_task_info[root_task], $task_id ) ) {
        //         //콘텐츠 상태정보 업데이트
        //         $this->update_content_status( $get_task_info['content_id'] , $get_task_info , 'workflow' );
        //     }
			
		// 	return;
		// }

		foreach ($get_next_job_infos as $get_next_job_info) {
            $content_id = $get_task_info['content_id'];
            

            //조건 작업 처리 
            if( !$this->conditionChecker( $content_id, null, $task_id, $get_next_job_info['workflow_rule_id'] ) ){
                //스킵용 작업상태 추가
                $new_task_id = $this->createTask($content_id, $channel, $next_job_priority, $get_task_info['task_user_id'], $arr_param_info , $get_next_job_info['workflow_rule_id'] );
                continue;
            }

			// 2012.03.17 by 이성용
			//트리구조 워크플로우로 변경

			$new_task_id = $this->insert_task_query($content_id, $channel, $next_job_priority, $get_task_info['task_user_id'], $arr_param_info , $get_next_job_info['workflow_rule_id'] );
			if ($new_task_id) {
				//이전 인터페이스정보가 있는지 확인
				$interface_info = $this->db->queryRow("select * from INTERFACE_CH where TARGET_ID=$task_id");
				if ( ! empty($interface_info)) {
					$this->InsertInterfaceCH($interface_info['interface_id'], $interface_info['interface_channel'], $interface_info['interface_type'],$new_task_id,$content_id);
				}
				// 다음작업이 loudness 관련 작업 일 경우 작업 유형에 따라 ATS로 작업요청
				if(INTERWORK_LOUDNESS == 'Y') {
					$loudness_task_info = $this->db->queryRow("
										SELECT	*
										FROM	BC_TASK
										WHERE	TASK_ID = $new_task_id
									");

					$loudness_task_type = $loudness_task_info['type'];
					$loudness_user_id = $loudness_task_info['task_user_id'];
					$now = date('YmdHis');
					$loudness_id = getSequence('LOUDNESS_SEQ');

					$ats = new ATS();

					switch($loudness_task_type) {
						case LOUDNESS_MEASUREMENT:
							$loudness_type = 'M';

							$this->db->exec("
									INSERT INTO TB_LOUDNESS
										(LOUDNESS_ID, CONTENT_ID, STATE, TASK_ID, REQ_USER_ID, REQ_DATETIME, REQ_TYPE, IS_CORRECT)
									VALUES
										($loudness_id, $content_id, '0', $new_task_id, '$loudness_user_id', '$now', '$loudness_type', 'N')
							");

							$loudness_source_path = LOUDNESS_ROOT_STORAGE.'/'.$loudness_task_info['source'];
							$loudness_new_source_path = str_replace('/', '\\', $loudness_source_path);
							// 함수 처리 필요
							$xml = createLoudnessMeasurementXML($loudness_user_id, $loudness_new_source_path);
							$front_position = strpos($xml, '<AudioToolsServer Version="0.1">');
							$end_position = strpos($xml, '</Response>');

							$xml = substr($xml, $front_position);
							$xml = substr($xml, 0, '-'.(strlen('</Response>')+1));

							$param = array(
									'jobXML' => $xml,
									'jobPriority' => '1'
							);

							$result = $ats->submitJob($param);

							if($result['submitJobResult'] == 0) {
								$jobUID = $result['jobUID'];
								//update
								$this->db->exec("
										UPDATE	TB_LOUDNESS
										SET		JOBUID = '$jobUID'
										WHERE	LOUDNESS_ID = $loudness_id
										AND		JOBUID IS NULL
								");
							}
						break;
						case LOUDNESS_ADJUST :
							$loudness_type = 'A';

							$this->db->exec("
									INSERT INTO TB_LOUDNESS
										(LOUDNESS_ID, CONTENT_ID, STATE, TASK_ID, REQ_USER_ID, REQ_DATETIME, REQ_TYPE, IS_CORRECT)
									VALUES
										($loudness_id, $content_id, '0', $new_task_id, '$loudness_user_id', '$now', '$loudness_type', 'N')
							");

							$loudness_source_path = LOUDNESS_ROOT_STORAGE.'/'.$loudness_task_info['source'];
							$loudness_new_source_path = str_replace('/', '\\', $loudness_source_path);

							$adjust_xml = createLoudnessAdjustXML($loudness_user_id, $loudness_new_source_path, LOUDNESS_STANDARD_LUFS);
							$front_position = strpos($adjust_xml, '<AudioToolsServer Version="0.1">');
							$end_position = strpos($adjust_xml, '</Response>');

							$adjust_xml = substr($adjust_xml, $front_position);
							$adjust_xml = substr($adjust_xml, 0, '-'.(strlen('</Response>')+1));

							$adjust_param = array(
									'jobXML' => $adjust_xml,
									'jobPriority' => '1'
							);

							$adjust_result = $ats->submitJob($adjust_param);

							if($adjust_result['submitJobResult'] == 0) {
								$adjust_jobUID = $adjust_result['jobUID'];
								//update
								$this->db->exec("
										UPDATE	TB_LOUDNESS
										SET		JOBUID = '$adjust_jobUID'
										WHERE	LOUDNESS_ID = $loudness_id
										AND		JOBUID IS NULL
								");
							}
						break;
					}
				}
			}
		}
	}


	function update_filesize($media_id , $size)
	{
        if( !$media_id || is_null($size) ) return false;
		 $this->db->exec("update bc_media set filesize=$size where media_id = $media_id");
	}

	function update_filename($media_id , $filename)
	{
        if( !$media_id ) return false;
		$full_path = $this->db->queryOne("select path from bc_media where media_id = $media_id");

		//$path_array = explode('/', $full_path);
		//array_pop($path_array);//파일명제거
		//$mid_path = implode('/',$path_array);

		$mid_path = pathinfo($full_path, PATHINFO_DIRNAME);
		
		//if, ori dir and filename dir diff, change to filename dir.
		if(!strstr($full_path, '.')) {
			$mid_path = $full_path;
		}

//		$filepath = str_replace('\\', '/', $filename);
//		$filepath = trim($filepath, '/');
//		$filepath_array = explode('/', $filepath);
//		$filename = array_pop($filepath_array);

		$filename = str_replace('\\', '/', $filename);
		$filename = pathinfo($filename, PATHINFO_BASENAME);

		$new_path = $mid_path.'/'.$filename;
		$new_path = $this->db->escape($new_path);

		$this->db->exec("update bc_media set path='$new_path' where media_id = $media_id");
	}

	function update_key($content_id, $media_id , $key)
	{
		//미디어 인덱서에서 추출한 UID 입력
		$check = $this->db->queryOne("select media_id from bc_media_indexer where media_id= $media_id ");
		if( empty($check) )
		{
			$this->db->exec("insert into bc_media_indexer (content_id , media_id , key ) values($content_id, $media_id, '$key' )");
		}
		else
		{
			$this->db->exec("update bc_media_indexer set key='$key' where media_id = $media_id");
		}
	}

	function update_content_status($content_id, $get_task_info , $type )
	{
		global $arr_sys_code;

		if( $type == 'workflow' )//전체 워크플로우 완료시
		{
            //변경 2018.10.19 이승수
            //앞단에서 마지막작업인지 체크 후 넘어오도록. 여기서는 상태값만 변경
            $update_status = $this->db->queryOne("select content_status from BC_TASK_WORKFLOW where task_workflow_id = {$get_task_info['task_workflow_id']} ");
            if( !is_null($update_status) && is_numeric($update_status) )
            {
                $r = $this->db->exec("update bc_content set status='$update_status' where content_id=$content_id ");

                searchUpdate($content_id);
            }
		}
		else if( $type == 'task') //작업단위 완료시
		{
			$query = "select content_status from BC_TASK_WORKFLOW_RULE where task_workflow_id = {$get_task_info['task_workflow_id']} and task_rule_id={$get_task_info['task_rule_id']} and job_priority={$get_task_info['job_priority']} ";
            $update_status = $this->db->queryOne($query);

            if( !is_null($update_status) && is_numeric($update_status) )
            {
               // $update_query = "update bc_content set status='$update_status' where content_id=$content_id ";
                //$r = $this->db->exec($update_query);

                $usrMeta = [];

                $contentService = new \Api\Services\ContentService(app()->getContainer());
                $contentMeta = [
                    'status' => $update_status
                ];                
                $content = \Api\Models\Content::find($content_id);
                $usrMetaInfo = $contentService->findContentUsrMeta($content_id);
                $user = [
                    'user_id' => $get_task_info['task_user_id']
                ];
                $user = (object)$user;

                if ( $content_id && $get_task_info['task_user_id']) {

                    //입수시 콘텐츠 등록 심의 자동 등록                  
                    $reviewService = new \Api\Services\ReviewService(app()->getContainer());   

                    if( \Api\Types\ContentStatusType::WAITING == $update_status  ){                    
						
                        $isAutoAccept = $contentService->isAutoAccept($content, $usrMetaInfo);
                        if( $isAutoAccept ){
                            //방송형태: 구매, 지원이거나  소재형태:  프로그램 아닌것
                            //자동 승인
                            //등록 요청 대상 제외          
                            
                            $usrMeta['othbc_at'] = 'Y' ;//공개여부
                            $usrMeta['reviv_posbl_at'] = 'Y';//재생가능여부

                            $reviewMsg = "자동 콘텐츠 등록 처리";
                            $reviewData = [
                                'content_id' => $content_id,
                                'review_ty_se' => 'content',
                                'title' => $content->title,
                                'reject_cn' => $reviewMsg,
                                'review_user_id' => 'admin'
                            ];
                            $review = $reviewService->createAndComplete((object)$reviewData, $user);
    
                            //승인 상태로 변경
                            $contentMeta['status'] = \Api\Types\ContentStatusType::COMPLETE;
                        }else{
                            $reviewData = [
                                'content_id' => $content_id,
                                'review_ty_se' => 'content',
                                'title' => $content->title
                            ];                        
                            $review = $reviewService->create((object)$reviewData, $user);

							//# 이지서티 API , 개인정보검출 
							$easyCertiBaseURL = env('EASYCERTI_API_URL');
    						$easyCertiClient = new \Api\Modules\EasyCertiClient($easyCertiBaseURL);
							$contentUsrMeta = $easyCertiClient->getMetadata($content_id);

							$param = $easyCertiClient->makeEasyCertiArg($contentUsrMeta, $user->user_id);

							$response = $easyCertiClient->postPersonalInformationDetection($param);
							$easyCertiData = json_decode($response->getContents(), true);

							if (isset($easyCertiData['privacy_summary']['IsPriv']) && $easyCertiData['privacy_summary']['IsPriv'] == '1') {
								//# 검출내역이 있을 시, INDVDLINFO_AT = 'Y'
								$insert = $easyCertiClient->saveINDVDLINFO($easyCertiData['privacy_detail']['content']['content_list'], $content_id);
							} else {
								//# 검출내역이 없을 시, INDVDLINFO_AT = 'N', INDEVELINFO 테이블 row 값 null 처리
								$easyCertiClient->makeINDVDLINFORowDataNull($content_id);
							}
							
                        }
                    }else if( \Api\Types\ContentStatusType::COMPLETE == $update_status ){
                        $usrMeta['othbc_at'] = 'Y' ;//공개여부
                        $usrMeta['reviv_posbl_at'] = 'Y';//재생가능여부

                        $reviewMsg = "자동 콘텐츠 등록 처리";
                        $reviewData = [
                            'content_id' => $content_id,
                            'review_ty_se' => 'content',
                            'title' => $content->title,
                            'reject_cn' => $reviewMsg,
                            'review_user_id' => 'admin'
                        ];
                        $review = $reviewService->createAndComplete((object)$reviewData, $user);
                    }
                }

                $content->status = $contentMeta['status'];

                //포털 메타데이터 변경
                $usrMeta = $contentService->changePortalMeta($content, $usrMetaInfo , $usrMeta );
                    
                //ContentStatusType::COMPLETE
                $contentService->updateUsingArray($content_id, $contentMeta, [],[],$usrMeta, $user);
            }
		}
	}


	function checkResponseXML($response_xml, $request_xml, $host, $port, $page )
	{
		if(empty($response_xml) )
		{
			$err_msg = 'unable to connect';
			$response_xml = $this->db->escape($response_xml);
			$last_id = $this->db->queryOne("select max( LINK_XML_ERROR_LIST_ID ) from LINK_XML_ERROR_LIST ");
			$link_xml_error_list_id = $last_id + 1;
			$query = "insert into  LINK_XML_ERROR_LIST ( LINK_XML_ERROR_LIST_ID,HOST,PORT,PAGE,STATUS,REQUEST_XML,RESPONSE_XML , log ) values ( $link_xml_error_list_id, '$host', '$port', '$page', '', '$request_xml' , '$response_xml' , '$err_msg') ";
			//$r = $this->db->exec($query);
			return false;
		}


		libxml_use_internal_errors(true);
		$xml = simplexml_load_string($response_xml);

		if (!$xml) {
			foreach(libxml_get_errors() as $error)
			{
				$err_msg .= $error->message . "\n";
			}

			$response_xml = $this->db->escape($response_xml);
			$err_msg		= $this->db->escape($err_msg);
			$last_id = $this->db->queryOne("select max( LINK_XML_ERROR_LIST_ID ) from LINK_XML_ERROR_LIST ");
			$link_xml_error_list_id = $last_id + 1;
			$query = "insert into  LINK_XML_ERROR_LIST ( LINK_XML_ERROR_LIST_ID,HOST,PORT,PAGE,STATUS,REQUEST_XML,RESPONSE_XML , log ) values ( $link_xml_error_list_id, '$host', '$port', '$page', '', '$request_xml' , '$response_xml' , '$err_msg') ";
			//$r = $this->db->exec($query);
			return false;
		}
		else
		{
			$result = $xml->Result;

			if( strtoupper($result) != 'SUCCESS' )
			{
				$response_xml = $this->db->escape($response_xml);
				$request_xml		= $this->db->escape($request_xml);
				$result		= $this->db->escape($result);
				$last_id = $this->db->queryOne("select max( LINK_XML_ERROR_LIST_ID ) from LINK_XML_ERROR_LIST ");
				$link_xml_error_list_id = $last_id + 1;
				$query = "insert into  LINK_XML_ERROR_LIST ( LINK_XML_ERROR_LIST_ID,HOST,PORT,PAGE,STATUS,REQUEST_XML,RESPONSE_XML , log ) values ( $link_xml_error_list_id, '$host', '$port', '$page', '', '$request_xml' , '$response_xml' , '$result') ";
				//$r = $this->db->exec($query);

				return false;
			}
		}

		return true;
	}

	function Post_XML_Soket($host, $page, $string, $port='80')
	{
		$fp = fsockopen($host, $port, $errno, $errstr, 30);
		$response = '';
		if (!$fp) {
			return "$errstr ($errno)<br />\n";
		}else{
			$out = "POST /".$page." HTTP/1.1\r\n";
			$out .= "User-Agent: Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1; WOW64; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)\r\n";
			$out .= "Content-type: application/x-www-form-urlencoded\r\n";
			$out .= "Content-length: ". strlen($string) ."\r\n";
			$out .= "Host: ".$host."\r\n";
			$out .= "Connection: Close\r\n\r\n";
			$out .= $string;
			fwrite($fp, $out);
			while (!feof($fp)) {
				$response .= fgets($fp, 128);
			}
			fclose($fp);
		}
		return $response;
	}


	function make_xml($content_id, $user_id, $channel, $file_path = null )
	{

		$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Geminisoft />");

		$get_content_info = $this->db->queryRow("
			SELECT	A.*
					,B.BS_CONTENT_TITLE
					,C.UD_CONTENT_TITLE
			FROM	(
					SELECT	*
					FROM	BC_CONTENT
					WHERE	CONTENT_ID = $content_id
					) A
					LEFT OUTER JOIN
					BC_BS_CONTENT B
					ON (A.BS_CONTENT_ID=B.BS_CONTENT_ID)
					LEFT OUTER JOIN
					BC_UD_CONTENT C
					ON (A.UD_CONTENT_ID=C.UD_CONTENT_ID)
		");
		$user_nm =  $this->db->queryOne("
			SELECT	USER_NM
			FROM	BC_MEMBER
			WHERE	USER_ID='$user_id'
		");

		if($get_content_info['is_group'] == 'G') {
			$is_group = 'Y';
			$arr_content_id = array();
			$get_group_content = $this->db->queryAll("
				SELECT	CONTENT_ID
				FROM	BC_CONTENT
				WHERE	CONTENT_ID=".$content_id."
				OR		PARENT_CONTENT_ID=".$content_id."
				ORDER BY CONTENT_ID
			");
			foreach($get_group_content as $ggc) {
				$arr_content_id[] = $ggc['content_id'];
			}
		} else {
			$is_group = 'N';
			$arr_content_id = array($content_id);
		}

		$request = $xml->addChild("Information");
		$request->addAttribute('TaskChannel', $channel);
		$request->addAttribute('TargetPath', htmlspecialchars($file_path));
		/// Content info ///
		$content = $request->addChild("ContentInformation");
		$make_content = $content->addChild("ContentID", $get_content_info['content_id']);
		$make_content = $content->addChild("Category", $get_content_info['category_id']);
		$make_content = $content->addChild("Title", htmlspecialchars($get_content_info['title']));
		$make_content = $content->addChild("UserID", $user_id); //사용자 아이디는 디비? 세션사용자?
		$make_content = $content->addChild("UserName", $user_nm);
		$make_content = $content->addChild("ExpireDate", $get_content_info['expired_date']);
		$make_content = $content->addChild("IsGroup", $is_group);
		$make_content = $content->addChild("NumberOfContent", count($arr_content_id));

		/// Usr meta info ///
		$usr_meta = $request->addChild("UserDefinedMetadata");
		$usr_meta->addAttribute("TableID", $get_content_info['ud_content_id']);
		$usr_meta->addAttribute("TableName", $get_content_info['ud_content_title']);
		$get_meta_info = MetaDataClass::getFieldValueInfo('usr', $get_content_info['ud_content_id'] , $content_id);
		foreach($get_meta_info as $infos)
		{
			if($infos['usr_meta_field_type'] == 'container') continue;
			$meta_info = $usr_meta->addChild("UsrMeta", htmlspecialchars($infos['value']));
			$meta_info->addAttribute('ID', $infos['usr_meta_field_id']);
			$meta_info->addAttribute('Name', htmlspecialchars($infos['usr_meta_field_title']));
			$meta_info->addAttribute('Code', strtolower($infos['usr_meta_field_code']));
		}

		foreach($arr_content_id as $content_id) {
			$content = $request->addChild("Content");

			/// Media info ///
			$media = $content->addChild("MediaInformation");
			$get_media_info = $this->db->queryAll("
				SELECT	B.PATH AS ROOT_PATH
						,A.*
				FROM	(
						SELECT	*
						FROM	BC_MEDIA
						WHERE	CONTENT_ID = $content_id
						) A
						LEFT OUTER JOIN
						BC_STORAGE B
						ON (A.STORAGE_ID=B.STORAGE_ID)
			");
			foreach($get_media_info as $infos)
			{
				if($infos['root_path'] == '') {
					$infos['root_path'] = strtoupper($infos['media_type']).' PATH';
				}
				$make_media = $media->addChild("Media");
				$make_media->addAttribute('Type', $infos['media_type']);
				$make_media->addAttribute('RootPath', htmlspecialchars($infos['root_path']));
				$make_media->addAttribute('Path', htmlspecialchars($infos['path']));
				$make_media->addAttribute('Filesize', $infos['filesize']);
			}

			/// Sys meta info ///
			$sys_meta = $content->addChild("SystemMetadata");
			$sys_meta->addAttribute("TypeID", $get_content_info['bs_content_id']);
			$sys_meta->addAttribute("TypeName", $get_content_info['bs_content_title']);
			$get_sys_info = MetaDataClass::getFieldValueInfo('sys', $get_content_info['bs_content_id'] , $content_id);
			foreach($get_sys_info as $infos)
			{
				$sys_info = $sys_meta->addChild("SysMeta", htmlspecialchars($infos['value']));
				$sys_info->addAttribute('ID', $infos['sys_meta_field_id']);
				$sys_info->addAttribute('Name', htmlspecialchars($infos['sys_meta_field_title']));
				$sys_info->addAttribute('Code', strtolower($infos['sys_meta_field_code']));
			}
		}





		return $xml->asXML();
	}

	function immediate_send_to_content($content_id)
	{
		return true;
	}

	function extra_work( $task, $content_id )
	{
		$user_id = $task['task_user_id'];

		//PAS로 전송작업 완료시
		if( strstr($task['destination'], 'move_pas') || ( $task['workflow_rule_id'] == 38 ) ) {
			return true;
		}

		//loudness
		$interwork_loudness =  $this->db->queryOne("
			SELECT	COALESCE((
						SELECT	USE_YN
						FROM	BC_SYS_CODE A
						WHERE	A.TYPE_ID = 1
						AND		A.CODE = 'INTERWORK_LOUDNESS'), 'N') AS USE_YN
			FROM	(
					SELECT	USER_ID
					FROM	BC_MEMBER
					WHERE	USER_ID = '".$user_id."') DUAL
		");
		$check_ingest_workflow = $this->db->queryOne("
									SELECT	COUNT(REGISTER)
									FROM	BC_TASK_WORKFLOW
									WHERE	TYPE = 'i'
									AND		ACTIVITY = '1'
									AND		REGISTER = '".$task['destination']."'
								");

		if($check_ingest_workflow > 0 && $interwork_loudness == 'Y' && in_array($task['type'], array(60, 80))) {
			// it will works when the original media has filesize
			$original = $this->db->queryRow("
							SELECT	*
							FROM	BC_MEDIA
							WHERE	CONTENT_ID = $content_id
							AND		MEDIA_TYPE = 'original'
						");
			// it will works when the content do not have loudness media
			$isLoudness = $this->db->queryOne("
							SELECT	COUNT(MEDIA_ID)
							FROM	BC_MEDIA
							WHERE	CONTENT_ID = $content_id
							AND		MEDIA_TYPE = 'loudness'
						");
			// automatiacly do the job when ud_content is use y
			$loudness_config = $this->db->queryRow("
									SELECT	LC.IS_LOUDNESS, LC.IS_CORRECT
									FROM	TB_LOUDNESS_CONFIGURATION LC,
											BC_CONTENT C
									WHERE	LC.UD_CONTENT_ID = C.UD_CONTENT_ID
									AND		LC.CATEGORY_ID = C.CATEGORY_ID
									AND		C.CONTENT_ID = $content_id
							");

			if($original['filesize'] > 0 && $original['status'] != 1 && $isLoudness == 0 && $loudness_config['is_loudness'] == 'Y') {
				$ats = new ATS();
				$channel = 'loudness_measure';

				$loudness_task_id = $this->start_task_workflow($content_id, $channel, $user_id );

				$loudness_type = 'M';
				$is_correct = $loudness_config['is_correct'];

				$now = date('YmdHis');
				$loudness_id = getSequence('LOUDNESS_SEQ');

				$this->db->exec("
						INSERT INTO TB_LOUDNESS
							(LOUDNESS_ID, CONTENT_ID, STATE, TASK_ID, REQ_USER_ID, REQ_DATETIME, REQ_TYPE, IS_CORRECT)
						VALUES
							($loudness_id, $content_id, '0', $loudness_task_id, '$user_id', '$now', '$loudness_type', '$is_correct')
				");

				//$ats = new ATS();

				$loudness_task_info = $this->db->queryRow("
											SELECT	*
											FROM	BC_TASK
											WHERE	TASK_ID = $loudness_task_id
									");

				$loudness_source = $loudness_task_info['source_root'].'/'.$loudness_task_info['source'];
				$loudness_target = $loudness_task_info['target_root'].'/'.$loudness_task_info['target'];
				$new_path = str_replace('/', '\\', $loudness_task_info['source']);
				// source 경로에 대해서는 수정 작업 필요
				//$loudness_source = '\\\\192.168.1.202\\Storage\\Storage\\highres\\'.$new_path;
				$loudness_source = LOUDNESS_ROOT_STORAGE.'\\'.$new_path;
				// 함수 처리 필요
				$xml = createLoudnessMeasurementXML($user_id, $loudness_source);
				$front_position = strpos($xml, '<AudioToolsServer Version="0.1">');
				$end_position = strpos($xml, '</Response>');

				$xml = substr($xml, $front_position);
				$xml = substr($xml, 0, '-'.(strlen('</Response>')+1));

				$param = array(
						'jobXML' => $xml,
						'jobPriority' => '1'
				);

				$result = $ats->submitJob($param);

				if($result['submitJobResult'] == 0) {
					$jobUID = $result['jobUID'];
					//update
					$this->db->exec("
							UPDATE	TB_LOUDNESS
							SET		JOBUID = '$jobUID'
							WHERE	LOUDNESS_ID = $loudness_id
							AND		JOBUID IS NULL
					");
				}
			}
		}
		/* loudness changed the rule - 2016.05.03 by Alex
		// send fix Loudness after FS
		if(strstr($task['destination'], 'loudness_adjust') && $task['type'] == 60) {
			$adjust_ats = new ATS();

			$loudness_adjust_task_info = $this->db->queryRow("
											SELECT	*
											FROM	BC_TASK
											WHERE	ROOT_TASK = ".$task['root_task']."
											AND		TYPE = ".LOUDNESS_ADJUST
										);

			if(!empty ($loudness_adjust_task_info)) {
				$loudness_type = 'A';
				$is_correct = 'Y';

				$now = date('YmdHis');
				$loudness_adjust_id = getSequence('LOUDNESS_SEQ');

				$loudness_adjust_task_id = $loudness_adjust_task_info['task_id'];

				$this->db->exec("
						INSERT INTO TB_LOUDNESS
							(LOUDNESS_ID, CONTENT_ID, STATE, TASK_ID, REQ_USER_ID, REQ_DATETIME, REQ_TYPE, IS_CORRECT)
						VALUES
							($loudness_adjust_id, $content_id, '0', $loudness_adjust_task_id, '$user_id', '$now', '$loudness_type', '$is_correct')
				");

				$adjust_source = $loudness_adjust_task_info['source_root'].'/'.$loudness_adjust_task_info['source'];

				$new_path = str_replace('/', '\\', $loudness_adjust_task_info['source']);
				// source 경로에 대해서는 수정 작업 필요
				$adjust_source = '\\\\192.168.1.202\\Storage\\Storage\\highres\\'.$new_path;

				$adjust_xml = createLoudnessAdjustXML($user_id, $adjust_source, $standard);
				$front_position = strpos($adjust_xml, '<AudioToolsServer Version="0.1">');
				$end_position = strpos($adjust_xml, '</Response>');

				$adjust_xml = substr($adjust_xml, $front_position);
				$adjust_xml = substr($adjust_xml, 0, '-'.(strlen('</Response>')+1));

				$adjust_param = array(
						'jobXML' => $adjust_xml,
						'jobPriority' => '1'
				);
				//@file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/ats_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] query ===> '.$adjust_xml."\r\n", FILE_APPEND);


				$adjust_result = $adjust_ats->submitJob($adjust_param);

				if($adjust_result['submitJobResult'] == 0) {
					$adjust_jobUID = $adjust_result['jobUID'];
					//update
					$this->db->exec("
							UPDATE	TB_LOUDNESS
							SET		JOBUID = '$adjust_jobUID'
							WHERE	LOUDNESS_ID = $loudness_adjust_id
							AND		JOBUID IS NULL
					");
				}
			}
		}*/

		return true;
	}

	function getStorageInfo($content_id, $task_info)
	{
		$content_info = $this->db->queryRow("select * from view_content where content_id=$content_id  ");
		$ud_storage_group_id = $content_info['ud_storage_group_id'];

		//사용자 그룹 여부 확인
		if( empty($ud_storage_group_id) ) return false;

		$isChangeInfo = $this->db->queryAll("select * from bc_ud_storage_group_map where storage_group_id=$ud_storage_group_id " );

		if( empty($isChangeInfo) ) return false;
		$str_map_info = array();
		foreach($isChangeInfo as $info)
		{
			$str_map_info [$info['source_storage_id']] = $info['ud_storage_id'];
		}

		if(empty($task_info['workflow_rule_id'])){

			$storage_info = $this->db->queryRow("select source_path,target_path from BC_TASK_RULE where task_rule_id={$task_info['task_rule_id']}");


			$new_source_path = $storage_info['source_path'];
			$new_target_path = $storage_info['target_path'];
			//매핑된 스토리지ID일경우
			if( !empty( $str_map_info[$storage_info['source_path']] ) ){
				$new_source_path = $str_map_info[$storage_info['source_path']];
			}

			if( !empty( $str_map_info[$storage_info['target_path']] ) ){
				$new_target_path = $str_map_info[$storage_info['target_path']];
			}

		}else{
			$storage_info = $this->db->queryRow(" select r.source_path,r.target_path,tr.source_path_id,tr.target_path_id from BC_TASK_WORKFLOW_RULE tr, BC_TASK_RULE r where tr.task_rule_id=r.task_rule_id and tr.WORKFLOW_RULE_ID={$task_info['workflow_rule_id']}");


			$new_source_path = $storage_info['source_path'];
			$new_target_path = $storage_info['target_path'];

			if( !empty( $str_map_info[$storage_info['source_path']] ) ){
				$new_source_path = $str_map_info[$storage_info['source_path']];
			}

			if( !empty( $str_map_info[$storage_info['target_path']] ) ){
				$new_target_path = $str_map_info[$storage_info['target_path']];
			}

			if( !empty( $storage_info['source_path_id'] ) ){
				$new_source_path = $storage_info['source_path_id'];
			}

			if( !empty( $storage_info['target_path_id'] ) ){
				$new_target_path = $storage_info['target_path_id'];
			}

			if( !empty( $str_map_info[$storage_info['source_path_id']] ) ){
				$new_source_path = $str_map_info[$storage_info['source_path_id']];
			}

			if( !empty( $str_map_info[$storage_info['target_path_id']] ) ){
				$new_target_path = $str_map_info[$storage_info['target_path_id']];
			}


		}

		return array(
			'source_path' => $new_source_path,
			'target_path' => $new_target_path
		);
	}


	function checkStorageGroup($content_id, $task_info)
	{
		//스토리지 그룹 정보가 있을경우 해당 콘텐츠의 정보가 대상 스토리지 그룹과 일치하지 않을때 스킵처리 한다 2013-01-18 이성용
		//스토리지 그룹 정보가 없다면 진행.
		//true => skip
		//false => 작업 진행

		if( empty($task_info['storage_group']) || ($task_info['storage_group'] == 0) ){
			return false;
		}

		$content_info = $this->db->queryRow("select * from view_content where content_id=$content_id  ");

		if( empty( $content_info ) || empty($content_info['storage_group']) ) {
			return false;
		}

		//스토리지 그룹정보가 있는데 다를경우
		if( !( $content_info['storage_group'] & $task_info['storage_group'] ) ){
			return true;
		}

	}


	// function checkLimitStorage($task_type,$type,$storage_id){

	// 	$islimit = false;

	// 	$limit_info = $this->getLimitStorage($task_type, $type );

	// 	if( !empty($limit_info) ){
	// 		foreach($limit_info as $info){

	// 			if( ( $info['c_limit'] <=  $info['cnt'] ) && ( $info['storage_id'] == $storage_id ) ){
	// 				$islimit = true;
	// 			}
	// 		}
	// 	}
	// 	return $islimit;
    // }
    

    function checkLimitStorage($type, $src_id, $trg_id){
		$islimit = false;
		$check_date = date("YmdHis", strtotime("-2 day"));
		$ftp_storage = $this->db->queryRow("
							SELECT	STORAGE_ID, LIMIT_SESSION
							FROM	BC_STORAGE
							WHERE	TYPE = 'FTP'
							AND STORAGE_ID IN ($src_id, $trg_id)
						");
	
		$search_storage = $ftp_storage['storage_id'];
		
		if($ftp_storage['limit_session'] != '0') {
            //0은 unlimit로 체크 안함
            //start_datetime이 있으며 queue인 상태는, 작업관리에서 재시작시에 발생함
            $count = $this->db->queryOne("
                        SELECT	SUM(CNT) AS CNT
                        FROM	(
                            (
                                SELECT	COUNT(TASK_ID) AS CNT
                                FROM	BC_TASK
                                WHERE	STATUS IN ('processing')
                                AND		TYPE = '$type'
                                AND		(SRC_STORAGE_ID = $search_storage OR TRG_STORAGE_ID = $search_storage)
                                AND		START_DATETIME > '$check_date'
                            )
                            UNION ALL
                            (
                                SELECT	COUNT(TASK_ID) AS CNT
                                FROM	BC_TASK
                                WHERE	STATUS IN ('assigning', 'queue')
                                AND		TYPE = '$type'
                                AND		(SRC_STORAGE_ID = $search_storage OR TRG_STORAGE_ID = $search_storage)
                            )
                        ) A
					");
			if($count >= $ftp_storage['limit_session']) {
				$islimit = true;
			}
		}

		return $islimit;
	}

    function updateQueueStatus($task_type){
//		return;
		$current_status = 'pending';
		$next_status	= 'queue';

		$pending = $this->db->queryRow("
						SELECT	*
						FROM	BC_TASK
						WHERE	STATUS = '$current_status'
                        AND		TYPE = '$task_type'
                        ORDER BY PRIORITY, TASK_ID
					");


		if( count($pending) < 1 ){
			return true;
		}

        $src_stg_id = $pending['src_storage_id'];
        $trg_stg_id = $pending['trg_storage_id'];
        $task_id = $pending['task_id'];
        
        $chk_limit = $this->checkLimitStorage($task_type, $src_stg_id, $trg_stg_id);

        if(!$chk_limit) {
            $this->db->exec("
                UPDATE	BC_TASK
                SET		STATUS = '$next_status'
                WHERE	TASK_ID = $task_id
            ");
        }

	}

	// function updateQueueStatus($task_type){

	// 	$current_status = 'pending';
	// 	$next_status	= 'queue';

	// 	$isPending = $this->db->queryOne("select count(task_id) from bc_task where status='$current_status' ");
	// 	$this->_log('','isPending',"select count(task_id) from bc_task where status='$current_status' ");
	// 	if( $isPending < 1 ){
	// 		return true;
	// 	}

	// 	$source_limit_info = $this->getLimitStorage($task_type,'source');
	// 	$this->_log('','source_limit_info',print_r($source_limit_info, true));
	// 	if( !empty($source_limit_info) ){
	// 		foreach($source_limit_info as $info ){
	// 			if( $info['c_limit'] >  $info['cnt'] ){
	// 				$permit = $info['c_limit'] - $info['cnt'];
	// 				if( $permit > 0 ){
	// 					if(DB_TYPE == 'oracle') {
	// 						$query = "update
	// 							bc_task
	// 								set status='$next_status'
	// 							where task_id in (
	// 								select
	// 									task_id
	// 								from (
	// 									select t.task_id
	// 										from bc_task t, bc_task_storage ts
	// 									where t.task_id=ts.task_id
	// 									and t.status='$current_status'
	// 									and ts.src_storage_id='{$info['storage_id']}'
	// 									order by t.priority , t.creation_datetime, t.job_priority
	// 								)
	// 								where ROWNUM <= $permit
	// 							) and status='$current_status' ";
	// 					} else {
	// 						$query = "update
	// 							bc_task
	// 								set status='$next_status'
	// 							where task_id in (
	// 								select
	// 									task_id
	// 								from (
	// 									select t.task_id
	// 										from bc_task t, bc_task_storage ts
	// 									where t.task_id=ts.task_id
	// 									and t.status='$current_status'
	// 									and ts.src_storage_id='{$info['storage_id']}'
	// 									order by t.priority , t.creation_datetime, t.job_priority
	// 								)
	// 								where LIMIT $permit
	// 							) and status='$current_status' ";
	// 					}
						
	// 					$this->_log('','query',$query);
	// 					$r =$this->db->exec($query);
	// 				}
	// 			}
	// 		}
	// 	}
	// 	$target_limit_info = $this->getLimitStorage($task_type,'target');
	// 	$this->_log('','target_limit_info',print_r($target_limit_info, true));
	// 	if( !empty($target_limit_info) ){
	// 		foreach($target_limit_info as $info ){
	// 			if( $info['c_limit'] >  $info['cnt'] ){
	// 				$permit = $info['c_limit'] - $info['cnt'];
	// 				if( $permit > 0 ){
	// 					if(DB_TYPE == 'oracle') {
	// 						$query = "update
	// 							bc_task
	// 								set status='$next_status'
	// 							where task_id in (
	// 								select
	// 									task_id
	// 								from (
	// 									select t.task_id
	// 										from bc_task t, bc_task_storage ts
	// 									where t.task_id=ts.task_id
	// 									and t.status='$current_status'
	// 									and ts.trg_storage_id='{$info['storage_id']}'
	// 									order by t.priority , t.creation_datetime, t.job_priority
	// 								)
	// 								where ROWNUM <= $permit
	// 							)  and status='$current_status' ";
	// 					} else {
	// 						$query = "update
	// 							bc_task
	// 								set status='$next_status'
	// 							where task_id in (
	// 								select
	// 									task_id
	// 								from (
	// 									select t.task_id
	// 										from bc_task t, bc_task_storage ts
	// 									where t.task_id=ts.task_id
	// 									and t.status='$current_status'
	// 									and ts.trg_storage_id='{$info['storage_id']}'
	// 									order by t.priority , t.creation_datetime, t.job_priority
	// 								)
	// 								where LIMIT $permit
	// 							)  and status='$current_status' ";
	// 					}
						
	// 					$this->_log('','query',$query);
	// 					$r = $this->db->exec($query);
	// 				}
	// 			}
	// 		}
	// 	}
	// }

	function getLimitStorage($task_type,$type){
        return false;
		// if( $type == 'target' ){
		// //write limit
		// 	if(DB_TYPE == 'oracle') {
		// 		$query = "SELECT
		// 				S.STORAGE_ID, S.WRITE_LIMIT C_LIMIT, S_CNT.CNT CNT
		// 			FROM
		// 				(
		// 					SELECT
		// 						TRG_STORAGE_ID, COUNT(TASK_ID) CNT
		// 					FROM (
		// 						SELECT
		// 							( SELECT TS.TRG_STORAGE_ID FROM BC_TASK_STORAGE TS WHERE T.TASK_ID=TS.TASK_ID ) TRG_STORAGE_ID, T.TASK_ID
		// 						FROM
		// 							(
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='queue' )
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='processing' AND to_date(START_DATETIME, 'yyyymmddhh24miss' ) > sysdate -1)
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='assigning'  AND to_date(START_DATETIME, 'yyyymmddhh24miss' ) > sysdate -1)
		// 						) T
		// 					)
		// 					WHERE
		// 						TRG_STORAGE_ID > 0
		// 					GROUP BY
		// 						TRG_STORAGE_ID ) S_CNT
		// 			RIGHT OUTER JOIN
		// 				(SELECT * FROM BC_STORAGE WHERE WRITE_LIMIT > 0) S
		// 			ON (S.STORAGE_ID=S_CNT.TRG_STORAGE_ID) ";
		// 	} else {
		// 		$query = "SELECT
		// 				S.STORAGE_ID, S.WRITE_LIMIT C_LIMIT, S_CNT.CNT CNT
		// 			FROM
		// 				(
		// 					SELECT
		// 						TRG_STORAGE_ID, COUNT(TASK_ID) CNT
		// 					FROM (
		// 						SELECT
		// 							( SELECT TS.TRG_STORAGE_ID FROM BC_TASK_STORAGE TS WHERE T.TASK_ID=TS.TASK_ID ) TRG_STORAGE_ID, T.TASK_ID
		// 						FROM
		// 							(
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='queue' )
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='processing' AND START_DATETIME > to_char((current_timestamp+'-1 day'), 'yyyymmddhh24miss'))
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='assigning'  AND START_DATETIME > to_char((current_timestamp+'-1 day'), 'yyyymmddhh24miss'))
		// 						) T
		// 					) TT
		// 					WHERE
		// 						TRG_STORAGE_ID > 0
		// 					GROUP BY
		// 						TRG_STORAGE_ID ) S_CNT
		// 			RIGHT OUTER JOIN
		// 				(SELECT * FROM BC_STORAGE WHERE WRITE_LIMIT > 0) S
		// 			ON (S.STORAGE_ID=S_CNT.TRG_STORAGE_ID) ";
		// 	}
			
		// }else if( $type == 'source' ){
		// 	//read limit
		// 	if(DB_TYPE == 'oracle') {
		// 		$query = "SELECT
		// 				S.STORAGE_ID, S.READ_LIMIT C_LIMIT, S_CNT.CNT CNT
		// 			FROM
		// 				(
		// 					SELECT
		// 						TRG_STORAGE_ID, COUNT(TASK_ID) CNT
		// 					FROM (
		// 						SELECT
		// 							( SELECT TS.TRG_STORAGE_ID FROM BC_TASK_STORAGE TS WHERE T.TASK_ID=TS.TASK_ID ) TRG_STORAGE_ID, T.TASK_ID
		// 						FROM
		// 							(
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='queue' )
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='processing' AND to_date(START_DATETIME, 'yyyymmddhh24miss' ) > sysdate -1)
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='assigning'  AND to_date(START_DATETIME, 'yyyymmddhh24miss' ) > sysdate -1)
		// 						) T
		// 					)
		// 					WHERE
		// 						TRG_STORAGE_ID > 0
		// 					GROUP BY
		// 						TRG_STORAGE_ID ) S_CNT
		// 			RIGHT OUTER JOIN
		// 				(SELECT * FROM BC_STORAGE WHERE READ_LIMIT > 0) S
		// 			ON (S.STORAGE_ID=S_CNT.TRG_STORAGE_ID) ";
		// 	} else {
		// 		$query = "SELECT
		// 				S.STORAGE_ID, S.READ_LIMIT C_LIMIT, S_CNT.CNT CNT
		// 			FROM
		// 				(
		// 					SELECT
		// 						TRG_STORAGE_ID, COUNT(TASK_ID) CNT
		// 					FROM (
		// 						SELECT
		// 							( SELECT TS.TRG_STORAGE_ID FROM BC_TASK_STORAGE TS WHERE T.TASK_ID=TS.TASK_ID ) TRG_STORAGE_ID, T.TASK_ID
		// 						FROM
		// 							(
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='queue' )
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='processing' AND START_DATETIME > to_char((current_timestamp+'-1 day'), 'yyyymmddhh24miss'))
		// 								UNION ALL
		// 								(SELECT TASK_ID FROM BC_TASK WHERE TYPE='$task_type' AND STATUS='assigning' AND START_DATETIME > to_char((current_timestamp+'-1 day'), 'yyyymmddhh24miss'))
		// 						) T
		// 					) TT
		// 					WHERE
		// 						TRG_STORAGE_ID > 0
		// 					GROUP BY
		// 						TRG_STORAGE_ID ) S_CNT
		// 			RIGHT OUTER JOIN
		// 				(SELECT * FROM BC_STORAGE WHERE READ_LIMIT > 0) S
		// 			ON (S.STORAGE_ID=S_CNT.TRG_STORAGE_ID) ";
		// 	}
			

		// }else{
		// 	return false;
		// }
		// $this->_log('','query',$query);
		// $limit_info  = $this->db->queryAll($query);

		// if( empty($limit_info) ){
		// 	return false;
		// }

		// return $limit_info;
    }
    
    function isCompleteWorkflow($task_workflow_id , $root_task, $task_id ){
        /**
         * 워크플로우가 전부 완료했는지 체크하는 함수
         * 워크플로우 완료 후 다음 처리시 사용
         * 현재 add_next_job에서 체크
         */
       $workflow_plan_list = $this->db->queryAll("
       SELECT
            WR.TASK_RULE_ID ,
            WR.TASK_RULE_PARANT_ID ,
            WR.WORKFLOW_RULE_ID ,
            WR.WORKFLOW_RULE_PARENT_ID,
            T.TASK_ID, 
            T.STATUS
        FROM
            ( SELECT * FROM  BC_TASK_WORKFLOW_RULE WR WHERE WR.TASK_WORKFLOW_ID = $task_workflow_id ) WR
        LEFT JOIN 
            (SELECT * FROM  BC_TASK T WHERE ROOT_TASK = $root_task ) T
        ON (  WR.WORKFLOW_RULE_ID=T.WORKFLOW_RULE_ID )
       ");

        if( empty($workflow_plan_list) ){
            return false;
        }

        $return = true;
        foreach($workflow_plan_list as $list){

            $check_task_id  = $list['task_id'];
            $check_status   = $list['status'];

            if( $check_task_id == $task_id ){
                //현재 작업은 아직 업데이트가 안됬으므로 완료라고 판단
                $check_status = 'complete';
            }
            

            //작업이 완료 안된게 있으면 제외 
            if( $check_status != 'complete' ){
                $return = false; 
            }
        }
        return $return; 
    }

    /**
     * 스킵용 작업 생성
     *
     * @param [type] $content_id
     * @param [type] $channel
     * @param [type] $job_priority
     * @param [type] $task_user_id
     * @param [type] $arr_param_info
     * @param [type] $workflow_rule_id
     * @param [type] $options
     * @return void
     */
    function createTask($content_id, $channel, $job_priority, $task_user_id, $arr_param_info=null , $workflow_rule_id=null, $options = null){
        		//_debug("register_sequence", "insert_task_query FUNC  *** channel : ".$channel);
        $query = "SELECT * FROM BC_TASK_WORKFLOW WHERE REGISTER = '$channel' AND TYPE != 'p'";

        $task_workflow_info = $this->db->queryRow($query);
        $task_workflow_id = $task_workflow_info['task_workflow_id'];
        $wf_bs_content_id = $task_workflow_info['bs_content_id'];


        $bc_content_info = $this->db->queryRow("select * from bc_content where content_id=".$content_id);
        $ct_bs_content_id = $bc_content_info['bs_content_id'];

        if($wf_bs_content_id > 0) {
            $query = "SELECT * FROM BC_TASK_WORKFLOW
                WHERE REGISTER = '$channel' AND TYPE != 'p' AND BS_CONTENT_ID = ".$ct_bs_content_id;
            $task_workflow_info = $this->db->queryRow($query);
            $task_workflow_id = $task_workflow_info['task_workflow_id'];
            $wf_bs_content_id = $task_workflow_info['bs_content_id'];
        }


		$query = "
			select (SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = r.SOURCE_PATH_ID) SRC_RULE_PATH,
						(SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = r.TARGET_PATH_ID) TRG_RULE_PATH,
						r.*
			from 	bc_task_workflow_rule r
			where 	task_workflow_id = $task_workflow_id
			and 	workflow_rule_id = $workflow_rule_id
			and 	job_priority = {$job_priority}";

		//_debug("register_sequence", "insert_task_query query  ***1   : ".$query);
		$get_jobs = $this->db->queryRow($query);

		//AD OD 워크플로우 구분 2013-01-18 이성용
		//if( $this->checkStorageGroup($content_id, $get_jobs) ) return false;
		//$getStorageInfo = $this->getStorageInfo($content_id, $get_jobs);
		$query = "
			select tr.*, tt.type
			from 	bc_task_rule tr, bc_task_type tt
			where 	tr.task_type_id = tt.task_type_id
			and 	tr.task_rule_id = {$get_jobs['task_rule_id']}";
		//_debug("register_sequence", "insert_task_query query  ***2  : ".$query);
		$task_rule = $this->db->queryRow($query);


		$task_type = $task_rule['type'];
		$parameter = $task_rule['parameter'];

		if ($arr_param_info != null) {
			if(!empty($arr_param_info[0]['root_task'])){
				$root_task = $arr_param_info[0]['root_task'];
			}
		}

		$cur_time = date('YmdHis');
		$source = 'null';
		$target = 'null';

        $task_status ='skip';

		$source_media_id = 0;
        $target_media_id = 0;
        $src_storage_id = 0;
		$trg_storage_id = 0;

        
		$task_id = getSequence('TASK_SEQ');
		$insert_data = array(
			'task_id' => $task_id,
			'media_id' => $target_media_id,
			'type' => $task_type,
			'status' => $task_status,
			'priority' => $this->priority,
			'source' => $source,
			//'source_id' => $source_login_info['login_id'],
			//'source_pw' => $source_login_info['login_pw'],
			'target' => $target,
			//'target_id' => $target_login_info['login_id'],
			//'target_pw' => $target_login_info['login_pw'],
			'parameter' => $parameter,
			'creation_datetime' => $cur_time,
			'destination' => $channel,
			'task_workflow_id' => $get_jobs['task_workflow_id'],
			'job_priority' => $get_jobs['job_priority'],
			'task_rule_id' => $get_jobs['task_rule_id'],
			'task_user_id' => $task_user_id,
			'root_task' => $root_task,
			'workflow_rule_id' => $get_jobs['workflow_rule_id'],
			'src_content_id' => $content_id,
			'src_media_id' => $source_media_id,
			'trg_media_id' => $target_media_id,
			'src_storage_id' => $src_storage_id,
			'trg_storage_id' => $trg_storage_id
		);

		//_debug("register_sequence", print_r($insert_data,true));

        $this->db->insert('BC_TASK', $insert_data);
        return $task_id;
    }

	function _log($filename,$name,$contents){

		$root = $_SERVER['DOCUMENT_ROOT'].'/log/';
		if(empty($filename)){
			$filename = 'task_mamager_'.date('Y-m-d').'.log';
		}
		@file_put_contents($root.$filename, "\n".$_SERVER['REMOTE_ADDR']."\t".date('Y-m-d H:i:s')."]\t".$name." : \n".print_r($contents,true)."\n", FILE_APPEND);
	}
}
?>

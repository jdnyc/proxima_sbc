<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');

class Zodiac {
	const RUNDOWN_SOAP_URL = SOAP_ZODIAC_RUNDOWN;
	const ARTICLE_SOAP_URL = SOAP_ZODIAC_ARTICLE;
	const USER_SOAP_URL = SOAP_ZODIAC_USER;
	
	public $rundown_soap;
	public $article_soap;
	public $user_soap;

	function __construct($url = null) {     
        if( !$url ){
            $rundownUrl = self::RUNDOWN_SOAP_URL;
            $articleUrl = self::ARTICLE_SOAP_URL;
            $userUrl = self::USER_SOAP_URL;
        }else{
            $rundownUrl = $url['rundownUrl'];
            $articleUrl = $url['articleUrl'];
            $userUrl = $url['userUrl'];
        }
		$this->rundown_soap = new nusoap_client($rundownUrl, TRUE);
		$this->article_soap = new nusoap_client($articleUrl, TRUE);
        $this->user_soap = new nusoap_client($userUrl, TRUE);
        $this->user_soap->soap_defencoding = 'UTF-8';
        $this->user_soap->decode_utf8 = false;
	}

	/**
	* 작업진행상태 업데이트
	* @param $param
	* @return bool|string
	*/
	function putUpdateContentsTransfer($param) {

		$return = $this->rundown_soap->call('putUpdateContentsTransfer', array(
								'plyout_id' => $param['plyout_id'],
								'media_cd' => $param['media_cd'],
								'trnsf_rate' => $param['trnsf_rate'],
								'trnst_st' => $param['trnst_st'],
								'server' => $param['server'],
								'server_ip' => $param['server_ip'],
								'message' => $param['message'],
								'usr_id' => $param['usr_id']
				));

		if ( ! empty($return->return)) {
			$reencode = json_encode($return->return);
			$result = json_decode($reencode, true);
		} else {
			$result = $return;
		}

		return $result;
	}
	
	/**
	 * 의뢰상태 업데이트
	 * @param $param
	 * @return bool|string
	 */
	function putUpdateRequestStatus ($param) {

		$return = $this->article_soap->call('putUpdateRequestStatus', array(
			'request_id'		=>	$param['request_id'],
			'status_cd'			=>	$param['status_cd'],
			'worker_id'			=>	$param['worker_id'],
			'worker_message'	=>	$param['worker_message'],
			'user_id'			=>	$param['user_id'],
			'ord_title'			=>	$param['ord_title'],
			'ord_ctt'			=>	$param['ord_ctt'],
			'updtr_id'			=>	$param['updtr_id'],
			'updt_dtm'			=>	$param['updt_dtm'],
			'completed_id'		=>	$param['completed_id'],
			'completed_dtm'		=>	$param['completed_dtm']
		));
	
		if ( ! empty($return->return)) {
			$reencode = json_encode($return->return);
			$result = json_decode($reencode, true);
		} else {
			$result = $return;
		}
	
		return $result;
	}
	
	/**
	 * 보도정보 사용자 연계
	 * @param $param
	 * @return bool|string
	 */
	function userManage ($param) {
        InterfaceClass::_LogFile('', 'Client call', 'function:'.'userManage'.', param:'.print_r($param, true));
		$return = $this->user_soap->call('userManage', array(
				'action'			=>	$param['action'],
				'user_id'			=>	$param['user_id'],
				'user_nm'			=>	$param['user_nm'],
				'password'			=>	$param['password'],
				'interPhone'		=>	$param['interPhone'],
				'homePhone'			=>	$param['homePhone'],
				'handPhone'			=>	$param['handPhone'],
				'email'				=>	$param['email'],
				'rmk'				=>	$param['rmk'],
				'update_user_id'	=>	$param['update_user_id']
        ));
        InterfaceClass::_LogFile('', 'Client call', 'function:'.'userManage'.', return:'.print_r($return, true));
		if ( !empty($return['return'])) {
			//$reencode = json_encode($return->return);
            if( !($result = json_decode($return['return'], true) ) ){
                $result = simplexml_load_string($return['return']);
                $success = (bool)$result->result['success'];
                $msg =(string)$result->result['msg'];

                $result = [
                    'success' => $success,
                    'msg' => $msg
                ];
                
            }
		} else {
			$result = $return;
		}
	
		return $result;
    }
        
        
    function getCaption($wsdl, $post,$test_user_id){
        $request_data = array(
            'artcl_id'	=> $post['artcl_id'],
            'rd_id'		=> $post['rd_id'],
            'rd_seq'	=> (string)$post['rd_seq'],
            'usr_id' => $test_user_id,
            'format' => 'JSON'
        );
        

        $return = InterfaceClass::client($wsdl, 'getSelectArticleCaptionText' , $request_data);
        $r_data = json_decode($return['return'], true);

        $result = array(
            'success' => $r_data['result']['success'],
            'data' => $r_data['data']['record']['caption']
        );
        
        return $result;
    }

    function updateOrder($wsdl, $post,$test_user_id){

        /*
            [action] => update_order
        [type] => 002
        [media_cd] => 001
        [artcl_id] => 
        [rd_id] => 20160701131800RN06959
        [rd_seq] => 8
        [store] => [{"object_id":"253024","playout_id":"P2016070300936"},{"object_id":"253023","playout_id"
        :"P2016070300935"},{"object_id":"253022","playout_id":"P2016070300934"},{"object_id":"253014","playout_id"
        :"P2016070200926"}]

        type : 001(기사), 002(큐시트)
        artcl_id : 기사아이디
        rd_id : 런다운아이디
        rd_seq : 런다운시퀀스
        media_cd : 001(영상), 002(그래픽)
        playout_ord : 순번
        object_id : 컨텐츠아이디
        playout_id : 송출아이디
        user_id : 사용자아이디
        */

        $store = json_decode($post['store'], true);
        $array_request = array();
        foreach( $store as $key=>$data ){
            $request = array(
                'type' => $post['type'],
                'media_cd' => $post['media_cd'],
                'artcl_id' => $post['artcl_id'],
                'rd_id' => $post['rd_id'],
                'rd_seq' => $post['rd_seq'],
                'playout_ord' => $key.'',
                'object_id' => $data['object_id'],
                'playout_id' => $data['playout_id'],
                'user_id' => $test_user_id
            );
            array_push($array_request, $request);
        }



        $request_data = array(
            'order_list' => json_encode(array('data' => $array_request))
        );
        

        $return = InterfaceClass::client($wsdl, 'putUpdateContentsOrder' , $request_data);
        $r_data = json_decode($return['return'], true);

        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record']
        );
        
        return $result;
    }
    function requestListArticle($wsdl, $post,$test_user_id){
        $search = json_decode($post['search'], true);
        $artcl_titl = empty($search['artcl_titl']) ? '' : $search['artcl_titl'];
        $artcl_frm_cd = empty($search['artcl_frm_cd']) || $search['artcl_frm_cd'] == 'value' ? '' : $search['artcl_frm_cd'];
        $issu_id = empty($search['issu_id']) || $search['issu_id'] == 'value' ? '' : $search['issu_id'];
        $apprv_div_cd = empty($search['apprv_div_cd']) || $search['apprv_div_cd'] == 'value' || $search['apprv_div_cd'] == 'all' ? '' : $search['apprv_div_cd'];


        $start_date = empty($search['start_date']) ? date("Y-m-d", strtotime(date("Ymd")."-3 month")) : $search['start_date'];
        $end_date = empty($search['end_date']) ? date("Y-m-d") : $search['end_date'];
        $start = empty($post['start']) ? 0 : $post['start'];
        $limit = empty($post['limit']) ? 20 : $post['limit'];
        $page = ($start+1)/$limit;
        if($page == 0 ){
            $curPage = 1;
        }else{
            $curPage = ceil($page);
        }
        $request = array(
            'orderXml' => '',
            'srcXml' => $artcl_titl,
            'frnoti_yn' => 'N',
            'frm_cd' => $artcl_frm_cd,
            'fld_cd' => '',
            'dept_cd' => '',
            'artcl_type' => $issu_id,
            'issu_id' => '',
            'issu_seq' => '',
            'issu_dt' => '',
            'sns_yn' => 'N',
            'apr_cd' => $apprv_div_cd,
            'artcl_target_gubun' => '',
            'ch_div_cd' => '001',
            'sch_div_cd' => '001',
            'del_yn' => 'N',
            'sdate' => $start_date,
            'edate' => $end_date,
            'rpt_pln_id' => '',
            'curPage' => $curPage,
            'rowcount' => $limit,
            'usr_id' => $test_user_id,
            'token' => '',
            'usr_ip' => '',
            'format' => 'json',
            'lang' => 'KOR',
            'os_type' => 'CS'
        );

        $return = InterfaceClass::client($wsdl, 'getSelectArticleExtend' , $request);
        $r_data = json_decode($return['return'], true);
        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }else if($r_data['data']['totalcount'] == 1){
            $r_data['data']['record'] = array($r_data['data']['record']);
        }

        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record']
        );

        return $result;
    }

    function requestListMatchingArticle($wsdl, $post,$test_user_id, $media_cd){
        $request = array(
            'artcl_id' => $post['artcl_id'],
            'media_cd' => $media_cd,
            'ch_div_cd' => '001',
            'usr_id' => $test_user_id,
            'token' => '',
            'usr_ip' => '',
            'format' => 'json',
            'lang' => 'KOR',
            'os_type' => 'CMS'
        );

        $return = InterfaceClass::client($wsdl, 'getSelectArticleMediaListExt' , $request);
        $r_data = json_decode($return['return'], true);

        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }else if($r_data['data']['totalcount'] == 1){
            $r_data['data']['record'] = array($r_data['data']['record']);
        }

        $_data = array();
        foreach( $r_data['data']['record'] as $data ){
            if(is_array($data['playout_time'])){
                $data['playout_time'] = '';
            }else{
                $data['playout_time'] = str_pad($data['playout_time'], 6, '0', STR_PAD_LEFT);
            }
            array_push($_data, $data);
        }
        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $_data
        );

        return $result;
    }

    function updateStatus($ord_id, $user_id){
        global $db;

        $query = "
            UPDATE	TB_ORD SET
            ORD_STATUS = 'complete'
            WHERE	ORD_ID = '".$ord_id."'
        ";
        $result = $db->exec($query);

        $zodiac = new Zodiac();
        $zodiac->putUpdateRequestStatus(array(
            'request_id'		=>	$ord_id,
            'status_cd'			=>	'completed',
            'worker_id'			=>	'',
            'worker_message'	=>	'',
            'user_id'			=>	'',
            'ord_title'			=>	'',
            'ord_ctt'			=>	'',
            'updtr_id'			=>	'',
            'updt_dtm'			=>	'',
            'completed_id'		=>	$user_id,
            'completed_dtm'		=>	date("YmdHis")
        ));
        return $result;
    }

    function matchingArticle($wsdl, $post,$test_user_id, $media_cd, $user_id, $channel){
        global $db;
        //송출 아이디 생성
        //$ord_tr_id = createTransmissionId();
        $content_id = $post['content_id'];
        $playout_id = createTransmissionId($content_id);
        

        //송출 목록 생성 TB_ORD_TRANSMISSION
        $ord_tr_id = addListTransmission($content_id, $user_id, $playout_id, $post['ord_id']);

        if( $ord_tr_id ){
            //송출
            //전송 이력 확인
            $contentService = new \Api\Services\ContentService( app()->getContainer() );
            $taskService = new \Api\Services\TaskService( app()->getContainer() );
            $contentStatus = $contentService->findStatusMeta($content_id);

            if ( $contentStatus->scr_trnsmis_sttus == 'complete' || $contentStatus->scr_trnsmis_sttus == '3000' ) {
                //전송 완료

                //콘텐츠에 부조 전송 상태 완료시 바로 완료 처리함
                $trCompletedAt = $contentStatus->scr_trnsmis_end_dt ?? date('YmdHis');         
                $check_update = updateStatus($post['ord_id'], $user_id);
                $r = $db->exec("UPDATE TB_ORD_TRANSMISSION SET COMPLETE_TIME=".$trCompletedAt.",UPDATE_TIME=".$trCompletedAt." ,TR_STATUS='complete', TR_PROGRESS='100' WHERE ORD_TR_ID=$ord_tr_id");

            }else if( !empty($contentStatus->scr_trnsmis_sttus) ){
                //진행중인경우
                if( $contentStatus->scr_trnsmis_sttus == 'request' ){
                    $result =  array(
                        'success' => false,
                        'msg' => '전송 준비중입니다'
                    );
                }else{
                    $bfTask = \Api\Models\Task::where('src_content_id' , $content_id)->where('destination','transmission_zodiac')->orderBy('task_id','desc')->first();
                    if( !empty($bfTask) ){
                        $task_id = $bfTask->task_id;
                    }
                }
            }else{
                $task_id = doTransmission($content_id, $user_id);
            }

            if ($task_id) {
                //송출테이블에 TASK_ID 업데이트
                $update_tr = updateTransmission($task_id, $ord_tr_id);

                if ($update_tr) {
                    if ($post['type_content'] == SEQUENCE) {//SEQUENCE
                        $ori_extention = 'seq_mxf';
                    } else {
                        $ori_extention = 'original';
                    }
                    $content_info = $db->queryRow("
                    SELECT	C.GROUP_COUNT, M.PATH
                    FROM	BC_CONTENT C, BC_MEDIA M
                    WHERE	C.CONTENT_ID = M.CONTENT_ID
                    AND		C.CONTENT_ID = $content_id
                    AND		M.MEDIA_TYPE = '".$ori_extention."'
                    ");
                
                    $request = array(
                    'artcl_id' =>  $post['artcl_id'],
                    'media_id' => $content_id,
                    'media_cd' => $media_cd,//영상 001 그래픽 002
                    'media_nm' => $post['title'],//영상 제목
                    'duration' => $post['duration'],
                    'format' => 'json',
                    'lang' => 'KOR',
                    'usr_id' => $test_user_id,
                    'playout_id' => $playout_id,
                    'ext'		=> pathinfo($content_info['path'], PATHINFO_EXTENSION),
                    'frame_count' => $content_info['group_count']
                );

                    $return = InterfaceClass::client($wsdl, 'putUpdateArticleMatchExt', $request);
                    $data_count = showDetalArticle(SOAP_ZODIAC_ARTICLE, $post, $test_user_id);
                    $r_data = json_decode($return['return'], true);

                    if ($post['location'] == 'request') {
                        $check_update = updateStatus($post['ord_id'], $user_id);
                    }

                    $result = array(
                    'success' => $r_data['result']['success'],
                    'total' => $r_data['data']['totalcount'],
                    'data' => $r_data['data']['record'],
                    'data_count' => $data_count['data']
                );
                } else {
                    $result =  array(
                    'success' => false,
                    'msg' => '작업 등록id 업데이트 실패'
                );
                }
            } else {
                $result =  array(
                'success' => false,
                'msg' => '파일전송 실패'
            );
                
            }
        }else{
            $result =  array(
                'success' => false,
                'msg' => '송출 목록 생성 실패'
            );
        }

        return $result;
    }

    function makeField($data){

        $name_field = array(
            'artcl_id' => '기사아이디',
            'ch_div_nm' => '',
            'artcl_frm_nm' => '기사형식명',
            'artcl_fld_nm' => '기사분야명',
            'artcl_titl' => '기사제목',
            'artcl_ctt' => '기사내용',
            'dept_nm' => '부서명',
            'artcl_reqd_sec' => '',
            'artcl_div_nm' => '',
            'issu_seq' => '',
            'apprv_div_nm' => '승인구분명',
            'artcl_ord' => '',
            'brdc_cnt' => '',
            'org_artcl_id' => '',
            'urg_yn' => '긴급여부',
            'internet_only_yn' => '',
            'frnoti_yn' => '',
            'embg_yn' => '엠바고여부',
            'sns_yn' => '',
            'top_yn' => '',
            'del_yn' => '삭제여부',
            'os_type' => '',
            'inputr_id' => '입력자아이디',
            'input_dtm' => '입력일시',
            'inputr_nm' => '입력자명',
            'updtr_id' => '수정자아이디',
            'updt_dtm' => '수정일시',
            'updtr_nm' => '수정자명',
            'grphc_count' => '그래픽매칭갯수',
            'video_count' => '영상매칭갯수',
            'cvcount' => '',
            'cgcount' => '',
            'attc_file_count' => '',
            'ord_gt_count' => '',
            'ord_gc_count' => '',
            'ord_vt_count' => '',
            'ord_vc_count' => '',
            //큐시트 관련
            'rd_id' => '큐시트Id',
            'rd_seq' => '큐시트시퀀스',
            'ch_div_nm' => '채널명',
            'artcl_id' => '기사ID',
            'artcl_frm_nm' => '기사형식',
            'artcl_fld_nm' => '기사분야',
            'artcl_titl' => '기사제목',
            'artcl_ctt' => '기사내용',
            'rptr_id' => '',
            'rptr_nm' => '기자명',
            'dept_nm' => '부서명',
            'apprv_div_nm' => '승인구분',
            'apprv_dtm' => '승인일시',
            'apprvr_nm' => '승인자명',
            'inputr_nm' => '입력자',
            'input_dtm' => '입력일시',
            'updtr_nm' => '수정자',
            'updt_dtm' => '수정일시'
        );

        $ex = array('artcl_id','issu_seq','artcl_ord','brdc_cnt','org_artcl_id','os_type');
        $field_form = array();
        foreach($data as $field=>$d){
            if( is_array($d) || strstr($field, '_cd') || in_array($field, $ex) || empty($name_field[$field]) )  {
                continue;
            }else if($field == 'artcl_ctt'){
                //$value = addslashes($d);
                //$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
                //array_push($field_form, "{xtype:'textarea', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."', height : 400,listeners: {afterrender:function(self){self.getEl().setStyle({fontSize:'13px'})}} }");
            }else{
                //$value = addslashes($d);
                //$value = str_replace("\r", '', str_replace("\n", '\\n', $value));
                //array_push($field_form, "{xtype:'textfield', readOnly: true, fieldLabel:'".$name_field[$field]."', value:'".$value."'}");
            }
        }

        $fields = join(',', $field_form);

        return $fields;
    }

    function showDetalArticle($wsdl, $post,$test_user_id){
        global $db;

        $request = array(
            'artcl_id' => $post['artcl_id'],
            'edit_yn' => '',
            'ch_div_cd' => '001',
            'usr_id' => $test_user_id,
            'token' => '',
            'usr_ip' => '',
            'format' => 'json',
            'lang' => 'KOR',
            'os_type' => 'CS'
        );

        $return = InterfaceClass::client($wsdl, 'getSelectArticleInfo' , $request);
        $r_data = json_decode($return['return'], true);
        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }

        //$data = makeField($r_data['data']['record']);

        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record']
        );

        return $result;
    }

    function unmatchingArticle($wsdl, $post,$test_user_id, $media_cd){
        $request = array(
            'artcl_id' => $post['artcl_id'],
            'media_id' => $post['content_id'],
            'plyout_id' => $post['plyout_id'],
            'media_cd' => $media_cd,//영상 001 그래픽 002
            'usr_id' => $test_user_id,
            'lang' => 'KOR',
            'format' => 'json'
        );

        $return = InterfaceClass::client($wsdl, 'UpdateArticleUnMatch' , $request);
        $r_data = json_decode($return['return'], true);
        $data_count = showDetalArticle($wsdl, $post,$test_user_id);
        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record'],
            'data_count' => $data_count['data']
        );

        return $result;
    }

    function requestListProgram($wsdl, $post,$test_user_id){
        $search = json_decode($post['search'], true);
        $start_date = empty($search['broad_ymd']) ? date("Y-m-d 00:00:00") : date("Y-m-d 00:00:00", strtotime($search['broad_ymd']));
        $end_date = empty($search['broad_ymd']) ? date('Y-m-d 23:59:59') : date("Y-m-d 23:59:59", strtotime($search['broad_ymd']));
        $start = empty($post['start']) ? 0 : $post['start'];
        $limit = empty($post['limit']) ? 20 : $post['limit'];
        $page = ($start+1)/$limit;
        if($page == 0 ){
            $curPage = 1;
        }else{
            $curPage = ceil($page);
        }

        $prognm = empty($search['pgm_nm']) ? '' : $search['pgm_nm'];

        $request = array(
            'pgm_nm' => $prognm,
            'sdate' => $start_date,
            'edate' => $end_date,
            'ch_div_cd' => '001',
            'curpage' => $curPage,
            'rowcount' => $limit,
            'usr_id' => $test_user_id,
            'lang' => 'KOR',
            'format' => 'json'
        );

        //print_r($request);exit;

        $return = InterfaceClass::client($wsdl, 'getSelectRundownExt' , $request);
        $r_data = json_decode($return['return'], true);
        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }else if($r_data['data']['totalcount'] == 1){
            $r_data['data']['record'] = array($r_data['data']['record']);
        }

        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record']
        );

        return $result;
    }

    function requestListRundown($wsdl, $post,$test_user_id){
        $start = empty($post['start']) ? 0 : $post['start'];
        $limit = empty($post['limit']) ? 20 : $post['limit'];
        $page = ($start+1)/$limit;
        if($page == 0 ){
            $curPage = 1;
        }else{
            $curPage = ceil($page);
        }
        $request = array(
            'rd_id' => $post['search'],
            'usr_id' => $test_user_id,
            'lang' => 'KOR',
            'format' => 'json'
        );


        $return = InterfaceClass::client($wsdl, 'getSelectRundownDtlExt' , $request);
        $r_data = json_decode($return['return'], true);
        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }else if($r_data['data']['totalcount'] == 1){
            $r_data['data']['record'] = array($r_data['data']['record']);
        }

        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record']
        );

        return $result;
    }

    function requestListMachingRundown($wsdl, $post,$test_user_id, $media_cd){
        $start = empty($post['start']) ? 0 : $post['start'];
        $limit = empty($post['limit']) ? 20 : $post['limit'];
        $page = ($start+1)/$limit;
        if($page == 0 ){
            $curPage = 1;
        }else{
            $curPage = ceil($page);
        }
        $request = array(
            'rd_id' => $post['artcl_id'],
            'rd_seq' => $post['rd_seq'],
            'media_cd' => $media_cd,
            'usr_id' => $test_user_id,
            'lang' => 'KOR',
            'format' => 'json'
        );

        $return = InterfaceClass::client($wsdl, 'getSelectRundownDtlMatch' , $request);
        $r_data = json_decode($return['return'], true);
        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }else if($r_data['data']['totalcount'] == 1){
            $r_data['data']['record'] = array($r_data['data']['record']);
        }

        $_data = array();
        foreach( $r_data['data']['record'] as $data ){
            if(is_array($data['playout_time'])){
                $data['playout_time'] = '';
            }else{
                $data['playout_time'] = str_pad($data['playout_time'], 6, '0', STR_PAD_LEFT);
            }
            array_push($_data, $data);
        }
        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $_data
        );

        return $result;
    }

    function addListTransmission($content_id, $user_id, $playout_id, $ord_id){
        global $db;

        //송출 목록 생성 TB_ORD_TRANSMISSION

        //$seq_id = getSequence('SEQ_TB_ORD_TRANSMISSION');//SEQ_TB_ORD_TRANSMISSION
        
        $seq = getSequence('SEQ_TB_ORD_TRANSMISSION');
        $ord_tr_id = date('Ymd').'TR'.str_pad($seq, 5, '0', STR_PAD_LEFT);

        $query_insert = "
            INSERT	INTO	TB_ORD_TRANSMISSION
            (ORD_TR_ID, CONTENT_ID, CREATE_TIME, CREATE_USER, PLAYOUT_ID, ORD_ID)
            VALUES
            ('".$ord_tr_id."','".$content_id."','".date('YmdHis')."', '".$user_id."', '".$playout_id."', '".$ord_id."')
        ";

        if($db->exec($query_insert)){
            return $ord_tr_id;
        }else{
            return false;
        }
    }

    function updateTransmission($task_id, $seq_id){
        global $db;

        //송출테이블에 TASK_ID 업데이트
        $query_update = "
            UPDATE	TB_ORD_TRANSMISSION	SET
            TASK_ID = '".$task_id."'
            WHERE	ORD_TR_ID = '".$seq_id."'
        ";

        $result = $db->exec($query_update);

        return $result;
    }

    function doTransmission($content_id, $user_id){
        global $db;
        
        $media_info = $db->queryRow("
            SELECT	*
            FROM		BC_MEDIA
            WHERE	CONTENT_ID = ".$content_id."
                AND	MEDIA_TYPE = 'original'
                AND	STATUS IN ('0','3')
                AND	FILESIZE > 0
        ");

        if( empty($media_info['media_id']) ) {
            // 미디어가 없을 경우 false return
            $result =  array(
                'success' => false,
                'msg' => '요청하신 콘텐츠의 원본파일이 없습니다.'
            );
        } else {
            // 미디어가 있을 경우에는 전송요청
            $content_info = $db->queryRow("
                SELECT	C.BS_CONTENT_ID, C.IS_GROUP, G.UD_GROUP_CODE, C.GROUP_COUNT
                FROM	BC_CONTENT C, BC_UD_GROUP G
                WHERE	C.UD_CONTENT_ID = G.UD_CONTENT_ID
                AND		CONTENT_ID = ".$content_id."
            ");
            $frame_count = 1;
            if( $content_info['ud_group_code'] == 4 ){//CG
                if( $content_info['bs_content_id'] == SEQUENCE || $content_info['is_group'] == 'G' ){
                    //$channel = 'transmission_graphic_group_zodiac';
                    $channel = 'transmission_graphic_seq_zodiac';
                }else{
                    $channel = 'transmission_graphic_zodiac';
                }
                if( $content_info['bs_content_id'] == SEQUENCE ){
                    $frame_count = $content_info['group_count'];
                }
            }else{
                if( $content_info['is_group'] == 'G' ){
                    $channel = 'transmission_group_zodiac';
                }else{
                    $path = $db->queryOne("
                                SELECT	PATH
                                FROM	BC_MEDIA
                                WHERE	MEDIA_TYPE = 'original'
                                AND		CONTENT_ID = $content_id
                            ");
                    
                    $ext = pathinfo($path, PATHINFO_EXTENSION );
                    
                    if(strtoupper($ext) == 'MXF') {
                        $channel = 'transmission_zodiac';
                    } else if (strtoupper($ext) == 'MOV') {
                        $channel = 'transmission_zodiac_rewrap';
                    }
                }
            }
            $ext = substr(strrchr($media_info['path'],"."),1);
            $task = new TaskManager($db);
            $task_id = $task->start_task_workflow($content_id, $channel, $user_id);

            return $task_id;
        }
    }

    function matchingRundown($wsdl, $post,$test_user_id, $media_cd, $user_id, $channel){
        global $db;
        //송출 아이디 생성
        //$ord_tr_id = createTransmissionId();
        $content_id = $post['content_id'];
        $playout_id = createTransmissionId($content_id);
        

        //송출 목록 생성 TB_ORD_TRANSMISSION
        $ord_tr_id = addListTransmission($content_id, $user_id, $playout_id, $post['ord_id']);
        

        if( $ord_tr_id ){
            
            //송출
            $task_id = doTransmission($content_id, $user_id);

            if($task_id){
                //송출테이블에 TASK_ID 업데이트
                $update_tr = updateTransmission($task_id, $ord_tr_id);

                if( $update_tr ){
                    if( $post['type_content'] == SEQUENCE ){//SEQUENCE
                        $ori_extention = 'seq_mxf';
                    }else{
                        $ori_extention = 'original';
                    }
                    $content_info = $db->queryRow("
                            SELECT	C.GROUP_COUNT, M.PATH
                            FROM	BC_CONTENT C, BC_MEDIA M
                            WHERE	C.CONTENT_ID = M.CONTENT_ID
                            AND		C.CONTENT_ID = $content_id
                            AND		M.MEDIA_TYPE = '".$ori_extention."'
                            ");

                    $request = array(
                        'rd_id' =>  $post['artcl_id'],
                        'rd_seq' =>  $post['rd_seq'],
                        'media_id' => $content_id,
                        'media_cd' => $media_cd,//영상 001 그래픽 002
                        'human_id' => '',
                        'media_nm' => $post['title'],//영상 제목
                        'duration' => $post['duration'],
                        'usr_id' => $test_user_id,
                        'lang' => 'KOR',
                        'format' => 'json',
                        'playout_id' => $playout_id,
                        'ext'		=> pathinfo($content_info['path'], PATHINFO_EXTENSION),
                        'frame_count' => $content_info['group_count']
                    );

                    $return = InterfaceClass::client($wsdl, 'putUpdateRundownMatch' , $request);
                    $data_count = showDetailRundown(SOAP_ZODIAC_RUNDOWN, $post,$test_user_id);
                    $r_data = json_decode($return['return'], true);

                    if( $post['location'] == 'request' ){
                        $check_update = updateStatus($post['ord_id'], $user_id);
                    }

                    $result = array(
                        'success' => $r_data['result']['success'],
                        'total' => $r_data['data']['totalcount'],
                        'data' => $r_data['data']['record'],
                        'data_count' => $data_count['data']
                    );
                }else{
                    $result =  array(
                        'success' => false,
                        'msg' => '작업 등록id 업데이트 실패'
                    );
                }



            }else{
                $result =  array(
                    'success' => false,
                    'msg' => '파일전송 실패'
                );
            }
        }else{
            $result =  array(
                'success' => false,
                'msg' => '송출 목록 생성 실패'
            );
        }

        return $result;
    }

    function showDetailRundown($wsdl, $post,$test_user_id){
        $request = array(
            'rd_id' => $post['artcl_id'],
            'rd_seq' => $post['rd_seq'],
            'ch_div_cd' => '001',
            'usr_id' => $test_user_id,
            'token' => '',
            'usr_ip' => '',
            'format' => 'json',
            'lang' => 'KOR',
            'os_type' => 'CMS'
        );

        $return = InterfaceClass::client($wsdl, 'getSelectRundownDtlArticleWithCap' , $request);
        $r_data = json_decode($return['return'], true);
        if(!is_array($r_data['data']['record'])){
            $r_data['data']['record'] = array();
        }
        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record']
        );

        return $result;
    }

    function unmatchingRundown($wsdl, $post,$test_user_id, $media_cd){
        $request = array(
            'rd_id' => $post['artcl_id'],
            'rd_seq' => $post['rd_seq'],
            'media_id' => $post['content_id'],
            'plyout_id' => $post['plyout_id'],
            'media_cd' => $media_cd,//영상 001 그래픽 002
            'usr_id' => $test_user_id,
            'lang' => 'KOR',
            'format' => 'json'
        );

        $return = InterfaceClass::client($wsdl, 'putUpdateRundownUnMatch' , $request);
        $r_data = json_decode($return['return'], true);
        $data_count = showDetailRundown($wsdl, $post,$test_user_id);
        $result = array(
            'success' => $r_data['result']['success'],
            'total' => $r_data['data']['totalcount'],
            'data' => $r_data['data']['record'],
            'data_count' => $data_count['data']
        );

        return $result;
    }

    function createTransmissionId($content_id){
        global $db;

        $check_id_query = "
            SELECT  M.CONTENT_ID, M.PATH, T.PLAYOUT_ID, C.BS_CONTENT_ID
            FROM    BC_MEDIA M
                    LEFT JOIN TB_ORD_TRANSMISSION_ID T
                    ON  M.CONTENT_ID = T.CONTENT_ID,
                    BC_CONTENT C
            WHERE   M.CONTENT_ID = ".$content_id."
            AND     M.CONTENT_ID = C.CONTENT_ID
            AND     M.MEDIA_TYPE = 'original'
        ";
        $check_id = $db->queryRow($check_id_query);

        if( empty($check_id['playout_id']) ){
            if( $check_id['bs_content_id'] == SEQUENCE ){
                $path = explode('/',  dirname($check_id['path']));
                $playout_id = array_pop($path);
            }else{
                $playout_id = basename($check_id['path'], strrchr($check_id['path'], '.'));
            }
            $seq_id = getSequence('SEQ_TB_ORD_TRANSMISSION_ID');//SEQ_TB_ORD_TRANSMISSION_ID
            $insert_data = array(
                'id'			=>	$seq_id,
                'content_id'	=>	$content_id,
                'playout_id'			=>	$playout_id
            );
            $db->insert('TB_ORD_TRANSMISSION_ID', $insert_data);
        }else {
            $playout_id = $check_id['playout_id'];	
        }

        return $playout_id;
    }

    function getAllowedContentType($user_id, $grant){
        global $db;

        $ud_contents = $db->queryAll("
            SELECT	UD_CONTENT_ID
            FROM	BC_UD_CONTENT
        ");
        
        $allowed_contents = array();
        foreach( $ud_contents as $ud_content_id){
            if (checkAllowUdContentGrant($user_id, $ud_content_id['ud_content_id'], $grant)) {
                array_push($allowed_contents, $ud_content_id['ud_content_id']);
            }
        }

        return $allowed_contents;
    }

}
?>

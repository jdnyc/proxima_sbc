<?php

class BIS {
	const BIS_URL = BIS_RESTFUL_URL;
	private $soap;

	function __construct() {
		
	}

	function fn_log($title, $log) {
		if(is_string($log)){
			@file_put_contents(LOG_PATH.'/'.basename(__FILE__).'_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.$title . ':::' . $log."\n", FILE_APPEND);
		}else{
			@file_put_contents(LOG_PATH.'/'.basename(__FILE__).'_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.$title . ':::' . print_r($log,true)."\n", FILE_APPEND);
		}
	}

	function fn_xmltojson($av_xml) {
		$av_xml = str_replace(array("\n", "\r", "\t"), '', $av_xml);
		$av_xml = trim(str_replace('"', "'", $av_xml));
		$simpleXml = simplexml_load_string($av_xml);
		$json = json_encode($simpleXml);
		return $json;
	}
	  
	  

	function post_send($url, $av_param) {

		$header[] = "Content-Type: html/text; charset=utf-8";
	
		$session = curl_init();
	
		curl_setopt($session, CURLOPT_HEADER,         false);
		//curl_setopt($session, CURLOPT_HTTPHEADER,     $header);
		curl_setopt($session, CURLOPT_HTTPHEADER, array('Cache-Control: no-cache', 'Content-Type: application/xml; charset=utf-8'));
		curl_setopt($session, CURLOPT_URL,            $url);
		curl_setopt($session, CURLOPT_POSTFIELDS,     $av_param);
		curl_setopt($session, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($session, CURLOPT_POST,           1);
		 
		$response = curl_exec($session);
	
		curl_close($session);
	
		return $response;
	}

	function ProgramList_(){
		$restful_action = 'getBSProgramList';
		$restful_page = BIS_RESTFUL_URL.$restful_action;
		
		$v_params1 = '<Param><channel_id>Z</channel_id></Param>';
		
		$this->fn_log('restful_page', $restful_page);
		$this->fn_log('params', $v_params1);
		$bis_return = $this->post_send($restful_page, $v_params1);
		$this->fn_log('ProgramList bis_return', $bis_return);

		$bis_obj = simplexml_load_string($bis_return);
		$this->fn_log('ProgramList result_count', $bis_obj->result_count);
		
		if($bis_obj->result_count > 0){
			$bis_array = json_decode( json_encode( $bis_obj ), 1 );
			$v_data = $bis_array['programs']['program'];
			$return = new stdClass();
			//$this->fn_log('ProgramList v_data', $v_data);

			for($i = 0; $i < $bis_array['result_count']; $i++){
				//$this->fn_log('ProgramList v_data[i]', $v_data[$i]);
				$v_temp = new stdClass();
				//$obj->requestmeta = new stdClass;
				$v_temp->pgm_id = $v_data[$i]['program_id'];
				$v_temp->pgm_nm = $v_data[$i]['program_title'];
				$v_temp->pgm_ori_nm = $v_data[$i]['original_title'];
				$v_temp->genre = $v_data[$i]['genre_clf'];
				$v_temp->product_clf = $v_data[$i]['product_clf'];
				$v_temp->info_grd = $v_data[$i]['rating'];
				$v_temp->news_yn = $v_data[$i]['news_yn'];
				$v_temp->flash_yn = $v_data[$i]['flash_yn'];
	
				$return->data[$i] = $v_temp;
			}
		}else{
			$return = array();
		}

		//$v_return['data'] = $return;

		$this->fn_log('ProgramList v_return', $return);
	
		return $this->result($return);
	}

	function ProgramList(){
		$restful_action = 'getBSProgramList';
		$restful_page = BIS_RESTFUL_URL.$restful_action;
		
		$v_params1 = '<Param><channel_id>Z</channel_id></Param>';
		
		$this->fn_log('restful_page', $restful_page);
		$this->fn_log('params', $v_params1);
		$bis_return = $this->post_send($restful_page, $v_params1);
		//$this->fn_log('ProgramList bis_return', $bis_return);

		$bis_obj = simplexml_load_string($bis_return);
		//$this->fn_log('ProgramList result_count', $bis_obj->result_count);
		
		if($bis_obj->result_count > 0){
			$bis_array = json_decode(json_encode($bis_obj), 1);
			$v_data = $bis_array['programs']['program'];
			$return = array();
			//$this->fn_log('ProgramList v_data', $v_data);

			for($i = 0; $i < $bis_array['result_count']; $i++){
				//$this->fn_log('ProgramList v_data[i]', $v_data[$i]);
				$v_temp = array();
				$v_temp['pgm_id'] = $v_data[$i]['program_id'];
				$v_temp['pgm_nm'] = $v_data[$i]['program_title'];
				$v_temp['pgm_ori_nm'] = $v_data[$i]['original_title'];
				$v_temp['genre'] = $v_data[$i]['genre_clf'];
				$v_temp['product_clf'] = $v_data[$i]['product_clf'];
				$v_temp['info_grd'] = $v_data[$i]['rating'];
				$v_temp['news_yn'] = $v_data[$i]['news_yn'];
				$v_temp['flash_yn'] = $v_data[$i]['flash_yn'];

				array_push($return, $v_temp);
			}
		}else{
			$return = array();
		}

		$return = json_encode($return);
		$this->fn_log('ProgramList return', $return);

		return $return;
	}

	function ProgramInfo($av_program_id){
		$restful_action = 'getBSProgramInfo';
		$restful_page = BIS_RESTFUL_URL.$restful_action;
		
		$v_params1 = '<Param><channel_id>Z</channel_id><program_id>'.$av_program_id.'</program_id></Param>';
		
		//$this->fn_log('restful_page', $restful_page);
		//$this->fn_log('params', $v_params1);
		$bis_return = $this->post_send($restful_page, $v_params1);
		//$this->fn_log('ProgramInfo bis_return', $bis_return);

		$bis_obj = simplexml_load_string($bis_return);

		if($bis_obj->result_count > 0){
			$bis_array = json_decode(json_encode($bis_obj), 1);
			$v_data = $bis_array['programs']['program'];
			//$this->fn_log('ProgramInfo v_data', $v_data);

			$return['pgm_id'] = $v_data['program_id'];			//프로그램 ID
			$return['pgm_nm'] = $v_data['program_title'];		//프로그램 제목
			$return['pgm_ori_nm'] = $v_data['original_title'];	//원제
			$return['genre'] = $v_data['genre_clf'];			//장르구분
			$return['pgm_category'] = $v_data['pgm_category'];	//카테고리
			$return['sub_category'] = $v_data['sub_category'];	//세부 카테고리
			$return['info_grd'] = $v_data['rating'];			//시청등급
			$return['form_clf'] = $v_data['form_clf'];			//정규/특집구분
			$return['foreign_clf'] = $v_data['foreign_clf'];	//해외구분
			$return['product_clf'] = $v_data['product_clf'];	//제작구분
			$return['runtime'] = $v_data['runtime'];			//방송길이
			$return['onair_day'] = $v_data['onair_day'];		//방송요일[0,1 이진수로 구분 (ex: 1110000 : 월화수 방송)]
			$return['series_yn'] = $v_data['series_yn'];		//시리즈여부
			$return['co_brod_clf'] = $v_data['co_brod_clf'];	//편성분야(보고서집계용)
			$return['co_cont_clf'] = $v_data['co_cont_clf'];	//내용구분(보고서집계용)
			$return['live_clf'] = $v_data['live_clf'];			//생방구분
			$return['news_yn'] = $v_data['news_yn'];			//뉴스프로그램 여부
			$return['flash_yn'] = $v_data['flash_yn'];			//속보프로그램 여부
			$return['emergency_yn'] = $v_data['emergency_yn'];	//긴급프로그램 여부
			$return['pilot_yn'] = $v_data['pilot_yn'];			//파일럿프로그램 여부
			$return['evalu_yn'] = $v_data['evalu_yn'];			//시청자평가프로그램 여부
			$return['caption_yn'] = $v_data['caption_yn'];		//자막 여부
			$return['explan_yn'] = $v_data['explan_yn'];		//화면해설 여부
			$return['sign_lang_yn'] = $v_data['sign_lang_yn'];	//수화방송 여부
			$return['resolution_clf'] = $v_data['resolution_clf'];	//화질구분
			$return['close_yn'] = $v_data['close_yn'];			//종영여부
			$return['pd_nm'] = $v_data['pd_nm'];				//담당ID명
			$return['director'] = $v_data['director'];			//감독
			$return['starring_actor'] = $v_data['starring_actor'];	//주연
			$return['actor'] = $v_data['actor'];				//출연
			$return['writer'] = $v_data['writer'];				//작가
			$return['country'] = $v_data['country'];			//제작국가
			$return['synopsis'] = $v_data['synopsis'];			//줄거리
		}else{
			$return = array();
		}

		$return = json_encode($return);
		$this->fn_log('ProgramInfo return', $return);
	
		return $return;
	}

	function EpisodeList($param){
		$restful_action = 'getBSSeriesList';
		$restful_page = BIS_RESTFUL_URL.$restful_action;
		
		if(empty($param['epsd_no'])){
			$v_epsd_no = '0';
		}else{
			$v_epsd_no = $param['epsd_no'];
		}
		
		$v_params1 = '<Param><channel_id>Z</channel_id><program_id>'.$param['pgm_id'].'</program_id><series_no>'.$v_epsd_no.'</series_no></Param>';
		//$v_params1 = '<Param><channel_id>Z</channel_id><program_id>PZ1901F00018</program_id><series_no>0</series_no></Param>';

		//$this->fn_log('restful_page', $restful_page);
		//$this->fn_log('params', $v_params1);
		$bis_return = $this->post_send($restful_page, $v_params1);
		//$this->fn_log('EpisodeList bis_return', $bis_return);

		$bis_obj = simplexml_load_string($bis_return);
		//$this->fn_log('EpisodeList result_count', $bis_obj->result_count);
		
		if($bis_obj->result_count > 0){
			$bis_array = json_decode(json_encode($bis_obj), 1);
			$v_data = $bis_array['serieslist']['series'];
			$return = array();
			//$this->fn_log('EpisodeList v_data', $v_data);

			for($i = 0; $i < $bis_array['result_count']; $i++){
				//$this->fn_log('EpisodeList v_data[i]', $v_data[$i]);
				$v_temp = array();
				$v_temp['pgm_id'] = $v_data[$i]['program_id'];			//프로그램 ID
				$v_temp['pgm_nm'] = $param['pgm_nm'];
				$v_temp['epsd_no'] = $v_data[$i]['series_no'];			//시리즈번호(회차)
				$v_temp['epsd_nm'] = $v_data[$i]['series_title'];		//회차 타이틀(부제)
				$v_temp['epsd_ori_nm'] = $v_data[$i]['original_title'];	//회차 원부제
				$v_temp['onair_ymd'] = $v_data[$i]['onair_ymd'];		//최초방송일
				$v_temp['runtime'] = $v_data[$i]['runtime'];			//방송길이
				$v_temp['rating'] = $v_data[$i]['rating'];				//시청등급
				$v_temp['form_clf'] = $v_data[$i]['form_clf'];			//정규/특집구분
				$v_temp['epsd_end_yn'] = $v_data[$i]['last_yn'];		//최종회여부 - 매핑
				$v_temp['starring_actor'] = $v_data[$i]['starring_actor'];	//주연
				$v_temp['actor'] = $v_data[$i]['actor'];				//출연
				$v_temp['commentator'] = $v_data[$i]['commentator'];	//해설
				$v_temp['shoot_ymd'] = $v_data[$i]['shoot_ymd'];		//촬영일/경기일
				$v_temp['rec_place'] = $v_data[$i]['place'];			//장소 - 매핑
				$v_temp['synopsis'] = $v_data[$i]['synopsis'];			//줄거리
				$v_temp['is_deleted'] = '0';							//삭제 여부
				$v_temp['is_show'] = 'Y';								//조회 여부
				$v_temp['pgm_type'] = 'bis';							//프로그램 구분

				array_push($return, $v_temp);
			}
		}else{
			$return = array();
		}

		$return = json_encode($return);
		$this->fn_log('EpisodeList return', $return);

		return $return;
		/*
		$return = $this->soap->EpisodeList(array(
			'date'=> $param[date],
			'pgm_id'=> $param[pgm_id],
			'sort'=> $param[sort],
			'dir'=> $param[dir],
			'start'=> $param[start],
			'limit'=> $param[limit]
		));
		
		return $this->result($return);
		*/
	}

	function MaterialList($param){
		$restful_action = 'getBSAssetList';
		$restful_page = BIS_RESTFUL_URL.$restful_action;

		$v_pgm_id = $param['pgm_id'];
		$v_epsd_no = $param['epsd_no'];
		//$v_pgm_id = 'PZ1907F00020';
		//$v_epsd_no = '3';

		if(empty($param['flag'])){
			$flag = 'PGM';
		}else{
			$flag = $param['flag'];
		}

		if(empty($param['asset_type'])){
			$asset_type = 'PGM';
		}else{
			$asset_type = $param['asset_type'];
		}
		
		$v_params1 = '<Param><channel_id>Z</channel_id><search_id>'.$v_pgm_id.'</search_id><series_no>'.$v_epsd_no.'</series_no><flag>'.$flag.'</flag><asset_type>'.$asset_type.'</asset_type><search_name>'.$param['search_name'].'</search_name><reg_bgn_ymd>'.$param['reg_bgn_ymd'].'</reg_bgn_ymd><reg_end_ymd>'.$param['reg_end_ymd'].'</reg_end_ymd></Param>';
		//$v_params1 = '<Param><channel_id>Z</channel_id><program_id>PZ1901F00018</program_id><series_no>0</series_no></Param>';

		$this->fn_log('restful_page', $restful_page);
		$this->fn_log('params', $v_params1);
		$bis_return = $this->post_send($restful_page, $v_params1);
		$this->fn_log('MaterialList bis_return', $bis_return);

		$bis_obj = simplexml_load_string($bis_return);
		$this->fn_log('MaterialList result_count', $bis_obj->result_count);
		
		if($bis_obj->result_count > 0){
			$bis_array = json_decode(json_encode($bis_obj), 1);
			$v_data = $bis_array['assets']['asset'];
			$return = array();
			$this->fn_log('MaterialList v_data', $v_data);

			for($i = 0; $i < $bis_array['result_count']; $i++){
				//$this->fn_log('MaterialList v_data[i]', $v_data[$i]);
				$v_temp = array();
				$v_temp['pgm_id'] = $param['pgm_id'];						//프로그램 ID
				$v_temp['pgm_nm'] = $param['pgm_nm'];
				$v_temp['epsd_no'] = $param['epsd_no'];						//시리즈번호(회차)
				$v_temp['epsd_nm'] = $param['epsd_nm'];						//회차 타이틀(부제)
				$v_temp['asset_id'] = $v_data[$i]['asset_id'];				//소재 ID
				$v_temp['asset_type'] = $v_data[$i]['asset_type'];			//소재구분
				$v_temp['asset_title'] = $v_data[$i]['asset_title'];		//소재제목
				$v_temp['asset_info'] = $v_data[$i]['asset_info'];			//소재정보
				$v_temp['tc_in'] = $v_data[$i]['tc_in'];					//TC IN
				$v_temp['tc_out'] = $v_data[$i]['tc_out'];					//TC OUT
				$v_temp['duration'] = $v_data[$i]['duration'];				//Duration
				$v_temp['tape_id'] = $v_data[$i]['tape_id'];				//테이프 ID
				$v_temp['audio_type'] = $v_data[$i]['audio_type'];			//오디오 구분
				$v_temp['resolution_clf'] = $v_data[$i]['resolution_clf'];	//화질 구분
				$v_temp['use_yn'] = $v_data[$i]['use_yn'];					//사용 여부
				$v_temp['use_bgn_ymd'] = $v_data[$i]['use_bgn_ymd'];		//사용 시작일
				$v_temp['use_end_ymd'] = $v_data[$i]['use_end_ymd'];		//사용 종료일
				$v_temp['ingest_yn'] = $v_data[$i]['ingest_yn'];			//인제스트 여부
				$v_temp['archive_yn'] = $v_data[$i]['archive_yn'];			//아카이브 여부
				$v_temp['cms_asset_id'] = $v_data[$i]['cms_asset_id'];		//CMS 소재 ID
				$v_temp['reg_dt'] = $v_data[$i]['reg_dt'];					//최초등록일
				$v_temp['mod_dt'] = $v_data[$i]['mod_dt'];					//최종수정일

				$v_temp = array();
				$v_temp['key'] = $v_data[$i]['asset_id'];
				$v_temp['val'] = $v_data[$i]['asset_title'];

				array_push($return, $v_temp);
			}
		}else{
			$return = array();
		}

		$return = json_encode($return);
		$this->fn_log('MaterialList return', $return);

		return $return;
		/*
		$return = $this->soap->MaterialList(array(
			'pgm_id'=> $param['pgm_id'],
			'epsd_no'=> $param['epsd_no'],
			'ver_cd' => $param['ver_cd']
		));

		return $this->result($return);
		*/
	}

	function Material($param){

		$return = $this->soap->Material(array(
			'tape_id'=> $param['tape_id'],
			'mtrl_id'=> $param['mtrl_id'],
			'tcin' => $param['tcin'],
			'tcout'=> $param['tcout'],
			'duration'=> $param['duration'],
			'clip_yn'=> $param['clip_yn'],
			'clip_yn1'=> $param['clip_yn1'],
			'clip_yn2'=> $param['clip_yn2'],
			'clip_yn3'=> $param['clip_yn3'],
			'arc_yn'=> $param['arc_yn'],
			'regr'=> $param['regr'],
			'action'=> $param['action']
		));

		return $this->result($return);
	}

	function SetMaterial($param){

		$return = $this->soap->SetMaterial(array(
			'mtrl_id' => $param['mtrl_id'],
			'tcin'  => $param['tcin'],
			'tcout' => $param['tcout'],
			'duration' => $param['duration'],
			'clip_yn' => $param['clip_yn'],
			'clip_yn1' => $param['clip_yn1'],
			'regr' => $param['regr'],
			'action' => $param['action']
		));

		return $this->result($return);
	}

	function getMaterial($mtrl_clf, $mtrl_nm){

		$return = $this->soap->GetMaterial(array(
			'mtrl_clf'=> $mtrl_clf,
			'mtrl_nm'=> $mtrl_nm,
		));

		return $this->result($return);
	}

	function APC($param){

		$return = $this->soap->APC(array(
			'chnl_gb'=> $param['chnl_gb'],
			'tape_id'=> $param['tape_id'],
			'title'=> $param['title'],
			'clip_id' => $param['clip_id'],
			'clip_flag'=> $param['clip_flag'],
			'eom'=> $param['eom'],
			'som'=> $param['som'],
			'dur'=> $param['dur']
		));

		return $this->result($return);
	}

	function GetDuration($param) {

		$return = $this->soap->GetDuration(array(
			'trff_no'=> $param['trff_no'],
			'trff_seq'=> $param['trff_seq'],
			'trff_ymd'=> $param['trff_ymd']
		));

		if( !empty($return->return) ){

			$reencode = json_encode($return->return);
			$result =  json_decode($reencode , true);
		}

		return $result;
	}

	function GetPlanProgramList($param) {

		$return = $this->soap->GetPlanProgramList(array(
			'chan_cd' => $param['chan_cd'],
			'trff_ymd' => $param['trff_ymd'],
			'trff_clf' => $param['trff_clf'],
			'trff_no' => $param['trff_no']
		));

		return $this->result($return);
	}

	function result($response) {

		$result = array();

		$response = $response->return;
		if ( ! empty($response)) {

			$response =  json_decode($response , true);
			if ($response[0]['err_code'] != 0) {
				throw new Exception($response[0]['err_msg']);
			}

			$result = $response;
		}

		return json_encode($result);
	}
}
<?php

class Master
{

	const SOAP_URL = 'http://192.168.10.206:8080/webservice/MasterControlSync?wsdl';
	const SOAP_URL2 = 'http://192.168.10.206:8080/webservice/ArchiveCommon?wsdl';
	const SOAP_URL_DAS = 'http://192.168.10.47:86/interface/app/common.php?wsdl';

	function __construct()
	{

	}

	function _log($log){
		@file_put_contents(LOG_PATH.'/Master_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.print_r($log,true)."\n", FILE_APPEND);
	}

	//소재ID로 정보 구하기
	function getMtrlInfo($param){
		$client = new SoapClient( self::SOAP_URL2  );

		$this->_log($param);

		$return = $client->MaterialInfo(array(
			'mtrl_id' => $param[mtrl_id]
		) );
		
		$this->_log($return);

		$result = $result['return'];

		if( !empty($return->return) ){

			$reencode = json_encode($return->return);
			$result =  json_decode($reencode , true);
		}
		return $result;
	}
		

	// NPS에서 주조BIS 등록
	function DataSync($param){
		$client = new SoapClient( self::SOAP_URL  );

		$this->_log($param);

		$return = $client->DataSync(array(
			'asset_id' => $param[asset_id],
			'aspect_ratio' => $param[aspect_ratio],
			'asset_tp' => $param[asset_tp],
			'audio_bitrate' => $param[audio_bitrate],
			'audio_channel' => $param[audio_channel],
			'audio_clf' => $param[audio_clf],
			'clip_yn' => $param[clip_yn],
			'clip_yn1' => $param[clip_yn1],
			'clip_yn2' => $param[clip_yn2],
			'clip_yn3' => $param[clip_yn3],
			'default_yn' => $param[default_yn],
			'description' => $param[description],
			'device_id' => $param[device_id],
			'duration' => $param[duration],
			'eom' => $param[eom],
			'episode_no' => $param[episode_no],
			'epsd_id' => $param[epsd_id],
			'epsd_no' => $param[epsd_no],
			'file_nm' => $param[file_nm],
			'file_size' => $param[file_size],
			'frame_rate' => $param[frame_rate],
			'frame_size' => $param[frame_size],
			'genre_tp' => $param[genre_tp],
			'house_no' => $param[house_no],
			'language' => $param[language],
			'media_tp' => $param[media_tp],
			'mtrl_clf' => $param[mtrl_clf],
			'mtrl_id' => $param[mtrl_id],
			'mtrl_nm' => $param[mtrl_nm],
			'mtrl_tp' => $param[mtrl_tp],
			'path' => $param[path],
			'pgm_id' => $param[pgm_id],
			'program_code' => $param[program_code],
			'program_id' => $param[program_id],
			'reg_user_id' => $param[reg_user_id],
			'regr' => $param[regr],
			'som' => $param[som],
			'sort_seq' => $param[sort_seq],
			'status' => $param[status],
			'tape_id' => $param[tape_id],
			'tcin' => $param[tcin],
			'tcout' => $param[tcout],
			'title' => $param[title],
			'ver_cd' => $param[ver_cd],
			'ver_nm' => $param[ver_nm],
			'version' => $param[version],
			'video_bitrate' => $param[video_bitrate]
		) );

		$this->_log($return);

		if( !empty($return->return) ){

			$reencode = json_encode($return->return);
			$result =  json_decode($reencode , true);
		}
		return $result;
	}

	// NPS에서 주조CMS 등록
	function DataSyncCMS($param){
		$client = new SoapClient( self::SOAP_URL );
		$arr = array(array(
			'asset_id' => $param[asset_id],
			'aspect_ratio' => $param[aspect_ratio],
			'asset_tp' => $param[asset_tp],
			'audio_bitrate' => $param[audio_bitrate],
			'audio_channel' => $param[audio_channel],
			'description' => $param[description],
			'device_id' => $param[device_id],
			'duration' => $param[duration],
			'eom' => $param[eom],
			'episode_no' => $param[episode_no],
			'file_nm' => $param[file_nm],
			'file_size' => $param[file_size],
			'frame_rate' => $param[frame_rate],
			'frame_size' => $param[frame_size],
			'genre_tp' => $param[genre_tp],
			'language' => $param[language],
			'media_tp' => $param[media_tp],
			'mtrl_id' => $param[mtrl_id],
			'mtrl_tp' => $param[mtrl_tp],
			'path' => $param[path],
			'program_code' => $param[program_code],
			'program_id' => $param[program_id],
			'reg_user_id' => $param[reg_user_id],
			'som' => $param[som],
			'status' => $param[status],
			'title' => $param[title],
			'version' => $param[version],
			'video_bitrate' => $param[video_bitrate]
		));
		$arr = json_encode($arr);
		$this->_log( $arr );
		$return = $client->DataSyncCMS(array(
			'json_string' => $arr
		));
		

		$this->_log( htmlentities( $client->__getLastRequest() ) );
		$this->_log( $return );

		if( !empty($return->return) ){

			$reencode = json_encode($return->return);
			$result =  json_decode($reencode , true);
		}
		return $result;
	}

	// NPS에서 주조CMS 등록요청
	function DataSyncCMS_Request($param){
		$client = new SoapClient( self::SOAP_URL_DAS );
		$arr = array(array(
			'asset_id' => $param[asset_id],
			'aspect_ratio' => $param[aspect_ratio],
			'asset_tp' => $param[asset_tp],
			'audio_bitrate' => $param[audio_bitrate],
			'audio_channel' => $param[audio_channel],
			'description' => $param[description],
			'device_id' => $param[device_id],
			'duration' => $param[duration],
			'eom' => $param[eom],
			'episode_no' => $param[episode_no],
			'file_nm' => $param[file_nm],
			'file_size' => $param[file_size],
			'frame_rate' => $param[frame_rate],
			'frame_size' => $param[frame_size],
			'genre_tp' => $param[genre_tp],
			'language' => $param[language],
			'media_tp' => $param[media_tp],
			'mtrl_id' => $param[mtrl_id],
			'mtrl_tp' => $param[mtrl_tp],
			'path' => $param[path],
			'program_code' => $param[program_code],
			'program_id' => $param[program_id],
			'reg_user_id' => $param[reg_user_id],
			'som' => $param[som],
			'status' => $param[status],
			'title' => $param[title],
			'pgm_id' => $param[pgm_id],
			'pgm_nm' => $param[pgm_nm],
			'version' => $param[version],
			'video_bitrate' => $param[video_bitrate],
			'nps_content_id' => $param[content_id],
			'req_comment' => $param[req_comment]
		));
		$arr = json_encode($arr);
		$this->_log( $arr );
		$return = $client->TransferRequest($arr);
		

		$this->_log( htmlentities( $client->__getLastRequest() ) );
		$this->_log( $return );

		if( !empty($return->return) ){

			$reencode = json_encode($return->return);
			$result =  json_decode($reencode , true);
		}
		return $result;
	}
}

?>
<?php

class BIS {
	const SOAP_URL = BIS_SOAP_URL;
	private $soap;

    function __construct() {
        $this->soap = new SoapClient( self::SOAP_URL );
    }

    function _log($log) {
        @file_put_contents(LOG_PATH.'/BIS_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.print_r($log,true)."\n", FILE_APPEND);
    }

    function ProgramList($param){ 
        @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/'.basename(__FILE__).'_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] param ===> '.print_r($param, true)."\r\n", FILE_APPEND);

        $return = $this->soap->ProgramList(array(
            'chan_cd' => $param['chan_cd'],
            'pgm_nm'=> $param['pgm_nm'],
            'use_yn'=> $param['use_yn'],
            'page'=> $param['page'],
            'row_per_page'=> $param['row_per_page'],
            'sort_field'=> $param['sort_field'],
            'sort_dir'=> $param['sort_dir']
        ));

        return $this->result($return);
    }

    function EpisodeList($param){

        $return = $this->soap->EpisodeList(array(
            'date'=> $param[date],
            'pgm_id'=> $param[pgm_id],
            'sort'=> $param[sort],
            'dir'=> $param[dir],
            'start'=> $param[start],
            'limit'=> $param[limit]
        ));

        return $this->result($return);
    }

    function MaterialList($param){

        $return = $this->soap->MaterialList(array(
            'pgm_id'=> $param['pgm_id'],
            'epsd_no'=> $param['epsd_no'],
            'ver_cd' => $param['ver_cd']
        ));

        return $this->result($return);
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
            'trff_no'=> $param[trff_no],
            'trff_seq'=> $param[trff_seq],
            'trff_ymd'=> $param[trff_ymd]
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
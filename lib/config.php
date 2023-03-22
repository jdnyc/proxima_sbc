<?php

// 2017.10.07 hkkim 에러 보이도록 설정
$error_repoting = ~E_NOTICE;
error_reporting($error_repoting);
ini_set('display_errors', 'Off');


define('ROOT', dirname(__FILE__));
define('BASEDIR', __DIR__.'/..');
if(!defined('DS'))
    define('DS', DIRECTORY_SEPARATOR);

$autoloadPath = dirname(__DIR__) . DS . 'vendor' . DS . 'autoload.php';
if(!file_exists($autoloadPath)) {
	echo 'Autoload path : ' . $autoloadPath;
	echo '<h1>You need to install packages.</h1>';
	echo '<h1>Type "composer install" on terminal.</h1>';
	die();
}

include $autoloadPath;

$extjsPath = __DIR__ . '/extjs';
if (!file_exists($extjsPath)) {
	echo 'Extjs path : ' . $extjsPath;
	echo '<h1>You need to move extjs library.</h1>';
	echo '<h1>Read the below link.</h1>';
	echo '<a href="https://github.com/whamma/proxima-dev-env/blob/master/README.md#1-extjs-%EB%9D%BC%EC%9D%B4%EB%B8%8C%EB%9F%AC%EB%A6%AC-%EC%A0%81%EC%9A%A9" target="_blank">Proxima dev env</a>';
	die();
}

//일부 상수 선언 위치 변경 2014-12-02 이성용
require_once("config.SYSTEM.php");

/**
 * API 서비스에서 config.php를 로드할 경우(TaskManager를 사용한다던지..)
 * Cannot override frozen service오류가 발생하는데 이는 Slim app을 두번 생성하면
 * 서비스 컨테이너가 두번 생기는데 이것은 Slim app에서 허용하지 않는다.
 * 따라서 PHP APP에서는 구 페이지든 API 서비스든 Slim App은 하나만 생성해야 한다.
 */
if(\Api\Application::getApp() === null) {
	//api 헤더 선언
	require_once(ROOT."/bootstrap.php");
}

// CUSTOM_NAME이 defined되어 있으면 커스터마이징 된 기능들이 App에 포함된다.
if(defined('CUSTOM_NAME') && !empty(CUSTOM_NAME)) {
	// 커스텀 루트(커스텀 사이트별로 경로를 변경해줘야 함)
	define('CUSTOM_ROOT', dirname(ROOT).'/custom/' . CUSTOM_NAME);	
}

//require_once("MDB2.php");
require_once(ROOT."/error_handler.php");
require_once(ROOT."/lang.php");

require_once(ROOT."/MetaData.class.php");
require_once(ROOT."/interface.class.php");
require_once(ROOT."/Logger.php");

// for customizing
if (defined('CUSTOM_ROOT')) {
	$customConfigPath = CUSTOM_ROOT.'/lib/config.php';
	if(file_exists($customConfigPath))
	{
		require_once($customConfigPath);
	}
}

try{

	if(DB_TYPE == 'oracle' ) 
	{		
		require_once(ROOT."/DB.Class.php");
		$mdb = new CommonDatabase(DB_TYPE,DB_USER, DB_USER_PW, DB_HOST.':'.DB_PORT.'/'.DB_SID );
	} else {	
		require_once(ROOT."/DB.Class.php");
		$mdb = new CommonDatabase(DB_TYPE,DB_USER, DB_USER_PW, DB_SERVICE);
	}

	$GLOBALS['db'] = &$mdb;
	$GLOBALS['db_type'] = DB_TYPE;

	//Get System Code
	$arr_sys_code_all = $mdb->queryAll("select * from bc_sys_code");
	$arr_sys_code = array();
	$archive_count = 0;
	foreach($arr_sys_code_all as $sys) {
		$arr_sys_code[strtolower($sys['code'])] = $sys;
		if($sys['ref1'] == 'archive' && $sys['use_yn'] == 'Y') {
			$archive_count++;
		}
	}

	$GLOBALS['arr_sys_code'] = $arr_sys_code;

	//If, use archive, set flag to Y.
	if($archive_count == 1) { //Assume, one system have one archive.
		$archive_use_yn = 'Y';
	} else {
		$archive_use_yn = 'N';
	}
	define('ARCHIVE_USE_YN',	$archive_use_yn);

	function getSequence($seq_name)
	{
		global $db;
		$seq_name = trim($seq_name);

		if( DB_TYPE == 'oracle' ){
			$query = "select ".$seq_name.".nextval from dual";
		}else{
			//$query = "select FN_GET_SEQ('".$seq_name."') from BC_MEMBER WHERE USER_ID = 'admin'";
			$query = "select nextval('".$seq_name."')";
		}
		return $db->queryOne($query);
	}

	function getNextSequence(){
		return getSequence('seq');
	}

	function getNextTaskSequence(){
		return getSequence('task_seq');
	}

	function getNextNoticeSequence(){
		return getSequence('notice_seq');
	}

	function getNextIngestSequence(){
		return getSequence('ingest_seq');
	}

	/**
	 * 시퀀스 filename_seq / 5자리
	 * dbms_job 매일 초기화 filename_seq
	 * 함수 func_filename 'P'||TO_CHAR(SYSDATE, 'YYYYMMDD') || LPAD(filename_seq.NEXTVAL,5,0)
	 * @return bool|null [string] [description]
	 */
	function buildFileID(){
		global $db;

		if( DB_TYPE == 'oracle' ){
			$query = "select func_filename() as filename from dual";
		}else{
			$query = "select func_filename() as filename from BC_MEMBER WHERE USER_ID = 'admin'";
		}

		return $db->queryOne($query);
	}

	function buildSEQID($prefix, $seq_name) {
		global $db;

		$year = date('y');
		$month = strtoupper( dechex(date('m')) );
		$day = date('d');
		$curr_date = $year.$month.$day;

		$result = $db->queryOne("select count(*) from log_seq_id where type='$prefix' and header='$curr_date'");
		if ( $result == 0 )
		{
			$r = $db->exec("call p_reset_seq('$seq_name')");
			$r = $db->exec("insert into log_seq_id values ('$prefix', '$curr_date')");
		}
		$seq = getSequence($seq_name);

		return "$curr_date".sprintf('%03d', $seq);
	}

	function buildArchiveID($prefix, $seq_name)
	{
		global $db;

		$curr_date = date('Ymd');

		$result = $db->queryOne("select count(*) from log_archive_id where type='$prefix' and dt='$curr_date'");
		if ( $result == 0 )
		{
			$r = $db->exec("call p_reset_seq('$seq_name')");

			$db->exec("insert into log_archive_id values ('$prefix', '$curr_date')");
		}

		if( DB_TYPE == 'oracle' ){
			$query = "select archive_seq.nextval from dual";
		}else{
			$query = "select nextval('archive_seq')";
		}

		$seq = $db->queryOne($query);

		return "$prefix$curr_date-".sprintf('%05d', $seq);
	}

	//SESSION LIMIT Value
	if(!empty($_SESSION['user'])) {
	//	$session_time_limit = $db->queryOne("
	//							SELECT	REF1
	//							FROM	BC_SYS_CODE
	//							WHERE	CODE  = 'SESSION_TIME_LIMIT'
	//						");
		$session_time_limit = $arr_sys_code['session_time_limit']['ref1'];
		$_SESSION['user']['session_expire'] = time() + ((int)$session_time_limit * 60);		
	}

	function get_code_name_field()
	{
		if( $_SESSION['user']['lang'] == 'en' ){
			$code_name = 'ENAME';
		}else if( $_SESSION['user']['lang'] == 'other' ){
			$code_name = 'OTHER';
		}else{
			$code_name = 'NAME';
		}

        return $code_name;
	}

	//loudness serverIP
	/*$ats_server_ip = $db->queryOne("
						SELECT	REF2
						FROM	BC_SYS_CODE
						WHERE	CODE = 'INTERWORK_LOUDNESS'
					");

	$ats_soap_address = 'http://'.$ats_server_ip.'/Axis2/services/ATSManagementService';
	*/
	//$interwork_loudness = $db->queryRow("
	//						SELECT	*
	//						FROM	BC_SYS_CODE
	//						WHERE	CODE = 'INTERWORK_LOUDNESS'
	//					");
	$interwork_loudness = $arr_sys_code['interwork_loudness'];

	define('INTERWORK_LOUDNESS', $interwork_loudness['use_yn']);
	define('LOUDNESS_ROOT_STORAGE', str_replace('/', '\\', $interwork_loudness['ref2']));
	define('LOUDNESS_STANDARD_LUFS', $interwork_loudness['ref3']);
	define('LOUDNESS_SERVER_IP', $interwork_loudness['ref4']);
	define('LOUDNESS_RESULT_DIRECTORY', str_replace('/', '\\', $interwork_loudness['ref5']));

    /**
     * CJO, 쓰려고 했다가 안쓰는 기능들 플래그로 처리
     */
    define('CJO_UPDATE_AGENCY_AFTER_HARRIS_SEND', 'N');//해리스 전송 후 Agency 업데이트



	/*
		프리미어 관련 코드
	*/
	define('PREMIERE_AGENT_NM','premiere');
	define('PHOTOSHOP_AGENT_NM','photoshop');

	/* -- END -- */

	ini_set('magic_quotes_runtime',     0);
	ini_set('magic_quotes_sybase',      0);

	define('ENV', 'development');
	
	define('SERVER_IP',		$_SERVER['SERVER_ADDR']);	//192.168.1.80
	define('SERVER_PORT',	$_SERVER['SERVER_PORT']);	//8080
	define('SERVER_HOST',	$_SERVER['HTTP_HOST']);		//192.168.1.80:8080

	define('CHA_SOAP_DAS', 'http://192.168.10.42:86/interface/app/common.php?wsdl');
	define('SOAP_DAS', 'http://192.168.100.4:8080/interface/app/common.php?wsdl');

	// Quality Check 2016.04.26 Alex
	//$interwork_qc = $db->queryRow("
	//		SELECT	*
	//		FROM	BC_SYS_CODE
	//		WHERE	CODE = 'INTERWORK_QC'
	//");
	$interwork_qc = $arr_sys_code['interwork_qc'];
	define('INTERWORK_QC', $interwork_qc['use_yn']); // use of Quality Check


	//$interwork_zodiac = $db->queryRow("
	//	SELECT	*
	//	FROM		BC_SYS_CODE
	//	WHERE	CODE = 'INTERWORK_ZODIAC'
	//");
	$interwork_zodiac = $arr_sys_code['interwork_zodiac'];

	define('INTERWORK_ZODIAC', $interwork_zodiac['use_yn']);//2015-11-24 proxima_zodiac zodiac 연계 여부  
	define('SOAP_ZODIAC_ARTICLE', $interwork_zodiac['ref1']);//2015-11-24 proxima_zodiac zodiac 연계 기사  ArticleIFService
	define('SOAP_ZODIAC_RUNDOWN', $interwork_zodiac['ref2']);//2015-11-24 proxima_zodiac zodiac 연계 큐시트  RundownIFService
	define('SOAP_ZODIAC_USER', $interwork_zodiac['ref3']);//2016-11-01 proxima_zodiac zodiac 연계 사용자  UserIFService

	//2016-03-10 공지사항 첨부파일 저장 경로
	define('UPLOAD_ROOT',	'D:/Storage/upload');
	define('ATTACH_ROOT',	'D:/Storage/lowres');
	define('NOTICE_ATTACH_ROOT', 'D:/Storage/attached/'); // at last path, / need

	//define('SGL_ROOT', '//192.168.1.200/d/storage/storage/highres');//\\192.168.1.200\d\Storage_Zodiac\highres
	//define('SGL_PFR_ROOT', '//192.168.1.200/d/storage/storage/pfr');
	define('SGL_ROOT', $arr_sys_code['interwork_flashnet']['ref2']);
	define('SGL_PFR_ROOT', $arr_sys_code['interwork_flashnet']['ref3']);

	//2016-03-10 사용처 불분명...
	define('HIGHRES_ROOT',	'D:/Storage/highres');
	define('LOWRES_ROOT',	'D:/Storage/lowres');

	define('FRAMERATE',	29.97);
	define('ERROR_QUERY', 100);

	define('LOG_PATH', $_SERVER['DOCUMENT_ROOT'].'/log');
	define('DC_NAME', 'dc=npsad,dc=ebs,dc=co,dc=kr');		//도메인 네임
	define('DC_STORAGE_PATH', 'C:/NPS/');		//도메인 네임
	
	//server IP DEFINE 2010/12.23. sungmin.

	//$streamer_addr_ip = convertIP( $_SERVER['REMOTE_ADDR'] ,'stream' );
	$streamer_addr_ip = STREAM_SERVER_IP;	//HRDK streamer server IP
	define('STREAMER_ADDR', 'rtmp://'.$streamer_addr_ip);	

	//UD GROUP CODE
	define('MEDIA_BIT',		1);
	define('AUDIO_BIT',		2);
	define('CG_BIT',		4);
	define('UD_CONTENT_RESTORE', 4000291);
	// 디버그
	define('DEBUG_ERROR',	5);

	// task agent
	define('TASK_TIMEOUT',		60);
	define('TASK_NOHAVEITEM',	20);
	define('TASK_ERROR',		10);

	define('TASK_WORKFLOW_ARCHIVE', 840);
	define('TASK_WORKFLOW_RESTORE', 841);

	define('ANONYMOUS_GROUP',	84018);
	define('ADMIN_GROUP',		1);
	define('CG_GROUP',			4802258);
	define('CG_ADMIN_GROUP',	4802260);
	define('GROUP_INGEST',      4803750);
	define('REVIEW_GROUP',      9999999);
	define('SC_GROUP', 		    15);
	define('TEST_GROUP', 		17);

		//비상송출그룹
	define('AMG_TM_GROUP',	4802770);

	define('MOVIE', 506);
	define('SOUND', 515);
	define('IMAGE', 518);
	define('DOCUMENT', 57057);
	define('SEQUENCE', 57078);

	define('SYSMETA_DOCUMENT_FORMAT', 1710654);

	// OS check 2016-11-15 by sylee
	if (stripos(PHP_OS, 'win') === 0) {
		define('SERVER_TYPE', 'windows');
	}elseif (stripos(PHP_OS, 'linux') === 0) {
		define('SERVER_TYPE', 'linux');
	}

	//$ud_contents = $db->queryAll("select * from bc_ud_group");

	$GLOBALS['MEDIA_LIST'] = array();
	$GLOBALS['MEDIA_ROOT_CATEGORY'] = null;
	$GLOBALS['CG_LIST'] = array();
	$GLOBALS['CG_ROOT_CATEGORY'] = null;
	$GLOBALS['AUDIO_LIST'] = array();
	$GLOBALS['AUDIO_ROOT_CATEGORY'] = null;

	//super admin
	$GLOBALS['SUPER_ADMIN'] = array('admin', 'tester');
	$GLOBALS['ADMIN_GROUP_ID'] = $mdb->queryOne("SELECT MEMBER_GROUP_ID FROM BC_MEMBER_GROUP WHERE IS_ADMIN = 'Y' ");;


	/*
	if( count($ud_contents) < 0 ){
		foreach($ud_contents as $content) {
			switch($content['ud_group_code']) {
			case MEDIA_BIT :
				array_push($MEDIA_LIST, $content['ud_content_id']);
				$media_root_category = $content['root_category_id'];
				break;
			case AUDIO_BIT :
				array_push($AUDIO_LIST, $content['ud_content_id']);
				$audio_root_category = $content['root_category_id'];
				break;
			case CG_BIT :
				array_push($CG_LIST, $content['ud_content_id']);
				$cg_root_category = $content['root_category_id'];
				break;
			}
		}
	}
	*/

}catch(Exception $e){
	echo $e->getMessage();
}
define('ARIEL_CATALOG',					10);
define('ARIEL_THUMBNAIL_CREATOR',		11);
define('ARIEL_QC',						15);
define('ARIEL_TRANSCODER',				20);
define('ARIEL_TRANSCODER_hi',			21);
define('ARIEL_TRANSCODER_OVERLAY',		25);
define('ARIEL_IMAGE_TRANSCODER',		22);
define('ARIEL_PATIAL_FILE_RESTORE',		30);
define('ARIEL_REWARPPING',				31);
define('ARIEL_AVID_TRANSCODER',			40);
define('ARIEL_LOUDNESS',				50);
define('ARIEL_TRANSFER_FS',				60);
define('ARIEL_TRANSFER_FS_TO_NEARLINE', 61);
define('ARIEL_TRANSFER_FS_NPSTODAS',	62);
define('ARIEL_TRANSFER_YTN',			63);
define('ARIEL_TRANS_AUDIO',				70);
define('ARIEL_TRANSFER_FTP',			80);
define('ARIEL_FTP_DCART',				81);
define('ARIEL_HIGH_TRANSCODER',			90);
define('ALTO_ARCHIVE',					110);
define('ARIEL_DELETE_JOB',				100);
define('ARIEL_MXF_VALIDATE',			34);
define('ARIEL_EXTRACTMOVKEY',			35);
define('ARIEL_MOV_HEADER',				36);
define('ARIEL_OPENDIRECTORY',			120);
define('ARIEL_INFOVIEW',				130);

define('ARCHIVE',						110);
define('ARCHIVE_DELETE',				150);
define('RESTORE',						160);
define('RESTORE_PFR',					140);
define('ARCHIVE_LIST',					170);

// For Loudness
define('LOUDNESS_MEASUREMENT',			200);
define('LOUDNESS_ADJUST',			210);

define('SNS_SHARE',					220);
define('SNS_DELETE',				221);

define('REG_COMPLETE', 0);
define('REG_WATCH_FOLDER_QUEUE', 1);
define('REG_WATCH_FOLDER_PROCESSING', 2);

//For Image Transcoding
define('PROXY_INFO_TASK_RULE_ID', '14');
define('THUMB_INFO_TASK_RULE_ID', '15');

define('CODESET_CHANNEL_NAME',			1);

// 콘텐츠 상태 ( status ) //2010.12.16 김성민. 2번까지 상태값 디파인.
define('FILER_REG_STATUS',				-2);	// NPS to DAS전송시 작업완료전까지의 상태
define('CONTENT_STATUS_REG_HIDDEN',		-2);	// NPS to DAS전송시 작업완료전까지의 상태
define('INGEST_READY',					-3);//인제스트리스트 등록시
define('ORACLE_MIGRATION_STATUS',		-4); //(2011/01/12 조훈휘 추가)에이전트에 등록을 위한 상태값
define('INGEST_LIST_STATUS',			-3);//인제스트리스트 등록시
define('SUB_CONTENT_STATUS',			-7);//인제스트리스트 등록시
define('WATCH_FLODER_REGIST',			-1);//와치폴더등록시 상태값
define('REGIST_TEST_HIDDEN',			-4);//민효 테스트용
define('CONTENT_STATUS_REG_READY',		 0);	// 등록 대기 상태
define('CONTENT_STATUS_REFUSE',			-5); //반려 시 상태값
define('CONTENT_STATUS_REACCEPT',		-6); //재승인 요청 상태값 2011-02-22 by 이성용
define('INGEST_COMPLETE',				1);
define('INGEST_STATUS',					1); // 인제스트등록시
define('CONTENT_STATUS_COMPLETE',		2); // 등록완료 상태
define('CONTENT_STATUS_REVIEW_READY',	3);
define('CONTENT_STATUS_REVIEW_ACCEPT',	4);
define('CONTENT_STATUS_REVIEW_RETURN',	5);
define('CONTENT_STATUS_REVIEW_HALF',	6);
define('d_cart_regist_complete',		7); // d_cart전송완료 상태
define('CONTENT_STATUS_WATCHFOLDER_REG_WAIT',		9);//와치폴더 등록대기 상태

//ex) 사용자 그룹 3번에 읽기, 쓰기, 중해상도 다운로드 3가지 권한을 준다면
//		GRANT_READ + GRANT_WRITE + GRANT_MR_DOWNLOAD = 19가 된다.(BC_GRANT테이블의 group_grant 필드값.)
// 촤대 권한 62개
// 32bit 최대 2147483647
// 64bit 최대 9223372036854775807
// 참고 http://php.net/manual/en/language.types.integer.php

define('GRANT_BASE', 					0x1);

//권한 관리 checkAllowUdContentGrant 함수 사용
//2019-03-25 이승수. 권한관리 통합
define('GRANT_READ',                GRANT_BASE);//1
define('GRANT_EDIT',                GRANT_BASE << 1);//2
define('GRANT_CONTENT_DELETE',      GRANT_BASE << 2);//4
define('GRANT_DELETE',              GRANT_BASE << 2);//4
define('GRANT_CREATE',              GRANT_BASE << 3);//8
define('GRANT_DOWNLOAD',            GRANT_BASE << 4);//16
define('GRANT_SHOW_WORKFLOW',       GRANT_BASE << 5);//32
define('GRANT_CONTENT_MENU',        GRANT_BASE << 6);//64
define('GRANT_CATEGORY_MANAGE',     GRANT_BASE << 7);//128

define('GRANT_EDIT_MY_CONTENT',		GRANT_BASE << 11);//2048 내가 등록한 콘텐츠 수정
define('GRANT_DELETE_MY_CONTENT',	GRANT_BASE << 12);//4096 내가 등록한 콘텐츠 삭제

define('GRANT_ACCESS_READ', 			    GRANT_BASE);//1
define('GRANT_ACCESS_EDIT', 			    GRANT_BASE << 1);//2
define('GRANT_ACCESS_CREATE',			    GRANT_BASE << 2);//4
define('GRANT_ACCESS_DOWNLOAD',			    GRANT_BASE << 3);//8 우클릭으로 다운로드함.
define('GRANT_ACCESS_CATEGORY_MANAGE', 	    GRANT_BASE << 4);//16
define('GRANT_ACCESS_VIEW_CUESHEET',        GRANT_BASE << 5);//32
define('GRANT_ACCESS_APPROVAL_CONTENT',     GRANT_BASE << 6);//64
define('GRANT_ACCESS_EDIT_MY_CONTENT',		GRANT_BASE << 11);//2048 내가 등록한 콘텐츠 수정
define('GRANT_ACCESS_DELETE_MY_CONTENT',	GRANT_BASE << 12);//4096 내가 등록한 콘텐츠 삭제

define('GRANT_ARCHIVE',					GRANT_BASE << 3);//8
define('GRANT_RESTORE',					GRANT_BASE << 4);//16
define('GRANT_LOUDNESS',				GRANT_BASE << 5);//32
define('GRANT_CONTENT_ACCEPT',			GRANT_BASE << 7);//128
define('GRANT_PFR', 	                GRANT_BASE << 10);//1024
define('GRANT_CUESHEET', 	            GRANT_BASE << 17);//131072

// 설정
define('CONFIG_THUMB_PREVIEW_LIMIT', 6);	// 썸네일 수   위치 : /store/get_content_list/libs/functions.php
define('CONFIG_THUMB_DIV_WIDTH', 403);		// 쎔네일 표시 div 가로길이 위치 : /store/get_content_list/libs/functions.php
define('CONFIG_THUMB_DIV_HEIGHT', 240);		// 쎔네일 표시 div 세로길이 위치 : /store/get_content_list/libs/functions.php
define('CONFIG_QTIP_WIDTH', 400);			// qtip 길이 위치 :  /pages/browse/content.php
define('CONFIG_QTIP_WIDTH_DOCUMENT', 160);	// qtip 길이_프로젝트 및 기타 위치 :  /pages/browse/content.php
define('CONFIG_THUMB_IMG_WIDTH', 120);		// 썸네일 이미지  가로길이 위치 : /store/get_content_list/libs/functions.php


// 2011-12-23
///상태값 3,4 를 추가  by 허광회
//콘텐츠 상태값
//define('CONTENT_STATUS_REG_READY',		0); //등록 대기
//define('CONTENT_STATUS_REVIEW_READY',	1); //심의 대기
//define('CONTENT_STATUS_REG_COMPLETE',	2); //등록 완료 : 기술 심의, 내용 심의 대상에 따라 둘 다 대상이면 둘 다 완료 되어야 등록 완료로 변경된다.
define('CONTENT_STATUS_DELETE_REQEUST',	3); //삭제 요청 :  사용자 요청으로 상태값이 변경된다.
define('CONTENT_STATUS_DELETE_EXPIRE',  4); //삭제 요청 :  기한만료가 되면 상태값이 변경된다.
define('CONTENT_STATUS_DELETE_APPROVE', 5); //삭제 승인 : 콘텐츠의 기한만료나 사용자 요청으로 삭제 요청을 승락하면 이값으로 변경된다.
define('CONTENT_STATUS_DELETE_COMPLETE',6); //삭제 완료 : 콘텐츠의 관리자의 승인으로 삭제 완료된 상태값

define('CONTENT_STATUS_YOUTUBE_DELETE_REQEUST',7);


//사용자 요구로 인한 처리를 관리자 or 자동 FLAG
// true 면 관리자 승인없이 자동 처리 되도록함
define('DELETE_USER_REQUEST_FLAG',true);

//미디어 상태값
define('del_complete_code','DC'); // 미디어파일 삭제상태
define('del_error_code','DO'); // 미디어파일 삭제 에러상태
define('del_admin_approve_code','DA'); //미디어파일 관리자 승인상태
define('del_request_code','DR'); //미디어파일 사용자가 삭제 요청상태
define('del_limit_code','DL'); //미디어파일 만료상태

define('DEL_MEDIA_COMPLETE_FLAG','DMC'); //미디어 파일 삭제 완료 상태
define('DEL_MEDIA_ERROR_FLAG','DME'); // 미디어 파일 에러 상태
define('DEL_MEDIA_REQUEST_FLAG','DMR'); //미디어 파일 사용자 요청 상태
define('DEL_MEDIA_DATE_EXPIRE_FLAG','DME'); //미디어 파일 만료 상태
define('DEL_MEDIA_CONTENT_REQUEST_FLAG','DCR'); // 콘텐츠의 삭제 요청으로인한 미디어 삭제요청상태
define('DEL_MEDIA_CONTENT_EXPIRE_FLAG','DCE'); // 콘텐츠의 기한만료로 인한 미디어 삭제요청상태

//다음날 삭제 처리할 파일 FLAG형태
define('DEL_MEDIA_AUTO_APPROVE_FLAG','DAA'); // 콘텐츠 자동승인 상태
define('DEL_MEDIA_ADMIN_APPROVE_FLAG','DMA'); //미디어 파일 관라자 승인 상태

//NPS와 XML 통신을 위한 서버 정보 정의 2012-06-12 by 이성용
define('DAS_MAM_SERVER_IP', '10.10.10.171');
define('DAS_MAM_SERVER_PORT', '80');
define('DAS_MAM_PAGE_FOR_UPDATE', 'interface/link_cms/update_status.php');
define('DAS_MAM_PAGE_FOR_TO_DAS', 'interface/link_cms/nps_to_das.php');

define('DMC_MAM_SERVER_IP', '172.16.2.4');
define('DMC_MAM_SERVER_PORT', '80');
define('DMC_MAM_PAGE_FOR_UPDATE', 'interface/link_cms/update_status.php');
define('DMC_MAM_PAGE_FOR_TO_DMC', 'interface/link_cms/nps_to_dmc.php');
#============================================
# log aciton 정의

# accept			//콘텐츠 승인
# transferFS		//FS전송
# edit				//콘텐츠 수정
# regist			//콘텐츠 등록
# rewarpping
# transcodingM		//트랜스코딩
# accept_request	//재승인요청
# transcodingA		//트랜스코딩
# refuse_encoding	//콘텐츠 반려 인코딩문제
# catalog			//카달로그 등록
# login				//로그인
# dastonps			//DAS To NPS 전송
# DMC				//DMC 등록
# read				//읽기
# transferFTP		/FTP 등록
# delete			//콘텐츠 삭제 - content 테이블의 is_deleted => 1 업데이트
# refuse_meta		//콘텐츠 반려 메타데이터문제
# download			//콘텐츠 다운로드
#sub_content_regist	//가상클립 등록

#============================================

# content_type 정의
# 영상물 = 506
# 음성   = 515
# 이미지 = 518
# 문서   = 57057
# 기타   = 643

#Manager 작업 코드/////////////////////////////////////////////////
#define ARIEL_CATALOG               10
#define ARIEL_TRANSCODER            20
#define ARIEL_PATIAL_FILE_RESTORE   30
#define ARIEL_AVID_TRANSCODER       40
#define ARIEL_TRANSFER_FS           60
#define ARIEL_TRANS_AUDIO           70
#define ARIEL_TRANSFER_FTP          80

#<Medias><media Type=""/></Medias>에서 media의 Type 정의///////////
#original	:원본 미디어
#thumb		:이미지 미디어 일시의 섬네일
#proxy		:영상 미디어의 프록시 영상 등록시
#text		:문서 미디어의 내용데이터
#동영상일 경우 <media>태그가 original 한줄뿐이지만 이미지 일경우 <media>태그가 두줄. <media Type"original" /><media Type\"thumbnail">

//get_queue.php 에 시스템메타 넘버 정의 필요


#등록대기 = 0 (default)
#등록요청 = 1
#심의비대상(등록완료) = 2
#심의대기중 = 3
#승인 = 4
#반려 = 5
#조건부승인 = 6

#harris -
#Y/P :사전제작
#Y/C : 클린
#Y/R : 지난방송
#Y/V : VOD


function convertIP( $remote_ip , $type = null )
{
	//_SERVER["REMOTE_ADDR"]//리모트아이피
	//_SERVER["SERVER_PORT"]//서버포트
	$port = $_SERVER["SERVER_PORT"];

	if( $type == 'board' )
	{
		$port = '8080';
	}

	if( empty($remote_ip) ) $remote_ip = $_SERVER["REMOTE_ADDR"];
	return $remote_ip;

}

function fn_checkAuthPermission($var){	
	if ( $var['user']['user_id'] !='' && $var['user']['user_id'] !='temp'){
	}else{
		echo '<script type="text/javascript">
			alert("권한이 없습니다.");
			window.location = "/";
		</script>';
        die();
	}
}
?>
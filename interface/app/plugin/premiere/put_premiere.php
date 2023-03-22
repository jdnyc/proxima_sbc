<?php

/*
 프리미어 플러그인 메타및 파일 실제로 등록처리
 두가지 방식
 1. 원래는 fcp_xml 로 저장 하려 했으나 pproj 로 저장하는 방식  (로드시 해당 seq_id만 로드하도록 구현)
 2. 해당 영상을 Render를 통해 등록하는 방식

 2016-08-24 by hkh

 MAC 등록관련 부분 추가 및 버그 수정
 2016-11-09 by hkh
*/

session_start();

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/MetaData.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/Search.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

//$receive_xml = iconv('euc-kr', 'utf-8', file_get_contents('php://input'));
$receive_datas  = $_POST['datas'];
$log_path       = $_SERVER['DOCUMENT_ROOT'].'/log/register_sequence_'.date('Ymd').'.log';
$log_file_nm    = "register_sequence"; //로그 파일 NM
$filename       = $log_file_nm;
$path           = $_POST['path'];
$type           = $_POST['type'];
$content_id     = $_POST['content_id']; // 등록시 content_id
$seq_id         = $_POST['seq_id'] ? $_POST['seq_id'] : '';  // 등록시 관리되는 시퀀스 ID


$media_type     	= $arr_sys_code['premiere_plugin_use_yn']['ref3'];
$lowres         	= $arr_sys_code['premiere_plugin_use_yn']['ref4'];
$render_prefix_path = $arr_sys_code['premiere_plugin_use_yn']['ref5'];

$mam_render_prefix_path  = "";

/*
  MAC에서 등록 관련
*/
$ismac          = $_POST['ismac']?$_POST['ismac'] : false;

if($lowres){
	 $lowres = explode(";",$lowres);	

	 if($ismac == 'true' || $ismac === true) {
	 	_debug($filename, "MAC ========== LOWRES  GET!!!");	
	 	$lowres = $lowres[1];
	 }else {
	 	_debug($filename, "WINDOW ========== LOWRES  GET!!!");	
	 	$lowres = $lowres[0];
	 }
}

if($render_prefix_path){
	 $render_prefix_path = explode(";",$render_prefix_path);
	 _debug($filename, print_r($render_prefix_path,true));	
	 $mam_render_prefix_path = $render_prefix_path[0];
	 if($ismac == 'true' || $ismac === true) {
	 	_debug($filename, "MAC ========== render_prefix_path  GET!!!");	
	 	$render_prefix_path = $render_prefix_path[1];
	 }else {
	 	_debug($filename, "WINDOW ========== render_prefix_path  GET!!!");	
	 	$render_prefix_path = $render_prefix_path[0];
	 }
}

_debug($filename, print_r($receive_datas,true));
_debug($filename, "ismac : ".$ismac);
_debug($filename, "lowres : ".$lowres);
try
{
		/**
			FCP_XML or Project 파일로만 등록할 경우 ..
			우선 영상 정보에 관한건 없다. ProJECT 파일을 압축을 풀어 안에 XML 파싱하지 않는 이상 시퀀스 ID 정보를 알수 없음

			실제 Project에서 시퀀스 정보만 가져올수 있으면
			프리미어어서 불러오고 싶은 시퀀스ID만 알면 자동으로 시퀀스 로드를 할  수 있음.
			현재는 하지 못함..
      추후 해당 pporj 파일의 시퀀스ID를 저장할 수 있는 TABLE을 만들고 그 정보를 넣어 줌 으로서
      시퀀스 로드시 해당 시퀀스만 정확히 로드 할 수 있는 것으로 파악되어 코딩됨

      랜더 부분
		*/

		if($type == 'fcp_xml')
		{

      //문법 검사 Json 형태로 받아짐
			$ReqRender		=  InterfaceClass::checkSyntax($receive_datas);
			$type			    = $ReqRender['type'];
			$render_data	= $ReqRender['data'];
			$requestxml   = $render_data;

      _debug($filename, $type);
      _debug($filename,print_r($render_data,true));

      //JSON 형태로 들어온 메타정보를 분석
			$metaValues = InterfaceClass::getMetaValues($requestxml['metadata']);

      _debug($filename, print_r($metaValues,true));

			$category_id   = $requestxml['metadata'][0]['c_category_id'];
			$ud_content_id = $requestxml['metadata'][0]['k_ud_content_id'];
			$title         = $requestxml['metadata'][0]['k_title'];

			$bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");

      // 기본 콘텐츠 상태 값
      $status = 2;

      // 콘텐츠 등록자 user_id
			$user_id	= $requestxml['user_id'];

      _debug($filename, print_r($category_id,true));
      _debug($filename, print_r($ud_content_id,true));
      _debug($filename, print_r($title,true));
      _debug($filename, print_r($bs_content_id,true));

			//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($category_id,true)."\n\n", FILE_APPEND);
			//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($ud_content_id,true)."\n\n", FILE_APPEND);
			//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($title,true)."\n\n", FILE_APPEND);
			//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($bs_content_id,true)."\n\n", FILE_APPEND);

      // POST로 온 값이 없을시 자동으로 생성
			$content_id = $content_id ? $content_id : getSequence('SEQ_CONTENT_ID');
      _debug($filename, "content_id :".$content_id);
			//file_put_contents($log_path, date("Y-m-d H:i:s\t")."content_id : $content_id /"."\n\n", FILE_APPEND);

			InterfaceClass::insertContent($metaValues, $content_id, $category_id,$bs_content_id, $ud_content_id, $title , $user_id, $status);
      _debug($filename, "============ content_ insert  complete =============");
		//	file_put_contents($log_path, date("Y-m-d H:i:s\t")."============content_ insert ============= /"."\n\n", FILE_APPEND);

			InterfaceClass::insertMetaValues($metaValues, $content_id, $ud_content_id);
      _debug($filename, "============ meatavalues insert complete =============");
			//file_put_contents($log_path, date("Y-m-d H:i:s\t")."============meatavalues  insert ============= /"."\n\n", FILE_APPEND);

      /**
        NEW 미디어아이디 시퀀스로 생성
      */
      $media_id =  getSequence('SEQ_MEDIA_ID');

      $db->insert('tb_premiere_seqinfo', array(
          'content_id' => $content_id,
          'media_id' => $media_id,
          'seq_id' => $seq_id
      ));

      /*
			$seq_info_query = "
				insert into tb_premiere_seqinfo (content_id,media_id,seq_id)
				values({$content_id},{$media_id},'{$seq_id}')
			";

			$db->exec($seq_info_query);
      */

      _debug($filename, "============ tb_premiere_seqinfo insert complete =============");



		$fullpath = $path;

		$created_datetime = date('YmdHis');
		$expired_date = InterfaceClass::check_media_expire_date($ud_content_id, 'original', $created_datetime);
      $expired_date = $expired_date ? $expired_date : '99981231000000';
      $channel = 'premiere_proj';
      // filesize 체크하는 부분을 넣으려 했으나 그냥 고정으로 우선 100byte로 넘음
      // 만약 문제시 실제 해당 경로 byte 구하는 부분을 넣어줘야함
      $default_filesize = 100;

      // $storage_id = ''; 
      $us_type ="lowres";

      $storage_info = $db->queryRow("
			SELECT	*
			FROM	VIEW_UD_STORAGE
			WHERE	ud_content_id = ".$ud_content_id."
			and		us_type='".$us_type."'
	  ");

	   _debug($filename, print_r($storage_info,true));

	  $fullpath  = str_replace("\\","/",$fullpath);
	  
	  $path = $storage_info['path'];
	  _debug($filename, "=== == PAth : ".$path);

	  $fullpath  = str_replace(strtolower($path),"",strtolower($fullpath));

	  //MAC인경우 Volumbs 
	  if($ismac){
	  	 $fullpath  = str_replace(strtolower($lowres),"",strtolower($fullpath));
	  }

	  _debug($filename, "=== == fullpath : ".$fullpath);


      $db->insert('bc_media', array(
          'content_id' => $content_id,
          'media_id' => $media_id,
          'media_type' => $media_type,
          'storage_id' => $storage_info['storage_id'],
          'path' => $fullpath,
          'reg_type' => $channel,
          'filesize' => $default_filesize,
          'created_date' => $created_datetime,
          'expired_date' => $expired_date
      ));
      /*
			$query = "insert into bc_media
                    (content_id,
                     media_id,
                     media_type,
                     storage_id,
                     path,
                     reg_type,
                     filesize,
                     created_date,
                     expired_date)
						     values
                     ({$content_id},
                     {$media_id},
                     '{$media_type}',
                     0,
                     '{$fullpath}',
                     '{$channel}',
                     {$default_filesize},
                     '{$created_datetime}',
                     '{$expired_date}')
               ";

			$db->exec($query);
      */
      _debug($filename, "============ bc_media insert complete =============");

		//insertSysMetaValus($metaValues, $content_id, $meta_table_id)
			$sys_metaValues = array("sys_filename"=>$path);
			InterfaceClass::insertSysMetaValus($sys_metaValues, $content_id, $bs_content_id);
      _debug($filename, "============ insertSysMetaValus insert complete =============");
			//file_put_contents($log_path, date("Y-m-d H:i:s\t")."============insertSysMetaValus  insert ============= /"."\n\n", FILE_APPEND);

			echo json_encode(array(
				'success' => true
			));
		}
		else if($type == 'render'){

			/*
				Direct Render로 해당 영상을 추출
			*/

      //문법 검사 Json 형태로 받아짐
			$ReqRender		= InterfaceClass::checkSyntax($receive_datas);
			$type			    = $ReqRender['type'];
			$render_data	= $ReqRender['data'];
			$requestxml   = $render_data;

      _debug($filename, $type);
      _debug($filename,print_r($render_data,true));

      //JSON 형태로 들어온 메타정보를 분석
			$metaValues = InterfaceClass::getMetaValues($requestxml['metadata']);

      _debug($filename, print_r($metaValues,true));

			$category_id   = $requestxml['metadata'][0]['c_category_id'];
			$ud_content_id = $requestxml['metadata'][0]['k_ud_content_id'];
			$title         = $requestxml['metadata'][0]['k_title'];

			$bs_content_id = $db->queryOne("select bs_content_id from bc_ud_content where ud_content_id='$ud_content_id'");

      // 기본 콘텐츠 상태 값
      $status = 2;

      // 콘텐츠 등록자 user_id
			$user_id	= $requestxml['user_id'];

      _debug($filename, print_r($category_id,true));
      _debug($filename, print_r($ud_content_id,true));
      _debug($filename, print_r($title,true));
      _debug($filename, print_r($bs_content_id,true));

			//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($category_id,true)."\n\n", FILE_APPEND);
		//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($ud_content_id,true)."\n\n", FILE_APPEND);
			//file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($title,true)."\n\n", FILE_APPEND);
		//	file_put_contents($log_path, date("Y-m-d H:i:s\t").print_r($bs_content_id,true)."\n\n", FILE_APPEND);

      $content_id = $content_id ? $content_id : getSequence('SEQ_CONTENT_ID');
      _debug($filename, "content_id :".$content_id);

			file_put_contents($log_path, date("Y-m-d H:i:s\t")."content_id : $content_id /"."\n\n", FILE_APPEND);
			InterfaceClass::insertContent($metaValues, $content_id, $category_id,$bs_content_id, $ud_content_id, $title , $user_id, $status);
			file_put_contents($log_path, date("Y-m-d H:i:s\t")."============content_ insert ============= /"."\n\n", FILE_APPEND);
			InterfaceClass::insertMetaValues($metaValues, $content_id, $ud_content_id);
			file_put_contents($log_path, date("Y-m-d H:i:s\t")."============meatavalues  insert ============= /"."\n\n", FILE_APPEND);

			/*
				transcoding and catalog job run
			*/
			$this_workflow_channel = 'premiere';
 _debug($filename, "this_workflow_channel :".$this_workflow_channel);
			$task = new TaskManager($db);
      /*
        $path 부분  opt5 에 prefix 를 때어낸후 워크플로우 실행
      */
      $path = str_replace("/","\\",$path);
      $path = str_replace("\\\\","\\",$path);

      $render_prefix_path = str_replace("/","\\",$render_prefix_path);

			$path = str_replace(strtolower($render_prefix_path),"",strtolower($path));
			_debug($filename, "render_prefix_path :".$render_prefix_path);
 			_debug($filename, "path :".$path);
			//MAC인경우 Volumbs 
		  if($ismac){
		  	$path  = str_replace(strtolower($render_prefix_path),"",strtolower($path));

		  	 //$path = $mam_render_prefix_path.$path;
		  }

		  $path = str_replace("\\","/",$path);
		  _debug($filename, "mam_render_prefix_path :".$mam_render_prefix_path);
		  _debug($filename, "MAC CHECK path :".$path);


			$task_id = $task->insert_task_query_outside_data($content_id, $this_workflow_channel, 1, $user_id, $path);

			 _debug($filename, "task_id :".$task_id);

			if($task_id)
			{
				echo json_encode(array(
					'success' => true,
					'task_id' => $task_id
				));
			}
			else
			{
				echo json_encode(array(
					'success' => false,
					'msg'=>'workflow_start fail'
				));
			}


		}




}
catch (Exception $e)
{
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

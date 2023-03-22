<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

/**
  해당 관련 정보 가져고이
*/

$type = $_REQUEST['type'];
$ismac = $_REQUEST['ismac'];

try{

// content_id 생성
// 시퀀스만 저장
  if($type == 'pproj_save'){

    $content_id	= getSequence('SEQ_CONTENT_ID');

    if($content_id){
      /**
        저장 경로 만들기 사이트마다 다를 경우 여기서 수정해줘야함
      */

      $default_path = $arr_sys_code['premiere_plugin_use_yn']['ref4'];
	
	  $default_paths = explode(";",$default_path);

	  if($ismac){
		  $default_path = $default_paths[1];
	  }else {
		  $default_path = $default_paths[0];
	  }

      $m_path = date('Y')."/".date('m')."/".date('d')."/".$content_id;

      $save_dir  =  $default_path."/".$m_path;
      $save_path = $default_path."/".$m_path."/".$content_id."_sequence.prproj";
      $save_path = $default_path."/".$content_id."_seqeuence.prproj";
     $mk_save_dir = $default_paths[0]."/".$m_path;

	  if($ismac){
		 
	  }else {
		 $save_dir  = str_replace("/","\\",$save_dir);
		 $save_path = str_replace("/","\\",$save_path);
	  }

      

      //경로를 만들어줘야 한다.. exist
      @mkdir($mk_save_dir, 0755, true);

      echo json_encode(array(
      	"success" => true,
      	"content_id" => $content_id,
        "save_path"  => $save_path
      ));

    }else {
      throw new Exception("Request's Generated is failed!");
    }
  }
  //스마트 렌더를 이용하여 렌더 후 워크플로우 
  else if($type =="render"){

    $content_id	= getSequence('SEQ_CONTENT_ID');

    if($content_id){

      /**
        저장 경로 만들기 사이트마다 다를 경우 여기서 수정해줘야함
      */

	  $m_path = date('Y')."/".date('m')."/".date('d')."/".$content_id;

      $default_path = $arr_sys_code['premiere_plugin_use_yn']['ref5'];

	  $default_paths = explode(";",$default_path);
	
	  if($ismac){
		  $default_path = $default_paths[1];
	  }else {
		  $default_path = $default_paths[0];
	  }
	  
      $save_dir  =  $default_path."/".$m_path;
      $save_path = $default_path."/".$m_path."/".$content_id."_render.mxf";
      $save_path = $default_path."/".$content_id."_render.mxf";
	  if($ismac){
		 
	  }else {
		 $save_dir  = str_replace("/","\\",$save_dir);
		 $save_path = str_replace("/","\\",$save_path);
	  }
    
    $mk_save_dir = $default_paths[0]."/".$m_path;

      //경로를 만들어줘야 한다.. exist
      mkdir($mk_save_dir, 0755, true);

      echo json_encode(array(
      	"success" => true,
      	"content_id" => $content_id,
        "save_path"  => $save_path
      ));

    }else {
      throw new Exception("Request's Generated is failed!");
    }
  }
  else {
    throw new Exception("Type request value is Empty!");
  }

}catch(Excpeion $e){

  echo json_encode(array(
  	"success" => false,
    "msg"=>$e->getmessage(),
  	"data" => $result
  ));

}


?>

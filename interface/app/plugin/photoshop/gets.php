<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

/**
  해당 관련 정보 가져고이
*/

$type = $_REQUEST['type'];

try{

// content_id 생성
// 시퀀스만 저장
  if($type == 'image_save'){

    $content_id	= getSequence('SEQ_CONTENT_ID');

    if($content_id){
      /**
        저장 경로 만들기 사이트마다 다를 경우 여기서 수정해줘야함
      */

      $default_path = $arr_sys_code['photoshop_plugin_use_yn']['ref4'];
      $m_path = date('Y')."/".date('m')."/".date('d')."/".$content_id;

      $save_dir  =  $default_path."/".$m_path;
      $save_path = $default_path."/".$m_path."/".$content_id."_ps.jpg";
      $save_dir  = str_replace("/","\\",$save_dir);
      $save_path = str_replace("/","\\",$save_path);

      //경로를 만들어줘야 한다.. exist
      @mkdir($save_dir, 0755, true);

      echo json_encode(array(
      	"success" => true,
      	"content_id" => $content_id,
        "save_path"  => $save_path
      ));

    }else {
      throw new Exception("Request's Generated is failed!");
    }
  }else {

    throw new Exception("Request Type is Wrong!");
  }

}catch(Excpeion $e){

  echo json_encode(array(
  	"success" => false,
    "msg"=>$e->getmessage(),
  	"data" => $result
  ));

}


?>

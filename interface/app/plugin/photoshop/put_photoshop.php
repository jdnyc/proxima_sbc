<?php

/*
 포토샵 플러그인

 2016-08-26 by hkh
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
$log_path       = $_SERVER['DOCUMENT_ROOT'].'/log/register_photoshop_'.date('Ymd').'.log';
$log_file_nm    = "register_photoshop"; //로그 파일 NM

$path           = $_POST['path'];
$type           = $_POST['type'];
$content_id     = $_POST['content_id']; // 등록시 content_id
$render_prefix_path = $arr_sys_code['photoshop_plugin_use_yn']['ref4'];

_debug($filename, print_r($receive_datas,true));

try
{
  if($type == 'jpeg'){


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
			$this_workflow_channel = 'fileingest';

			$task = new TaskManager($db);
      /*
        $path 부분  opt5 에 prefix 를 때어낸후 워크플로우 실행
      */
      $path = str_replace("/","\\",$path);
      $path = str_replace("\\\\","\\",$path);

      $render_prefix_path = str_replace("/","\\",$render_prefix_path);

      $path = str_replace(strtolower($render_prefix_path),"",strtolower($path));

			$task_id = $task->insert_task_query_outside_data($content_id, $this_workflow_channel, 1, $user_id, $path);

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

<?php
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
require_once(dirname(__DIR__) . DS . 'lib'. DS .'lang.php');
class SNS
{
	private $arr_sys_code = null;
	public $restful_url_1 = '';
	public $restful_url_2 = '';
	public $restful_url_3 = '';

	public function __construct() {
		global $arr_sys_code;
		$this->arr_sys_code = $arr_sys_code;
		$this->restful_url_1 = $arr_sys_code['interwork_sns']['ref1'];
		$this->restful_url_2 = $arr_sys_code['interwork_sns']['ref2'];
		$this->restful_url_3 = $arr_sys_code['interwork_sns']['ref3'];
	}

	function _log($log){
		@file_put_contents(LOG_PATH.'/SNS_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').'] '.print_r($log,true)."\r\n", FILE_APPEND);
	}

	// get title for SNS
	function getTitle($content_id){
		global $db;
		$title = $db->queryOne("SELECT TITLE FROM BC_CONTENT WHERE CONTENT_ID=".$content_id);
		return $title;
	}

	// get content for SNS
	function getContent($content_id, $line_char="\r\n"){
		global $db;
		$content_info = $db->queryRow("SELECT * FROM BC_CONTENT WHERE CONTENT_ID=".$content_id);
		$meta_all = MetaDataClass::getFieldValueInfo('usr',$content_info['ud_content_id'],$content_id);
		$arr_sns_meta = array();
		$str_sns_meta = '';
		foreach($meta_all as $meta) {
			if($meta['is_social'] == 1) {
				$arr_sns_meta[] = array(
					'field' => $meta['usr_meta_field_title'],
					'value' => $meta['value']
				);
				$str_sns_meta .= $meta['usr_meta_field_title'].' : '.$meta['value'].$line_char;
			}
		}
		$str_sns_meta = rtrim($str_sns_meta, $line_char);

		return array(
			'content_string' => $str_sns_meta,
			'content_array' => $arr_sns_meta
		);
	}

	function upload($task_id) {
		global $db, $arr_sys_code;

		$this->_log("upload task_id:".$task_id);

		$sns_task_info = $db->queryRow("
			SELECT B.PATH
				  ,A.*
			FROM  (
				  SELECT B.SOURCE
						,B.SRC_STORAGE_ID
						,A.*
				  FROM  (
						SELECT *
						FROM BC_SOCIAL_TRANSFER
						WHERE TASK_ID=".$task_id."
						) A
						LEFT OUTER JOIN
						BC_TASK B
						ON(A.TASK_ID=B.TASK_ID)
				  ) A
				  LEFT OUTER JOIN
				  BC_STORAGE B
				  ON(A.SRC_STORAGE_ID=B.STORAGE_ID)
		");
		//$this->_log("task_info:".$task_id."\r\n".print_r($sns_task_info,true));
		$social_type = $sns_task_info['social_type'];

		//get token from user_info 2016-10-12 sylee
		$user_id = $db->queryOne("SELECT TASK_USER_ID FROM BC_TASK WHERE TASK_ID=".$task_id);
		$tokeninfo = $db->queryRow("
		SELECT MO.*
		FROM BC_MEMBER M
		JOIN BC_MEMBER_OPTION MO
		ON( M.MEMBER_ID=MO.MEMBER_ID )
		WHERE M.USER_ID='$user_id'");
		//	facebook_token character varying(1000), -- facebook_token ...
		//  twitter_token character varying(1000), -- twitter_token...
		//  youtube_token character varying, -- youtube_token...
		//  facebook_token_expire character varying(14), -- facebook_token_expire date
		switch($social_type)
		{
			case 'FACEBOOK':
				$url = $arr_sys_code['interwork_sns']['ref1'];
				$function_nm = 'putVideoPublish';
				
				$parse_url = parse_url($url);
				$page = $parse_url['path'].$function_nm;
				$code_info = $db->queryRow("SELECT * FROM BC_CODE WHERE CODE='".$social_type."'");
				$path = $sns_task_info['path'].'/'.$sns_task_info['source'];
				$name = basename($path);
				//$msg = $sns_task_info['content'];
				$msg = _text('MN00249')." : ".$sns_task_info['title']."\r\n".$sns_task_info['content'];
				$string = $code_info['ref3']."&task_id=".$task_id."&path=".$path."&name=".$name."&message=".$msg;
				//$this->_log("Post_XML_Soket: ".$parse_url['host'].", ".$page.", ".$string.", ".$parse_url['port']);
				//$return = Post_XML_Soket($parse_url['host'], $page, $string, $parse_url['port']);

				$access_token_arr = explode("=", $code_info['ref3']);
				$access_token = $access_token_arr[1];
				if( !empty($tokeninfo['facebook_token']) ){
					$access_token = $tokeninfo['facebook_token'];
				}
				$params = array(
					'access_token' => $access_token,
					'task_id' => $task_id,
					'path' => $path,
					'name' => $name,
					'message' => $msg
				);
				$url = $url.$function_nm;

				$this->_log('url:'.$url);
				$this->_log('params:'.print_r($params,true));

				$return = @request_async($url, $params);
				$this->_log("Post_XML_Soket Return: ".$return);
			break;
			case 'TWITTER':
				$url = $arr_sys_code['interwork_sns']['ref2'];
				$function_nm = 'putUpdateStatus';

				$parse_url = parse_url($url);
				$page = $parse_url['path'].$function_nm;
				$code_info = $db->queryRow("SELECT * FROM BC_CODE WHERE CODE='".$social_type."'");
				//$msg = $sns_task_info['content'];
				$msg = _text('MN00249')." : ".$sns_task_info['title']."\r\n".$sns_task_info['content'];
				$string = "task_id=".$task_id."&message=".$msg;
				$this->_log("Post_XML_Soket: ".$parse_url['host'].", ".$page.", ".$string.", ".$parse_url['port']);
				//$return = Post_XML_Soket($parse_url['host'], $page, $string, $parse_url['port']);


				if( !empty($tokeninfo['twitter_token']) ){
					$access_token = $tokeninfo['twitter_token'];
				}

				$params = array(
					'access_token' => $access_token,
					'task_id' => $task_id,
					'message' => $msg
				);
				$url = $url.$function_nm;
				$this->_log('url:'.$url);
				$this->_log('params:'.print_r($params,true));

				$return = @request_async($url, $params);
				$this->_log("Post_XML_Soket Return: ".$return);
			break;
			case 'YOUTUBE':
				$url = $arr_sys_code['interwork_sns']['ref3'];
				$function_nm = 'uploadVideo';

				$parse_url = parse_url($url);
				$page = $parse_url['path'].$function_nm;
				$code_info = $db->queryRow("SELECT * FROM BC_CODE WHERE CODE='".$social_type."'");
				$path = $sns_task_info['path'].'/'.$sns_task_info['source'];
				$name = basename($path);
				$title = $sns_task_info['title'];
				$msg = $sns_task_info['content'];
				$string = "task_id=".$task_id."&path=".$path."&title=".$title."&description=".$msg;
				$this->_log("Post_XML_Soket: ".$parse_url['host'].", ".$page.", ".$string.", ".$parse_url['port']);
				//$return = Post_XML_Soket($parse_url['host'], $page, $string, $parse_url['port']);

				if( !empty($tokeninfo['youtube_token']) ){
					$access_token = $tokeninfo['youtube_token'];
				}

				$params = array(
					'access_token' => $access_token,
					'task_id' => $task_id,
					'path' => $path,
					'title' => $title,
					'description' => $msg
				);
				$url = $url.$function_nm;
				$this->_log('url:'.$url);
				$this->_log('params:'.print_r($params,true));

				$return = @request_async($url, $params);
				$this->_log("Post_XML_Soket Return: ".$return);
			break;
		}
	}

	function delete($task_id) {
		global $db, $arr_sys_code;

		$sns_info = $db->queryRow("SELECT * FROM BC_SOCIAL_TRANSFER WHERE TASK_ID=".$task_id);
		$sns_id = $sns_info['sns_id'];
		$social_type = $sns_info['social_type'];
		$code_info = $db->queryRow("SELECT * FROM BC_CODE WHERE CODE='".$social_type."'");

		switch($social_type)
		{
			case 'FACEBOOK':
				$url = $arr_sys_code['interwork_sns']['ref1'];
				$function_nm = 'putDeleteObject';
				$url = $url.$function_nm;

				$access_token_arr = explode("=", $code_info['ref3']);
				if( !empty($tokeninfo['facebook_token']) ){
					$access_token = $tokeninfo['facebook_token'];
				}
				$params = array(
					'access_token' => $access_token,
					'id' => $sns_id,
					'task_id' => $task_id
				);
				$this->_log("Post_XML_Soket Request: ".$url."\r\n".print_r($params,true));
				$return = @request_async($url, $params);
				$this->_log("Post_XML_Soket Return: ".$return);
			break;
			case 'TWITTER':
				$url = $arr_sys_code['interwork_sns']['ref2'];
				$function_nm = 'putDestroyStatus';
				$url = $url.$function_nm;
				if( !empty($tokeninfo['twitter_token']) ){
					$access_token = $tokeninfo['twitter_token'];
				}

				$params = array(
					'access_token' => $access_token,
					'id' => $sns_id,
					'task_id' => $task_id
				);
				$this->_log("Post_XML_Soket Request: ".$url."\r\n".print_r($params,true));
				$return = @request_async($url, $params);
				$this->_log("Post_XML_Soket Return: ".$return);
			break;
			case 'YOUTUBE':
				//not yet
			break;
		}
	}	
}
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//$xml ="<Request>
//<TaskID>343779</TaskID>
//<TypeCode>60</TypeCode>
//<Progress>100</Progress>
//<Status>complete</Status>
//<Log>10.10.10.141 : Transfer FS 완료</Log>
//</Request>";
try
{

	$action = $_POST['action'];
    $task_id_list = json_decode($_POST['task_id_list'], true);

	if( empty($action) || empty($task_id_list) ) throw new Exception('not found param');

    foreach($task_id_list as $task_id) {
        $task_info  = $db->queryRow("select t.task_id , t.assign_ip , td.job_name , t.type from bc_task t,BC_TASK_RULE td where t.TASK_RULE_id=td.TASK_RULE_id and t.task_id=$task_id ");
        
        if(empty($task_info))  throw new Exception('empty task info');

        $assign_ip = $task_info['assign_ip'];
        $job_name = $task_info['job_name'];
        $request = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>'.chr(10).'<Request />');
        $request->addChild("TaskID", $task_id );
        $request->addChild("TypeCode", $task_info['type'] );
        $request->addChild("Progress", "100" );
        $request->addChild("Status", $action );
        $request->addChild("Log", $assign_ip." : ".$job_name." complete");

        $xml =  $request->asXML();

        $result = post_request('http://'.$_SERVER['HTTP_HOST'].'/workflow/update_task_status.php', $xml);

        // if ($result['status'] == 'ok')
        // {
        //     $rexml = $result['content'];

        //     $rexml = reConvertSpecialChar(reConvertSpecialChar($rexml));
        //     libxml_use_internal_errors(true);
        //     $rexml = simplexml_load_string($rexml);
        //     if (!$rexml)
        //     {
        //         foreach(libxml_get_errors() as $error)
        //         {
        //             $err_msg .= $error->message . "\t";
        //         }

        //         throw new Exception('xml error: '.$err_msg);
        //     }

        //     if($rexml->Result != 'success' ) 
        //     {
        //         throw new Exception( $rexml->Result );
        //     }
        // }
        // else
        // {
        //     throw new Exception('A error occured: ' . $result['error']);
        // }
    }

    die(json_encode(array(
        'success' => true
    )));

}
catch (Exception $e)
{

	switch($e->getCode())
	{
		case ERROR_QUERY:
			$msg = $e->getMessage().'( '.$db->last_query . ' )';
		break;

		default:
			$msg = $e->getMessage();
		break;
	}

	die(json_encode(array(
		'success' => false,
		'msg' => $msg
	)));
}



function post_request($url, $data, $referer='') {

    // Convert the data array into URL Parameters like a=b&foo=bar etc.
  //  $data = http_build_query($data);

    // parse the given URL
    //@file_put_contents(LOG_PATH.'/task_complete_order_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').']'.$url."\n", FILE_APPEND);
    // @file_put_contents(LOG_PATH.'/task_complete_order_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').']'.$data."\n", FILE_APPEND);
    $url = parse_url($url);

    if ($url['scheme'] != 'http') {
        die('Error: Only HTTP request are supported !');
    }

    // extract host and path:
    $host = $url['host'];
    $port = empty($url['port']) ? 80 : $url['port'];
    $path = $url['path'];

    // open a socket connection on port 80 - timeout: 30 sec
    $fp = fsockopen($host, $port, $errno, $errstr, 30);

    if ($fp){
        // send the request headers:
        fputs($fp, "POST $path HTTP/1.1\r\n");
        fputs($fp, "Host: $host\r\n");

        if ($referer != '')
            fputs($fp, "Referer: $referer\r\n");

        fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
        fputs($fp, "Content-length: ". strlen($data) ."\r\n");
        fputs($fp, "Connection: close\r\n\r\n");
        fputs($fp, $data);

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }
		@file_put_contents(LOG_PATH.'/task_complete_order_'.date('Ymd').'.log', '['.date('Y-m-d H:i:s').']'.$result."\n", FILE_APPEND);

    }
    else {
        return array(
            'status' => 'err',
            'error' => "$errstr ($errno)"
        );
    }

    // close the socket connection:
    fclose($fp);

    // split the result header from the content

	$result = explode("\r\n\r\n", $result, 2);

    $header = isset($result[0]) ? $result[0] : '';
    $content = isset($result[1]) ? $result[1] : '';

    // return as structured array:
    return array(
        'status' => 'ok',
        'header' => $header,
        'content' => $content
    );
}

?>
<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-16
 * Time: 오전 12:54
 */

use Monolog\Handler\RotatingFileHandler;

$server->register('SetQueuedIngestScheduleItem',
    array(
        'request' => 'xsd:string'
    ),
    array(
        'response' => 'xsd:string'
    ),
    $namespace,
    $namespace.'#SetQueuedIngestScheduleItem',
    'rpc',
    'encoded',
    'SetQueuedIngestScheduleItem'
);

function SetQueuedIngestScheduleItem($json_str) {
    global $server, $logger;

    $logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/func_' . __FUNCTION__ . '.log', 14));
    $logger->addInfo($json_str);
    $definition = array(
        'id' => array('type' => 'string', 'required' => true),
        'filename' => array('type' => 'string', 'required' => true)
    );

    try {

		// 데이터 검증
        $params = validator($definition, $json_str);

        
        $response = Requests::post('http://127.0.0.1/api/v1/ingest/schedule/set-queued', array(), $params);
       // $response = Requests::put('http://127.0.0.1/interface/app/ArielV2/schedule.php/set/queued/'.$params['id'].'/'.$params['filename']);
        $result = json_decode($response->body);

        if ( ! $result) throw new Exception($response->body);

    } catch (Exception $e) {
        $result = array(
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 1
        );
    }

    $result = json_encode($result);

    $logger->addInfo($result);

    return $result;
}
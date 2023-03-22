<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-15
 * Time: 오후 4:06
 */

use Monolog\Handler\RotatingFileHandler;

$server->register('SoapGetTaskId',
    array(
        'type' => 'xsd:string',
    ),
    array(
        'code' => 'xsd:string',
        'msg' => 'xsd:string'
    ),
    $namespace,
    $namespace.'#SoapGetTaskId',
    'rpc',
    'encoded',
    'SoapGetTaskId'
);

function SoapGetTaskId($type) {
    global $server, $logger;

    // $logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/func_' . __FUNCTION__ . '.log', 14));
    
    return array(
        'code' => '0', 
        'msg' => '10001'
    );
}
<?php

$server->register('UpdateMonitorResource',
    array(
        'server_name' => 'xsd:string',
        'server_ip' => 'xsd:string',
        'use_cpu' => 'xsd:string',
        'use_memory' => 'xsd:string'
    ),
    array(
		'response'	=> 'xsd:string'
	),
    $namespace,
    $namespace.'#UpdateMonitorResource',
    'rpc',
    'encoded',
    'UpdateMonitorResource'
);


function UpdateMonitorResource($server_name, $server_ip, $use_cpu, $use_memory) {
    global $db;

    try {
        $db->exec("DELETE FROM MONITORING_SERVER WHERE  IP='$server_ip'");
        $id = $db->queryOne("SELECT COALESCE(MAX(ID), 0) + 1 FROM MONITORING_SERVER");
        $db->insert("MONITORING_SERVER", array(
            'ID' => $id,
            'NAME' => $server_name,
            'IP' => $server_ip,
            'USED_CPU' => $use_cpu,
            'USED_MEMORY' => $use_memory
        ));

		return 'true';
    } catch (Exception $e) {
		return 'false';
    }
}

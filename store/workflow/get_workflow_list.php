<?php
/**
 * 김형기
 * xdcam_transfer와 같은 프리셋 채널명으로 구체화된 워크플로우(인제스트, 아웃제스트) 목록을 조회한다.
 */
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

use \Proxima\core\Request;
use \Proxima\core\Response;
use \Proxima\models\system\Workflow;

$presetChannel = Request::post('workflow_preset_channel');

$workflows = Workflow::findWorkflowsByPresetChannel($presetChannel);

$data = [];
foreach($workflows as $workflow) {
    $data[] = [
        'channel' => $workflow->get('register'),
        'name' => $workflow->get('user_task_name')
    ];
}

Response::echoJsonOk($data);

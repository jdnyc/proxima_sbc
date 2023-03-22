<?php
set_time_limit(0);
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/ATS.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');

use \Proxima\models\loudness\AudioToolsServer;

/* 반복적으로 돌면서 작업상태 체크 및 진행률 등을 업데이트 함
 현재는 무한반복문 형태로 개발 추후 보완예정- 2018.04.16 Alex */
while(true) {
    /* 사용여부에 따라 무한루프를 종료시킬 수 있도록 추가 - 2018.04.25 Alex */
    $useWebAgent = $db->queryOne("
        SELECT  USE_YN
        FROM    BC_CODE
        WHERE   CODE_TYPE_ID = (SELECT ID FROM BC_CODE_TYPE WHERE CODE = 'USE_WEB_AGENT')
        AND     CODE = 'USEYN'
    ");

    if($useWebAgent == 'N') {
        break;
    }
    /* 진행중(assign / processing)인 작업이 있는지 확인 후 있으면 상태값 업데이트 */
    $ats = new AudioToolsServer($param);
    
    $processingTasks = $db->queryAll("
                            SELECT  *
                            FROM    BC_TASK
                            WHERE   TYPE = '55'
                            AND     STATUS IN ('assigning', 'processing')
                            ORDER BY TASK_ID ASC
                        ");

    if(!empty($processingTasks)) {
        foreach($processingTasks as $task) {
            $task_id = $task['task_id'];
            $contentId = $task['src_content_id'];
            $loudnessMapInfo = $db->queryRow("
                            SELECT  *
                            FROM    TB_LOUDNESS_MAP
                            WHERE   TASK_ID = ".$task_id
                        );

            $workflowUID = $loudnessMapInfo['workflowuid'];
            $agentServerIp = '10.26.100.206:9090';

            $processingParam = array(
                'task_id' => $task_id,
                'workflowUID' => $workflowUID,
                'serverIp' => $agentServerIp
            );

            $ats = new AudioToolsServer($processingParam);

            $status = $ats->getWorkflowProgres($processingParam);

            if($status == 'complete') {
                /* 성공일 경우 결과값 조회후 DB 업데이트 */
                $completeParam = array(
                    'task_id' => $task_id,
                    'workflowUID' => $workflowUID,
                    'content_id' => $contentId
                );

                $ats->getWorkflowProcessingResult($completeParam);
            }
        }
    }

    /* 대기중인 작업 확인 후 대기중인 작업이 있을 경우 서버별 리미트 확인 후 신규 작업 추가 */
    $queuedTasks = $db->queryAll("
                        SELECT  T.*,
                                (SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = T.SRC_STORAGE_ID) AS SRC_PATH,
                                (SELECT PATH FROM BC_STORAGE WHERE STORAGE_ID = T.TRG_STORAGE_ID) AS TRG_PATH
                        FROM    BC_TASK T
                        WHERE   STATUS = 'queue'
                        AND     TYPE = '55'
                        ORDER BY PRIORITY ASC, TASK_ID ASC
                    ");
    if(!empty($queuedTasks)) {
        foreach($queuedTasks as $queueTask) {
            $targetAgent = checkLoudnessJob();
            if($targetAgent != 'skip') {
                $db->exec("
                    UPDATE  BC_TASK
                    SET     STATUS = 'assigning'
                    WHERE   TASK_ID = ".$queueTask['task_id']
                );

                $source = str_replace('/', '\\', ($queueTask['src_path'].'/'.$queueTask['source']));
                $output = str_replace('/', '\\', $queueTask['trg_path']);
                $queuedParam = array(
                    'source' => $source,
                    'output' => $output,
                    'serverIp' => $targetAgent.':9090',
                    'task_id' => $queueTask['task_id']
                );
                $ats = new AudioToolsServer($queuedParam);
                @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/alex_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] queuedParam ===> '.print_r($queuedParam, true)."\r\n", FILE_APPEND);
                $ats->submitWorkflow($queuedParam);
            }
        }
    }

    sleep(20);
    @file_put_contents($_SERVER['DOCUMENT_ROOT'].'/log/alex_test_'.date('Ymd').'.log', $_SERVER['REMOTE_ADDR']."\t[".date('Y-m-d H:i:s').'] while문 실행중'."\r\n", FILE_APPEND);
 }
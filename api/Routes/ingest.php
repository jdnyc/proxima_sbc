<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Proxima\core\Session;

return function (App $app) {
    /**
     * 인제스트 스케줄 조회
     */
    $app->post('/ingest/schedule', \Api\Controllers\IngestController::class . ':getSchedule');

    /**
     * 인제스트 스케줄 작업 시작
     */
    $app->post('/ingest/schedule/set-queued', \Api\Controllers\IngestController::class . ':setQueued');  
};

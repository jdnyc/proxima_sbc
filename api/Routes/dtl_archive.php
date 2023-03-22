<?php

use Slim\App;

return function (App $app) {
    // 작업 이벤트 조회
    $app->get('/dtl-archive/{task_id}/events', \Api\Controllers\DtlArchiveController::class . ':getEvents')
    ->add(\Api\Middleware\AuthMiddleware::class);
};

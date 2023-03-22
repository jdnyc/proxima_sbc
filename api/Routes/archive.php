<?php

use Slim\App;

return function (App $app) {
    // 아카이브 단건 조회
    $app->get('/archive/{content_id}', \Api\Controllers\TbRequestController::class . ':archiveRequestFileDeleteCheck')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 아카이브 삭제 요청
    $app->post('/archive/request-delete', \Api\Controllers\TbRequestController::class . ':requestDelete')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 리스토어 요청
    $app->post('/archive/request-restore', \Api\Controllers\TbRequestController::class . ':requestRestore')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 아카이브 상태 변경
    $app->put('/archive/update-status/{req_no}', \Api\Controllers\TbRequestController::class . ':updateStatus')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 아카이브 상태 변경
    $app->delete('/archive/delete-request/{req_no}', \Api\Controllers\TbRequestController::class . ':deleteRequest')
    ->add(\Api\Middleware\AuthMiddleware::class);

};

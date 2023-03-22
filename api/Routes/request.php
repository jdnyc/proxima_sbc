<?php

use Slim\App;

return  function (App $app) {
    // 의뢰
    //의뢰 리스트
    $app->get('/request', \Api\Controllers\RequestController::class . ':getRequestList')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //의뢰 단건 조회
    $app->get('/request/{ord_id}', \Api\Controllers\RequestController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //의뢰 요청
    $app->post('/request', \Api\Controllers\RequestController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 진행상태 변경
    $app->post('/request/{ord_id}/update-status', \Api\Controllers\RequestController::class . ':statusUpdate')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 진행상태 변경(취소)
    $app->post('/request/{ord_id}/update-status-cancel', \Api\Controllers\RequestController::class . ':updateStatusCancel')
    ->add(\Api\Middleware\AuthMiddleware::class);
    //의뢰 삭제
    $app->post('/request/{ord_id}/delete', \Api\Controllers\RequestController::class . ':delete')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 담당자 변경
    $app->post('/request/{ord_id}/update-charger', \Api\Controllers\RequestController::class . ':updateCharger')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 수정
    $app->post('/request/{ord_id}', \Api\Controllers\RequestController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 첨부파일 추가
    $app->post('/request-attach', \Api\Controllers\RequestController::class . ':attach')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 첨부파일 단건 조회
    $app->get('/attach/{ord_id}', \Api\Controllers\RequestController::class . ':showAttach')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 첨부파일 단건 조회
    $app->delete('/attach/{id}', \Api\Controllers\RequestController::class . ':deleteAttach')
    ->add(\Api\Middleware\AuthMiddleware::class);
};

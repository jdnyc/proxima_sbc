<?php

use Slim\App;

return function (App $app) {
    // 심의
    //심의 리스트
    $app->get('/dash-board-reviews', \Api\Controllers\ReviewController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 심의 생성
    $app->post('/dash-board-reviews', \Api\Controllers\ReviewController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 진행상태 변경
    $app->post('/dash-board-reviews/{id}/update-status', \Api\Controllers\ReviewController::class . ':statusUpdate')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 담당자 변경
    $app->post('/dash-board-reviews/{id}/update-charger', \Api\Controllers\ReviewController::class . ':updateCharger')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 의뢰 수정
    $app->post('/dash-board-reviews/{id}/update-rejectCn', \Api\Controllers\ReviewController::class . ':updateRejectCn')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 리스토어 권한 승계
    // 리스토어 권한 승계 수임자 리스트
    $app->get('/authority-mandate', \Api\Controllers\AuthorityMandateController::class . ':getMandataryListByMandator')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 리스토어 권한 승계 수임자 등록
    $app->post('/authority-mandate', \Api\Controllers\AuthorityMandateController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 리스토어 권한 승계 수정
    $app->post('/authority-mandate/{authority_mandate_id}', \Api\Controllers\AuthorityMandateController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 리스토어 권한 승계 삭제
    $app->delete('/authority-mandate/{authority_mandate_id}', \Api\Controllers\AuthorityMandateController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);
};

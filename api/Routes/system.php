<?php

use Slim\App;

return function (App $app) {

    // 메뉴 권한 조회
    $app->post('/permission/search-by-path', \Api\Controllers\PermissionController::class . ':searchByPath')
    ->add(\Api\Middleware\AuthMiddleware::class);

     // 사용자 콘텐츠 권한 조회
     $app->post('/permission/content-grant', \Api\Controllers\PermissionController::class . ':contentGrant')
     ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더관리 조회
    $app->get('/folder-mngs', \Api\Controllers\FolderMngController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 폴더관리 생성
    $app->post('/folder-mngs', \Api\Controllers\FolderMngController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 폴더관리 수정
    $app->put('/folder-mngs/{id}', \Api\Controllers\FolderMngController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 폴더관리 삭제
    $app->delete('/folder-mngs/{id}', \Api\Controllers\FolderMngController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더관리 사용자 조회
    $app->get('/folder-mngs/{id}/users', \Api\Controllers\FolderMngController::class . ':findByWithUser')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더관리 사용자 매핑
    $app->put('/folder-mngs/{id}/users', \Api\Controllers\FolderMngController::class . ':saveUser')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->get('/folder-mngs-sync', \Api\Controllers\FolderMngController::class . ':sync')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더 신청 관리
    $app->get('/folder-mng-requests', \Api\Controllers\FolderMngRequestController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더 신청 관리 신청
    $app->post('/folder-mng-requests', \Api\Controllers\FolderMngRequestController::class . ':create')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더 신청 관리 수정
    $app->put('/folder-mng-requests/{id}', \Api\Controllers\FolderMngRequestController::class . ':update')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더 신청 관리 상태변경
    $app->put('/folder-mng-requests/{id}/update-status', \Api\Controllers\FolderMngRequestController::class . ':updateStatus')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 폴더 신청 관리 삭제
    $app->delete('/folder-mng-requests/{id}', \Api\Controllers\FolderMngRequestController::class . ':delete')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 사용자 조회
    $app->get('/users', \Api\Controllers\UserController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
};

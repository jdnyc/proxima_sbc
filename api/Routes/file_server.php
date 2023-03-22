<?php

use Slim\App;

return function (App $app) {
    
    /**
     * 작업 목록 조회
     */
    $app->get('/file-server/jobs', \Api\Controllers\FileServerController::class . ':indexJob')
        ->add(\Api\Middleware\AuthMiddleware::class);   

    /**
     * 작업 목록 조회 포털용
     */
    $app->get('/external-file-server/jobs', \Api\Controllers\FileServerController::class . ':indexJob')
 //   ->add(\Api\Middleware\ApiLogMiddleware::class)
    ->add(\Api\Middleware\AuthMiddleware::class);   

    /**
     * 작업 생성
     */
    $app->post('/file-server/jobs', \Api\Controllers\FileServerController::class . ':storeJob')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 일괄 작업 생성
     */
    $app->post('/file-server/jobs-many', \Api\Controllers\FileServerController::class . ':storeManyJob')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 일괄 작업 상세 조회
     */
    $app->get('/file-server/jobs-many/{job_ids}', \Api\Controllers\FileServerController::class . ':showManyJob')
        ->add(\Api\Middleware\AuthMiddleware::class);  

    /**
     * 작업 상세 조회
     */
    $app->get('/file-server/jobs/{job_id}', \Api\Controllers\FileServerController::class . ':showJob')
        ->add(\Api\Middleware\AuthMiddleware::class);  

    /**
     * 작업 할당
     */
    $app->post('/file-server/jobs/{job_id}/assign', \Api\Controllers\FileServerController::class . ':assignJob')
        ->add(\Api\Middleware\AuthMiddleware::class);  

    /**
     * 작업 진행 상태 업데이트
     */
    $app->post('/file-server/jobs/{job_id}/update-status', \Api\Controllers\FileServerController::class . ':updateJobStatus')
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 작업 업데이트
     */
    $app->put('/file-server/jobs/{job_id}', \Api\Controllers\FileServerController::class . ':updateJob')
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 작업 업데이트
     */
    $app->put('/file-server/jobs/{job_id}/priority', \Api\Controllers\FileServerController::class . ':updateJobPriority')
    ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 작업 삭제
     */
    $app->delete('/file-server/jobs/{job_id}', \Api\Controllers\FileServerController::class . ':destroyJob')
        ->add(\Api\Middleware\AuthMiddleware::class); 


    /**
     * 파일서버 목록 조회
     */
    $app->get('/file-server/file-servers', \Api\Controllers\FileServerController::class . ':indexFileServer')
        ->add(\Api\Middleware\AuthMiddleware::class);   

    /**
     * 파일서버 생성
     */
    $app->post('/file-server/file-servers', \Api\Controllers\FileServerController::class . ':storeFileServer')
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 파일서버 상세 조회
     */
    $app->get('/file-server/file-servers/{file_server_id}', \Api\Controllers\FileServerController::class . ':showFileServer')
        ->add(\Api\Middleware\AuthMiddleware::class);  

    /**
     * 파일서버 업데이트
     */
    $app->put('/file-server/file-servers/{file_server_id}', \Api\Controllers\FileServerController::class . ':updateFileServer')
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 파일서버 삭제
     */
    $app->delete('/file-server/file-servers/{file_server_id}', \Api\Controllers\FileServerController::class . ':destroyFileServer')
        ->add(\Api\Middleware\AuthMiddleware::class); 
    
        
};

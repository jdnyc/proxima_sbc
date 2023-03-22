<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $app->get('/tape', \Api\Controllers\TapeController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    $app->get('/tape/search', \Api\Controllers\TapeController::class . ':search')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //수동 목록 추가
    $app->post('/tape/create', \Api\Controllers\TapeController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //수동 목록 수정
    $app->post('/tape/update', \Api\Controllers\TapeController::class . ':update')
    ->add(\Api\Middleware\AuthMiddleware::class);
    
    //수동 목록 삭제
    $app->post('/tape/delete', \Api\Controllers\TapeController::class . ':delete')
    ->add(\Api\Middleware\AuthMiddleware::class);

    $app->get('/tape/{barcode}/medias', \Api\Controllers\TapeController::class . ':getMedias')
        ->add(\Api\Middleware\AuthMiddleware::class);

    //테잎 목록 싱크
    $app->get('/tape/sync', \Api\Controllers\TapeController::class . ':sync');

    //테잎 별 오브젝트 목록 싱크
    $app->get('/tape/sync-media', \Api\Controllers\TapeController::class . ':syncMedia');
};

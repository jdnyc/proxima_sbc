<?php

use Slim\App;

return function (App $app) {
    //다운로드 목록
    $app->get('/downloads', \Api\Controllers\DownloadController::class . ':index');
    //다운로드 생성
    $app->post('/downloads', \Api\Controllers\DownloadController::class . ':store');
    //다운로드 상세조회
    $app->get('/downloads/{download_id}', \Api\Controllers\DownloadController::class . ':show');
    //다운로드 업데이트
    $app->put('/downloads/{download_id}', \Api\Controllers\DownloadController::class . ':update');
    //다운로드 삭제
    $app->delete('/downloads/{download_id}', \Api\Controllers\DownloadController::class . ':destroy');
};

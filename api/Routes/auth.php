<?php

use Slim\App;

return function (App $app) {
    //비밀번호 변경
    $app->post('/auth/login', \Api\Controllers\AuthController::class . ':login');
    //비밀번호 변경
    $app->post('/auth/logout', \Api\Controllers\AuthController::class . ':logout')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //로그인 인증번호 재전송
    $app->post('/auth/number-re-send', \Api\Controllers\AuthController::class . ':authNumberReSend');
};

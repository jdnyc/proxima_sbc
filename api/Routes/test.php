<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Proxima\core\Session;

Session::init();
return function (App $app) {
    $app->post('/push-test', \Api\Controllers\PushTestController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    $app->put('/push-test/{id}', \Api\Controllers\PushTestController::class . ':index')
    ->add(\Api\Middleware\AuthMiddleware::class);
    $app->delete('/push-test/{id}', \Api\Controllers\PushTestController::class . ':index')
    ->add(\Api\Middleware\AuthMiddleware::class);
};

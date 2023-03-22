<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Proxima\core\Session;

return function (App $app) {
    $app->get('/migration/{table}', \Api\Controllers\MigrationController::class . ':index');
    $app->get('/migration/count/{table}', \Api\Controllers\MigrationController::class . ':count');
    // $app->put('/push-test/{id}', \Api\Controllers\MigrationController::class . ':index')
    // ->add(\Api\Middleware\AuthMiddleware::class);
    // $app->delete('/push-test/{id}', \Api\Controllers\MigrationController::class . ':index')
    // ->add(\Api\Middleware\AuthMiddleware::class);
};

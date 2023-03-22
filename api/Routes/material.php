<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Api\Controllers\MaterialController;

return function (App $app) {
    $app->get('/materials', \Api\Controllers\MaterialController::class . ':index');
    $app->post('/materials-scenes', \Api\Controllers\MaterialController::class . ':scenes');
};

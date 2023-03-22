<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Proxima\core\Session;

Session::init();
return function (App $app) {
    $app->get('/', function (Request $request, Response $response, array $args) {
        echo 'Proxima API';        
    });

    $app->get('/phpinfo', function(Request $request, Response $response, array $args) {
        phpinfo();
    });
};

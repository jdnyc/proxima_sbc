<?php

$ROOT_DIR = __DIR__;
$API_DIR = $ROOT_DIR . '/../api';

require $ROOT_DIR  . '/../vendor/autoload.php';


// Settings
$settings = require $API_DIR . '/settings.php';

// Pre Error Handler
$settings['errorHandler'] = function ($c) {
    return new \Api\Exceptions\ErrorHandler($c['settings']['displayErrorDetails']);
};

$app = \Api\Application::create($settings);

// Set up dependencies
$dependencies = require $API_DIR . '/dependencies.php';
$dependencies($app);

// set session driver
$sessionConfig = $settings['settings']['session'];
$appConfig = $settings['settings']['app'];
$connection = $app->getContainer()->get('db');

if($appConfig['env'] === 'production' && $sessionConfig['driver'] === 'sso') {
    $handler = new \Api\Core\Session\SSOSessionHandler($connection, 'sessions');        
    session_set_save_handler($handler, true);
}

// Register middleware
$middleware = require $API_DIR . '/middleware.php';
$middleware($app);

date_default_timezone_set(config('timezone'));
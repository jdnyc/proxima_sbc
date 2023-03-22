<?php

if (PHP_SAPI == 'cli-server') {
    // To help the built-in PHP dev server, check if the request was actually for
    // something which should probably be served as a static file
    $url  = parse_url($_SERVER['REQUEST_URI']);
    $file = __DIR__ . $url['path'];
    if (is_file($file)) {
        return false;
    }
}

require dirname(__DIR__) . '/vendor/autoload.php';

// Settings
$settings = require __DIR__ . '/settings.php';

// Pre Error Handler
$settings['errorHandler'] = function ($c) {
    return new \Api\Exceptions\ErrorHandler($c['settings']['displayErrorDetails']);
};

$app = \Api\Application::create($settings);

// Set up dependencies
$dependencies = require __DIR__ . '/dependencies.php';
$dependencies($app);

// set session driver
$sessionConfig = $settings['settings']['session'];
$appConfig = $settings['settings']['app'];

if ($appConfig['env'] === 'production' && $sessionConfig['driver'] === 'sso') {
    $connection = $app->getContainer()->get('db');
    $handler = new \Api\Core\Session\SSOSessionHandler($connection, 'sessions');
    session_set_save_handler($handler, true);
}

// 언어
require dirname(__DIR__) . '/lib/lang.php';

// 타임코드 헬퍼
require dirname(__DIR__) . '/lib/timecode.class.php';

// Register middleware
$middleware = require __DIR__ . '/middleware.php';
$middleware($app);

date_default_timezone_set(config('timezone'));

// Register routes
$app->group('/v1', function () use ($app) {
    $routes = scandir(__DIR__ . '/Routes');
    foreach ($routes as $route) {
        if ($route === '.' || $route === '..') {
            continue;
        }
        $route = require __DIR__ . '/Routes/' . $route;
        $route($app);
    }
});

// Run app
$app->run();

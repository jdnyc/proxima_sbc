<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    // Error Handler
    $container['errorHandler'] = function ($c) {
        return new \Api\Exceptions\ErrorHandler($c['settings']['displayErrorDetails']);
    };

    $container['phpErrorHandler'] = function ($c) {
        return new \Api\Exceptions\PhpErrorHandler($c['settings']['displayErrorDetails']);
    };

    // database
    $container['db'] = function ($c) {
        $settings = $c->get('settings');        
        return \Api\Support\Helpers\DatabaseHelper::getConnection($settings['database']);
    };    

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };

    // auth
    $container['auth'] = function ($c) {
        return new \Api\Auth\Auth;
    };

    // Request overriding
    $container['request'] = function ($c) {
        $request =  \Api\Http\ApiRequest::createFromEnvironment($c['environment']);
        return $request;
    };

    // Response overriding
    $container['response'] = function ($c) {
        return new \Api\Http\ApiResponse;
    };

    $container['sso'] = function ($c) {
        $settings = $c->get('settings')['sso'];
        $ssoClient = new \Api\Core\Session\lib\bandiSSO(
            $settings['url'],
            $settings['scope'],
            $settings['client_id'],
            $settings['client_secret'],
            ''
        );
        return $ssoClient;
    };
    $container['sso_admin'] = function ($c) {
        $settings = $c->get('settings')['sso'];
        $ssoClient = new \Api\Core\Session\lib\bandiSSO_admin(
            $settings['admin_url'],
            $settings['scope'],
            $settings['client_id'],
            $settings['client_secret'],
            ''
        );
        return $ssoClient;
    };

    $container['zodiac'] = function ($c) {       
        require_once(dirname(__DIR__) . '/lib/Zodiac.class.php'); 
        $zodiacClient = new \zodiac();
        return $zodiacClient;
    };
    
    //레거시 db
    $container['dbLegacy'] = function ($c) {
        global $db;
        if( !empty($db) ){
            return $db;
        }
        $dbType = 'oracle' ;
        require_once(dirname(__DIR__) . '/lib/DB.Class.php');
        $dbInfo = \Api\Support\Helpers\DatabaseHelper::getSettings();     
        $db = new CommonDatabase($dbType,$dbInfo['username'], $dbInfo['password'], $dbInfo['host'].':'.$dbInfo['port'].'/'.$dbInfo['database'] );
        $GLOBALS['db'] = &$db;
        return $db;
    };

    $container['searcher'] = function ($c) {
     
        $settings = $c->get('settings');  
        $url = $settings['searcher']['url'];
        $db = $c['dbLegacy'];
        $searcher = new \Proxima\core\Searcher($db, $url);
        return $searcher;
    };

    $container['metadata'] = function ($c) {       
        require_once(dirname(__DIR__) . '/lib/MetaData.class.php'); 
        $db = $c['dbLegacy'];
        $metadata = new \MetaDataClass();
        return $metadata;
    };
};

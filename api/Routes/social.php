<?php

use Slim\App;

return function (App $app) {
    
    /**
     * SNS 채널 목록 조회
     */
    $app->get('/social/channels', \Api\Controllers\ChannelController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);   
    
    /**
     * SNS 채널의 플랫폼 목록 조회
     */
    $app->get('/social/channels/{channel_id}/platforms', \Api\Controllers\ChannelController::class . ':getPlatforms')
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * SNS 플랫폼 목록 조회
     */
    $app->get('/social/platforms', \Api\Controllers\PlatformController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class); 

    /**
     * 유튜브 카테고리 조회
     */
    $app->get('/social/categories', \Api\Controllers\PlatformController::class . ':getCategories')
        ->add(\Api\Middleware\AuthMiddleware::class); 
        
};

<?php

use Slim\App;

return function (App $app) {

    // 주문관리 목록
    $app->get('/content-orders', \Api\Controllers\OrderController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 주문관리 신규 생성
    $app->post('/content-orders', \Api\Controllers\OrderController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //주문관리 수정
    $app->put('/content-orders/{order_num}', \Api\Controllers\OrderController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //진행상태 변경
    $app->post('/content-orders/{order_num}/update-status', \Api\Controllers\OrderController::class . ':statusUpdate')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 주문관리 삭제
    $app->delete('/content-orders/{order_num}', \Api\Controllers\OrderController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // // orderItem 목록
    // $app->get('/content-orderItems', \Api\Controllers\OrderItemController::class . ':index')
    //     ->add(\Api\Middleware\AuthMiddleware::class);
    // orderItems 조회
    $app->get('/content-orders/{order_num}/items', \Api\Controllers\OrderItemController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // orderItems 수정 & 생성
    $app->put('/content-orders/{order_num}/items', \Api\Controllers\OrderItemController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // // orderItem 삭제
    // $app->delete('/content-orders/{order_num}/items', \Api\Controllers\OrderItemController::class . ':delete')
    //     ->add(\Api\Middleware\AuthMiddleware::class);


    // orderSalesPrice 목록
    $app->get('/content-order-price', \Api\Controllers\OrderSalesPriceController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // orderSalesPrice 수정 & 생성
    $app->put('/content-order-price', \Api\Controllers\OrderSalesPriceController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // orderSalesPrice 삭제
    $app->delete('/content-order-price/{idx}', \Api\Controllers\OrderSalesPriceController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // method 별 목록조회
    $app->get('/content-order-price/{method}', \Api\Controllers\OrderSalesPriceController::class . ':getPriceFieldByMethod')
        ->add(\Api\Middleware\AuthMiddleware::class);
};

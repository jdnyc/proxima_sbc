<?php

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;

return function (App $app) {
    $app->get('/tasks', \Api\Controllers\TaskController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    $app->post('/tasks/workflow', \Api\Controllers\TaskController::class . ':createWorkflow')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->post('/tasks/to-do', \Api\Controllers\TaskController::class . ':toDo');

    $app->get('/tasks/{task_id}/status', \Api\Controllers\TaskController::class . ':getStatus')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->get('/tasks-by-fs-job/{fs_job_id}/status', \Api\Controllers\TaskController::class . ':getStatusByFsJob')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->get('/tasks/{task_id}/download-status', \Api\Controllers\TaskController::class . ':getDownloadStatus')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->post('/tasks/create-proxy-media', \Api\Controllers\TaskController::class . ':createProxyMedia')
        ->add(\Api\Middleware\AuthMiddleware::class);
        $app->post('/tasks/send-to-media', \Api\Controllers\TaskController::class . ':sendToMedia')
        ->add(\Api\Middleware\AuthMiddleware::class);
};

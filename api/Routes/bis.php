<?php

use Slim\App;

return function (App $app) {
    $app->get('/bis-programs', \Api\Controllers\BisProgramController::class . ':index')
    ->add(\Api\Middleware\AuthMiddleware::class);
    $app->get('/bis-programs/{pgm_id}', \Api\Controllers\BisProgramController::class . ':show')
    ->add(\Api\Middleware\AuthMiddleware::class);
    $app->post('/bis-programs/search', \Api\Controllers\BisProgramController::class . ':search')
    ->add(\Api\Middleware\AuthMiddleware::class);
    $app->get('/bis-episodes', \Api\Controllers\BisEpisodeController::class . ':index')
    ->add(\Api\Middleware\AuthMiddleware::class);
    $app->get('/bis-episodes/{pgm_id_epsd_no}', \Api\Controllers\BisEpisodeController::class . ':show')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // $app->post('/bis-episodes/search', \Api\Controllers\BisEpisodeController::class . ':search')
    // ->add(\Api\Middleware\AuthMiddleware::class);
    $app->get('/bis-programs/{pgm_id}/episodes', \Api\Controllers\BisEpisodeController::class . ':getEpisodesByPgmId')
    ->add(\Api\Middleware\AuthMiddleware::class);
};

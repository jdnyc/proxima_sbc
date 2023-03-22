<?php

use Slim\App;

return function (App $app) {
    
    /**
     * API 작업 업데이트
     */
    $app->put('/api-jobs/{api_job_id}', \Api\Controllers\ApiJobController::class . ':update');
};

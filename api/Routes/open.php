<?php

use Slim\App;

return function (App $app) {
    // 사용자 조회
    $app->get('/open/users', \Api\Controllers\UserController::class . ':index');
    $app->get('/open/user/admin', \Api\Controllers\UserController::class . ':showAdmin');

    // 사용자ID MASKING 조회
    $app->get('/open/users/masking', \Api\Controllers\UserController::class . ':getMaskingUserList');
    // 프로그램 조회
    // $app->get('/open/bis-programs', \Api\Controllers\BisProgramController::class . ':index');

    //사용자 아이디 중복 확인
    $app->get('/open/users/exists', \Api\Controllers\MemberRequestController::class . ':existsUser');

    // $app->get('/open/bis-programs/{pgm_id}/episodes', \Api\Controllers\BisEpisodeController::class . ':getEpisodesByPgmId');

    //사용자 신청
    $app->post('/open/users/request', \Api\Controllers\MemberRequestController::class . ':requestUser');



    $app->get('/open/bis-programs', \Api\Controllers\BisProgramController::class . ':index');
    $app->get('/open/bis-programs/{pgm_id}', \Api\Controllers\BisProgramController::class . ':show');
    $app->post('/open/bis-programs/search', \Api\Controllers\BisProgramController::class . ':search');
    $app->get('/open/bis-episodes', \Api\Controllers\BisEpisodeController::class . ':index');
    $app->get('/open/bis-episodes/{pgm_id_epsd_no}', \Api\Controllers\BisEpisodeController::class . ':show');
    $app->get('/open/bis-programs/{pgm_id}/episodes', \Api\Controllers\BisEpisodeController::class . ':getEpisodesByPgmId');

    $app->get('/open/folder-mngs', \Api\Controllers\FolderMngController::class . ':index');
    // 폴더 프로그램아이디로 조회
    $app->get('/open/folder-mngs/{pgm_id}', \Api\Controllers\FolderMngController::class . ':showByPgmId');    

    // 코드 아이템 조회(코드셋 아이디 또는 코드 셋 코드)
    $app->get('/open/data-dic-code-sets/{code_set_id}/code-items', \Api\Controllers\DataDicCodeItemController::class . ':getCodeItemsByCodeSetId');
        
};

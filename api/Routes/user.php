<?php

use Slim\App;

return function (App $app) {
    //비밀번호 변경
    $app->post('/users/me/change-password', \Api\Controllers\UserController::class . ':changeMyPassword')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //비밀번호 변경
    $app->post('/users/me/option', \Api\Controllers\UserController::class . ':updateMyOption')
        ->add(\Api\Middleware\AuthMiddleware::class);

        
    //생성
    $app->post('/users', \Api\Controllers\UserController::class . ':create')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //수정
    $app->put('/users/{user_id}', \Api\Controllers\UserController::class . ':update')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //post용 수정
    $app->post('/users/{user_id}/update', \Api\Controllers\UserController::class . ':update')
    ->add(\Api\Middleware\AuthMiddleware::class);
    
    //사용자 삭제
    $app->delete('/users/{user_id}', \Api\Controllers\UserController::class . ':delete')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //post용 삭제
    $app->post('/users/{user_id}/delete', \Api\Controllers\UserController::class . ':delete')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //사용자 인증 초기화
    $app->put('/users/{user_id}/init-auth', \Api\Controllers\UserController::class . ':initAuth')
    ->add(\Api\Middleware\AuthMiddleware::class);

    
    //사용자 동기화 계정별 
    $app->post('/users/sync/{user_id}', \Api\Controllers\UserController::class . ':UserSync')
        ->add(\Api\Middleware\AuthMiddleware::class);

    //사용자 동기화 sso
    $app->post('/users/sync', \Api\Controllers\UserController::class . ':UserSyncAll')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 사용자 조회
    $app->get('/search-users', \Api\Controllers\UserController::class . ':indexFromSearch')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);

      //외부 사용자 조회
      $app->get('/external-users', \Api\Controllers\UserController::class . ':indexFromExternal')
      ->add(\Api\Middleware\ApiLogMiddleware::class)
          ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 사용자 추가
    $app->post('/external-users', \Api\Controllers\UserController::class . ':createFromExternal')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);
    //외부 사용자 수정
    $app->post('/external-users/{user_id}', \Api\Controllers\UserController::class . ':updateFromExternal')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);
    //외부 사용자 삭제
    $app->post('/external-users/{user_id}/delete', \Api\Controllers\UserController::class . ':deleteFromExternal')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);
    //수정용 인증번호 발급
    $app->post('/external-users/certification-number/create', \Api\Controllers\UserController::class . ':createCertificationNumber')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);
        //수정용 인증번호 발급
        $app->post('/external-users/certification-number/check', \Api\Controllers\UserController::class . ':checkCertificationNumber')
        ->add(\Api\Middleware\ApiLogMiddleware::class)
            ->add(\Api\Middleware\AuthMiddleware::class);

    //사용자 신청 목록
    $app->get('/users/request-list', \Api\Controllers\MemberRequestController::class . ':requestUsersList')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //사용자 신청
    $app->post('/users/request', \Api\Controllers\MemberRequestController::class . ':requestUser')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //사용자 신청 수정
    $app->put('/users/request/{id}', \Api\Controllers\MemberRequestController::class . ':requestUserUpdate')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //사용자 아이디 중복 확인
    $app->get('/users/exists', \Api\Controllers\MemberRequestController::class . ':existsUser')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 상태변경
    $app->put('/users/change-status/{id}', \Api\Controllers\MemberRequestController::class . ':changeStatus')
        ->add(\Api\Middleware\AuthMiddleware::class);

    //유저 또는 관리자 체크
    $app->get('/users-admin-check', \Api\Controllers\UserController::class . ':usersOrAdminCheck')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 개인설정 개인정보 변경
    $app->post('/user/user-info', \Api\Controllers\UserController::class . ':updateUserInfo')
        ->add(\Api\Middleware\AuthMiddleware::class);
    
    // 사용자 접속이력
    $app->get('/user-login-history', \Api\Controllers\UserController::class . ':getUserLoginHistories')
        ->add(\Api\Middleware\AuthMiddleware::class);
};

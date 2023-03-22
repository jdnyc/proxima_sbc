<?php

use Slim\App;

return function (App $app) {

    //일자별 아카이브 통계
    $app->get('/statistics-archive-daily', \Api\Controllers\StatisticsController::class . ':dailyArchiveStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // //일자별 아카이브 통계->테스트
    // $app->get('/statistics-archive-daily-test', \Api\Controllers\StatisticsController::class . ':dailyArchiveStatisticsTEST')
    // ->add(\Api\Middleware\AuthMiddleware::class);

    //주간별 아카이브 통계
    $app->get('/statistics-archive-week', \Api\Controllers\StatisticsController::class . ':weekArchiveStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);
    //주간별 아카이브 통계 엑셀 다운로드
    $app->post('/statistics-archive-week/export-excel', \Api\Controllers\StatisticsController::class . ':exportToExcelWeekArchive')
    ->add(\Api\Middleware\AuthMiddleware::class);
    
    //운영 통계
    $app->get('/statistics-operation', \Api\Controllers\StatisticsController::class . ':operationStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 월 모니터용 CMS 통계
    $app->get('/statistics-monitor', \Api\Controllers\StatisticsController::class . ':monitorStatistics');

    // 다운로드 통계
    $app->get('/statistics-download', \Api\Controllers\StatisticsController::class . ':downloadStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 영상변환
    $app->get('/statistics-video-convert', \Api\Controllers\StatisticsController::class . ':videoConvertStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 통계
    $app->get('/statistics-content', \Api\Controllers\StatisticsController::class . ':contentStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 사용자 접속자 통계
    $app->get('/statistics-login-user', \Api\Controllers\StatisticsController::class . ':loginUserStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 제작폴더 신청 통계 조회
    $app->get('/statistics-folder-request', \Api\Controllers\StatisticsController::class . ':folderRequestStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 방송 심의 통계 조회
    $app->get('/statistics-review', \Api\Controllers\StatisticsController::class . ':reviewStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);
    
    // 운영 > 사용신청 승인
    $app->get('/statistics-user-approval', \Api\Controllers\StatisticsController::class . ':userApprovalStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 운영 > 의뢰
    $app->get('/statistics-request', \Api\Controllers\StatisticsController::class . ':requestStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 유형별
    $app->get('/statistics-content-type', \Api\Controllers\StatisticsController::class . ':contentTypeStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 등록 승인 통계 조회
    $app->get('/statistics-content-review', \Api\Controllers\StatisticsController::class . ':contentReviewStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 삭제 통계 조회
    $app->get('/statistics-content-deleted', \Api\Controllers\StatisticsController::class . ':contentOriginalArchiveDeletedStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 부서별
    $app->get('/statistics-content-department', \Api\Controllers\StatisticsController::class . ':contentDepartmentStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 프로그램별
    $app->get('/statistics-content-program', \Api\Controllers\StatisticsController::class . ':contentProgramStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 프로그램별 > 타입목록
    $app->get('/statistics-content-program/type', \Api\Controllers\StatisticsController::class . ':getProgramType')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 부제별
    $app->get('/statistics-content-episode', \Api\Controllers\StatisticsController::class . ':contentEpisodeStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 부제별 > 프로그램 목록 조회
    $app->get('/statistics-content-episode/programs', \Api\Controllers\StatisticsController::class . ':getPrograms')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 포맷별
    $app->get('/statistics-content-format', \Api\Controllers\StatisticsController::class . ':contentFormatStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 동적 포맷 목록 조회
    $app->get('/statistics-content-get-format', \Api\Controllers\StatisticsController::class . ':getContentFormat')
    ->add(\Api\Middleware\AuthMiddleware::class);
    
    // 리스토어 통계
    $app->get('/statistics-restore', \Api\Controllers\StatisticsController::class . ':restoreStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 입수 > 출처별
    $app->get('/statistics-content-source', \Api\Controllers\StatisticsController::class . ':contentSourceStatistics')
    ->add(\Api\Middleware\AuthMiddleware::class);
};

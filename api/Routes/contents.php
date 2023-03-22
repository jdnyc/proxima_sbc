<?php

use Slim\App;

return function (App $app) {

    //목록 조회
    $app->get('/contents', \Api\Controllers\ContentController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);

    //상세 조회
    $app->get('/contents/{content_id}', \Api\Controllers\ContentController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);

    //proxy url
    $app->get('/contents/{content_id}/preview-url', \Api\Controllers\ContentController::class . ':previewUrl')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 등록
    $app->post('/contents', \Api\Controllers\ContentController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
        
    //콘텐츠 하위 클립 등록
    $app->post('/contents/{content_id}/clip', \Api\Controllers\ContentController::class . ':createClip')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 하위 클립 등록
    $app->post('/contents/{content_id}/check', \Api\Controllers\ContentController::class . ':checkContent')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 수정
    $app->put('/contents/{content_id}', \Api\Controllers\ContentController::class . ':update')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 숨김 수정
    $app->put('/contents/{content_id}/hidden', \Api\Controllers\ContentController::class . ':updateHidden')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 숨김 수정
    $app->put('/contents/{content_id}/type', \Api\Controllers\ContentController::class . ':updateType')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 만료일자 수정
    $app->put('/contents/{content_id}/expiredDate', \Api\Controllers\ContentController::class . ':updateExpiredDate')
    ->add(\Api\Middleware\AuthMiddleware::class);

    $app->post('/contents/{content_id}/dd-event', \Api\Controllers\ContentController::class . ':contentDDEvent')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 유형 조회
    $app->get('/contents/archive-management/content-type', \Api\Controllers\ContentController::class . ':getContentTypeList')
    ->add(\Api\Middleware\AuthMiddleware::class);

    // 사용금지 목록 조회
    $app->get('/contents/archive-management/use-prohibit', \Api\Controllers\ContentController::class . ':getUseProhibitList')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //사용금지 여부 설정
    $app->put('/contents/{content_id}/prohibited-use', \Api\Controllers\ContentController::class . ':updateProhibitedUse')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //라우드니스 측정 요청
    $app->put('/contents/{content_id}/loudness-measure', \Api\Controllers\ContentController::class . ':loudnessMeasure')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //콘텐츠 삭제
    $app->delete('/contents/{content_id}', \Api\Controllers\ContentController::class . ':delete')
    ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 필드매핑 목록 조회
    $app->get('/contents-map', \Api\Controllers\ContentController::class . ':listMap')
        ->add(\Api\Middleware\AuthMiddleware::class)
        ->add(\Api\Middleware\ConfigMiddleware::class);
    
    ///외부 필드매핑 상세 조회
    $app->get('/contents-map/{content_id}', \Api\Controllers\ContentController::class . ':showMap')
        ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 콘텐츠 등록
    $app->post('/contents-map', \Api\Controllers\ContentController::class . ':createMap')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
    ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 콘텐츠 수정
    $app->post('/contents-map/{content_id}', \Api\Controllers\ContentController::class . ':updateMap')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
    ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 콘텐츠 수정
    $app->post('/contents-map/{content_id}/push', \Api\Controllers\ContentController::class . ':updateMapPush')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
    ->add(\Api\Middleware\AuthMiddleware::class);

    //외부 콘텐츠 삭제
    $app->post('/contents-map/{content_id}/delete', \Api\Controllers\ContentController::class . ':deleteMap')
    ->add(\Api\Middleware\ApiLogMiddleware::class)
    ->add(\Api\Middleware\AuthMiddleware::class);

    //자식 콘텐츠 목록 조회
    $app->get('/contents/{content_id}/parent', \Api\Controllers\ContentController::class . ':listParent')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 다운로드 준비
    $app->post('/contents/{content_id}/prepare-download', \Api\Controllers\ContentController::class . ':prepareDownload')
        ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);

    // VOD 영상 생성
    $app->post('/contents/{content_id}/create-vod', \Api\Controllers\ContentController::class . ':createVOD')
        ->add(\Api\Middleware\ApiLogMiddleware::class)
        ->add(\Api\Middleware\AuthMiddleware::class);

    // SNS 게시물 목록
    $app->get('/contents/{content_id}/sns-posts', \Api\Controllers\SnsPostController::class . ':indexByContentId')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // SNS 게시
    $app->post('/contents/{content_id}/sns-posts', \Api\Controllers\SnsPostController::class . ':publish')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // SNS 게시물 상세 조회
    $app->get('/contents/{content_id}/sns-posts/{sns_post_id}', \Api\Controllers\SnsPostController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // SNS 게시물 메타데이터 수정
    $app->put('/contents/{content_id}/sns-posts/{sns_post_id}', \Api\Controllers\SnsPostController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // SNS 게시물 삭제
    $app->delete('/contents/{content_id}/sns-posts/{sns_post_id}', \Api\Controllers\SnsPostController::class . ':destroy')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->post('/categories', \Api\Controllers\CategoryController::class . ':getCategoryByHierarchy')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 콘텐츠 리스트 상세보기 컬럼 순서 저장
    $app->post('/member-option-column-save', \Api\Controllers\MemberOptionController::class . ':columnSave')
    ->add(\Api\Middleware\AuthMiddleware::class);

    $app->get('/contents/easy-certi/{content_id}', \Api\Controllers\ContentController::class . ':getPersonalInformationDetection')
        ->add(\Api\Middleware\AuthMiddleware::class);
};

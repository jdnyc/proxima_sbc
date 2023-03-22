<?php

use Slim\App;

return function (App $app) {

    // 데이터 사전 용어
    // 용어 목록
    $app->get('/data-dic-words', \Api\Controllers\DataDicWordController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 용어 조회
    $app->get('/data-dic-words/{word_id}', \Api\Controllers\DataDicWordController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 용어 생성
    $app->post('/data-dic-words', \Api\Controllers\DataDicWordController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 용어 수정
    $app->put('/data-dic-words/{word_id}', \Api\Controllers\DataDicWordController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 용어 삭제
    $app->delete('/data-dic-words/{word_id}', \Api\Controllers\DataDicWordController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 용어명으로 검색
    $app->post('/data-dic-words/search', \Api\Controllers\DataDicWordController::class . ':searchByName')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->post('/data-dic-words/import', \Api\Controllers\DataDicWordController::class . ':importFromExcel')
        ->add(\Api\Middleware\AuthMiddleware::class);

    $app->post('/data-dic-words/export', \Api\Controllers\DataDicWordController::class . ':exportToExcel')
        ->add(\Api\Middleware\AuthMiddleware::class);


    // 데이터 사전 코드 아이템
    // 코드 아이템 목록
    $app->get('/data-dic-code-items', \Api\Controllers\DataDicCodeItemController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 아이템 목록 (코드셋 아이디 조건)
    $app->get('/data-dic-code-items/{code_set_id}/code-items-list', \Api\Controllers\DataDicCodeItemController::class . ':codeItemsByCodeSetId')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 셋 조회
    $app->get('/data-dic-code-items/{code_item_id}', \Api\Controllers\DataDicCodeItemController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 아이템 조회(코드셋 아이디 또는 코드 셋 코드)
    $app->get('/data-dic-code-sets/{code_set_id}/code-items', \Api\Controllers\DataDicCodeItemController::class . ':getCodeItemsByCodeSetId')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 아이템 조회(코드셋 코드로 조회)
    $app->post('/data-dic-code-sets/{code_set_code}/codes', \Api\Controllers\DataDicCodeItemController::class . ':getCodeItemsByCodeSetCode')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 도메인별 코드 아이템 조회
    $app->get('/data-dic-domains/{domain_id}/code-items', \Api\Controllers\DataDicCodeItemController::class . ':getCodesByDomainId')
        ->add(\Api\Middleware\AuthMiddleware::class);
    $app->post('/data-dic-code-sets/{code_set_code}/code-nodes', \Api\Controllers\DataDicCodeItemController::class . ':getCodeItemsByHierarchy');

    // 코드 아이템 생성
    $app->post('/data-dic-code-items', \Api\Controllers\DataDicCodeItemController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 아이템 수정
    $app->put('/data-dic-code-items/{code_item_id}', \Api\Controllers\DataDicCodeItemController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 아이템 삭제
    $app->delete('/data-dic-code-items/{code_item_id}', \Api\Controllers\DataDicCodeItemController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 데이터 사전 코드 셋
    // 코드 셋 목록
    $app->get('/data-dic-code-sets', \Api\Controllers\DataDicCodeSetController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 셋 조회
    $app->get('/data-dic-code-sets/{code_set_id}', \Api\Controllers\DataDicCodeSetController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 아이템 생성
    $app->post('/data-dic-code-sets', \Api\Controllers\DataDicCodeSetController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 셋 수정
    $app->put('/data-dic-code-sets/{code_set_id}', \Api\Controllers\DataDicCodeSetController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 코드 셋 삭제
    $app->delete('/data-dic-code-sets/{code_set_id}', \Api\Controllers\DataDicCodeSetController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);


    // 데이터 사전 테이블
    // 테이블 목록
    $app->get('/data-dic-tables', \Api\Controllers\DataDicTableController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 테이블 조회
    $app->get('/data-dic-tables/{table_id}', \Api\Controllers\DataDicTableController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 필드별 테이블 목록 조회
    $app->get('/data-dic-fields/{field_id}/tables', \Api\Controllers\DataDicColumnController::class . ':getTablesByFieldId')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 테이블 생성
    $app->post('/data-dic-tables', \Api\Controllers\DataDicTableController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 테이블 수정
    $app->put('/data-dic-tables/{table_id}', \Api\Controllers\DataDicTableController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 테이블 삭제
    $app->delete('/data-dic-tables/{table_id}', \Api\Controllers\DataDicTableController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 데이터 사전 컬럼
    // 컬럼 목록
    $app->get('/data-dic-columns', \Api\Controllers\DataDicColumnController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 컬럼 조회
    $app->get('/data-dic-columns/{column_id}', \Api\Controllers\DataDicColumnController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 테이블별 컬럼목록 조회
    $app->get('/data-dic-tables/{table_id}/columns', \Api\Controllers\DataDicColumnController::class . ':getColumnsByTableId')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 필드별 컬럼목록 조회
    $app->get('/data-dic-fields/{field_id}/columns', \Api\Controllers\DataDicColumnController::class . ':getColumnsByFieldId')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 컬럼 생성
    $app->post('/data-dic-columns', \Api\Controllers\DataDicColumnController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 컬럼 수정
    $app->put('/data-dic-columns/{column_id}', \Api\Controllers\DataDicColumnController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 컬럼 삭제
    $app->delete('/data-dic-columns/{column_id}', \Api\Controllers\DataDicColumnController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);


    // 데이터 사전 도메인
    // 도메인 목록
    $app->get('/data-dic-domains', \Api\Controllers\DataDicDomainController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 도메인 조회
    $app->get('/data-dic-domains/{domain_id}', \Api\Controllers\DataDicDomainController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 도메인 생성
    $app->post('/data-dic-domains', \Api\Controllers\DataDicDomainController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 도메인 수정
    $app->put('/data-dic-domains/{domain_id}', \Api\Controllers\DataDicDomainController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 도메인 삭제
    $app->delete('/data-dic-domains/{domain_id}', \Api\Controllers\DataDicDomainController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);

    // 데이터 사전 필드
    // 필드 목록
    $app->get('/data-dic-fields', \Api\Controllers\DataDicFieldController::class . ':index')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 필드 조회
    $app->get('/data-dic-fields/{field_id}', \Api\Controllers\DataDicFieldController::class . ':show')
        ->add(\Api\Middleware\AuthMiddleware::class);
    //필드명 검색
    $app->post('/data-dic-fields/search', \Api\Controllers\DataDicFieldController::class . ':searchByName')
    ->add(\Api\Middleware\AuthMiddleware::class);
    // 필드 생성
    $app->post('/data-dic-fields', \Api\Controllers\DataDicFieldController::class . ':create')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 필드 수정
    $app->put('/data-dic-fields/{field_id}', \Api\Controllers\DataDicFieldController::class . ':update')
        ->add(\Api\Middleware\AuthMiddleware::class);
    // 필드 삭제
    $app->delete('/data-dic-fields/{field_id}', \Api\Controllers\DataDicFieldController::class . ':delete')
        ->add(\Api\Middleware\AuthMiddleware::class);
    
    //데이터 로그 검색
    $app->get('/data-logs/search', \Api\Controllers\DataLogController::class . ':index')
    ->add(\Api\Middleware\AuthMiddleware::class);
           
};

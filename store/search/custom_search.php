<?php

$rootDir = dirname(dirname(__DIR__));
if(!defined('DS'))
	define('DS', DIRECTORY_SEPARATOR);
require_once($rootDir . DS . 'lib' . DS . 'config.php');
require_once($rootDir . DS . 'vendor' . DS .'autoload.php');

use Proxima\core\Session;
use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\content\Category;
use Proxima\models\search\CustomSearch;
use Proxima\models\content\ContentStatus;

Session::init();
$api = new ApiRequest();

// 조회
$api->get(function($params) {

    $contentStatuses = ContentStatus::all();     
    $userId = $params['user_id'];
    if(empty($userId)) {
        $userId = Session::get('user')['user_id'];
    }
    $customSearchList = CustomSearch::findByUserId($userId);

    $data = [];
    foreach($customSearchList as $customSearch) {
        // key/value array
        $filters = $customSearch->get('filters');   
        
        if($filters['content_status'] !== null) {
            $contentStatus = $contentStatuses[$filters['content_status']];              
            
            if($contentStatus == null) {
                continue;
            }

            $color = $contentStatus->get('color');
            
            if(!empty($color)){
                $customSearch->set('color', $color);
            }                         
        }    

        if($filters['category_id'] !== null) {
            $categoryPath = Category::getPath($filters['category_id']);            
            $filters['category_path'] = $categoryPath;
            $customSearch->set('filters', $filters);
        }

        $data[] = $customSearch->getAll();     
    }       
    
    if(defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CustomSearchCustom')) {
        $customSearchData = end($data);
        $id = ((int)$customSearchData['id']) + 1;
        $customSearchItems = \ProximaCustom\core\CustomSearchCustom::getCustomSearchItems($id, \Proxima\core\Session::get('user')['user_id']);
        $data = array_merge($data, $customSearchItems);
    }
    
    Response::echoJsonOk($data);

});

// 커스텀 검색 저장
$api->put(function($params) {

    /*{"category_id": null,"search_keyword": null,"content_status": 0,"created_date": null}*/  
    $customSearchList = CustomSearch::findByUserId($params['user_id']);
    $filters = $params['filters'];
    $encodedFilters = json_encode($params['filters']);
    $name = $params['name'];
    $showOrder = $params['show_order'];

    $data = [];
    $isExists = false;
    // 중복 체크
    foreach($customSearchList as $customSearch) {
        if($encodedFilters == json_encode($customSearch->get('filters'))) {
            $isExists = true;
        }        
    }   
    
    if($isExists) {
        //Response::echoJsonError('동일한 검색양식이 존재합니다.');
        Response::echoJsonError(_text('MSG01027'));
        //MSG01027 동일한 검색 항목을 선택하셨습니다.
        die();
    }

    $userId = $params['user_id'];
    if(empty($userId)) {
        $userId = Session::get('user')['user_id'];
    }

    $data = [        
        'user_id' => $userId,
        'name' => $name,
        'filters' => $encodedFilters
    ];
    $customSearch = new CustomSearch($data);
    $customSearch->save();

    Response::echoJsonOk($data);

});

// 커스텀 검색 삭제
$api->delete(function($params) {
    
    $id = $params['id'];
    if(empty($id)) {
        Response::echoJsonError('$id is empty.');
        die();
    }

    CustomSearch::delete($id);

    Response::echoJsonOk();

});

$api->run();

<?php

// $rootDir = dirname(dirname(__DIR__));
// define('DS', DIRECTORY_SEPARATOR);

require_once '../../vendor/autoload.php';

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\user\Group;
use Proxima\models\content\Category;
use Proxima\models\search\CustomSearch;
use Proxima\models\system\CategoryGrant;

$api = new ApiRequest();

// 조회
$api->get(function($params) {
    
    $groupIds = $params['group_ids'];    
    $categoryId = $params['category_id'];

    if($categoryId === null) {
        Response::echoJsonError('categoryId is empty.');
        die();
    }

    $categoryGrants = CategoryGrant::findCategoryGrantByGroup($categoryId, $groupIds);    

    $data = [];
    foreach($categoryGrants as $categoryGrant) {
        $grant = $categoryGrant->getAll();
        $grant['category_name_path'] = Category::getNamePath($categoryGrant->get('category_full_path'));   
        $group = Group::find($categoryGrant->get('member_group_id'));
        $grant['group']['member_group_id'] = $group->get('member_group_id');
        $grant['group']['member_group_name'] = $group->get('member_group_name');
        $data[] = $grant;     
    }
    
    Response::echoJsonOk($data);

});

// 적용
$api->put(function($params) {
    $categoryId = $params['category_id'];   
    if($categoryId === null) {
        Response::echoJsonError(_text('MSG00122'));//카테고리를 선택해주세요
        die();        
    } 
    $groupIds = $params['member_group_ids'];
    if(empty($groupIds)) {
        Response::echoJsonError(_text('MSG02006'));//사용자 그룹을 선택해주세요
        die();
    }
    
    $grant = $params['grant'];
    $categoryFullPath = Category::getPath($categoryId);

    $categoryGrants = CategoryGrant::findCategoryGrantByGroup($categoryId, $groupIds);  
    foreach($groupIds as $groupId) {
        $categoryGrant = $categoryGrants[$groupId];   
        if($categoryGrant === null) {
            $data = [
                'category_id' => $categoryId,
                'member_group_id' => $groupId,
                'group_grant' => $grant,
                'category_full_path' => $categoryFullPath,
            ];
            $categoryGrant = new CategoryGrant($data);            
            $categoryGrant->save();
        }
    }

    Response::echoJsonOk();
    
});

// 삭제
$api->delete(function($params) {
    $id = $params['id'];
    if(empty($id)) {
        Response::echoJsonError('id is empty.');
        die();
    }
    CategoryGrant::delete($id);

    Response::echoJsonOk();
});

$api->run();

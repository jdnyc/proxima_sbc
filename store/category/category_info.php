<?php

// $rootDir = dirname(dirname(__DIR__));
// define('DS', DIRECTORY_SEPARATOR);

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

use Proxima\core\Response;
use Proxima\core\ApiRequest;
use Proxima\models\content\Category;
use Proxima\models\search\CustomSearch;
use Proxima\models\content\ContentStatus;

$api = new ApiRequest();

$api->get(function($params) {
    
    $categoryId = $params['category_id'];
    if($categoryId === null) {
        Response::echoJsonError('categoryId is empty.');
        die();
    }
    $category = Category::find($categoryId);
    $categoryInfo = [];
    $categoryInfo['category_id'] = $categoryId;   
    $categoryFullPath = Category::getPath($categoryId);
    $categoryInfo['category_full_path'] = $categoryFullPath;
    $categoryInfo['category_name_path'] = Category::getNamePath($categoryFullPath);
    $categoryInfo['category_title'] = $category->get('category_title');
    $categoryInfo['parent_id'] = $category->get('parent_id');
    $categoryInfo['content_size'] = $category->getContentsSize();

    Response::echoJsonOk([$categoryInfo]);

});

$api->run();
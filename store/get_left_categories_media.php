<?php

/**
 * 미디어 검색 화면의 카테고리 조회 서비스
 */
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

use \Proxima\core\Request;
use \Proxima\core\Session;
use \Proxima\core\Response;
use Api\Types\CategoryType;
use Proxima\models\content\Content;
use \Proxima\models\content\Category;
use Proxima\models\content\UserContent;
use \Proxima\models\system\CategoryGrant;

Session::init();
Session::checkUserAuth();

$categoryId   = (int)Request::post('node');
$path		  = Request::post('path');
$activeTabUdContentId  = Request::post('active_tab_ud_content_id');
$userContents  = explode(',', Request::post('ud_contents'));
$useWholeCategory = strtolower(Request::post('use_whole_category')) === 'true';
$searchNode = Request::post('search');

if(!empty($activeTabUdContentId)) {
	$userContentId = $activeTabUdContentId;
} else {
	$userContentId = Request::post('ud_content_id');
}

// 기본 권한
$permissions = ['read', 'add', 'edit', 'del', 'move', 'hidden', 'setting'];

$defaultCategoryGrant = [];
$nodeCopy = false;
/* 
	부모 노드 권한 승계
	클라이언트에서 권한을 넘겨준것으로 기본권한을 설정한다.
*/
foreach($permissions as $permission) {
	$grant = 0;
    if(!empty(Request::post($permission))) {
        $grant = (int)Request::post($permission);
    }
	$defaultCategoryGrant[$permission] = $grant;
}

$isChangeNewTab = false; // 전체 카테고리를 사용하지 않고 탭이 변경될때 true
if (!$useWholeCategory && !empty($userContentId)) {

	$userContentTmp = UserContent::find($userContentId);
	$userContentRootCategory = $userContentTmp->rootCategory();
}

$userSession = \Proxima\core\Session::get('user');
$userId = $userSession['user_id'];
$groups = \Proxima\models\user\User::find($userId)->groups();

// 그룹에 해당하는 카테고리 권한 조회
$categoryGrants = CategoryGrant::getCategoryGrantsByGroup($groups);
$categoryDep = Category::getDep($categoryId);

if ((!empty($searchNode) && ($categoryDep == "3"))) {
	$children = Category::getSearchChildrenByCategoryId($categoryId,$searchNode);
}else{
	$children = Category::getChildrenByCategoryId($categoryId);
	// 부처영상일경우 코드 값으로
	if($categoryId == CategoryType::PORTAL){
		$children = Category::getCodeCategory('INSTT');
		Response::echoJson($children);
		return;
	}else if($categoryId == CategoryType::TELE){
		$children = Category::getCodeCategory('TELECINE_TY_SE');
		Response::echoJson($children);
		return;
	}
	// 구매프로그램 카테고리
	//if($categoryId == "206"){
		// B => 구매
	//	$children = Category::getPgmByBisPgmAndBrdcstStleSe('B');
	//	$nodeCopy = true;
	//}
	//if($categoryId == "207"){
	//	// S => 지원
	//	$children = Category::getPgmByBisPgmAndBrdcstStleSe('S');
	//	$nodeCopy = true;
	//}
}
if (defined('CUSTOM_ROOT') && class_exists('\ProximaCustom\core\CategoryCustom')) {
	$children = \ProximaCustom\core\CategoryCustom::getVisibleCategories($children, $categoryId);
}
    
if (!empty($children)) {
	$contentCountMap = Content::getChildCategoryContentCount($categoryId);
}

$data = [];

foreach ($children as $child) {
	// 전체 카테고리를 사용하지 않고 루트를 불러올때는 사용자 정의 콘텐츠의 루트 카테고리만 반환한다.
	if (!$useWholeCategory &&
		$categoryId == Category::ROOT_ID && 
		$userContentRootCategory->get('category_id') != $child->get('category_id')) {
			continue;
	}

	$categoryGrant = [];

	$childCategoryId = $child->get('category_id');

	// 관리자면 모든 권한
	if ($userSession['is_admin'] == 'Y') {
		$categoryGrant = CategoryGrant::getAdminGrant();
	} else {
		// 해당카테고리의 권한 조회
		if (empty($categoryGrants)) {
			$categoryGrant = $defaultCategoryGrant;
		} else {
			$categoryGrant = CategoryGrant::getCategoryGrant($childCategoryId, $categoryGrants, $defaultCategoryGrant);	
		}
	}

	// 숨김 권한이면 무시한다
	if ($categoryGrant['hidden'] == 1) {
		continue;
	}

	// 읽기 권한이 있고 부모가 최상위 카테고리면 expand 시킴
	$expanded  = false;
	if ($categoryGrant['read'] == 1 && $child->get('parent_id') == 0) {
		$expanded = true;
	}

	$mappedUserContentIds = [];
	foreach ($child->userContents() as $userContent) {
		$mappedUserContentIds[] = (int)$userContent->get('ud_content_id');
	}

	if (empty($mappedUserContentIds) && !empty($userContents)) {
		foreach ($userContents as $userContentId) {
			$mappedUserContentIds[] = (int)$userContentId;
		}
	}

	$isLeaf = (boolean)$child->get('no_children');
	$empty = false;
	$categoryTitle = $child->get('category_title');

	if ($isLeaf) {
		$contentCount = $contentCountMap[$child->get('category_id')];
		$empty = false;
		if ($contentCount === 0) {
			$empty = true;
		} 
		// 빈 카테고리 색상 변경 옵션으로 변경 필요
		if ($empty) {
			//$categoryTitle = getEmptyTitle($child->get('category_title'));
		}
	}
    
	$nodeProperty = array(
		'id' => $child->get('category_id'),
		'code' => $child->get('code'),
		'text' => $categoryTitle,	
		'leaf' => $isLeaf,
		'qtip' => $child->get('category_title'),
		'expanded' => $expanded,
		'ud_contents' => $mappedUserContentIds,
		'empty' => $empty
	);

	if ($isChangeNewTab) {
		$nodeProperty['is_new_root'] = true;
	}
	//같은 카테고리를 사용할때에
	if($nodeCopy){
		$nodeProperty['copy'] = true;
		$nodeProperty['original_category_id'] = $child->get('original_category_id');
	}
	// 권한 주입
	foreach($permissions as $permission) {
		$nodeProperty[$permission] = $categoryGrant[$permission];
    }
    
    $nodeProperty['read']=1;
    $nodeProperty['edit']=0;
    $nodeProperty['del']=0;
    $nodeProperty['move']=0;
    $nodeProperty['hidden']=0;
    $nodeProperty['setting']=0;
	$data[] = $nodeProperty;
}

Response::echoJson($data);

function getEmptyTitle($title)
{
	return '<font color="#4e4e4e">'.$title.'</font>';
}
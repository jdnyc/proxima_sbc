<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

use \Proxima\core\Session;
use \Proxima\core\Request;
use \Proxima\core\Response;
use Proxima\models\content\Content;
use \Proxima\models\content\Category;
use \Proxima\models\system\CategoryGrant;

Session::init();
Session::checkUserAuth();

$categoryId   = Request::post('categoryId');
$newParentId  = Request::post('newParentId');

if(!checkCategoryId($categoryId) || !checkCategoryId($newParentId))
{
	echo json_encode(array(
			'success' => false,
			'message' => 'invalid parameter'/*,
			'categoryId' => $_REQUEST['categoryId'],
			'newParentId' => $_REQUEST['newParentId']*/
		));
	exit;
}

/**
 * 카테고리 이동 시 기존 카테고리를 대상 카테고리의 하위 카테고리로 이동하는 기능 추가 - 2018.05.09 Alex
 * 카테고리 이동시 아래작업 진행
 * 1. 카테고리 테이블 업데이트
 * 2. 카테고리 내 콘텐츠들의 카테고리 전체경로를 업데이트
 * 3. 업데이트 된 콘텐츠들을 검색엔진에 업데이트
 */

try
{
    if($categoryId == $newParentId) throw new Exception('현재 카테고리와 이동하고자 하는 카테고리가 동일합니다');
    /*이전 Parent Id 조회 */
    $orgParentId = $db->queryOne("
                        SELECT  PARENT_ID
                        FROM    BC_CATEGORY
                        WHERE   CATEGORY_ID = $categoryId
                    ");

    if(empty($orgParentId)) throw new Exception($orgParentId->getMessage(), ERROR_QUERY);
    
    if($orgParentId == $newParentId) throw new Exception('카테고리 경로가 현재와 동일합니다');

    /*신규 Parent Id 업데이트*/
    $updateCategory = $db->exec("
                        UPDATE  BC_CATEGORY
                        SET     PARENT_ID = $newParentId
                        WHERE   CATEGORY_ID = $categoryId
                    ");
    /*신규 Parent Id의 자식정보 업데이트*/
    $updateNewParentCategory = $db->exec("
                                UPDATE  BC_CATEGORY
                                SET     NO_CHILDREN = '0'
                                WHERE   CATEGORY_ID = $newParentId
                            ");
    
    /*이전 Parent Id 조회 후 하위 카테고리가 없을 경우 자식정보 업데이트 */
    $hasChildofOrgParentCategory = $db->queryOne("
                                    SELECT  COUNT(CATEGORY_ID)
                                    FROM    BC_CATEGORY
                                    WHERE   PARENT_ID = $orgParentId
                                ");

    if($hasChildofOrgParentCategory < 1) {
        $updateOrgParentCategory = $db->exec("
                                    UPDATE  BC_CATEGORY
                                    SET     NO_CHILDREN = '1'
                                    WHERE   CATEGORY_ID = $orgParentId
                                ");
    }

    /**
     * 카테고리 전체 경로는 콘텐츠의 삭제여부에 관계없이 무조건 업데이트
     * 이후 삭제 되지 않은 콘텐츠만 조회하여 검색엔진에 업데이트
     */
    $category_full_path = getCategoryFullPath($categoryId);
    $updateCategoryFullPath = $db->exec("
                                UPDATE  BC_CONTENT
                                SET     CATEGORY_FULL_PATH = '$category_full_path'
                                WHERE   CATEGORY_ID = $categoryId
                            ");

    $hasContents = $db->queryAll("
                        SELECT  CONTENT_ID
                        FROM    BC_CONTENT
                        WHERE   CATEGORY_ID = $categoryId
                        AND     IS_DELETED = 'N'
                        AND     IS_GROUP IN ('I', 'G')
                        AND     STATUS != -3
                ");
    if(count($hasContents) > 0) {
        foreach($hasContents as $content) {
            $content_id = $content['content_id'];
            searchUpdate($content_id);
        }
    }
    
    Response::echoJsonOk();

} catch (Exception $e) {
	$msg = $e->getMessage();
	switch($e->getCode())
	{
		case ERROR_QUERY:
			$err = $mdb->errorInfo();
			$msg .= $err[2].'( '.$mdb->last_query.' )';
		break;
	}

	Response::echoJsonError($msg);
}

function checkCategoryId($catId)
{
	//카테고리 아이디가 없거나 숫자가 아니면 false
	if(empty($catId) || !is_numeric($catId))
	{
		return false;
	}
	return true;
}
?>
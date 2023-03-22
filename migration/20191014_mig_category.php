<?php
use \Api\Models\User;
use \Api\Models\FolderMng;
use \Api\Services\DTOs\ContentDto;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;


require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/workflow/lib/task_manager.php');//2011.12.17 Adding Task Manager Class
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/interface.class.php');

try{



    $metaMap = array(

    );

    $contentService = new \Api\Services\ContentService($app->getContainer());

    $folderMng = new \Api\Models\FolderMng();

    // $contents = $service->getContentList($param);
    // $contents = $service->getContentByContentId(31);
    // echo print_r($contents , true);
    $migDB = new CommonDatabase('oracle','ktv', 'ktv', '10.10.51.34'.':'.'1521'.'/'.'CMS' );
    $migDB->setLimit(1000, 0 );

    $lists = $migDB->queryAll("SELECT * FROM KMS_CNF_TREENODE_TB WHERE PARENT IN (274,273,286) ORDER BY PARENT , NODEID");
    dump($lists);
    //205
    $nodeIdFolderMap = [
        274 => 2,
        273 => 3,
        286 => 0
    ];
    $nodeIdCategoryMap = [
        274 => 200,
        273 => 201,
        286 => 205,
        1992=> 205,
        1993=> 205,
        1994=> 205,
        1995=> 205,
        1996=> 205,
        1997=> 205,
        3612=> 205
    ];
    $userId = 'admin';
    $user = new User();
    $user->user_id = $userId;

    foreach ($lists as $list) {
        $nodeid = $list['nodeid'];
        $path = $list['nodename'];
        $parentId = $nodeIdFolderMap[$list['parent']];
        $parentCategoryId = $nodeIdCategoryMap[$list['parent']];
        $ownerCd = $list['exstrvalue'];
        $name = $list['caption'];
        
       $isExist = \Api\Models\FolderMng::where("FOLDER_PATH",$path)->get()->first();
        if( empty($isExist) ){
            //신규 생성

            $categoryId = $contentService->getSequence('SEQ_BC_CATEGORY_ID');
            $categoryData = [
                'category_id' => $categoryId,
                'parent_id' => $parentCategoryId,
                'category_title' => $name,
                'show_order' => $nodeid,
                'no_children' => 1,
                'extra_order'=> $nodeid,
                'dep' => 4
            ];
            $category = new \Api\Models\Category($categoryData);

            $category->save();
            //$categoryId = 2016;
            $folderData = [
                'folder_path'       => $path, 
                'folder_path_nm'    => $name,
                'owner_cd'          => $ownerCd,
                'regist_user_id'    => $userId,
                'updt_user_id'      => $userId,
                'parent_id'         => $parentId,
                'step'              => 3,
                'using_yn'          => 'Y',
                'category_id' => $categoryId
            ];
            $folderMng = new \Api\Models\FolderMng($folderData);
            $folderMng->logging = false;          
           $folderMng->save();

           $isExist = $folderMng;

        }
        dump($isExist);

        //누락되면 실행
        
// SELECT 'update FOLDER_MNG set category_id='|| BC_CATEGORY.category_id || ' where id=' || id|| ';' FROM FOLDER_MNG
// JOIN BC_CATEGORY ON (FOLDER_PATH_NM=bc_category.CATEGORY_TITLE)  WHERE FOLDER_MNG.CATEGORY_ID IS NULL AND bc_category.PARENT_ID=200;

    }
    dd( 'end');
}catch(Exception $e){
    echo $e->getMessage();
}

function createCategory(){


}

function getNotNull($dto){
    $newData = [] ; 
    foreach($dto as $key => $val){      
        if( $val != null ){
            $newData [] = $key;
        }
    }
    return $newData;
}

function renderVal($key , $val){

    //$dates = ['created_date','last_modified_date','updated_at'];
    $dates = ['regist_dt','updt_dt'];
    $dates8 = ['brdcst_de'];
    if( in_array( $key, $dates ) ){
        $carbon = new \Carbon\Carbon($val);
        $val = $carbon->format('YmdHis');
     }else if( in_array( $key, $dates8 )  ){
        $carbon = new \Carbon\Carbon($val);
        $val = $carbon->format('Ymd');
     }

     return  $val;
}

function isExistVideoId( $videoId ){
    global $db;

    $row = $db->queryRow("SELECT  * FROM BC_CONTENT_STATUS U join BC_CONTENT C ON (U.CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.bfe_video_id = '$videoId'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function isExistHomepageId( $homepageId ){
    global $db;

    $row = $db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.hmpg_cntnts_id = '$homepageId'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function getFindHomepageParents($progrm_code, $tme_no){
    global $db;
    $row = $db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.ALL_VIDO_AT='Y' AND U.progrm_code = '$progrm_code' and u.tme_no = '$tme_no'");
    if( !empty($row) ){
        return $row['content_id'];
    }else{
        return false;
    }
}

function getFindHomepageChildren($contentId , $progrm_code, $tme_no ){
    global $db;
    $lists = $db->queryAll("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.ALL_VIDO_AT='N' AND U.progrm_code = '$progrm_code' and u.tme_no = '$tme_no'");
    if( !empty($lists) ){
        foreach($lists as $list){
            $r = $db->exec("update bc_content set PARENT_CONTENT_ID='$contentId' where content_id='{$list['content_id']}'");
        }
        return true;
    }else{
        return false;
    }
}
?>
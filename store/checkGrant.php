<?php
/**
 * 2018.02.13 khk 동작에 대한 권한 체크
 * post로 json객체를 받으며 속성은 아래와 같다
 * grantType(string): grant_access와 grant_popup로 권한 유형이다
 * userId(string): 사용자 아이디
 * contentIds(Array): 콘텐츠 아이디 integer 배열
 * 
 * 응답값 역시 json객체이고 data는 배열로 속성은 아래와 같다.
 * contentId(integer) : 콘텐츠 아이디
 * isAllow(boolean) : 허용 여부
 * 
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

use \Proxima\core\Request;
use \Proxima\core\Response;
use \Proxima\core\Session;
use \Proxima\models\system\ContentGrant;

Session::init();

$input = Request::input();
$request = $input['post'];

$data = [];
foreach($request->contentIds as $contentId) {
    $content = \Proxima\models\content\Content::find($contentId);    

    if(!empty($request->accessGrant)) {  
        $isAllow = ContentGrant::checkAllowUdContentGrant($request->userId, $content->get('ud_content_id'), $request->accessGrant);        
    } else if(!empty($request->popupGrant)) {
        $isAllow = ContentGrant::checkAllowUdContentGrant($request->userId, $content->get('ud_content_id'), $request->popupGrant);            
    } else {
        Response::echoJsonError('요청 권한이 빈값입니다.');
        die();
    }

    $data[] = [
        'contentId' => $contentId,
        'isAllow' => $isAllow
    ];
}

Response::echoJsonOk($data);


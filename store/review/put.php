<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-04-08
 * Time: 오후 5:33
 */
session_start();

require_once '../../lib/config.php';
require_once '../../lib/Content.class.php';

$content_list = $_POST['content_list'];
$content_list = json_decode($content_list, true);
$action = $_POST['action'];
$comments = $_POST['comments'];
$user_id = $_SESSION['user']['user_id'];

$_success = true;
$_msg = '';

try {

    switch ($action) {
    case 'accept':
        $to_state = GRANT_REVIEW_ACCEPT;
        break;

    case 'reject':
        $to_state = GRANT_REVIEW_REJECT;
        break;

    case 'request':
        $to_state = GRANT_REVIEW_REQUEST;
        break;

    default:
        throw new Exception('action값이 없습니다.');
        break;
    }

    foreach ($content_list as $content) {
        $state = Content::getState($content['CONTENT_ID'], ~GRANT_REVIEW_ACCEPT & ~GRANT_REVIEW_REJECT & ~GRANT_REVIEW_REQUEST);
        Content::setState($content['CONTENT_ID'], $state, $to_state);
        Content::putReview($content['CONTENT_ID'], $to_state, $comments, $user_id);
    }
} catch (Exception $e) {
    $_success = false;
    $_msg = $e->getMessage();
}

echo json_encode(array(
    'success' => $_success,
    'msg' => $_msg
));

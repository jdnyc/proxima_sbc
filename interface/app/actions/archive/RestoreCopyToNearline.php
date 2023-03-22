<?php
require_once BASEDIR . '/lib/archive.class.php';

$server->register('RestoreCopyToNearline',
    array(
        'content_id' => 'xsd:string',
        'comments' => 'xsd:string',
        'req_no' => 'xsd:string',
        'user_id' => 'xsd:string'
    ),
    array(),
    $namespace,
    $namespace.'#RestoreCopyToNearline',
    'rpc',
    'encoded',
    'RestoreCopyToNearline'
);

/**
 * @param $content_id
 * @param string $comments
 * @param $req_no
 * @param $user_id
 * @return bool|nusoap_fault
 */
function RestoreCopyToNearline($content_id, $comments = '', $req_no, $user_id) {
    $result = true;

    try {
        $content_id = trim($content_id);
        $user_id = trim($user_id);

        if (empty($content_id)) {
            throw new Exception('content_id 값이 없습니다.');
        }

        if ( ! is_numeric($content_id)) {
            throw new Exception('content_id 값이 정수가 아닙니다.');
        }

        if (empty($user_id)) {
            throw new Exception('user_id 값이 없습니다.');
        }

        $archive = new Archive();
        $archive->RestoreCopyToNearline($content_id, $comments, $req_no, $user_id);
    } catch (Exception $e) {
        $result = new nusoap_fault(-1, null, $e->getMessage());
    }

    return $result;
}

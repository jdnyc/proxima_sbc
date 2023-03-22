<?php
require_once BASEDIR . '/lib/archive.class.php';

$server->register('ArchiveReject',
    array(
        'content_id' => 'xsd:string',
        'comment' => 'xsd:string',
        'user_id' => 'xsd:string'
    ),
    array(),
    $namespace,
    $namespace.'#ArchiveReject',
    'rpc',
    'encoded',
    'ArchiveReject'
);

function ArchiveReject($content_id, $comment, $user_id) {
    $result = true;

    try {
        $content_id = trim($content_id);
        $comment = trim($comment);
        $user_id = trim($user_id);

        if (empty($content_id)) {
            throw new Exception('content_id 값이 없습니다.');
        }

        if ( ! is_numeric($content_id)) {
            throw new Exception('content_id 값이 정수가 아닙니다.');
        }

        if (empty($comment)) {
            throw new Exception('comment 값이 없습니다.');
        }

        if (empty($user_id)) {
            throw new Exception('user_id 값이 없습니다.');
        }

        $archive = new Archive();
        $archive->reject($content_id, $comment, $user_id);
    } catch (Exception $e) {
        $result = new nusoap_fault(-1, null, $e->getMessage());
    }

    return $result;
}

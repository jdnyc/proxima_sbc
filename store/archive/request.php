<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/archive.class.php');

use Monolog\Handler\RotatingFileHandler;

$logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/' . substr(basename(__FILE__), 0, strrpos(basename(__FILE__), '.')) . '.log', 14));

try {
    $genre_tp = $_REQUEST['genre_tp'];
    $category_full_path = $_REQUEST['category_full_path'];
    $req_comment = $_REQUEST['req_comment'];
    $ud_content_id = $_REQUEST['ud_content_id'];
    $user_id = $_REQUEST['user_id'];
    $items = $_REQUEST['items'];

    $archived_list = checkArchived($items);
    if ( ! empty($archived_list)) {
        throw new Exception("아카이브된 콘텐츠 입니다.<br /><br />".join($archived_list, "<br />"));
    }

    $items = attachMetadata($items);
    $items = attachMediaInfo($items);

    $archive = new Archive();
    $original_result = $archive->request(array(
        'genre_tp' => $genre_tp,
        'req_comment' => $req_comment,
        'user_id' => $user_id,
         'ud_content_id' => $ud_content_id,
        'items' => convertSpecialCharSOAP($items)
    ));

//    $logger->info('$_REQUEST', $_REQUEST);
//    $logger->info($original_result);
//    $logger->info($items);

    $result = json_decode($original_result, true);
//    $logger->info($original_result);
    if (empty($result)) {
        throw new Exception('DAS로 부터 응답 값이 없습니다.');
    } else if ( ! is_array($result)) {
        throw new Exception('from das message: '.$result);
    } else if ($result['success'] == 'true') {

        // NPS ARCHIVE_REQUEST 테이블에 아카이브 이력 등록, 리턴한 DAS ID 등록
        foreach ($result['data'] as $entry) {

            // 대기
            $status = Archive::QUEUE;
            $request_id = getSequence('ARCHIVE_SEQ');
            $request_type = 'archive';
            $interface_id = '';
            $db->insert('ARCHIVE_REQUEST', array(
                'CONTENT_ID' => $entry['nps_content_id'],
                'REQUEST_ID' => $request_id,
                'REQUEST_TYPE' => $request_type,
                'COMMENTS' => $req_comment,
                'STATUS' => $status,
                'REQUEST_USER_ID' => $user_id,
                'CATEGORY_FULL_PATH' => $category_full_path,
                'INTERFACE_ID' => $interface_id,
                'DAS_CONTENT_ID' => $entry['das_content_id'])
            );
        }
        echo json_encode(array(
            'success' => true,
            'msg' => '요청이 완료되었습니다.'
        ));
    } else {
        throw new Exception($result['message']);
    }
} catch (Exception $e) {
    $logger->error($e->getMessage());
    echo json_encode(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}

function convertSpecialCharSOAP($string) {
    $string = str_replace('&', "&amp;", $string);
    $string = str_replace('<', "&lt;", $string);
    $string = str_replace('>', "&gt;", $string);

    return $string;
}

function attachMetadata($contents) {
    $data = json_decode($contents, true);
    foreach ($data as $k => $item) {
        $user_meta = MetaDataClass::getValueInfo('usr', $item['ud_content_id'], $item['content_id']);
        $system_meta = MetaDataClass::getValueInfo('sys', $item['bs_content_id'], $item['content_id']);
        $item = array_merge($item, $user_meta);
        $item = array_merge($item, $system_meta);
        $data[$k] = $item;
    }

    return json_encode($data);
}

function checkArchived($content_list) {
    $result = array();

    if (is_string($content_list)) {
        $_content_list = json_decode($content_list, true);
    }

    if (is_array($_content_list)) {
        foreach ($_content_list as $content) {
            if (Archive::is_archived($content['content_id']) !== false) {
                array_push($result, $content['title']);
            }
        }
    } else {
        throw new Exception('아카이브 요청하시 콘텐츠 목록 값에 오류가 있습니다('.$content_list.')');
    }

    return $result;
}

function attachMediaInfo($contents) {
    global $db;

    $data = json_decode($contents, true);
    foreach ($data as $k => $item) {
        $media_list = $db->queryAll("select * from bc_media where content_id = {$item['content_id']}");
        foreach ($media_list as $media) {
            $data[$k][$media['MEDIA_TYPE'].'_filesize'] = $media['FILESIZE'];
        }
    }

    return json_encode($data);
}

<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/soap/nusoap.php');

class Archive
{
    const REJECT = 4,
        ACCEPT = 3,
        QUEUE = 1;

    const SOAP_URL = 'http://1.1.1.216:8080/interface/app/common.php?wsdl';
    public $soap;

    function __construct()
    {
        $this->soap = new nusoap_client(self::SOAP_URL);
    }

    /**
     * 아카이브 요청
     * @param $param
     * @return mixed
     */
    function request($param)
    {
        $return = $this->soap->call('ArchiveRequest', array(
            'request' => json_encode(array(
                'genre_tp' => $param['genre_tp'],
                'req_comment' => $param['req_comment'],
                'ud_content_id' => $param['ud_content_id'],
                'user_id' => $param['user_id'],
                'items' => json_decode($param['items'])
            ))
        ));

        if ( ! empty($return->return)) {
            $reencode = json_encode($return->return);
            $result = json_decode($reencode, true);
        } else {
            $result = $return;
        }

        return $result;
    }

    /**
     * 아카이브 여부 확인
     * @param $content_id
     * @return bool|string
     */
    function isArchive($content_id)
    {

        $result = $GLOBALS['db']->queryOne("
            SELECT COUNT(*)
              FROM ARCHIVE_REQUEST
             WHERE CONTENT_ID = $content_id
        ");

        if ($result > 0) {
            $result = true;
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * DAS 에서 카테고리 가져오기
     * @param $param
     * @return mixed
     */
    function getCategory($param)
    {
        $client = new SoapClient(self::SOAP_URL);

        $return = $client->GetGenreCategory(json_encode(array(
            'node'=> $param['node']
        )));

        if ( ! empty($return->return)) {
            $reencode = json_encode($return->return);
            $result =  json_decode($reencode , true);
        } else {
            $result = $return;
        }

        return $result;
    }

    /**
     * 상태 변경
     * @param $content_id
     * @param $status
     * @param null $request_type
     * @throws Exception
     */
    function setStatus($content_id, $status, $request_type = null)
    {
        try {
            $query = "UPDATE ARCHIVE_REQUEST
                         SET STATUS = '$status'
                       WHERE CONTENT_ID = $content_id";
            if (isset($request_type)) {
                $query .= " AND REQUEST_TYPE='$request_type'";
            }

            $GLOBALS['db']->exec($query);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 아카이브
     * @param $content_id
     * @param $user_id
     * @throws Exception
     */
    function archive($content_id, $user_id)
    {
        try {
            $GLOBALS['db']->exec("UPDATE ARCHIVE_REQUEST
                                     SET STATUS ='" . Archive::ACCEPT . "',
                                         REQUEST_USER_ID = '$user_id'
                                   WHERE CONTENT_ID = $content_id
                                     AND STATUS=1");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 리스토어
     * @param $content_id
     * @param $comments
     * @param $req_no
     * @param $user_id
     * @throws Exception
     */
    function restore($content_id, $comments, $req_no, $user_id)
    {
        try {
            $id = $this->buildId();
            $request_type = 'restore';

            $GLOBALS['db']->exec("INSERT INTO ARCHIVE_REQUEST
                                              (REQUEST_ID, REQUEST_TYPE,
                                               CONTENT_ID, 'DAS_CONTENT_ID,
                                               STATUS, COMMENTS, REQUEST_USER_ID)
                                        VALUES ($id, '$request_type', $content_id,
                                                $req_no, '". Archive::QUEUE . "',
                                                '$comments', '$user_id')");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 반려
     * @param $content_id
     * @param $comment
     * @param $user_id
     * @throws Exception
     */
    function reject($content_id, $comment, $user_id)
    {
        try {
            $GLOBALS['db']->exec("UPDATE ARCHIVE_REQUEST
                                     SET STATUS = '". Archive::REJECT ."',
                                         REJECT_COMMENTS = '$comment',
                                         REQUEST_USER_ID = '$user_id'
                                   WHERE CONTENT_ID = $content_id");
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 아카이브 복사작업 완료하고 DAS에 알림
     * @param $content_id
     * @param $status
     * @param string $msg
     */
    function returnArchive($content_id, $status, $msg = '')
    {
        $req_no = $this->getRequestId($content_id);
        $this->soap->call('ArchiveReturnTBS', array(
            'req_no' => $req_no,
            'status' => $status,
            'message' => $msg
        ));
    }

    /**
     * 리스토어 완료 상태로 변경하고 DAS에 알림
     * @param $content_id
     * @param $status
     * @param string $msg
     * @throws Exception
     */
    function returnRestore($content_id, $status, $msg = '')
    {
        $this->setStatus($content_id, Archive::ACCEPT, 'restore');
        $req_no = $this->getRequestId($content_id);
        $this->soap->call('RestoreReturnTBS', array(
            'req_no' => $req_no,
            'status' => $status,
            'message' => $msg
        ));
    }

    /**
     * 아카이브 요청 아이이디 가져오기
     * @param $content_id
     * @return number
     */
    function getRequestId($content_id) {
        return $GLOBALS['db']->queryOne("SELECT DAS_CONTENT_ID
                                           FROM ARCHIVE_REQUEST
                                          WHERE CONTENT_ID = $content_id");
    }

    /**
     * ODA -> 온라인 스토리지로 복사루 온라인 -> 니어라인 스토리지로 복사 작업
     * @param $content_id
     * @param $comments
     * @param $req_no
     * @param $user_id
     * @throws Exception
     */
    function RestoreCopyToNearline($content_id, $comments, $req_no, $user_id)
    {
        $id = $this->buildId();
        $request_type = 'restore';

        try {
            $GLOBALS['db']->exec("INSERT INTO ARCHIVE_REQUEST
                                              (REQUEST_ID, REQUEST_TYPE,
                                               CONTENT_ID, 'DAS_CONTENT_ID,
                                               STATUS, COMMENTS, REQUEST_USER_ID)
                                        VALUES ($id, '$request_type', $content_id,
                                                $req_no, '". Archive::QUEUE . "',
                                                '$comments', '$user_id')");

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * request id 생성
     * @return sequence
     */
    function buildId()
    {
        return getSequence('ARCHIVE_SEQ');
    }

    /**
     * 아카이브 여부
     * IS_DELETE값이 N이고 STATUS값이 2
     * @param $content_id
     * @return number
     */
    static function is_archived($content_id)
    {
        $result = false;

        $status = $GLOBALS['db']->queryOne("SELECT STATUS
                                       FROM DAS.BC_CONTENT
                                      WHERE IS_DELETED='N'
                                        AND STATUS='2'
                                        AND CONTENT_ID = $content_id");
        if (isset($status)) {
            $result = $status;
        }

        return $result;
    }
}

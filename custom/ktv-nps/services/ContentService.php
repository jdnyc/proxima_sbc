<?php

namespace ProximaCustom\services;

use Carbon\Carbon;
use Api\Types\CategoryType;
use Proxima\core\ModelBase;
use Proxima\models\content\Content;
use Proxima\models\content\UserContent;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
require_once(dirname(dirname(dirname(__DIR__))) . DS . 'lib' . DS . 'config.php');

class ContentService
{
    /**
     * Database
     *
     * @var \Database
     */
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    public function searchByVideoCode($videoCode)
    {
        // 전시 영상 콘텐츠 유형에서만...
        $userContent = UserContent::findByCode('VIDEO');
        $tableName = $userContent->getMetadataTableName();

        $query = "SELECT * FROM 
                    bc_content c, {$tableName} m
                    WHERE
                    c.content_id = m.usr_content_id 
                AND m.usr_video_code = '{$videoCode}' AND c.is_deleted = 'N' ORDER BY c.content_id DESC";

        $contents = Content::queryList($query);
        return $contents;
    }

    public function searchByVideoName($videoName)
    {
        $userContent = UserContent::findByCode('VIDEO');
        $tableName = $userContent->getMetadataTableName();

        $query = "SELECT * FROM 
                    bc_content c, {$tableName} m
                    WHERE
                    c.content_id = m.usr_content_id 
                AND c.title ilike '%{$videoName}%' AND c.is_deleted = 'N' ORDER BY c.content_id DESC";

        $contents = Content::queryList($query);
        return $contents;
    }

    public function searchByItems($items)
    {
        $userContent = UserContent::findByCode('VIDEO');
        $tableName = $userContent->getMetadataTableName();

        $contentIds = [];
        foreach ($items as $item) {
            $contentIds[] = $item->get('content_id');
        }
        $contentIdsStr = implode(',', $contentIds);

        $query = "SELECT * FROM 
                    bc_content c, {$tableName} m
                    WHERE
                    c.content_id = m.usr_content_id 
                AND c.content_id in ($contentIdsStr) AND c.is_deleted = 'N' ORDER BY c.content_id DESC";

        $contents = Content::queryList($query);
        return $contents;
    }

    private function cleanUpConditions($conditions)
    {
        if (is_null($conditions) || !is_array($conditions)) {
            return $conditions;
        }
        $cleanedConditions = [];
        foreach ($conditions as $k => $v) {
            $cleanedConditions[$k] = trim(strip_tags($v));
        }
        return $cleanedConditions;
    }

    public function makeSearchQuery($conditions, $udContentId, $filters)
    {
        $conditions = $this->cleanUpConditions($conditions);
        if (empty($conditions)) {
            $now = Carbon::now();
            $cpDate = $now->copy()->subMonth();
            $toDate = $now->format('Ymd') . '235959';
            $fromDate = $cpDate->format('Ymd') . '000000';
        } else {
            $fromDate = strip_date($conditions['from_date']) . '000000';
            unset($conditions['from_date']);
            $toDate = strip_date($conditions['to_date']) . '235959';
            unset($conditions['to_date']);
        }

        $userContents = UserContent::all();
        $tableName = ($userContents[$udContentId])->getMetadataTableName();

        // 사용자 정의 콘텐츠 유형별로 쿼리 바디를 만든다.
        $middleQueryList = [];
        foreach ($userContents as $userContent) {
            $middleQuery = "FROM 
                    bc_content c, {$tableName} m
                    WHERE
                    c.content_id = m.usr_content_id AND c.is_deleted='N' 
                    AND c.created_date > '{$fromDate}' AND c.created_date < '{$toDate}' AND status >= 0";
            $middleQueryList[$userContent->get('ud_content_id')] = $middleQuery;
        }

        // $where = [];
        // if (!empty($categoryPath)) {
        //     $where[] = "c.category_full_path like '{$categoryPath}%'";
        // }

        $queryItem = '';

        $contentFields = ['updater_id', 'title', 'created_date'];
        // 조건을 where 배열에 추가
        $allowedSearchFields = [
            'title', 'video_code', 'channel_code', 'pgm_code', 'item_code', 'aspect_ratio',
            'use', 'updater_id'
        ];
        if (!empty($conditions)) {
            foreach ($conditions as $field => $value) {
                if (empty($value) || !in_array($field, $allowedSearchFields)) {
                    continue;
                }

                // 상품 테이블에서 검색
                if ($field === 'item_code') {
                    $queryItem = "SELECT content_id FROM items WHERE code = '{$value}'";
                    continue;
                }

                if (in_array($field, $contentFields)) {
                    if ($field === 'title') {
                        $where[] = "c.{$field} ilike '%{$value}%'";
                    } else {
                        $where[] = "c.{$field} = '{$value}'";
                    }
                } else {
                    $where[] = "m.usr_{$field} = '{$value}'";
                }
            }
        }

        if (!empty($filters)) {
            if ($filters['content_status'] !== null) {
                $where[] = "c.status = {$filters['content_status']}";
            } else {
                $where[] = 'c.status != -3';
            }

            if ($filters['created_date'] !== null) {
                $fromDate = date('Ymd', strtotime("-{$filters['created_date']} day"));
                $today = date('Ymd');
                $where[] = "(c.created_date between '{$fromDate}000000' and '{$today}235959')";
            }

            if ($filters['category_path'] !== null) {
                $where[] = " c.CATEGORY_FULL_PATH like '{$filters['category_path']}%' ";
            }
        }

        if (!empty($where)) {
            foreach ($middleQueryList as $k => $v) {
                $wherePart = implode(' AND ', $where);

                $middleQueryList[$k] = $v . ' AND ' . $wherePart;
            }
        }

        $query = "SELECT c.content_id " . $middleQueryList[$udContentId];

        if (!empty($queryItem)) {
            $query = "({$query}) INTERSECT ($queryItem)";
        }

        return $query;
    }

    public function search($udContentId, $filterType, $categoryPath, $conditions, $pagination, $sort)
    {
        $userContents = UserContent::all();
        $tableName = ($userContents[$udContentId])->getMetadataTableName();

        $fromDate = strip_date($conditions['from_date']) . '000000';
        unset($conditions['from_date']);
        $toDate = strip_date($conditions['to_date']) . '235959';
        unset($conditions['to_date']);

        // 사용자 정의 콘텐츠 유형별로 쿼리 바디를 만든다.
        $middleQueryList = [];
        foreach ($userContents as $userContent) {
            $middleQuery = "FROM 
                    bc_content c, {$tableName} m
                    WHERE
                    c.content_id = m.usr_content_id AND c.is_deleted='N' 
                    AND c.created_date > '{$fromDate}' AND c.created_date < '{$toDate}'";
            $middleQueryList[$userContent->get('ud_content_id')] = $middleQuery;
        }

        $where = [];
        if (!empty($categoryPath)) {
            $where[] = "c.category_full_path like '{$categoryPath}%'";
        }

        $contentFields = ['updater_id', 'title', 'created_date'];
        // 조건을 where 배열에 추가
        foreach ($conditions as $field => $value) {
            if (empty($value)) {
                continue;
            }

            if (in_array($field, $contentFields)) {
                if ($field === 'title') {
                    $where[] = "c.{$field} ilike '%{$value}%'";
                } else {
                    $where[] = "c.{$field} = '{$value}'";
                }
            } else {
                $where[] = "m.{$field} = '{$value}'";
            }
        }

        if (!empty($where)) {
            foreach ($middleQueryList as $k => $v) {
                $wherePart = implode(' AND ', $where);

                $middleQueryList[$k] = $v . ' AND ' . $wherePart;
            }
        }

        // total 집계
        $totals = [];
        foreach ($userContents as $userContent) {
            $middleQuery = $middleQueryList[$userContent->get('ud_content_id')];
            $totalQuery = "SELECT count(*) AS count " . $middleQuery;
            $count = $this->db->queryOne($totalQuery);
            $totals[$userContent->get('ud_content_id')] = $count;
        }

        $this->db->setLimit($pagination['limit'], $pagination['offset']);

        if (in_array($sort['field'], $contentFields)) {
            $sort['field'] = 'c.' . $sort['field'];
        } else {
            $sort['field'] = 'm.' . $sort['field'];
        }

        $query = "SELECT * " . $middleQueryList[$udContentId];
        if (isset($sort['field']) && isset($sort['dir'])) {
            $query .= " ORDER BY c.content_id DESC, {$sort['field']} {$sort['dir']}";
        }

        $rows = $this->db->queryAll($query);

        return [
            'totals' => $totals,
            'rows' => $rows
        ];
    }

    public static function getLastMediaId($prefix){
        global $db;
        $maxMediaId = $db->queryOne("select max(media_id) from bc_usrmeta_content where media_id like '$prefix%'");

        return $maxMediaId;
    }

    /**
     * 미디어ID로 파일명 생성
     *
     * @param [type] $mediaId
     * @param [type] $fileExt
     * @return void
     */
    public static function getFileName($mediaId, $udContentId, $metaInfo = null ){
        //global $db;
        $brodTypeCode = '';//BRDCST_STLE_SE;//방송형태구분
        $prodStepCode = '';//PROD_STEP_SE='';//제작단계구분
        $mediaCode = substr($mediaId, 8, 1);
        if($mediaCode == 'V' || $mediaCode == 'T' || $mediaCode == 'M'|| $mediaCode == 'R'  ){
            //영상인경우 추가
            if( !empty($metaInfo) ){
                $brodTypeCode = $metaInfo['brdcst_stle_se'];
                if( empty($brodTypeCode) ){
                    $brodTypeCode = 'E';
                }
                
                $prodStepCode = $metaInfo['prod_step_se'];
                $prodStepSeMap = [
                    1 => 'O',
                    9 => 'P',
                    3 => 'M',
                    2 => 'C',
                    7 => 'F'
                ];
                $prodStepCode = $prodStepSeMap[$udContentId];
                if( empty($prodStepCode) ){
                    $prodStepCode = 'O';
                }
            }
        }
        //$fileExt = strtolower($fileExt);
        //23.01.30 원본파일명 규칙 변경 (년월일(8)+구분문자(1)+시퀀스(5))
        $fileName = $mediaId;
        // $fileName = $mediaId.$brodTypeCode.$prodStepCode ;
        return $fileName;
    }

    /**
     * 일자 기준으로 시퀀스 초기화
     * 로그 테이블에 날짜가 없을때 초기화
     * 이중화시 서버시간이 다를 경우 중복가능으로 주의
     *
     * @param [type] $seqName
     * @param integer $curDate
     * @return void
     */
    public static function getCycleSeq($seqName, $curDate = -1 ) {
        global $db;
        if($curDate == -1 ){            
		    $curDate = date("Ymd");
        }
   
        // $isSeq = DB::table('USER_SEQUENCES')->where('sequence_name','=',$seqName)->first();
        // if( empty($isSeq) ){
        //     $r =  DB::statement("CREATE SEQUENCE {$seqName} INCREMENT BY 1 START WITH 1 MINVALUE 0 MAXVALUE 99999 CYCLE");
        // }

        $resetInfo = $db->queryOne("select count(*) as cnt from log_seq_id where type='$seqName' and header='$curDate'");	     
        if ( $resetInfo == 0 ){
            $r = $db->exec("insert into log_seq_id values ('$seqName', '$curDate')");
            $r = $db->exec("DROP SEQUENCE {$seqName} ");
            $r = $db->exec("CREATE SEQUENCE {$seqName} INCREMENT BY 1 START WITH 1 MINVALUE 0 MAXVALUE 99999 CYCLE");            
        }
        return true;
    }
 
    public static function getFolderPath($category_id, $limitStep = 2, $id = null ){
        global $db;
        $returnVal = '';
        $fullPath = '';
        if($id){
            $pathInfo = $db->queryRow("SELECT id,PARENT_ID,FOLDER_PATH,step,USING_YN,PGM_ID,CATEGORY_ID FROM FOLDER_MNG WHERE DELETED_AT IS null and id=$id");
        }else{
            $pathInfo = $db->queryRow("SELECT id,PARENT_ID,FOLDER_PATH,step,USING_YN,PGM_ID,CATEGORY_ID FROM FOLDER_MNG WHERE DELETED_AT IS null and category_id=$category_id");
            if( empty($pathInfo) ){
                $parentId = $db->queryOne("SELECT PARENT_ID FROM BC_CATEGORY WHERE CATEGORY_ID=$category_id");
                if ($parentId) {
                    $fullPath = self::getFolderPath($parentId, $limitStep);
                }
            }
        }
        if( !empty($pathInfo) ){
            $path = $pathInfo['folder_path'];
            if( $pathInfo['parent_id'] > 0 && $pathInfo['step'] > $limitStep ){
                $fullPath = self::getFolderPath( $category_id , $limitStep, $pathInfo['parent_id'] );
            }
            if( !empty($fullPath) ){
                $returnVal =  $fullPath.'/'.$path;
            }else{
                $returnVal =  $path;
            }
            
        }else{
            $returnVal = $fullPath;
        }
        $returnVal = str_replace('//', '/', $returnVal);

        return $returnVal;
    }
}

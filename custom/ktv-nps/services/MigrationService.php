<?php

namespace ProximaCustom\services;

use \Api\Models\User;
use Api\Types\CategoryType;
use \Api\Services\DTOs\MediaDto;
use \Api\Services\DTOs\ContentDto;
use \Api\Services\DTOs\ContentStatusDto;
use \Api\Services\DTOs\ContentSysMetaDto;
use \Api\Services\DTOs\ContentUsrMetaDto;


if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
require_once(dirname(dirname(dirname(__DIR__))) . DS . 'lib' . DS . 'config.php');


class MigrationService{

    public $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * 스토리지 아이디 조회
     *
     * @param [type] $bsContentId
     * @param [type] $udContentId
     * @param [type] $categoryId
     * @param [type] $mediaType
     * @return void
     */
    function getStorageId($bsContentId, $udContentId, $categoryId, $mediaType ){
        // 112	X:/nearline	니어라인 스토리지
        // 121	\\10.10.51.15\main\migration\e-영상역사관\영상	이관 역사관스토리지
        // 104	X:/CMS	메인 스토리지
        // 105	X:/lowres	저해상도 스토리지
        // 118	//10.10.51.15/main/CMS/CMSData/data	ASIS 섬네일이미지
        // 119	//10.10.51.15/lowres	ASIS 저해상도 스토리지
        // 120	\\10.10.51.15\main\migration\KTV 홈페이지\영상\7df25851-29bb-48ce-b3ed-12d715903294	이관 홈페이지스토리지
        $storageId = 104;
        
        if($mediaType == 'original'){
            if($categoryId == CategoryType::HISTORY){
                //역사관
                $storageId = 121;
            }else if($categoryId == CategoryType::HOME){
                //홈페이지
                $storageId = 120;
            }else{
                $storageId = 104;
            }

        }else if($mediaType == 'proxy'){
            if($categoryId == CategoryType::HISTORY){
                //역사관
                $storageId = 121;
            }else if($categoryId == CategoryType::HOME){
                //홈페이지
                $storageId = 120;
            }else{
                $storageId = 105;
            }

        }else if($mediaType == 'thumb'){
            if($categoryId == CategoryType::HISTORY){
                //역사관
                $storageId = 121;
            }else if($categoryId == CategoryType::HOME){
                //홈페이지
                $storageId = 120;
            }else{
                $storageId = 105;
            }
        }
        return $storageId;
    }


    function updateNestDto($dto){
        $keys = $this->getNotNull($dto);
        return $dto->only(...$keys);
    }

    /**
     * dto에서 공백 제외
     *
     * @param [type] $dto
     * @return void
     */
    function getNotNull($dto){
        $newData = [] ; 
        foreach($dto as $key => $val){      
            if( $val != null ){
                $newData [] = $key;
            }
        }
        return $newData;
    }

        
    function getImagePath($path){
        if( strlen($path) <= 3   ){
            $path = (int)$path;
            $rtn = '0'.'/'.'0'.'/'.$path.'.kdf';
        }else if( strlen($path) == 4 || strlen($path) == 5 || strlen($path) == 6 ){       
            $secPos = strlen($path) - 3 ;
            $filename = (int) substr($path, -3 ) ;
            $path = (int) substr($path, -6 , $secPos ) ;
            $rtn = '0'.'/'.$path.'/'.$filename.'.kdf';
        }else if( strlen($path) == 7 || strlen($path) == 8 || strlen($path) == 9  ){
            $secPos = strlen($path) - 6 ;
            $filename = (int) substr($path, -3 ) ;
            $subPath = (int) substr($path, -6, 3 ) ;
            $path = (int) substr($path, 0 , $secPos ) ;
            $rtn = $path.'/'.$subPath.'/'.$filename.'.kdf';
        }else{
            $rtn ='unkown.kdf';
        }
        return $rtn;
    }

        
    function createQcInfo ($mediaId, $qcInfos){

        //QC정보 입력받기전에 전체 지움.
        $this->db->exec("
            DELETE	FROM BC_MEDIA_QUALITY
            WHERE	MEDIA_ID = '$mediaId'"
        );

        $i = 1; 
        foreach($qcInfos as $qc) {	
            // //이상봉실장님 버전 QC type code
            // $qc_type = array(
            //         0 => 'Black',
            //         1 => 'Single color',
            //         2 => 'Still',
            //         3 => 'Color bar',
            //         4 => 'Similar image',
            //         5 => 'No audio samples',
            //         6 => 'Mute',
            //         7 => 'Loudness'
            // );
                
            // $qc_type_str = $qc_type[(string)$qc['type']];
            // if(empty($qc_type_str)) {
            //     $qc_type_str = 'Etc';
            // }

            // $qc_start = substr($qc['start'],0,2)*3600+substr($qc['start'],3,2)*60+substr($qc['start'],6,2);
            // $qc_end = substr($qc['end'],0,2)*3600+substr($qc['end'],3,2)*60+substr($qc['end'],6,2);

            $new_qc_seq = getSequence('SEQ_BC_MEDIA_QUALITY_ID');

            $r = $this->db->exec("
                    INSERT INTO BC_MEDIA_QUALITY
                        (QUALITY_ID, MEDIA_ID, QUALITY_TYPE, START_TC, END_TC, SHOW_ORDER, SOUND_CHANNEL, no_error)
                    VALUES
                        ($new_qc_seq, '$mediaId', '".$qc['quality_type']."', '".$qc['start_tc']."', '".$qc['end_tc']."', $i, '','1')
                ");
            $i++;
        }

        // //QC 전체에 대한 정보 넣어주는 테이블
        // $idx = $i-1;
        // $hasQC = $db->queryOne("select count(content_id) from bc_media_quality_info where content_id = $content_id");

        // if($idx > 0) {
        //     if($hasQC > 0) {
        //         $query = "update bc_media_quality_info set error_count = '$idx', last_modify_date = '$now' where content_id = '$content_id'";
        //         $db->exec($query);
        //     } else {
        //         $query = "insert into bc_media_quality_info (content_id, error_count, created_date) values ('$content_id','$idx', '$now')";
        //         $db->exec($query);
        //     }
        // } else {
        //     /*검출된 정보가 없을 경우에도 QC를 진행한 부분을 확인하기 위해서 값 추가 / 있으면 업데이트 없으면 인서트 - 2018.03.20 Alex */
        //     if($hasQC > 0) {
        //         $db->exec("
        //             UPDATE	BC_MEDIA_QUALITY_INFO
        //             SET		ERROR_COUNT = $idx,
        //                     LAST_MODIFY_DATE = '$now',
        //             WHERE	CONTENT_ID = $content_id
        //         ");
        //     } else {
        //         $db->exec("
        //             INSERT INTO BC_MEDIA_QUALITY_INFO
        //                 (CONTENT_ID, ERROR_COUNT, CREATED_DATE)
        //             VALUES
        //                 ($content_id, 0, '$now')
        //         ");
        //     }
        //     $pass_add_next_job = 'true';
        // }
        return true;
    }

    /**
     * 마이그레이션 경로 조합
     *
     * @param [type] $path
     * @param [type] $filename
     * @param [type] $removePath
     * @return array 
     * fullPath
     * filename
     * path
     * ext
     * 
     */
    function getPath( $path, $filename , $removePath = null ){        
       
        $path = str_replace('\\','/', $path);
        $filename = str_replace('\\','/', $filename);

        if( is_array($removePath) ){
            foreach($removePath as $rmPath){
                $rmPath = trim( $rmPath, '/');
                $path = str_replace($rmPath,'', $path);
            }
        }else if( $removePath ){
            $removePath = trim( $removePath, '/');
            $path = str_replace($removePath,'', $path);
        }

        $path = trim( $path, '/');
        $filename = trim( $filename, '/');
        $fullPath = $path.'/'.$filename;
  
        $srcPathArray = explode( '/', $fullPath);
       
        $newFilename = array_pop($srcPathArray) ;
        $midPath = join('/' , $srcPathArray);
        $newFilenameArray = explode( '.', $newFilename);
        $srcExt = strtolower( array_pop($newFilenameArray) );

        return [
            'fullPath' => $fullPath,
            'filename' => $newFilename,
            'path' => $midPath,
            'ext' => $srcExt
        ] ;
    }

    /**
     * 날짜형 변환
     *
     * @param [type] $key
     * @param [type] $val
     * @return void
     */
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

    /**
     * db 목록에서 dto 값 매핑
     *
     * @param [type] $dto
     * @param [type] $list
     * @param array $metaKeyMap
     * @return void
     */
    function dtoMapper($dto, $list, $metaKeyMap = [] ){
        foreach ($dto as $key => $val) {
            $newKey = $key;   
            if (!is_null($metaKeyMap[$key])) {
                $newKey = $metaKeyMap[$key];
            }

        
            if (isset($list[$newKey])) {
                $dto->$key = $this->renderVal($newKey, $list[$newKey]);
            }
        }
        return $dto;
    }

        
    function isExistMediaId( $mediaId ){

        $row = $this->db->queryRow("SELECT  usr_content_id FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.media_id = '$mediaId'");
        if( !empty($row) ){
            return $row['usr_content_id'];
        }else{
            return false;
        }
    }

    function isExistVideoId( $videoId ){

        $row = $this->db->queryRow("SELECT  content_id FROM BC_CONTENT_STATUS U join BC_CONTENT C ON (U.CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.bfe_video_id = '$videoId'");
        if( !empty($row) ){
            return $row['content_id'];
        }else{
            return false;
        }
    }

    /**
     * 시퀀스 ID 발행
     *
     * @return void
     */
    function getContentId(){
        return getSequence('SEQ_CONTENT_ID');
    }

    /**
     * 역사관 키값 조회
     *
     * @param [type] $dtaDetailId
     * @return boolean
     */
    function isExistehistoryId( $dtaDetailId ,$bs_content_id , $ehistory_id = null , $uci = null ){
        
        if( !empty($ehistory_id) ){
            $row = $this->db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' and C.bs_content_id='$bs_content_id' 
             AND U.dta_detail_id = '$dtaDetailId'
             and EHISTRY_ID= '$ehistory_id'
            and UCI= '$uci'
             ");
        }else{
            $row = $this->db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' and C.bs_content_id='$bs_content_id'  AND U.dta_detail_id = '$dtaDetailId'");
        }
        if( !empty($row) ){
            return $row['usr_content_id'];
        }else{
            return false;
        }
    }

    /**
     * 홈페이지 키값 조회
     *
     * @param [type] $homepageId
     * @return boolean
     */
    function isExistHomepageId( $homepageId ){
    
        $row = $this->db->queryRow("SELECT  * FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.hmpg_cntnts_id = '$homepageId'");
        if( !empty($row) ){
            return $row['usr_content_id'];
        }else{
            return false;
        }
    }

    /**
     * 홈페이지용 부모콘텐츠 조회
     *
     * @param [type] $progrm_code
     * @param [type] $tme_no
     * @return void
     */
    function getFindHomepageParents($progrm_code, $tme_no){
    
        $row = $this->db->queryRow("SELECT  c.content_id FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.ALL_VIDO_AT='Y' AND U.progrm_code = '$progrm_code' and u.tme_no = '$tme_no'");
        if( !empty($row) ){
            return $row['content_id'];
        }else{
            return false;
        }
    }

    /**
     * 홈페이지용 자식 콘텐츠에 부모콘텐츠 매핑
     *
     * @param [type] $contentId
     * @param [type] $progrm_code
     * @param [type] $tme_no
     * @return void
     */
    function getFindHomepageChildren($contentId , $progrm_code, $tme_no ){

        $lists = $this->db->queryAll("SELECT  c.content_id FROM BC_USRMETA_CONTENT U join BC_CONTENT C ON (U.USR_CONTENT_ID=C.CONTENT_ID)  WHERE C.IS_DELETED='N' AND U.ALL_VIDO_AT='N' AND U.progrm_code = '$progrm_code' and u.tme_no = '$tme_no'");
        if( !empty($lists) ){
            foreach($lists as $list){
                $r = $this->db->exec("update bc_content set PARENT_CONTENT_ID='$contentId' where content_id='{$list['content_id']}'");
            }
            return true;
        }else{
            return false;
        }
    }
}
?>
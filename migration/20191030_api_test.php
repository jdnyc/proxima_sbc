<?php
//set_time_limit(3600);
use \Api\Models\User;
use \Api\Models\FolderMng;
use \Api\Models\ArchiveMedia;
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


require_once($_SERVER['DOCUMENT_ROOT']. '/lib/soap/nusoap.php');

try{

    // echo date("YmdHis").'<br/>';
    // $content_id = $_GET['content_id'];

    // $contentService = new \Api\Services\ContentService($app->getContainer());
    // $mediaService = new \Api\Services\MediaService($app->getContainer());
    // $mediaSceneService = new \Api\Services\MediaSceneService($app->getContainer());
$url= 'http://10.10.50.40:8080/SOAP/services/CisServicePort?wsdl';
    $soap = new SoapClient( $url );

// $param  =  [
//     'param' => [
//     'brd_form' => '',
//     'brd_form_nm' => '',
//     'brd_run' => '',
//     'delib_grd' => '',
//     'delib_nm' => '',
//     'epsd_nm' => '',
//     'err_code' => '',
//     'err_msg' => '',
//     'method_nm' => 'IF_BIS_003',
//     'pgm_nm'=> 'PG2130018D',
//     'epsd_no'=> '1',
//     'system_id'=> 'BIS',
//     'passwd'=> '1234'
// ]];

$param = [
    'param' => [
        'system_id' => 'BIS',
        'passwd' => '1234',//패스워드
        'method_nm' => 'IF_BIS_004',

        'clip_eom' => '00000000',//EOM시분초프레임
        'duration' => '00012600',//재생길이
        'clip_som' => '00012600',//SOM//시분초프레임 버림

        'mtrl_id' => '20191121T00415', //소재ID
        'file_nm' => '20191121T00415' , //파일명
        'tape_id' => '' ,//테입코드 파일명

        'mtrl_nm' => 'test테스트 1122',//소재명 title
        'mtrl_clf' => 'ZP',//소재구분 MATR_KND        
        'on_air_date' => '20191101',   //방송일 BRDCST_DE

        'pgm_id' => 'PG2130018D', //프로그램ID PROGRM_CODE
        'epsd_no' => '20',      //회차

        'regr' => 'admin',//등록자
        'modr' => 'admin',//수정자

        'remark' => '',     //비고 ??
        'trns_flag' => '3000',//전송상태
        'clip_yn' => 'Y',
        'arc_yn' => 'N'     ,//아카이브여부
        'audio_clf' => ''     ,//오디오구분
        'hd_clf' => '' //HD구분         ??
    ]
 ];
    // $return = $soap->IF_BIS_003($param );
    // dd( $return);

//     $client = new \nusoap_client($url, TRUE);
//     $client->soap_defencoding = 'UTF-8';
// //    // $client->soap_defencoding = 'EUC-KR';
//     $client->decode_utf8 = false;
//     dump( $client );
    // $return = $client->call('IF_BIS_004', $param);
    //$return = $client->call('IF_BIS_003', $param);
    dump( $return );
   // Check for a fault
if ($client->fault) {
    echo '<h2>Fault</h2><pre>';
    print_r($result);
    echo '</pre>';
}else {
    // Check for errors
    $err = $client->getError();
    if ($err) {
       // Display the error
       echo '<h2>Error</h2><pre>' . $err . '</pre>';
    } else {
       // Display the result
       echo '<h2>Result</h2><pre>';
       print_r($result);
       echo '</pre>';
    }
 }
 //    dd( $bisSoap );
    // $user = new User();
    // $user->user_id= 'admin';
    // $r = $contentService->delete($content_id, $user);

    // echo $contentId.'<br />';

}catch(Exception $e){
    echo $e->getMessage();
}

function createQcInfo ($mediaId, $qcInfos){
    global $db;

    //QC정보 입력받기전에 전체 지움.
    $db->exec("
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

        $r = $db->exec("
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
?>
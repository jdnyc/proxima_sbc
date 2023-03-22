<?php
namespace Api\Services;

use Api\Models\BisProgram;
use Api\Models\BisCode;
use Api\Services\BaseService;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * BIS 연동 서비스 
 */
class BisCommonService extends BaseService
{
    private $bisSoapUrl = 'http://10.10.50.40:8080/SOAP/services/CisServicePort?wsdl';
    /**
     * 성공
     */
    const SUCCESS = 0;
    /**
     * 권한
     */
    const GRANT = 1;
    /**
     * 필수입력
     */
    const REQUIRED = 2;
    /**
     * 데이터베이스
     */
    const DB_CODE = 3; 
    /**
     * soap
     *
     * @var [type]
     */
    private $bisSoap;

    private $passwd = '1234';
    private $systemId = 'BIS';

    private $logger;

    /**
     * 주조 소재 등록 연동 여부
     *
     * @var [type]
     */
    private $isMcr = false;

    public function __construct()
    {        
        $this->bisSoapUrl = config('bis')['url'] ?? $this->bisSoapUrl;

        $this->isMcr = config('bis')['mcr'] ?? $this->isMcr;

        require_once(dirname(dirname(__DIR__)) . '/lib/soap/nusoap.php');
        $this->bisSoap = new \nusoap_client($this->bisSoapUrl, TRUE);
        $this->bisSoap->soap_defencoding = 'UTF-8';
        $this->bisSoap->decode_utf8 = false;
      
        $logger = new \Monolog\Logger('BisCommonService');
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler(dirname(dirname(__DIR__)). '/log/'.'BisCommonService'.'-'.date('Y-m-d').'.log',\Monolog\Logger::DEBUG));
        $this->logger = $logger;
    }

    /**
     * 사용자 추가
     *
     * @param [type] $param
     * @return void
     */
    public function createUser($data) {

        $functionName = 'IF_COM_001';

        $deptNm         = $data->dept_nm;
        $emailAddress   = $data->email;
        $handPhone      = $data->phone;  
        $interPhone     = '';
        $password       = $data->password;
        $realName       = $data->user_nm;       
        $userId        = $data->user_id;

        $param = [
            'param' => [
                'system_id'     => $this->systemId,  
                'passwd'        => $this->passwd,//패스워드
                'method_nm'     => $functionName,
                //'deptnm'        => $deptNm,        
                'emailaddress'  => $emailAddress,     
                'handphone'     => $handPhone,             
                'interphone'    => $interPhone,
                'password'      => $password,
                'realname'      => $realName,
                'username'      => $userId
            ]
        ];
     
        $this->logger->info(print_r( $param,true));
        $return = $this->bisSoap->call($functionName, $param);
        $this->logger->info(print_r( $return,true));
		    return $return;
    }
    /**
     * 사용자 수정
     *
     * @param [type] $data
     * @return void
     */
    public function updateUser($data) {

        $functionName = 'IF_COM_002';
        $deptNm         = $data->dept_nm;
        $emailAddress   = $data->email;
        $handPhone      = $data->phone;  
        $interPhone     = $data->dep_tel_num;
        $password       = $data->password;
        $realName       = $data->user_nm;       
        $userId       = $data->user_id;

        $param = [
            'param' => [
                'system_id'     => $this->systemId,  
                'passwd'        => $this->passwd,//패스워드
                'method_nm'     => $functionName,
                //'deptnm'        => $deptNm,        
                'emailaddress'  => $emailAddress,     
                'handphone'     => $handPhone,             
               //'interphone'    => $interPhone,
                //'password'      => $password,
                'realname'      => $realName ,
                'username'      => $userId,
                'use_yn' => 'Y'
            ]
        ];
        $this->logger->info(print_r( $param,true));
        $return = $this->bisSoap->call($functionName, $param);
        $this->logger->info(print_r( $return,true));
		    return $return;
    }

    /**
     * 사용자 삭제
     *
     * @param [type] $userId
     * @return void
     */
    public function deleteUser($userId) {

        $functionName = 'IF_COM_003';
        $param = [
            'param' => [
                'system_id' => $this->systemId,  //??? 
                'passwd' => $this->passwd,//패스워드
                'method_nm' => $functionName,
                'username' => $userId
            ]
        ];

        $this->logger->info(print_r( $param,true));
        $return = $this->bisSoap->call($functionName, $param);
        $this->logger->info(print_r( $return,true));
		    return $return;
    }

    
    /**
     * 암호 변경 원문인듯?
     *
     * @param [type] $userId
     * @param [type] $password
     * @return void
     */
    public function changePassword($userId, $password )
    {

        $functionName = 'IF_COM_004';
        $param = [
            'param' => [
                'system_id' => $this->systemId,  //
                'passwd' => $this->passwd,//패스워드
                'method_nm' => $functionName,
                'password' => $password,
                'username' => $userId
            ]
        ];
        $this->logger->info(print_r( $param,true));
        $return = $this->bisSoap->call($functionName, $param);
        $this->logger->info(print_r( $return,true));
		    return $return;
    }
    
    /**
     * 운행 소재 등록
     *
     * @param [type] $userId
     * @return void
     */
    public function createContent($data, $userId){

        $functionName    = 'IF_BIS_004';

        $clip_som = '00000000';

        //확장자 제외
        $filename = $data->filename;
        $mtrl_id = $data->mtrl_id;
        if( strlen($data->title) > 100 ){
            //bis는 euc-kr이라 200 byte
            $title = utf8_strcut($data->title,33);
        }else{
            $title = $data->title;
        }
        $matr_knd = $data->matr_knd;
        if(empty($matr_knd)){
			$matr_knd =  'ZZ';
		}

        //프레임 버림 8자리 처리
        $sys_video_rt   = substr($data->sys_video_rt, 0, 8).':00';
        $sys_video_rt   = str_replace(':', '', $sys_video_rt);
        $sys_video_rt   = str_replace(';', '', $sys_video_rt);

        //방송일자
        $brdcst_de      = $data->brdcst_de ?? date("Ymd");
        if(empty($brdcst_de)){
			$brdcst_de =  date("Ymd");
		}
        //프로그램 / 부제 키
        $progrm_code    = $data->progrm_code;
        $tme_no         = $data->tme_no ?? 0;
		if(empty($tme_no)){
			$tme_no = '0';
		}

        //전송 완료 상태?
        $trns_flag         = $data->trns_flag;
        //비고
        $remark = $data->remark;

        //로그 확인필요
        $param = [
            'param' => [
                'system_id' => $this->systemId,
                'passwd' => $this->passwd,//패스워드
                'method_nm' => $functionName,

                'clip_eom' => $sys_video_rt,//EOM시분초프레임
                'duration' => $sys_video_rt,//재생길이
                'clip_som' => $clip_som,//SOM//시분초프레임 버림

                'mtrl_id' => $mtrl_id, //소재ID
                'file_nm' => $filename, //파일명
                'tape_id' => $filename,//테입코드 파일명

                'mtrl_nm' => $title,//소재명 title
                'mtrl_clf' => $matr_knd,//소재구분 MATR_KND        
                'on_air_date' => $brdcst_de,   //방송일 BRDCST_DE

                'pgm_id' => $progrm_code, //프로그램ID PROGRM_CODE
                'epsd_no' => $tme_no,      //회차

                'regr' => $userId,//등록자
                'modr' => $userId,//수정자

                'remark' => '',     //비고 ??

                'trns_flag' => $trns_flag,//전송상태

                'clip_yn' => 'Y',//??

                'arc_yn' => 'N'     ,//아카이브여부
                'audio_clf' => ''     ,//오디오구분
                'hd_clf' => '' //HD구분         ??
            ]
        ];
        $this->logger->info(print_r( $param,true));
        if( $this->isMcr ){
            $return = $this->bisSoap->call($functionName, $param);
            $this->logger->info(print_r( $return,true));
        }else{
            
        }
        return $return;
    }    

    /**
     * 전송 상태 업데이트
     * 사용안하는듯
     *
     * @param [type] $data
     * @param [type] $userId
     * @return void
     */
    function tmStatus ( $data, $userId){
        $functionName    = 'IF_BIS_003';
        $file_nm = $data->file_nm;
        $media_id = $data->media_id;
        $method_nm = $data->method_nm;
        $matr_knd = $data->matr_knd;
        $tm_status = $data->tm_status;

        $param = [
            'system_id' => $this->systemId,
            'passwd'    => $this->passwd,//패스워드
            'method_nm' => $functionName          
        ];
        $return = $this->bisSoap->call($functionName, $param);
    }

    function programSearch( $data, $userId){
        $functionName    = 'IF_BIS_003';
   
        $param = [
            'system_id' => $this->systemId,
            'passwd'    => $this->passwd,//패스워드
            'method_nm' => $functionName         
        ];
        $return = $this->bisSoap->call($functionName, $param);
    }

    /**
     * TC정보 등록
     *
     * @param [type] $data
     * @return void
     */
    public function updateProgramTCMeta($data)
    {
        $functionName    = 'IF_BIS_008';
        // <director>최용석,편효원,장대근,한지훈</director>
        // <epsd_no>278</epsd_no>
        // <err_code>0</err_code>
        // <keyword>이슈 본 278회</keyword>
        // <main_role>전문가,사례자</main_role>
        // <makepd>최용석</makepd><passwd>1234</passwd>
        // <pgm_id>PG2140026D</pgm_id>
        // <rec_place>대한민국</rec_place>
        // <rec_ymd>20191212</rec_ymd>
        // <scene>PD리포트 이슈 본 278회 - 안전한 스쿨존, 꼭 만들어 주세요!
        // - 스쿨존 민식이 사고, 자택에서 아버지 취재
        // - 사고현장 취재
        // - 서울 시내 스쿨존 과속 현장 취재
        // - 대구광역시청 스쿨존 개선 사업
        // - 대전광역시 서구청, 교육청, 학교 협업으로 등교길 안전보도 설치
        // - 민식이법 국회 통과, 다른 아이들 법 통과 기다림</scene>
        // <system_id>BIS</system_id>
      
        $director   = $data->director;
        $epsdNo    = $data->epsd_no;
        $keyword      = $data->keyword;
        $mainRole   = $data->main_role;
        $makePd   = $data->makepd;
        $pgmId   = $data->pgm_id;
        $recPlace   = $data->rec_place;
        $recYmd   = $data->rec_ymd;
        $scene   = $data->scene;
        
        $param = [
            'param' => [
                'system_id' => $this->systemId,
                'passwd' => $this->passwd,//패스워드
                'method_nm' => $functionName,

                'pgm_id' => $pgmId,//프로그램코드
                'epsd_no' => $epsdNo,//회차
                'director' => $director,//감독          
                'keyword' => $keyword,//키워드
                'main_role' => $mainRole, //출연자
                'makepd' => $makePd, //PD
                'rec_place' => $recPlace,//촬영장소
                'rec_ymd' => $recYmd,//촬영일자      
                'scene' => $scene   //신
            ]
        ];
        $this->logger->info(print_r( $param,true));
        if( $this->isMcr ){
            $return = $this->bisSoap->call($functionName, $param);
            $this->logger->info(print_r( $return,true));
        }else{
            
        }
        return $return;
    }

    /**
     * SOAP API가 없는듯 하여 DB 직접조회
     *
     * @return array
     */
    public function getUserInfo($userId)
    {
        $user = DB::table('CISCOM.TCO_USER','bis')->where('user_id', $userId)->get();
        return $user;
    }
}
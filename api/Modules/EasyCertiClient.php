<?php

namespace Api\Modules;

use GuzzleHttp\Client;
use Api\Models\Content;
use Api\Core\HttpClient;
use Api\Models\ContentStatus;
use Api\Models\ContentUsrMeta;
use Illuminate\Database\Capsule\Manager as DB;


class EasyCertiClient extends HttpClient
{
    private $baseSubUrl = '/upcloud';
    /**
     * 생성자
     */
    public function __construct($baseUrl = null)
    {
        if($baseUrl == null) $baseUrl = env('EASYCERTI_API_URL', 'http://10.10.50.66:5500');
        parent::__construct($baseUrl);
    }

    // 이지서티 개인정보 검출 API
    public function postPersonalInformationDetection($param)
    {
        $url = $this->baseSubUrl.'/privacys';

        $options = [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'form_params' => $param
        ];
        
        $result = $this->post($url, $options);
        return $result;
    }

    public function getMetadata($contentId)
    {
        $contentUsrMeta = ContentUsrMeta::where('usr_content_id', '=', $contentId)->first();
        return $contentUsrMeta;
    }

    public function makeEasyCertiArg($contentUsrMeta, $userId)
    {
        $strContentUsrMeta = json_encode($contentUsrMeta, JSON_UNESCAPED_UNICODE);
        if (!isset($userId)) $userId = 'admin';
        $getUserInfo = DB::table('BC_MEMBER')->where('user_id', '=', $userId)->first();
        $clientIp = getClientIp();
        $param = [
            'hostName' => '10.10.50.66',
            'hostPort' => '5500',
            'userIP' => $clientIp,
            'userId' => $userId,
            'userName' => $getUserInfo->USER_NM,
            'deptId' => $getUserInfo->DEPT_NM,
            'reqType' => 'CR',//upload
            'contents' => $strContentUsrMeta
            // 'title' => 'TEST',
        ];
        return $param;
    }

    public static function getSequence($seq_name)
	{
        $seq_name = trim($seq_name);
        $id = DB::selectOne("select $seq_name.nextval from dual");
        return $id->nextval;
    }

    public function saveINDVDLINFO($eocndvkfka, $contentId)
    {
        $arrData = [];
        foreach ($eocndvkfka as $key => $value) {
            $arrData[$key] = implode(',', $value);
        }

        $getContentId = DB::table('INDVDLINFO')->where('content_id', '=', $contentId)->first();

        $query = DB::table('INDVDLINFO');
        if(!empty($getContentId)) {
            $query = $query->where('content_id', '=', $contentId)->update($arrData);
        } else {
            $arrData['id'] = $this->getSequence('SEQ_INDVDLINFO_ID');
            $arrData['content_id'] = $contentId;
            $query = $query->insert($arrData);
        }

        $this->saveContentStatusINDVDLINFOAT($contentId, 'Y');

        return $query;
    }

    public function saveContentStatusINDVDLINFOAT($contentId, $flag)
    {
        ContentStatus::where('content_id', '=', $contentId)->update([
            'INDVDLINFO_AT' => $flag
        ]);
    }

    public function makeINDVDLINFORowDataNull($contentId)
    {
        //검출내역이 없을 시, INDVDLINFO_AT = 'N', INDEVELINFO 테이블 row 값 null 처리
        $query = DB::table('INDVDLINFO');
        $getContentId = $query->where('content_id', '=', $contentId)->first();
        if(!empty($getContentId)) {
            $arrData = [
                'ACCOUNT_NUMBERS' => null,
                'BUSSINESS_NAMES' => null,
                'BUSINESSMAN_NUMBERS' => null,
                'CAR_NUMBERS' => null,
                'CELLPHONE_NUMBERS' => null,
                'CORPORATION_NUMBERS' => null,
                'CREDIT_CARDS' => null,
                'DRIVER_NUMBERS' => null,
                'EMAILS' => null,
                'FOREIGN_NUMBERS' => null,
                'HEALTH_INSURANCES' => null,
                'NAMES' => null,
                'PASSPORT_NUMBERS' => null,
                'PREVENT_WORDS' => null,
                'SOCIAL_NUMBERS' => null,
                'TELEPHONE_NUMBERS' => null
            ];
            // dd($arrData);
            $query->where('content_id', '=', $contentId)->update($arrData);
        }
        $this->saveContentStatusINDVDLINFOAT($contentId, 'N');
    }
}
<?php
namespace Api\Services;

use Api\Services\BaseService;
use Api\Core\HttpClient;
/**
 * 조디악 restful 연동 서비스 
 */
class ZodiacService extends BaseService
{  

    private $baseUrl = 'http://10.10.50.135:9200/';
    private $sndPhnId = '0442048356';

    public function __construct()
    {
        $this->baseUrl = config('zodiac')['api_url'];
    }


    /**
     * 문자 보내기
     *
     * @param string $rcvPhnId 받는번호
     * @param string $msg 내용
     * @param string $sndPhnId 보내는번호
     * @return void
     */
    public function sendSMS($rcvPhnId, $msg, $sndPhnId = null)
    {
        $sndPhnId = $sndPhnId ?? $this->sndPhnId;
        $functionName = '/cps/service/insertSmsQueue';

        $sndPhnId = str_replace('-','',$sndPhnId);
        $rcvPhnId = str_replace('-','',$rcvPhnId);
        $params = [];        
        if( mb_strlen($msg) > 60 ){
            $usedCd = '10';
        }else{
            $usedCd = '00';
        }
        $payload = [
            'rcv_phn_id'=> $rcvPhnId,
            'snd_phn_id'=> $sndPhnId,
            'snd_msg'   => $msg,
            'used_cd'   => $usedCd            
        ];   
        $options = [
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode($payload)
        ];
            
        $client = new HttpClient($this->baseUrl);
        $result = $client->request('post', $functionName, $params, $options);
        $error = $client->isError();
    }
}
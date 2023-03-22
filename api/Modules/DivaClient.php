<?php

namespace Api\Modules;

use Api\Models\DivaApi;
use Api\Core\HttpClient;
use GuzzleHttp\Exception\ClientException;
use phpDocumentor\Reflection\Types\Null_;

/**
 * diva 8버전 web api 연계 클래스
 */
class DivaClient extends HttpClient
{
    private $baseUrl = 'https://10.10.50.100:8765';
    private $serviceSubUrl = 'dataservice';
    private $managerSubUrl = 'manager';

    private $username = 'ktv';
    private $password = 'ktv';

    private $token = 'Bearer token';

    private $collectionName = 'cms';//기본 카테고리

    private $archiveMedia = 'SPM_STORAGE';//미디어그룹

    private $sourceServer = 'san';//기본 저장위치
    private $destinationServer = 'san';//기본 저장위치
        
    /**
     * 생성자
     */
    public function __construct($baseUrl = null,$username = null ,$password = null  )
    {
        $this->baseUrl = $baseUrl ?? $this->baseUrl;
        $this->username = $username ?? $this->username;
        $this->password = $password ?? $this->password;
        $this->token = DivaApi::getToken();
        parent::__construct($baseUrl);
        
    }

    public function getServiceUrl()
    {
        return $this->baseUrl.'/'.$this->serviceSubUrl;
    }

    public function getManagerUrl()
    {
        return $this->baseUrl.'/'.$this->managerSubUrl;
    }

    private function getToken()
    {
        return  $this->token;
    }

    private function setToken($token)
    {
        DivaApi::setToken($token);
        $this->token = $token;
        return true;
    }

    public function login()
    {
        $url = $this->getServiceUrl().'/users/login';
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode([
                'username' => $this->username,
                'password' => $this->password
            ])
        ];
        //{"statusCode":1035,"statusDescription":"Access denied","statusName":"DIVA_ERR_ACCESS_DENIED"}
        $result = $this->post($url, $options);
        return json_decode($result->getContents());
    }

    public function isExpiredToken($resultContents)
    {
        if ($resultContents && $resultContents['statusCode'] == 1035) {
            $login = $this->login();
            $this->setToken($login->token);
            return true;
        }
        return false;
    }

    public function getWithAuth($url, $params, $options)
    {
        try {
            $result = $this->get($url, $params, $options);
            $resultContents = json_decode($result->getContents(),true);
        }catch(ClientException $exception)
        {          
            $resultContents = json_decode($exception->getResponse()->getBody()->getContents(),true);
            $isExpiredToken = $this->isExpiredToken($resultContents);
            if ($isExpiredToken) {
                $options['headers']['Authorization'] =  $this->getToken();
                $result = $this->get($url, $params, $options);
                $resultContents = json_decode($result->getContents(),true);
            }
            return $resultContents;
        }
        return $resultContents;
    }

    public function postWithAuth($url, $options)
    {
        try {
            $result = $this->post($url, $options);
            $resultContents = json_decode($result->getContents(), true);
        }catch(ClientException $exception)
        {          
            $resultContents = json_decode($exception->getResponse()->getBody()->getContents(),true);
            $isExpiredToken = $this->isExpiredToken($resultContents);
            if ($isExpiredToken) {
                $options['headers']['Authorization'] =  $this->getToken();
                $result = $this->post($url, $options);
                $resultContents = json_decode($result->getContents(),true);
            }
            return $resultContents;
        }
        return $resultContents;
    }

    public function getObjectInfo($objectName, $collectionName = null)
    {
        $url = $this->getManagerUrl().'/objects/info';
    
        //{{baseUrl}}/objects/info?objectName=20201218M07582&collectionName=cms
        $token = $this->getToken();

        if(empty($collectionName)){
            $collectionName = $this->collectionName;
        }
  
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json',
                'Authorization' => $token
            ]
        ];
        $params = [
            'objectName' => $objectName,
            'collectionName'=> $collectionName 
        ];
        $resultContents = $this->getWithAuth($url, $params, $options);
        return $resultContents;
    }

    public function dateArrayToDateString($dateArray)
    {
        return date("YmdHis", mktime($dateArray[3], $dateArray[4],$dateArray[5], $dateArray[1], $dateArray[2], $dateArray[0]));
    }

    public function getEvents($requestId ,$type = 'REQUEST',$severity = 'ALL',$page = 1,$size = 200)
    {
        $url = $this->getManagerUrl().'/events';
    
        //{{baseUrl}}/objects/info?objectName=20201218M07582&collectionName=cms
        $token = $this->getToken();
  
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json',
                'Authorization' => $token
            ]
        ];
        $params = [
            'type' => $type,
            'requestId'=> $requestId,
            'severity' => $severity,
            'page' => $page,
            'size' => $size
        ];
        $resultContents = $this->getWithAuth($url, $params, $options);
        return $resultContents;
    }

    public function getTapes( $page = 1, $size =1000 , $barcode = null )
    {
        $url = $this->getManagerUrl().'/tapes';
    
        $token = $this->getToken();
  
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json',
                'Authorization' => $token
            ]
        ];
        $params = [
            'page' => $page,
            'size'=> $size 
        ];
        if( !empty($barcode) ){
            $params['barcode'] = $barcode;
        }
        $resultContents = $this->getWithAuth($url, $params, $options);
        return $resultContents;
    }

    public function getAllTapes( ){
        $size = 3000;
        $page = 1;
        $results = [];
        $tapes = $this->getTapes(  $page, $size );
        $results = $tapes['tapes'];
        if (!empty($tapes['count'])) {
            $total = $tapes['count'];
            if( $total > $size ){
                $lastPage = (int)( $total / $size ) + 1;
                for($page ; $page <= $lastPage; $page++)
                {
                    $tapesPage = $this->getTapes(  $page, $size  );

                    $results = array_merge(  $results, $tapesPage['tapes']);
                }
            }
        }
       // dd(count($results));
        return $results;
    }

    public function archive($objectName, $filePathRoot, $components = [], $collectionName=null,$sourceServer=null, $instance=-1, $priority=50 , $qos=3 )
    {
        $url = $this->getManagerUrl().'/requests/archive';
        
        $options='';
        
        $media = $this->archiveMedia; //group
        
        if($collectionName == null){
            $collectionName = $this->collectionName;
        }
        if($sourceServer == null){
            $sourceServer = $this->sourceServer;
        }
        if (empty($components)) {
            throw new \InvalidArgumentException('required components');
            return [];
        }

        if(!empty($filePathRoot)){
            $filePathRoot = str_replace("/", "\\", $filePathRoot);
        }
        // {
        //     "collectionName": "<string>",(문자열): 개체의 컬렉션 이름(카테고리) ,
        //     "components": [(Array[string]): filePathRoot 매개변수에 의해 지정된 폴더와 관련된 파일 경로 이름 목록입니다. filePathRoot가 null인 경우 경로 이름은 절대 이름이어야 합니다. ,
        //         "<string>",
        //         "<string>"
        //     ],
        //     "media": "<string>",(문자열): 미디어는 인스턴스를 생성해야 하는 구성에서 선언된 테이프 그룹 또는 디스크 어레이를 지정합니다.DIVA CONNECT - 일반적으로 DIVA Connect는 로컬 사이트에 아카이브합니다. 사이트 이름이 미디어 앞에 추가되면 아카이브가 지정된 사이트로 리디렉션될 수 있습니다. 예: site1_TapeGroup1. ,
        //     "objectName": "<string>",
        //     "sourceServer": "<string>",(문자열): 소스의 이름(예: 비디오 서버, 브라우징 서버). 이 이름은 DIVA Core 구성 설명에서 알고 있어야 합니다.
        //     "comments": "<string>",
        //     "filePathRoot": "<string>",(문자열, 선택 사항): filenamesList 매개변수로 지정된 파일의 루트 폴더입니다. ,
        //     "options": "<string>",
        //     "priority": "<integer>", (integer, optional): The priority level for this request. The priority can be in the range zero to one hundred. The value zero is the lowest priority and one hundred the highest priority ,
        //     "qos": "<integer>"(정수, 선택 사항): 다음 코드 중 하나: DIVA_QOS_DEFAULT(0): 기본 서비스 품질(현재: 아카이브 작업을 위한 직접 및 캐시)에 따라 아카이브가 수행됩니다. DIVA_QOS_CACHE_ONLY (1): 캐시 아카이브만 사용합니다. DIVA_QOS_DIRECT_ONLY (2): 직접 아카이브만 사용합니다. 디스크 인스턴스가 생성되지 않습니다. DIVA_QOS_DIRECT_AND_CACHE (3): 사용 가능한 경우 직접 보관을 사용하거나 직접 보관을 사용할 수 없는 경우 캐시 보관을 사용합니다. DIVA_QOS_CACHE_AND_DIRECT (4): 사용 가능한 경우 캐시 아카이브를 사용하고 캐시 아카이브를 사용할 수 없는 경우 직접 아카이브를 사용합니다. 추가 및 선택적 서비스를 사용할 수 있습니다. 이러한 서비스를 요청하려면 이전에 문서화된 서비스 품질 매개변수와 다음 상수 사이에 논리적 OR을 사용하십시오. DIVA_ARCHIVE_SERVICE_DELETE_ON_SOURCE(0x0100): 테이프 마이그레이션이 완료되면 소스 파일을 삭제하십시오. 로컬 소스, 디스크 소스 및 표준 ftp 소스에 사용할 수 있습니다.
        // }
        
        $params = [
            'sourceServer' => $sourceServer,
            'objectName' => $objectName,
            'collectionName' => $collectionName,
            'instance' => $instance,
            'media' => $media,
            'options' => $options,
            'filePathRoot' => $filePathRoot,
            'components' => $components,
            'priority' => $priority,
            'qos' => $qos
        ];
        
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode($params)
        ];
        $resultContents = $this->postWithAuth($url, $options);
        return $resultContents;
    }

    public function restore($objectName, $filePathRoot, $collectionName=null,$destinationServer=null, $instance=-1, $priority=50 , $qos=4 )
    {          
        $url = $this->getManagerUrl().'/requests/restore';

        $options='';
        $media=''; //group
        if($collectionName == null){
            $collectionName = $this->collectionName;
        }
        if($destinationServer == null){
            $destinationServer = $this->destinationServer;
        }
        if(!empty($filePathRoot)){
            $filePathRoot = str_replace("/", "\\", $filePathRoot);
        }
        //O:/CMS/WorkingTemp/28/20130703V77049NP
        
        // {
        //     "destinationServer": "<string>",
        //     "objectName": "<string>",
        //     "collectionName": "<string>",
        //     "filePathRoot": "<string>",
        //     "instance": "<integer>",
        //     "options": "<string>",
        //     "priority": "<integer>",
        //     "qos": "<integer>"
        // }

        $params = [
            'destinationServer' => $destinationServer,
            'objectName' => $objectName,
            'collectionName' => $collectionName,
            'instance' => $instance,
            'media' => $media,
            'options' => $options,
            'filePathRoot' => $filePathRoot,
            'priority' => $priority,
            'qos' => $qos
        ];
      
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode($params)
        ];
        $resultContents = $this->postWithAuth($url, $options);
        return $resultContents;
    }

    /**
     * 오브젝트 삭제 - 동기
     *
     * @param string $objectName
     * @param string $collectionName
     * @param integer $instance
     * @param integer $priority
     * @return array
     */
    public function deleteObject($objectName, $collectionName=null, $instance=-1, $priority=50 )
    {
        $url = $this->getManagerUrl().'/requests/delete';

        $options='';
        $media='';//group
        if($collectionName == Null){
            $collectionName = $this->collectionName;
        }
        // {
        //     "objectName": "<string>",
        //     "collectionName": "<string>",
        //     "instance": "<integer>",
        //     "media": "<string>",
        //     "options": "<string>",
        //     "priority": "<integer>"
        // }

        $params = [
            'objectName' => $objectName,
            'collectionName' => $collectionName,
            'instance' => $instance,
            'media' => $media,
            'options' => $options,
            'priority' => $priority
        ];
        
        
        $options = [
            'verify' => false,
            'headers' => [
                'content-type' => 'application/json'
            ],
            'body' => json_encode($params)
        ];
        $resultContents = $this->postWithAuth($url, $options);
        return $resultContents;
    }

    public function deleteObjects($objectNames, $collectionName=null, $instance=-1, $priority=50)
    {
        $results = [];
        foreach($objectNames as $objectName)
        {
            $r = $this->deleteObject($objectName, $collectionName, $instance, $priority );
            $results [] = $r;
        }
        return $results;
    }
}
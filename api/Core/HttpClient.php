<?php

namespace Api\Core;

use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;

class HttpClient
{
    /**
     * Guzzle Http client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;
    /**
     * 헤더에 들어갈 Agent명
     *
     * @var string
     */
    protected $agent;

    /**
     * Error request , response
     *
     * @var boolean
     */
    protected $error = false;
    /**
     * request body 
     *
     * @var [type]
     */
    protected $body ;

    /**
     * 생성자
     *
     * @param string $baseUrl
     * @param string $agent
     */
    public function __construct($baseUrl, $agent = '')
    {
        $this->client = new \GuzzleHttp\Client(['base_uri' => $baseUrl]);
        $this->agent = $agent;
    }

    /**
     * url 통신
     *
     * @param string $path   base_uri 뒤의 path
     * @param array $params
     * @return object
     */
    public function get($path, $params, $options = null)
    {
        $subUrl = $this->makeSubUrl($path, $params);

        $options = $this->addDefaultHeaders($options);
        $res = $this->client->get($subUrl, $options);

        return $res->getBody();
    }

    private function addDefaultHeaders($options)
    {
        if (is_null($options)) {
            $options = [];
        }

        if (!isset($options['headers']['User-Agent']) && $this->agent) {
            $options['headers']['User-Agent'] = $this->agent;
        }

        if (!isset($options['headers']['Content-Type'])) {
            $options['headers']['Content-Type'] = 'application/json';
        }
        
        return $options;
    }

    /**
     * 파라미터를 url형식으로 변환
     *
     * @param string $path    base_uri 뒤의 path
     * @param array $params
     * @return string
     */
    private function makeSubUrl($path, $params)
    {
        $subUrl = $path;

        if ($params) {
            $p = [];
            foreach ($params as $k => $v) {
                $p[] = $k . '=' . $v;
            }
    
            $subUrl .= '?' . implode('&', $p);
        }

        return $subUrl;
    }

    /**
     * url 통신(post)
     *
     * @param string $path   base_uri 뒤의 path
     * @param array $options
     * @return object
     */
    public function post($path, $options = null)
    {
        $options =$this->addDefaultHeaders($options);
        $request = $this->client->post($path, $options);
        
        return $request->getBody();
    }

    /**
     * url 통신(request)
     *
     * @param string $method POST, GET, PUT, DELETE 같은 HTTP 매서드
     * @param string $path 호스트를 제외한 경로
     * @param string $params 쿼리 파라메터
     * @param string $options 다른 옵션
     * @return void
     */
    public function request($method, $path, $params, $options)
    {
        $options =$this->addDefaultHeaders($options);
        if (!empty($params)) {
            $options['query'] = $params;
        }
        try {
            $this->error = false;
            $request = $this->client->request($method, $path, $options);
            $this->body = $request->getBody();
            return $request->getBody();
        }
        catch (RequestException $e) {
            $this->error = new \stdClass();
            $this->error->request = Psr7\str($e->getRequest());
            $this->error->response = Psr7\str($e->getResponse());          
            return false;
        }
    }

    /**
     * 에러 있는경우 리턴
     *
     * @return boolean
     */
    public function isError(){
        if( $this->error ){
            return $this->error;
        }
        return false;
    }

    /**
     * 요청후 바디 조회
     *
     * @return void
     */
    public function getBody(){
        return $this->body;
    }
}

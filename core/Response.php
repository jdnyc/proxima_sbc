<?php

/**
 * 클라이언트에 응답을 줄 때 사용
 */

namespace Proxima\core;

class Response
{    
    /**
     * 응답 객체를 그대로 json encoding 후 echo 수행
     *
     * @param mixed $response 응답 객체
     * @return void
     */
    public static function echoJson($response)
    {
        if(!isset($response) || empty($response))
        {
            self::echoJsonError('response is empty.');
            return;
        }

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    /**
     * SimpleXMLElement형태의 응답을 echo 수행
     *
     * @param SimpleXMLElement $xml
     * @return void
     */
    public static function echoXml($xml)
    {
        if(!isset($xml) || empty($xml))
        {
            self::echoXmlError('xml is empty.');
            return;
        }   

        header('Content-Type: application/xml');
        echo $xml->asXML();
    }

    /**
     * 데이터를 넣으면 성공 응답 객체를 json encode하여 echo 함
     *
     * @param mixed $data 응답 데이터
     * @return void
     */
    public static function echoJsonOk($data = 'ok')
    {
        if(!isset($data))
        {
            $data = null;
        }        

        $response = ['success' => true, 'data' => $data];
        self::echoJson($response);        
    }

    /**
     * 데이터를 넣으면 실패 응답 객체를 json encode하여 echo 함
     *
     * @param mixed $message 실패 메세지
     * @return void
     */
    public static function echoJsonError($message)
    {
        if(!isset($message))
        {
            $message = '';
        }        

        $response = ['success' => false, 'msg' => $message];
        self::echoJson($response); 
    }

    /**
     * 메세지를 넣으면 성공 응답 객체를 xml형식으로 echo 함
     *
     * @param string $message
     * @return string
     */
    public static function echoXmlOk($message = 'ok')
    {
        $response = self::createXmlResponse();
        $result = $response->addChild('Result');
        $result->addAttribute('success', 'true');
        $result->addAttribute('msg', $message);
    
        self::echoXml($response);
    }   

    /**
     * 메세지를 넣으면 실패 응답 객체를 xml형식으로 echo 함
     *
     * @param string $message
     * @return string
     */
    public static function echoXmlError($message)
    {
        $response = self::createXmlResponse();
        $result = $response->addChild('Result');
        $result->addAttribute('success', 'false');
        $result->addAttribute('msg', $message);
    
        self::echoXml($response);
    }    

    /**
     * 기본적인 Xml응답을 위한 SimpleXMLElement객체 생성
     *
     * @return SimpleXMLElement SimpleXMLElement 인스턴스
     */
    public static function createXmlResponse()
    {
        return new \SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<Response />");
    }
}

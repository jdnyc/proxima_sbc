<?php

/**
 * 클라이언트에서 POST나 GET으로 요청 시 서버에서 해당 요청에 대한 파라메터를 얻을 때 사용
 * POST의 경우 XSS에 대한 처리가 되어 있음 
 * This is under development. Expect changes!
 * Class Request
 * Abstracts the access to $_GET, $_POST and $_COOKIE, preventing direct access to these super-globals.
 * This makes PHP code quality analyzer tools very happy.
 * @see http://php.net/manual/en/reserved.variables.request.php
 */

namespace Proxima\core;

class Request
{    
    /**
     * 요청에 대한 입력을 GET은 배열 POST, PUT, DELETE는 json객체를 포함한
     * 리턴 배열( ['method' => $method, 'input' => $input] )로 리턴
     *
     * @param boolean $clean strip_tags를 사용할지 여부
     * @return mixed
     */
    public static function input($clean = false)
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $input = [];
        switch ($method) {
            case 'get':
                {
                    $input['method'] = $method;
                    $input['get'] = $_GET;
                    break;
                }
            case 'post':
            case 'put':
            case 'delete':
                {
                    $input['method'] = $method;
                    $input[$method] = self::getInputJsonFromRawValue($clean);
                    break;
                }
        }
        return $input;
    }

    public static function getInputJsonFromRawValue($clean = false, $assoc = false)
    {
        $raw = file_get_contents('php://input');
        $raw = ($clean) ? trim(strip_tags($raw)) : $raw;

        if (validate_json($raw)) {
            $inputJson = json_decode($raw, $assoc);
        } else {
            $inputJson = [];
            parse_str($raw, $inputJson);
        }
        
        //print_r($inputJson);
        return $inputJson;
    }

    /**
     * Gets/returns the value of a specific key of the POST super-global.
     * When using just Request::post('x') it will return the raw and untouched $_POST['x'], when using it like
     * Request::post('x', true) then it will return a trimmed and stripped $_POST['x'] !
     *
     * @param mixed $key key
     * @param bool $clean marker for optional cleaning of the var
     * @return mixed the key's value or nothing
     */
    public static function post($key, $clean = false)
    {
        if (isset($_POST[$key])) {
            // we use the Ternary Operator here which saves the if/else block
            // @see http://davidwalsh.name/php-shorthand-if-else-ternary-operators
            return ($clean) ? trim(strip_tags($_POST[$key])) : $_POST[$key];
        }
    }

    /**
     * Returns the state of a checkbox.
     *
     * @param mixed $key key
     * @return mixed state of the checkbox
     */
    public static function postCheckbox($key)
    {
        return isset($_POST[$key]) ? 1 : NULL;
    }

    /**
     * gets/returns the value of a specific key of the GET super-global
     * @param mixed $key key
     * @return mixed the key's value or nothing
     */
    public static function get($key)
    {
        if (isset($_GET[$key])) {
            return $_GET[$key];
        }
    }

    /**
     * GET으로 날아오는 쿼리스트링 전체를 배열로 반환한다.
     *
     * @return array 쿼리스트링에 대한 key/value 배열
     */
    public static function rawParams()
    {
        $params = [];
        foreach($_GET as $k => $v) {
            if ($k == '_dc' || $k == 'query')
                continue;
            $params[$k] = $v;
        }
        return $params;
    }

    /**
     * gets/returns the value of a specific key of the REQUEST super-global
     *
     * @param mixed $key key
     * @return mixed the key's value or nothing
     */
    public static function request($key)
    {
        if (isset($_REQUEST[$key])) {
            return $_REQUEST[$key];
        }
    }

    /**
     * gets/returns the value of a specific key of the COOKIE super-global
     * @param mixed $key key
     * @return mixed the key's value or nothing
     */
    public static function cookie($key)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
    }

    /**
     * XML 요청 정보 파싱
     *
     * @return SimpleXmlElement SimpleXmlElement object
     */
    public static function xml()
    {
        $xml = file_get_contents('php://input');

        if ( empty($xml) ) {
            return false;
        }

        libxml_use_internal_errors(true);
        $request = simplexml_load_string($xml);
        if (!$request) {
            foreach(libxml_get_errors() as $error) {
                $err_msg .= $error->message . "\t";
            }

            //MSG02098 파싱에러
            throw new \Exception('xml '._text('MSG02098').': '.$err_msg);
        }

        return $request;
    }
}

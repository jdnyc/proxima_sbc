<?php

/**
 * HTTP 메소드를 이용한 CRUD를 수행할 때 조금 더 수월하게 사용하기 위한 클래스
 * 클로저를 이용하여 기능을 등록한다.
 */
namespace Proxima\core;

class ApiRequest
{
    private $functions = [];
    private $requests = [];

    private $cleanInput = false;

    public function __construct()
    {

    }

    public function get($func)
    {        
        $this->functions['get'] = $func;
    }

    public function post($func, $cleanInput = false)
    {
        $this->functions['post'] = $func;
        $this->cleanInput = $cleanInput;
    }

    public function put($func, $cleanInput = false)
    {
        $this->functions['put'] = $func;
        $this->cleanInput = $cleanInput;
    }

    public function delete($func, $cleanInput = false)
    {
        $this->functions['delete'] = $func;
        $this->cleanInput = $cleanInput;
    }

    public function controller($controllerName, $func, $cleanInput = false)
    {
        // 컨트롤러는 모두 post
        $this->functions[$controllerName] = $func;
        $this->cleanInput = $cleanInput;
    }

    public function run()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);
        $func = $this->functions[$method];
        
        switch($method)
        {
            case 'get':
                {                    
                    $input = Request::rawParams($_GET);
                    break;
                }
            case 'post':
            case 'put':
            case 'delete':
            default:
                {
                    $input = Request::getInputJsonFromRawValue($this->cleanInput, true);
                    break;
                }
        }

        $func($input);
    }

    /**
     * 요청 입력값 배열에서 값이 없는 항목을 제거한다.
     *
     * @param array $input
     * @return array
     */
    public static function getFilteredParams($input)
    {
        foreach($input as $key => $value) {
            if(empty($value)) {
                unset($input[$key]);
            }
        }
        return $input;
    }
}
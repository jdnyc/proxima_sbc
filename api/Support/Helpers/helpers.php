<?php


/**
 * 헬퍼 Function들
 */

/**
 * 마지막 쿼리 확인용
 */
if (!function_exists('dbd')) {
    function dbd($die = false)
    {
        \Illuminate\Database\Capsule\Manager::listen(
            function ($query) use ($die) {
                dump([
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
                if ($die) {
                    die();
                }
            }
        );
    }
}

if (!function_exists('config')) {
    /**
     * 환경설정 조회
     *
     * @param string $key settings의 키값으로 닷(.)으로 구분하여 depth에 관계없이 조회할 수 있다
     * @return mixed 리턴값은 배열 또는 값
     */
    function config($key)
    {
        $app = app();
        if (empty($app)) {
            return;
        }

        $settings = $app->getContainer()->get('settings');
        if (empty($key)) {
            return $settings;
        }

        $config = null;
        if(\Illuminate\Support\Str::contains($key, '.')) {
            $key = trim($key, '.');
            $keys = explode('.', $key);
            
            foreach($keys as $k) {
                if($config === null) {
                    $config = $settings[$k] ?? null;
                } else {
                    $config = $config[$k] ?? null;
                }
                if($config === null) {
                    return null;
                }
            }
        } else {
            $config = $settings[$key] ?? null;
        }

        return $config;
    }
}

/**
 * 인증
 */
if (!function_exists('auth')) {
    function auth()
    {
        $app = app();
        if (empty($app)) {
            return null;
        }
        return $app->getContainer()->get('auth');
    }
}

/**
 * app
 */
if (!function_exists('app')) {
    function app()
    {
        $app = \Api\Application::getApp();
        return $app;
    }
}

/**
 * request
 */
if (!function_exists('request')) {
    function request()
    {
        $app = app();
        if (empty($app)) {
            return null;
        }

        return $app->getContainer()->get('request');
    }
}

/**
 * response
 */
if (!function_exists('response')) {
    function response()
    {
        $app = app();
        if (empty($app)) {
            return null;
        }

        return $app->getContainer()->get('response');
    }
}

if (!function_exists('query')) {
    function query($query)
    {
        $helper = new \Api\Support\Helpers\QueryHelper($query);
        return $helper->query();
    }
}

if (!function_exists('paginate')) {
    /**
     * 페이지네이션
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    function paginate($query, $start = null, $limit = null )
    {
        $helper = new \Api\Support\Helpers\QueryHelper($query);
        return $helper->paginate($start, $limit);
    }
}

if (!function_exists('api_abort')) {
    function api_abort($message, $code = '', $status = 500)
    {
        throw new \Api\Exceptions\ApiException($message, $code, $status);
    }
}

if (!function_exists('api_abort_404')) {
    function api_abort_404($model)
    {
        $message = $model . ' not found.';
        api_abort($message, 'not_found', 404);
    }
}

if (!function_exists('yn_to_bool')) {
    function yn_to_bool($yn)
    {
        if (!isset($yn)) {
            return false;
        }

        if (!is_string($yn)) {
            return false;
        }

        return strtoupper($yn) === 'Y';
    }
}

if (!function_exists('bool_to_yn')) {
    function bool_to_yn($bool)
    {
        return $bool ? 'Y' : 'N';
    }
}

if (!function_exists('get_server_param')) {
    /**
     * 서버 변수 조회 함수
     *
     * @param [type] $key
     * @param [type] $default
     * @return void
     */
    function get_server_param($key, $default = null)
    {
        if (function_exists('request')) {
            if (request()) {
                $result = request()->getServerParam($key, $default);
            } else {
                $result = $default;
            }
        } else {
            $result = $_SERVER[$key];
        }
        return $result;
    }
}

if (!function_exists('dateToStr')) {
    /**
     * Date형식의 문자열을 지정된 포맷으로 출력하는 함수
     * 
     * @param string $dateStr 카본 파싱할 수 있는 날짜 문자열(예외 : now)
     * @param string $format 출력 포맷
     * 
     * @return string
     */
    function dateToStr($dateStr, $format = 'Y-m-d')
    {        
        $carbon = new \Carbon\Carbon($dateStr);
        return $carbon->format($format);
    }
}

if(!function_exists('server_os')) {
    /**
     * 서버 OS 유형
     *
     * @return string
     */
    function server_os() {
        if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'win';
        } if(strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
            return 'linux';
        }
        return 'unknown';
    }
}

/**
 * 접속 IP로 변경
 */
if(!function_exists('map_server_ip')) {
    function map_server_ip($srcUrl){
        $urlInfo = parse_url($srcUrl);

        $host = $urlInfo['host'];

        if( $host == '127.0.0.1' || $host == 'localhost' ){
            //접속 호스트명으로 변경
           // dd($_SERVER['HTTP_HOST']);
            if(  $_SERVER ){
                if ( $_SERVER['HTTP_HOST'] ) {
                    $host = $_SERVER['HTTP_HOST'];
                }
            }
            //외부 접속 도메인 ip
            $targetHosts = explode(",", config('sms_auth')['domain']);
            if( in_array($host, $targetHosts) ){
                $urlInfo['scheme'] = 'https';
                $host = 'send.g.ktv.go.kr';
                $urlInfo['port'] = '443';
            }else{
                $host = '10.10.50.132';
            }
        }else{
            //NAT IP 매핑 정의
        }

        $url = $urlInfo['scheme'] .'://'.$host;
        if( $urlInfo['port'] ){
            $url = $url .':'.$urlInfo['port'];
        }
        if( $urlInfo['path'] ){
            $url = $url.$urlInfo['path'];
        }

        if( $urlInfo['query'] ){
            $url = $url.'?'.$urlInfo['query'];
        }

        return $url;
    }
}

/**
 * Text 목록을 파싱해서 배열로 변경
 */
if( !function_exists('parseTextToArray') ){
    function parseTextToArray($text)
    {
        $textArray = explode("\n", $text);
        if( empty($textArray) || count($textArray) < 2 ){
            
            $textArray = explode("\r\n", $text);
            if (empty($textArray)) {
                return [];
            }else{
                foreach($textArray as $key => $text){
                    $textArray[$key] = trim($text);
                }
                return $textArray;
            }
        }else{
            foreach($textArray as $key => $text){
                $textArray[$key] = trim($text);
            }
            return $textArray;
        }
    }
}

/**
 * 로그 생성
 */
if( !function_exists('createLog') ){
    function createLog($action, $content_id, $description, $udContentId=null, $bsContentId=null, $user )
    {
        $log = new \Api\Models\Log();
        $log->action        = $action;
        $log->user_id       = $user->user_id;
        $log->created_date  = date("YmdHis");

        if($content_id && $udContentId == null || $bsContentId == null){
            
            $content = \Api\Models\Content::find($content_id);
            if( !empty($content) ){
                $bsContentId = $content->bs_content_id;
                $udContentId = $content->ud_content_id;
            }
        }

        $log->bs_content_id = $bsContentId;
        $log->ud_content_id = $udContentId;
        $log->content_id    = $content_id;
        $log->description   = $description;
        $log->client_ip   = get_server_param('REMOTE_ADDR', '127.0.0.1');      
        $log->save();
        return $log;
    }
}


if (!function_exists('checkCharset')) {
    function checkCharset($obj, $rule)
    {
        $chk = false;
        $obj = trim($obj);
        if ($obj) {
            //한글체크
            if ("kr" == $rule) {
                if (preg_match("/[\xA1-\xFE\xA1-\xFE]/", $obj)) {
                    $chk = true;
                }
            }

            //영문체크
            if ("en" == $rule) {
                if (preg_match("/[a-zA-Z]/", $obj)) {
                    $chk = true;
                }
            }

            //숫자체크
            if ("int" == $rule) {
                if (preg_match("/[0-9]/", $obj)) {
                    $chk = true;
                }
            }
        }
        return $chk;
    }
}

if (!function_exists('utf8_length')) {
    function utf8_length($str)
    {
        $len = strlen($str);
        for ($i = $length = 0; $i < $len; $length++) {
            $high = ord($str{$i});
            if ($high < 0x80) {//0<= code <128 범위의 문자(ASCII 문자)는 인덱스 1칸이동
                $i += 1;
            } elseif ($high < 0xE0) {//128 <= code < 224 범위의 문자(확장 ASCII 문자)는 인덱스 2칸이동
                $i += 2;
            } elseif ($high < 0xF0) {//224 <= code < 240 범위의 문자(유니코드 확장문자)는 인덱스 3칸이동
                $i += 3;
            } else {//그외 4칸이동 (미래에 나올문자)
                $i += 4;
            }
        }
        return $length;
    }
}

if (!function_exists('utf8_strcut')) {
    function utf8_strcut($str, $chars, $tail = null )
    {
        $tail = empty($tail)? '' : $tail;

        if (utf8_length($str) <= $chars) {//전체 길이를 불러올 수 있으면 tail을 제거한다.
            $tail = '';
        } else {
            $chars -= utf8_length($tail);
        }//글자가 잘리게 생겼다면 tail 문자열의 길이만큼 본문을 빼준다.
        $len = strlen($str);
        for ($i = $adapted = 0; $i < $len; $adapted = $i) {
            $high = ord($str{$i});
            if ($high < 0x80) {
                $i += 1;
            } elseif ($high < 0xE0) {
                $i += 2;
            } elseif ($high < 0xF0) {
                $i += 3;
            } else {
                $i += 4;
            }
            if (--$chars < 0) {
                break;
            }
        }
        return trim(substr($str, 0, $adapted)) . $tail;
    }
}
if (!function_exists('utf8_for_xml')) {
    function utf8_for_xml($string)
    {
        return preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $string);
    }
}


// 클라이언트 IP 가져오기
if (!function_exists('getClientIp')) {
    function getClientIp()
    { 
        $ipaddress= '';
        if(getenv('HTTP_X_FORWARDED_FOR')){
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        }else if (getenv('HTTP_CLIENT_IP')){
            $ipaddress = getenv('HTTP_CLIENT_IP');
        }else if(getenv('HTTP_X_FORWARDED')){
            $ipaddress = getenv('HTTP_X_FORWARDED');
        }else if(getenv('HTTP_FORWARDED_FOR')){
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        }else if(getenv('HTTP_FORWARDED')){
            $ipaddress = getenv('HTTP_FORWARDED');
        }else if(getenv('REMOTE_ADDR')){
            $ipaddress = getenv('REMOTE_ADDR');
        }else {
            $ipaddress='UNKNOWN';
        }
        return $ipaddress;
    }
}

// 클라이언트 IP 내부 외부 체크
if (!function_exists('checkInternalIp')) {
    function checkInternalIp()
    {
        $serverHost = get_server_param('HTTP_HOST');
        
        //대상 도메인
        $servers = explode(",", config('sms_auth')['domain']);

        // true : 내부, false : 외부
        return in_array($serverHost, $servers);
    }
}

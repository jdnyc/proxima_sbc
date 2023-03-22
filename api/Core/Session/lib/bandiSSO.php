<?php

namespace Api\Core\Session\lib;

class bandiSSO
{
    protected $sso_url, $client_id, $client_secret, $scope, $client_ip;
    protected $domain, $path;
    public $result;

    // 공통 변수
    public function __construct($sso_url, $scope, $client_id, $client_secret, $domain, $path = '/')
    {
        if (empty($sso_url)) {
            $this->sso_url = "http://sso.bandisnc.com:9091/oauth2/token.do";
        } else {
            $this->sso_url = $sso_url;
        }

        if (empty($scope)) {
            $this->scope = "http://sso.bandisnc.com";
        } else {
            $this->scope = $scope;
        }

        $this->client_ip = $_SERVER['REMOTE_ADDR'];

        // 해당 정보는 메일에 기입해 드린 문자열로 입력하시면 됩니다.
        if (empty($client_id)) {
            $this->client_id = "9bf985cbd05b47f787cf1c2fe329a7cc";
        } else {
            $this->client_id = $client_id;
        }

        if (empty($client_secret)) {
            $this->client_secret = "8whhw4xtt6imt7tyqbygmv3ab";
        } else {
            $this->client_secret = $client_secret;
        }
        // 해당 변수는 각 사이트에 맞게 설정해주시면 됩니다.
        // 쿠키 생성 시 사용되는 domain 입니다.
        if (empty($domain)) {
            $this->domain = ".bandisnc.com";
        } else {
            $this->domain = $domain;
        }

        if (empty($path)) {
            $this->path = "/";
        } else {
            $this->path = $path;
        }
    }
    
    /**
     * 인증 요청 ( 토큰 발급 )
     *
     * @param string $user_id 사용자 아이디
     * @param string $user_pwd 사용자 암호
     * @return array
     */
    public function getAccessToken($user_id, $user_pwd)
    {
        $parameters = array(
            'grant_type' => 'owner_password',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'user_id' => $user_id,
            'user_pwd' => $user_pwd,
            'scope' => $this->scope,
            'client_ip' => $this->client_ip
        );
        return $this->http_post($parameters);
    }
    
    /**
     * 토큰 재발급
     *
     * @param string $refresh_token 리프레시 토큰
     * @return void
     */
    public function refresh_token($refresh_token)
    {
        $parameters = array(
            'grant_type' => 'refresh_token',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->getToken('refresh_token', $refresh_token),
            'scope' => $this->scope,
            'client_ip' => $this->client_ip
        );

        return $this->http_post($parameters);
    }
    
    /**
     * 사용자 정보 요청
     *
     * @param string $access_token 엑세스 토큰
     * @return array
     */
    public function getUserInfo($access_token)
    {
        $parameters = array(
            'grant_type' => 'access_token_identify',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token' => $this->getToken('access_token', $access_token),
            'scope' => $this->scope,
            'client_ip' => $this->client_ip
        );

        return $this->http_post($parameters);
    }
    
    /**
     * 토큰 유효성 검사
     *
     * @param string $access_token
     * @return array
     */
    public function tokenValid($access_token)
    {
        $parameters = array(
            'grant_type' => 'token_valid',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token' => $this->getToken('access_token', $access_token),
            'scope' => $this->scope,
            'client_ip' => $this->client_ip
        );

        return $this->http_post($parameters);
    }

    /**
     * 로그아웃 ( 토큰 삭제 )
     *
     * @param string $access_token
     * @return array
     */
    public function removeToken($access_token)
    {
        $parameters = array(
            'grant_type' => 'logout',
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'access_token' => $this->getToken('access_token', $access_token),
            'scope' => $this->scope,
            'client_ip' => $this->client_ip
        );

        return $this->http_post($parameters);
    }
    
    /**
     * 로그인 시 호출 function
     *
     * @param string $user_id 사용자 아이디
     * @param string $user_pwd 사용자 암호
     * @return array
     */
    public function login($user_id, $user_pwd)
    {
        $result = $this->getAccessToken($user_id, $user_pwd);
        if ($result['error'] == '0000' || $result['error'] == 'VL-3130') {
            //$this->setSsoCookie('refresh_token',$result['refresh_token'],null);
            $this->setSsoCookie('access_token', $result['access_token'], $result['expires_in']);
        }
        return $result;
    }
    
    /**
     * 업무시스템에 사용자가 접근할 경우 사용하는 function
     *
     * @return array
     */
    public function validSSO()
    {
        $result = null;
        $refresh_token = $this->getToken('refresh_token', null);

        if ($refresh_token == null || $refresh_token == '') {
            $result = $this->setError('TK-3300', "REFRESH TOKEN이 존재하지 않습니다.");
        } else {
            $access_token = $this->getToken('access_token', null);
            if ($access_token == null || $access_token == '') {
                $result = $this->refresh_token($refresh_token);

                if ($result['error'] == '0000') {
                    $this->setSsoCookie('access_token', $result['access_token'], $result['expires_in']);
                } else {
                    $this->removeSsoCookie('access_token');
                    $this->removeSsoCookie('refresh_token');
                }
            } else {
                $result = $this->setError('0000', "정상처리되었습니다.");
            }
        }
        return $result;
    }

    private function http_post($parameters)
    {
        $ch = curl_init($this->sso_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json'
        ));
        $httpResult = curl_exec($ch);
        curl_close($ch);
        return json_decode($httpResult, true);
    }

    private function getToken($name, $value)
    {
        $token = $value;

        if ($value == '' || $value == null) {
            $token = $this->getSsoCookie($name);
        }

        return $token;
    }

    private function setError($error, $error_message)
    {
        $data = array(
            'error' => $error,
            'error_message' => $error_message
        );
        return $data;
    }

    private function setSsoCookie($name, $value, $expires_in)
    {
        if ($name == "refresh_token") {
            setCookie("refresh_token", $value, -1, $this->path, $this->domain);
        } else if ($name == "access_token") {
            setCookie("access_token", $value, (time() + 60), $this->path, $this->domain);
        }
    }

    private function getSsoCookie($name)
    {
        $result = null;

        if ($name == "refresh_token") {
            $result = $_COOKIE["refresh_token"];
        } else if ($name == "access_token") {
            $result = $_COOKIE["access_token"];
        }

        return $result;
    }

    private function removeSsoCookie($name)
    {
        if ($name == "refresh_token") {
            setCookie($name, '', (time() - 3600), $this->path, $this->domain);
        } else if ($name == "access_token") {
            setCookie($name, '', (time() - 3600), $this->path, $this->domain);
        }
    }
}

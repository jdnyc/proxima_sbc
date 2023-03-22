<?php

    namespace Api\Core\Session\lib;

	class bandiSSO_admin {
		protected $sso_url, $client_id, $client_secret;
		public $result;

		// 공통 변수
		function __construct($sso_url, $scope, $client_id, $client_secret, $domain, $path = '/') {
            if (empty($sso_url)) {
                $this->sso_url="http://10.10.110.206:8080";
            } else {
                $this->sso_url = $sso_url;
            }

			// 해당 정보는 메일에 기입해 드린 문자열로 입력하시면 됩니다.
            // 해당 정보는 메일에 기입해 드린 문자열로 입력하시면 됩니다.
            if (empty($client_id)) {
                $this->client_id = "6a599a73fbe44630890b2281e0bbaaf6";
            } else {
                $this->client_id = $client_id;
            }

            if (empty($client_secret)) {
                $this->client_secret = "rr8m79z6xw62xzmj91hj4sgg";
            } else {
                $this->client_secret = $client_secret;
            }
		}

		// 회원가입
		function createUser($user_id, $user_pwd, $nm, $email, $hpNo) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'userId'=>$user_id,
				'userPwd'=>$user_pwd,
				'userNm'=>$nm,
				'email'=>$email,
				'hpNo'=>$hpNo
			);

			$url = $this->sso_url."/ktv/createUser.do";

			return $this->http_post($url, $parameters);
        }
        
        		// 회원수정
		function updateUser($encUserId, $user_pwd, $nm, $email, $hpNo) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$encUserId,
				'userPwd'=>$user_pwd,
				'userNm'=>$nm,
				'email'=>$email,
				'hpNo'=>$hpNo
			);

			$url = $this->sso_url."/ktv/updateUser.do";

			return $this->http_post($url, $parameters);
		}

		// 회원삭제
		function deleteUser($enc_user_id) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$enc_user_id
			);

			$url = $this->sso_url."/ktv/deleteUser.do";

			return $this->http_post($url, $parameters);
		}

		// 비활성화
		function inactiveUser($enc_user_id) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$enc_user_id
			);

			$url = $this->sso_url."/ktv/inactiveUser.do";

			return $this->http_post($url, $parameters);
		}

		// 활성화
		function activeUser($enc_user_id) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$enc_user_id
			);

			$url = $this->sso_url."/ktv/activeUser.do";

			return $this->http_post($url, $parameters);
		}

		// 비밀번호 변경
		function updatePassword($enc_user_id, $user_pwd) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$enc_user_id,
				'userPwd'=>$user_pwd
			);

			$url = $this->sso_url."/ktv/updatePassword.do";

			return $this->http_post($url, $parameters);
		}

		// 사용자 단건 조회
		function selectUserById($enc_user_id) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$enc_user_id
			);

			$url = $this->sso_url."/ktv/selectUserById.do";

			return $this->http_post($url, $parameters);
		}

		// 사용자 단건 조회
		function selectAllUsers() {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret
			);

			$url = $this->sso_url."/ktv/selectAllUsers.do";

			return $this->http_post($url, $parameters);
        }
                
		// 사용자 인증 오류 해제
		function initAuth($enc_user_id) {
			$parameters = array(
				'clientId'=>$this->client_id,
				'clientSecret'=>$this->client_secret,
				'encUserId'=>$enc_user_id
			);

			$url = $this->sso_url."/ktv/initAuth.do";

			return $this->http_post($url, $parameters);
		}

		function http_post($url, $parameters) {
			$ch = curl_init($url);
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

		function setError($error, $error_message) {
			$data = array(
				'error'=>$error,
				'error_message'=>$error_message
			);
			return $data;
		}
	}
?>
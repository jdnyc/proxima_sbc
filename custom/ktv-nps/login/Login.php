<?php

namespace ProximaCustom\login;

if (!defined('DS'))
    define('DS', DIRECTORY_SEPARATOR);
require_once(dirname(__DIR__) . DS . 'lib' . DS . 'config.php');


// class LoginOk
// {
//     public function login($userId, $password, $loginType)
//     {
//         //AD나 iValue에 있지만, CMS에 사용자 정보가 없다면 기본그룹으로 지정해서 CMS등록
//         if ($ad_exists || $iv_exists) {
//             if (empty($user)) {
//                 if ($loginType == 'iv') {
//                     require_once(ROOT . "/DBOracle.class.php");
//                     $dbIV = new \DatabaseOracle(HM_IV_DB_ID, HM_IV_DB_PW, HM_IV_DB_IP . ':' . HM_IV_DB_PORT . '/' . HM_IV_DB_NAME);
//                     $userIV = $dbIV->queryRow("select * From v_cms_usr_d where empl_numb='$userId'");
//                 }
//                 $userData = [
//                     'user_id' => $userId,
//                     'user_nm' => $userIV['empl_name'],
//                     'dept_nm' => $userIV['dept_nm'],
//                     'email' => $userIV['email'],
//                     'password' => $userIV['pass_word'],
//                     'created_date' => date('YmdHis')
//                 ];
//                 $member = new \Proxima\models\user\User($userData);
//                 $member->save();
//                 $member = \Proxima\models\user\User::find($userId);
//                 $user = $member->getAll();
//             } else if ($user['del_yn'] == 'Y') {
//                 $member = \Proxima\models\user\User::find($userId);
//                 $member->set('del_yn', 'N');
//                 $member->save();
//                 $user = $member->getAll();
//             }
//         }

//         return $user;
//     }

//     private function makeSSOClient()
//     {
//         $dotenv = Dotenv::create(dirname(__DIR__), '.env');
//         $dotenv->load();
//         SSO_URL=http://10.10.110.206/oauth2/token.do
//         SSO_CLIENT_ID=6a599a73fbe44630890b2281e0bbaaf6
//         SSO_CLIENT_SECRET=8whhw4xtt6imt7tyqbygmv3ab
//         SSO_SCOPE=http://sso.ktv.co.kr
//         SSO_PATH=/
//         require_once(dirname(__DIR__) . '/lib/bandiSSO.class.php');
//         $sso = new \bandiSSO(
//             getenv('SSO_URL'),
//             getenv('SSO_CLIENT_ID'),
//             getenv('SSO_CLIENT_SECRET'),
//             getenv('SSO_SCOPE'),
//             getenv('SSO_PATH'),
//         );
//         $this->client = new bandiSSO([
//             'base_uri' => getenv('CAS_API_URL')
//         ]);
//     }
// }

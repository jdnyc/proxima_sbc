<?php

// Load .env file
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = \Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
} else {
    echo '.env does not exists';
    die();
}


return [
    'settings' => [
        'debug' => env('APP_DEBUG', false), // Enable woops
        'woops.editor' => 'vscode', // woops editor

        'displayErrorDetails'    => env('APP_DEBUG', false), // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // App Settings
        'app' => [
            'name' => env('APP_NAME', 'proxima-api'),
            'domain' => env('APP_DOMAIN', ''),
            'url'  => env('APP_URL', 'http://localhost'),
            'env'  => env('APP_ENV', 'local'),
            'ver'  => env('APP_VER', '3.0.0'),
        ],

        // Monolog settings
        'logger' => [
            'name'  => env('APP_NAME', 'proxima-api'),
            'path'  => isset($_ENV['docker']) ? 'php://stdout' : dirname(__DIR__) . '/log/app-'.date('Y-m-d').'.log',
            'level' => \Monolog\Logger::DEBUG,
        ],

        // Database settings
        'database' => [
            'logging' => env('DB_LOGGING', false),
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('bis'),
                \Api\Support\Helpers\DatabaseHelper::getSettings('diva')
            ]
        ],        

        'cors' => null !== env('CORS_ALLOWED_ORIGINS', '*'),

        // 인증할때 사용자 아이디 참조 필드명
        'app_auth_id' => env('APP_AUTH_ID', 'user_id'),

        // API KEY
        'api_key' => env('API_KEY', ''),

        // timezone
        'timezone' => env('TIMEZONE', 'Asia/Seoul'),

        // SSO
        'sso' => [
            'url' => env('SSO_URL'),
            'client_id' => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
            'scope' => env('SSO_SCOPE'),
            'admin_url' => env('SSO_ADMIN_URL'),
        ],

        #조디악 연동
        'zodiac' => [
            //연동 여부 우선 사용자 연동여부만
            'linkage' => env('ZODIAC_LINKAGE', false),
            'api_url' => env('ZODIAC_API_URL', false),
        ],
        
        #Open Directory 사용자 연동
        'od' => [
            //연동 여부
            'linkage' => env('OD_LINKAGE', false),
        ],

        #폴더 권한 연동 정보
        'auth_config' => [
            //연동 여부
            'linkage' => env('AUTH_CONFIG_LINKAGE', false),
            'fsname' => env('AUTH_CONFIG_FSNAME', 'BACKUP'),
            'auth_owner' =>  env('AUTH_CONFIG_AUTH_OWNER', 'ingest'),
            'path_auth_scratch' => '775',
            'path_auth_product' => '755',
            'group_news' => 'group_news',
            'group_product' => 'group_product',

            'prefix_path' => env('AUTH_CONFIG_PREFIX_PATH', '/Volumes/BACKUP/gemiso/Scratch'),            
            'mid_path' => env('AUTH_CONFIG_MID_PATH', 'gemiso/Scratch'),

            'mid_path_scratch' => env('AUTH_CONFIG_MID_PATH_SCRATCH', 'gemiso/Scratch'),
            'mid_path_news' => env('AUTH_CONFIG_MID_PATH_NEWS', 'gemiso/CMS/Video/News'),
            'mid_path_product' => env('AUTH_CONFIG_MID_PATH_PRODUCT', 'gemiso/CMS/Video/Product')
            
        ],

        # Session driver
        'session' => [
            'driver' => env('SESSION_DRIVER', 'file')          
        ],

        # Social api
        'social' => [
            'url' => env('SOCIAL_API_URL', ''),
            'publish_callback_url' => env('SOCIAL_PUBLISH_CALLBACK_URL', '')
        ],

        # 포털 배포용 URL
        'publish' => [
            'portal_url' => env('PORTAL_API_URL', ''),
            'api_http_url' => env('API_HTTP_URL', ''),
            'api_stream_url' => env('API_STREAM_URL', ''),
            'api_stream_hls_url' => env('API_STREAM_HLS_URL', ''),
            'api_stream_protocol' => env('API_STREAM_PROTOCOL', ''),
            'api_download_url' => env('API_DOWNLOAD_URL', '')
        ],

        # 플러그인 
        'plugin' => [ 
            'upload_path' => env('PLUGIN_UPLOAD_PATH', ''),
            'allow_flag' => explode(',',env('PLUGIN_ALLOW_FLAG', '')),
            'ban_user' => explode(',',env('PLUGIN_AUTO_BAN_USER', ''))
        ],
        //웹 업로드
        'web_upload' => [
            'url' => env('WEB_UPLOAD_URL', ''),
            'root_path' => env('WEB_UPLOAD_ROOT','')
        ],
        # 검색엔진
        'searcher' => [
            'url' =>  env('SEARCHER_URL', ''),
        ],
         # BIS
        'bis' => [
            'url' =>  env('BIS_SOAP_URL', ''),
            'mcr' =>  env('BIS_MCR_LINKAGE', ''),
            'user' =>  env('BIS_USER_LINKAGE', '')
        ],

        # 스트리밍 URL
        'streaming' => [
            'private_url' => env('STREAMING_PRIVATE_URL', ''),
            'public_url' => env('STREAMING_PUBLIC_URL', '')
        ],

        'sms' => [
            'task' => env('IS_SMS_TASK', ''),
        ],
        #로그인 2차 인증
        'sms_auth' => [
            'domain' =>env('SMS_AUTH_DOMAIN',''),
            'allow_user' =>env('SMS_AUTH_ALLOW_USER',''),
        ],
        'delete_policy' => [
            'nearline' => env('DELETE_POLICY_NEARLINE_PERCENT'),
        ],
        'diva_api' => [
            'base_url' => env('DIVA_REST_API'),
        ],
        'schedule' => [
            'archive_mig_cnt' => env('ARCHIVE_MIG_CNT'),
        ],
        #개인정보검출
        'easycerti' => [
            'url' => env('EASYCERTI_API_URL'),
        ]
    ],
];

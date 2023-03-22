<?php

namespace Api\Support\Helpers;

use Yajra\Oci8\Oci8Connection;
use Illuminate\Database\Capsule\Manager as Capsule;
use Yajra\Oci8\Connectors\OracleConnector;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class DatabaseHelper
{
    /**
     * 데이터 베이스 매니저 생성
     *
     * @param array $settings
     * @return Illuminate\Database\Capsule\Manager
     */
    public static function getConnection($settings)
    {
        if(empty($settings) || empty($settings['connections'])) {
            throw new \Exception('Setting should not empty.');
        }
        
        $capsule = null;
        foreach($settings['connections'] as $setting) {
            $config = [
                'driver'       => $setting['driver'],
                'host'         => $setting['host'],
                'port'         => $setting['port'],
                'database'     => $setting['database'],
                'username'     => $setting['username'],
                'password'     => $setting['password'],
                'charset'      => $setting['charset'],
                'prefix'       => $setting['prefix'],
                'server_version' => $setting['server_version'],
                'options' => [ 12 => true ] //pconnect 옵션
            ];
    
            if (!empty($setting['service_name'])) {
                $config['service_name'] = $setting['service_name'];
            }
    
            if($capsule === null) {
                $capsule = new Capsule();
            }
            $capsule->addConnection($config, $setting['name']);

            if ($config['driver'] === 'oracle') {                
                $capsule->getDatabaseManager()->extend('oracle', function ($config) {                    
                    $connector = new OracleConnector();
                    $connection = $connector->connect($config);
                    $db = new Oci8Connection($connection, $config['database'], $config['prefix']);
    
                    // set oracle session variables
                    $sessionVars = [
                        'NLS_TIME_FORMAT'         => 'HH24:MI:SS',
                        'NLS_DATE_FORMAT'         => 'YYYY-MM-DD HH24:MI:SS',
                        'NLS_TIMESTAMP_FORMAT'    => 'YYYY-MM-DD HH24:MI:SS',
                        'NLS_TIMESTAMP_TZ_FORMAT' => 'YYYY-MM-DD HH24:MI:SS TZH:TZM',
                        'NLS_NUMERIC_CHARACTERS'  => '.,',
                    ];
    
                    // Like Postgres, Oracle allows the concept of "schema"
                    if (isset($config['schema'])) {
                        $sessionVars['CURRENT_SCHEMA'] = $config['schema'];
                    }
    
                    $db->setSessionVars($sessionVars);
    
                    return $db;
                });
            }
        }       

        $capsule->setEventDispatcher(new Dispatcher(new Container));
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        // begin init SQL logger
        if ($settings['logging']) {
            $dispatcher = $capsule->getEventDispatcher();
            $dispatcher->listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
                $msg =  "== SQL: " . $query->sql . "\n";
                $msg .= "== Params: " . join(', ', $query->bindings);
                $msg .= "\n\n";
                // if code is executed in CLI, echo message
                if (php_sapi_name() == 'cli') {
                    echo $msg;
                }
                // if code executed by server, log message so stderr
                else {
                    $msg = "[" . date("Y-m-d H:i:s") . "]\n" . $msg;
                    $logDir = dirname(dirname(dirname(__DIR__))) . '/log';
                    if (!file_exists($logDir)) {
                        @mkdir($logDir);
                    }
                    file_put_contents($logDir . '/db-'.date('Y-m-d').'.log', $msg, FILE_APPEND); // log into file
                    error_log($msg); // log into stderr. usable in php builtin server
                }
            });
        }

        return $capsule;
    }

    /**
     * 데이터베이스 설정 조회
     *
     * @param string $serviceName
     * @return array
     */
    public static function getSettings($serviceName = 'default')
    {
        $suffix = '';
        if(!empty($serviceName) && $serviceName !== 'default') {
            $suffix = '_' . strtoupper($serviceName);
        }
        $settings = [
            'name'      => $serviceName,
            'driver'    => env('DB_CONNECTION' . $suffix, ''),
            'host'      => env('DB_HOST' . $suffix, ''),
            'database'  => env('DB_DATABASE' . $suffix, ''),
            'username'  => env('DB_USERNAME' . $suffix, ''),
            'password'  => env('DB_PASSWORD' . $suffix, ''),
            'port'      => env('DB_PORT' . $suffix, ''),
            'charset'   => env('DB_CHARSET' . $suffix, ''),
            'collation' => env('DB_COLLATION' . $suffix, 'utf8_unicode_ci'),
            'prefix'    => env('DB_PREFIX' . $suffix, ''),
            'server_version' => env('DB_SERVER_VERSION' . $suffix, ''),
        ];

        return $settings;
    }
}

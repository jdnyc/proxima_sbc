<?php
/**
 * Created by PhpStorm.
 * User: cerori
 * Date: 2015-01-15
 * Time: 오후 4:06
 */

use Carbon\Carbon;
use Cron\CronExpression;
use Api\Services\IngestService;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\RotatingFileHandler;

$server->register('GetIngestSchedule',
    array(
        'request' => 'xsd:string'
    ),
    array(
        'response' => 'xsd:string'
    ),
    $namespace,
    $namespace.'#GetIngestSchedule',
    'rpc',
    'encoded',
    'GetIngestSchedule'
);

function GetIngestSchedule($json_str) {
    global $server, $logger;

    $logger->pushHandler(new RotatingFileHandler(BASEDIR . '/log/func_' . __FUNCTION__ . '.log', 14));
    $logger->addInfo('start:'.$json_str);
    $definition = array(
        'system_host' => array('type' => 'string', 'required' => true),
        'channel' =>  array('type' => 'string', 'required' => true),
        'from_date' => array('type' => 'date', 'required' => true, 'format' => 'YmdHi'),
        'to_date' => array('type' => 'date', 'required' => true, 'format' => 'YmdHi'),
        'current_date' => array('type' => 'date', 'required' => true, 'format' => 'YmdHi'),
        'user_id' => array('type' => 'string', 'required' => true),
        'user_ip' => array('type' => 'string', 'required' => true)
    );

    try {

        // 데이터 검증
        $params = validator($definition, $json_str);
        $system_host = $params['system_host'];
        $channel = $params['channel'];
        $base_datetime = Carbon::createFromFormat('YmdHi', $params['current_date']);
        $from_datetime = Carbon::createFromFormat('YmdHi', $params['from_date']);
        $to_datetime = Carbon::createFromFormat('YmdHi', $params['to_date']);

        // $settings = [
        //     'logging' => false,
        //     'connections' => [         
        //         \Api\Support\Helpers\DatabaseHelper::getSettings(),
        //     ],
        // ];

        // $capsule = \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        $container = app()->getContainer();


        $schedules = array();

        // throw new RuntimeException('ddd');
        
        $ingestService =  new IngestService($container);
        $result =  $ingestService->list($params);

        
        foreach ($result as $schedule) {
            $schedule = (array)$schedule;
            $cron = CronExpression::factory($schedule['cron']);
            try{
                if (($next = $cron->getNextRunDate($base_datetime))) {
                    $next_run_datetime = $next->format('YmdHis');
                    if ($next_run_datetime < $to_datetime->format('YmdHis')) {
                        $schedule['sdate'] = $next->format('Ymd');
                        $schedule['date_time'] = $next->format('Ymd');
                        $schedule['sort'] = $next->format('YmdHis');
                        array_push($schedules, $schedule);
                    }
                }
            }catch (InvalidArgumentException $e) {
                // nothing
            } catch (RuntimeException $e) {    
                // nothing
            }catch (\Exception $e) {    
                // nothing
            }
            
        }

        // 날짜 정렬
        if ( ! empty($schedules)) {
            foreach ($schedules as $key => $row) {
                $sort[$key] = $row['sort'];
            }

            array_multisort($sort, SORT_ASC, $schedules);
        }

        $result = array(
            'success' => true,
            'status' =>  0,
            'message' => "OK",
            'schedules' => $schedules
        );

    } catch (Exception $e) {
        $result = array(
            'success' => false,
            'message' => $e->getMessage(),
            'status' => 1
        );
    }

    $result = json_encode($result);

    $logger->addInfo($result);

    return $result;
}
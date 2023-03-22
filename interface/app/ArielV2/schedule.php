<?php
require_once '../vendor/autoload.php';

require_once '../lib/config.php';
require_once '../lib/functions.php';
require_once '../lib/interface.class.php';
require_once '../lib/MetaData.class.php';
require_once '../lib/soap/nusoap.php';
require_once '../lib/Search.class.php';
require_once '../workflow/lib/task_manager.php';
require_once 'app/insertMetadata.php';

use Slim\Slim;
use Carbon\Carbon;
use Cron\CronExpression;
use Stringy\Stringy as S;

$app = new Slim();

$app->post('/get', function() use($app, $db) {
    $params = $app->request->post();

    $system_host = $params['system_host'];
    $channel = $params['channel'];

    $base_datetime = Carbon::createFromFormat('YmdHi', $params['current_date']);
    $from_datetime = Carbon::createFromFormat('YmdHi', $params['from_date']);
    $to_datetime = Carbon::createFromFormat('YmdHi', $params['to_date']);

    try {

        $schedules = array();

        $result = $db->queryAll("
            SELECT *
              FROM INGESTMANAGER_SCHEDULE
             WHERE IS_USE = 1
               AND INGEST_SYSTEM_IP = '$system_host'
               AND CHANNEL = '$channel'
        ");

        foreach ($result as $schedule) {
            try {
				$cron = CronExpression::factory($schedule['cron']);
                if (($next = $cron->getNextRunDate($base_datetime))) {
                    $next_run_datetime = $next->format('YmdHis');
					if ($next_run_datetime < $to_datetime->format('YmdHis')) {
						$schedule['sdate'] = $next->format('Ymd');
                        $schedule['date_time'] = $next->format('Ymd');
                        $schedule['sort'] = $next->format('YmdHis');
						array_push($schedules, $schedule);
					}
                }
            } catch (InvalidArgumentException $e) {


                // nothing
            } catch (RuntimeException $e) {

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

        echo json_encode(array(
		  'success' => true,
		  'status' =>  0,
		  'message' => "OK",
		  'schedules' => $schedules
		));

    } catch (Exception $e) {
        echo json_encode(array(
		  'success' => false,
		  'status' =>  1,
		  'message' => $e->getMessage()
		));
	}
});

$app->put('/set/queued/:id/:filename', function($id, $filename) use($app, $db) {
		$table_schedule = 'INGESTMANAGER_SCHEDULE';
		$table_schedule_metadata = 'INGESTMANAGER_SCHEDULE_META';

        $schedule = $db->queryOne("select * from $table_schedule where schedule_id = $id");

		$metadata = $db->queryOne("select * from $table_schedule_metadata where schedule_id = $id");

        $obj = new stdClass;

        $obj->inserttype = 0;
        $obj->requestmeta = new stdClass;
        $obj->requestmeta->user_id = $schedule['user_id'];
        $obj->requestmeta->flag = 'ingest';
        $obj->requestmeta->metadata_type = 'id';
        $obj->requestmeta->metadata = array(new StdClass);
        $obj->requestmeta->metadata[0]->k_content_id = '';
        $obj->requestmeta->metadata[0]->k_topic_content_id = '';
        $obj->requestmeta->metadata[0]->k_ud_content_id = $schedule['ud_content_id'];
        $obj->requestmeta->metadata[0]->k_title = $schedule['title'];
        $obj->requestmeta->metadata[0]->c_category_id = $schedule['category_id'];
		foreach ($metadata as $n => $row) {
	        $obj->requestmeta->metadata[0]->$row['bc_usr_meta_field_id'] = $row['usr_meta_value'];
		}
        $obj->requestmeta->filename = $filename;

        $result = insertMetadata(json_encode($obj));

		echo $result;
});

$app->run();

function isgetScheduleSpecialDay($ip, $channel, $type, $date) {
    global $db;

    $result = $db->queryOne("
        SELECT count(*)
        FROM INGESTMANAGER_SCHEDULE
        WHERE INGEST_SYSTEM_IP = '$ip'
        AND CHANNEL = '$channel'
        AND SCHEDULE_TYPE = '$type'
        AND DATE_TIME = '$date'
    ");

    if ($result->fetchSingle() > 0) {
        return true;
    } else {
        return false;
    }
}

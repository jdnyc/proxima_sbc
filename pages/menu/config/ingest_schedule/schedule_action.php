<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/config.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/functions.php';
require_once $_SERVER['DOCUMENT_ROOT'].'/lib/Schedule.class.php';

$type_render = array(
	'오늘' => 0,
	'매일' => 1,
	'요일 반복' => 2,
	'일자 반복' => 3
);

$week_render = array(
	'일요일' => 0,
	'월요일' => 1,
	'화요일' => 2,
	'수요일' => 3,
	'목요일' => 4,
	'금요일' => 5,
	'토요일' => 6,
	'주중'	=> 7,
	'주말'	=> 8
);

$ingest_ip_render = array(
	'인제스트1' => '192.168.1.160'
);

$channel_render = array(
	'0번 채널' => 0,
	'1번 채널' => 1
);

try {
	/*
	   [schedule_id] => 88
    [title] => hhh
    [ingest_system_ip] => 192.168.1.160
    [channel] => 0
    [schedule_type] => 요일 반복
    [date_time1] => 일요일
    [date_time2] =>
    [start_time] => 14:24:45
    [duration] => 00:10:00
    [is_use] => 1
    [ud_content_id] => NPS
    [k_ud_content_id] => 207
    [c_category_id] => NPS > NPS > 편집
    [645] =>
    [646] =>
    [647] =>
    [648] =>
    [650] =>
    [651] =>
    [652] =>
    [653] =>
    [654] =>
    [category_id] => 4589633
	*/

    $logger->addInfo('params', $_REQUEST);

	switch ($_POST['action']) {
		case 'add':
			$params = $_POST['params'];
			$data = json_decode($params, true);

			$schedule_id =  getSequence('im_schedule_seq');
			$title = $db->escape($data['title']);
			$ingest_system_ip = $data['ingest_system_ip'];
			$channel = $data['channel'];
			$ud_content_id = $data['ud_content_id'];
			$ud_content_tab = $data['ud_content_tab'];

			$schedule_type = $data['schedule_type'];

			if (is_null($schedule_type)) throw new Exception('작업타입 오류');

			// 주간 일때
			if($schedule_type == 2) {
				$date_time = $data['date_time1'];
			} else {
				$date_time = str_replace('-','',$data['date_time2']);
			}

			if (empty($data['start_time']))  throw new Exception('작업시작시간정보 오류');
			if ( ! strtotime($data['start_time']))  throw new Exception('작업시작시간정보 오류');

			$start_time =  Date('His', strtotime(trim($data['start_time'])));

			if (empty($data['duration']) )  throw new Exception('재생길이정보 오류');
			if ( ! strtotime($data['duration']) )  throw new Exception('재생길이정보 오류');

			$duration = Date( 'His', strtotime(trim($data['duration'])));

			$dh = substr($duration , 0, 2);
			$di = substr($duration , 2, 2);
			$ds = substr($duration , 4, 2);

			$duration = ($dh * 3600) + ($di * 60) + $ds;

			$is_use = $data['is_use'];

			$create_time = date("YmdHis");
			$status = 0;
			$bs_content_id = 506;
			$user_id = $_SESSION['user']['user_id'];

			$category_id = $data['category_id'];
			if (is_null($category_id ) || $category_id=='0') {
				$category_id = $db->queryOne("select category_id from BC_CATEGORY_MAPPING where ud_content_id='$ud_content_id'");
			}

			if (is_null($user_id) || $user_id == 'temp') {
				 throw new Exception('유저정보가 없습니다. 로그인이 필요합니다.');
			}

			$schedule_list = $db->queryAll("select * from ingestmanager_schedule where INGEST_SYSTEM_IP='$ingest_system_ip' and CHANNEL='$channel' and is_use='1'");

			// if (duplicateCheck($schedule_list, $schedule_type, $date_time, $start_time, $duration )) {
			// 	throw new Exception('시간정보가 중복되는 스케줄이 존재합니다.');
			// }

			$schedule = new Schedule();
            switch ($schedule_type) {

                // 지정일 한번
                case 0:
                    $schedule->specifyDay($data['date_time2'], $data['start_time']);
                    break;

                // 매일 반복
                case 1:
                    $schedule->daily($data['start_time']);
                    break;

                // 주 반복
                case 2:
                    if (is_array($data['day_of_week'])) {
                        $date_time = join(',', $data['day_of_week']);
                    } else {
                        $date_time = $data['day_of_week'];
                    }
                    $schedule->weekly($date_time, $data['start_time']);
                    break;

                // 기간 지정
                case 3:
                    $schedule->term($data['start_date'], $data['end_date'], $data['start_time']);
                    break;
            }

            $cronExpression = $schedule->getCronExpression();

			$db->insert('ingestmanager_schedule', array(
                'SCHEDULE_ID' => $schedule_id,
                'INGEST_SYSTEM_IP' => $ingest_system_ip,
                'CHANNEL' => $channel,
                'SCHEDULE_TYPE' => $schedule_type,
                'DATE_TIME' => $date_time,
                'START_TIME' => $start_time,
                'DURATION' => $duration,
                'CREATE_TIME' => $create_time,
                'STATUS' => $status,
                'CATEGORY_ID' => $category_id,
                'BS_CONTENT_ID' => $bs_content_id,
                'UD_CONTENT_ID' => $ud_content_id,
                'UD_CONTENT_TAB' => $ud_content_tab,
                'TITLE' => $title,
                'USER_ID' => $user_id,
                'IS_USE' => $is_use,
                'CRON' => $cronExpression
            ));

			$field_info = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='$ud_content_id' order by show_order");

			foreach ($field_info as $info) {
				$r = $db->exec("insert into im_schedule_metadata (SCHEDULE_ID, UD_CONTENT_ID, BC_USR_META_FIELD_ID, USR_META_VALUE )
									values ('$schedule_id', '$ud_content_id', '{$info['usr_meta_field_id']}','')");
			}

			$field_type = array();
			foreach ($field_info as $info) {
				$field_type [$info['usr_meta_field_id']] = $info['usr_meta_field_type'];
            }
            

            $metadata = json_encode($data['values']);
           
            $container = app()->getContainer();
            $qb = $container->get('db');
            $qb::table('ingestmanager_schedule_meta')->insert(
                 ['schedule_id' => $schedule_id, 'metadata' => $metadata]
            );

			foreach ($data['values'] as $field_id => $value) {
				if ( ! is_numeric($field_id)) continue;

				$value = $db->escape($value);

				if ($field_type[$field_id] == 'datefield') {
					if ( ! is_null($value)) {
						if (strtotime($value)) {
							$value = date('YmdHis', strtotime($value));
						}
					}
				}

				$r = $db->exec("update im_schedule_metadata set usr_meta_value='$value' where schedule_id='$schedule_id' and bc_usr_meta_field_id='$field_id'");
			}

			$msg = '추가 작업 성공';
		break;

		case 'edit':
			$params = $_POST['params'];
			$data = json_decode($params, true);

			$schedule_id = $data['schedule_id'];
			$title = $db->escape($data['title']);
			$ingest_system_ip = $data['ingest_system_ip'];
			$channel = $data['channel'];
			$ud_content_id = $data['ud_content_id'];
			$ud_content_tab = $data['ud_content_tab'];
			$schedule_type = $data['schedule_type'];

			if (is_null($schedule_type)) throw new Exception('작업타입 오류');

			// 주간 일때
			if ($schedule_type == 2) {
				$date_time =$data['date_time1'];
			} else {
				$date_time = str_replace('-', '', $data['date_time2']);
			}

			if (empty($data['start_time'])) throw new Exception('작업시작시간정보 오류');
			if ( ! strtotime($data['start_time'])) throw new Exception('작업시작시간정보 오류');

			$start_time =  Date('His', strtotime(trim($data['start_time'])));

			if (empty($data['duration'])) throw new Exception('재생길이정보 오류');
			if ( ! strtotime($data['duration'])) throw new Exception('재생길이정보 오류');

			$duration = Date( 'His', strtotime(trim($data['duration'])));

			$dh = substr($duration , 0, 2);
			$di = substr($duration , 2, 2);
			$ds = substr($duration , 4, 2);

			$duration = ($dh * 3600) + ($di * 60) + $ds;

			$is_use = $data['is_use'];

			$old_info = $db->queryRow("select * from ingestmanager_schedule where schedule_id='$schedule_id'");

			$old_ud_content_id = $old_info['ud_content_id'];
			$category_id = $old_info['category_id'];

			$schedule_list = $db->queryAll("select * from ingestmanager_schedule where INGEST_SYSTEM_IP='$ingest_system_ip' and CHANNEL='$channel' and is_use='1' and schedule_id!='$schedule_id'");

			if (duplicateCheck($schedule_list, $schedule_type, $date_time, $start_time, $duration)) {
				throw new Exception('시간정보가 중복되는 스케줄이 존재합니다.');
			}

            $schedule = new Schedule();
            switch ($schedule_type) {

                // 지정일 한번
                case 0:
                    $schedule->specifyDay($data['date_time2'], $data['start_time']);
                    break;

                // 매일 반복
                case 1:
                    $schedule->daily($data['start_time']);
                    break;

                // 주 반복
                case 2:
                    if (is_array($data['day_of_week'])) {
                        $date_time = join(',', $data['day_of_week']);
                    } else {
                        $date_time = $data['day_of_week'];
                    }
                    $schedule->weekly($date_time, $data['start_time']);
                    break;

                // 기간 지정
                case 3:
                    $schedule->term($data['start_date'], $data['end_date'], $data['start_time']);
                    break;
            }

            $cronExpression = $schedule->getCronExpression();

			if ( ! is_null($data['category_id'])) {
				$category_id = $data['category_id'];
			}

			$field_info = $db->queryAll("select * from bc_usr_meta_field where ud_content_id='{$ud_content_id}' order by show_order");

			// 콘텐츠 유형이 변경 된 경우
			if ($old_ud_content_id != $ud_content_id ) {
				$r = $db->exec("delete from im_schedule_metadata where schedule_id='$schedule_id'");

				foreach ($field_info as $info) {
					$db->exec("insert into im_schedule_metadata
										(SCHEDULE_ID,UD_CONTENT_ID,BC_USR_META_FIELD_ID,USR_META_VALUE )
									values
										('$schedule_id', '$ud_content_id', '{$info['usr_meta_field_id']}', '')");
				}
			}

			$valueList=$db->queryAll("
				select
					ism.*
				from
					ingestmanager_schedule isc,
					im_schedule_metadata ism
				where
					isc.schedule_id=ism.schedule_id
				and isc.schedule_id='$schedule_id'");

			$field_list = array();
			foreach ($valueList as $list) {
				$field_list[$list['usr_meta_field_id']] = $list['bc_usr_meta_field_id'];
			}

			$field_type = array();
			foreach ($field_info as $info) {
				$field_type [$info['usr_meta_field_id']] = $info['usr_meta_field_type'];
            }
            
            

            $metadata = json_encode($data['values']);
           
            $container = app()->getContainer();
            $qb = $container->get('db');
            $qb::table('ingestmanager_schedule_meta')->where('schedule_id' , $schedule_id)->update(
                 ['metadata' => $metadata]
            );

			foreach ($data['values'] as $field_id => $value) {
				if ( ! is_numeric($field_id) ) continue;

				$value = $db->escape($value);

				if ($field_type[$field_id] == 'datefield') {
					if ( ! is_null($value)) {
						if (strtotime($value)) {
							$value= date('YmdHis', strtotime($value));
						}
					}
				}

				$check_field_exs = $db->queryRow("select * from im_schedule_metadata where SCHEDULE_ID='$schedule_id' and UD_CONTENT_ID='$ud_content_id' and BC_USR_META_FIELD_ID='$field_id'");

				// 추가
				if (empty($check_field_exs)) {
					$r = $db->exec("insert into im_schedule_metadata (SCHEDULE_ID,UD_CONTENT_ID,BC_USR_META_FIELD_ID,USR_META_VALUE ) values ('$schedule_id','$ud_content_id','$field_id','$value')");
				} else {
					// 수정
					$r = $db->exec("update im_schedule_metadata set usr_meta_value='$value' where schedule_id='$schedule_id' and bc_usr_meta_field_id='$field_id'");
				}
			}

			$db->update('INGESTMANAGER_SCHEDULE', array(
                'TITLE' => $title,
                'INGEST_SYSTEM_IP' => $ingest_system_ip,
                'CHANNEL' => $channel,
                'SCHEDULE_TYPE' => $schedule_type,
                'DATE_TIME' => $date_time,
                'START_TIME' => $start_time,
                'DURATION' => $duration,
                'IS_USE' => $is_use,
                'UD_CONTENT_TAB' => $ud_content_tab,
                'UD_CONTENT_ID' => $ud_content_id,
                'CATEGORY_ID' => $category_id,
                'CRON' => $cronExpression
            ), "SCHEDULE_ID = $schedule_id");

			$msg = '수정 작업 성공';
		break;

		case 'del':
            $schedules = json_decode($_POST['schedules'], true);

            if (empty($schedules)) {
                throw new Exception('삭제하실 스케줄 항목을 선택하세요.');
            }

            foreach ($schedules as $schedule) {
			    $db->exec("delete from ingestmanager_schedule where schedule_id='{$schedule['schedule_id']}'");
            }

            $msg = '삭제 작업 성공';

		break;

		default:
			 throw new Exception('action 정보가 없습니다.');
		break;
	}

	echo json_encode(array(
		'success' => true,
		'msg' => $msg
	));
} catch ( Exception $e ) {
	echo json_encode(array(
		'success' => false,
		'msg' => $e->getMessage()
	));
}

function duplicateCheck($schedule_list, $type , $date , $time , $duration) {

	foreach ($schedule_list as $schedule) {

		// 일회성 .. 시간만 체크 하면 됨
		if ($type == 0) {
			$targetWeek = date("W", strtotime( $date.$time ) );

			// 주간 반복 스케줄들
			if ($schedule['schedule_type'] == 2 )  {

				// 같은 요일일떄
				if (date("W") == $schedule['date_time']) {
					if (strtotime( $time ) == strtotime( $schedule['start_time'])) {

						// 시작시각이 같으면 안됨
						return true;
					} elseif (strtotime( $time ) <  strtotime( $schedule['start_time'])) {
						if ((strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'])) {
							return true;
						}
					} elseif (strtotime( $time ) > strtotime( $schedule['start_time'])) {
						if (strtotime( $time ) <= (strtotime( $schedule['start_time']) + $schedule['duration'])) {
							return true;
						}
					}

				// 주말일때
				} elseif ((date("W") == 0 || date("W") == 6) && ($schedule['date_time'] == 8)) {

					// 시작시각이 같으면 안됨
					if (strtotime( $time ) == strtotime($schedule['start_time'])) {
						return true;
					} elseif (strtotime( $time ) <  strtotime( $schedule['start_time'])) {
						if ((strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'])) {
							return true;
						}
					} elseif (strtotime( $time ) > strtotime( $schedule['start_time'])) {
						if (strtotime($time) <= (strtotime($schedule['start_time']) + $schedule['duration'])) {
							return true;
						}
					}

				// 평일일때
				} elseif ((date("W") == 1 || date("W") == 2 || date("W") == 3 || date("W") == 4 || date("W") == 5 )  && ( $schedule['date_time'] == 7 ) ) {

					// 시작시각이 같으면 안됨
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) ) {
						return true;
					} elseif (strtotime( $time ) <  strtotime( $schedule['start_time'] ) ) {
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) ) {
							return true;
						}
					} else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) ) {
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) ) {
							return true;
						}
					}
				}
			} else if( $schedule['schedule_type'] == 0 ) {

				// 동일한 날짜
				if( $schedule['date_time'] == $date )  {
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					{//시작시각이 같으면 안됨
						return true;
					}
					else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					{
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						{
							return true;
						}
					}
					else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					{
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						{
							return true;
						}
					}
				}
			}
			else if( $schedule['schedule_type'] == 1 )
			{
				if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
				{//시작시각이 같으면 안됨
					return true;
				}
				else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
				{
					if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
					{
						return true;
					}
				}
				else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
				{
					if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
					{
						return true;
					}
				}
			}
		}
		else if($type == 1) //매일
		{
			if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
			{//시작시각이 같으면 안됨
				return true;
			}
			else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
			{
				if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
				{
					return true;
				}
			}
			else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
			{
				if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
				{
					return true;
				}
			}
		}
		else if($type == 2) //주간반복
		{

			if($schedule['schedule_type'] == 2) //주간 반복 스케줄들
			{
				if($date == $schedule['date_time'])//같은 요일일때
				{
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					{//시작시각이 같으면 안됨
						return true;
					}
					else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					{
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						{
							return true;
						}
					}
					else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					{
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						{
							return true;
						}
					}
				}
				else if( ( $date == 0 || $date == 6 ) && ( $schedule['date_time'] == 8 ) )//주말일때
				{
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					{//시작시각이 같으면 안됨
						return true;
					}
					else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					{
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						{
							return true;
						}
					}
					else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					{
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						{
							return true;
						}
					}
				}
				else if( ( $date == 1 || $date == 2 || $date == 3 || $date == 4 || $date == 5 )  && ( $schedule['date_time'] == 7 ) )//평일일때
				{
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					{//시작시각이 같으면 안됨
						return true;
					}
					else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					{
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						{
							return true;
						}
					}
					else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					{
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						{
							return true;
						}
					}
				}
				else if( $date == 7 && ( $schedule['date_time'] == 1 || $schedule['date_time'] == 2  || $schedule['date_time'] == 3  || $schedule['date_time'] == 4  || $schedule['date_time'] == 5 ))
				{
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					{//시작시각이 같으면 안됨
						return true;
					}
					else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					{
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						{
							return true;
						}
					}
					else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					{
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						{
							return true;
						}
					}
				}
				else if( $date == 8 && ( $schedule['date_time'] == 0 || $schedule['date_time'] == 6 ) )
				{
					if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
					{//시작시각이 같으면 안됨
						return true;
					}
					else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
					{
						if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
						{
							return true;
						}
					}
					else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
					{
						if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
						{
							return true;
						}
					}
				}
			}
			else if( $schedule['schedule_type'] == 1 )
			{
				if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
				{//시작시각이 같으면 안됨
					return true;
				}
				else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
				{
					if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
					{
						return true;
					}
				}
				else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
				{
					if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
					{
						return true;
					}
				}
			}
			else if( $schedule['schedule_type'] == 0 )
			{
				if( strtotime( $time ) == strtotime( $schedule['start_time'] ) )
				{//시작시각이 같으면 안됨
					return true;
				}
				else if( strtotime( $time ) <  strtotime( $schedule['start_time'] ) )
				{
					if( ( strtotime( $time ) +  $duration ) >=  strtotime( $schedule['start_time'] ) )
					{
						return true;
					}
				}
				else if( strtotime( $time ) > strtotime( $schedule['start_time'] ) )
				{
					if( strtotime( $time ) <= ( strtotime( $schedule['start_time'] ) + $schedule['duration'] ) )
					{
						return true;
					}
				}
			}
		}
	}

	return false;
}


?>

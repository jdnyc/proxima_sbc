<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
try
{

	$req_str = file_get_contents('php://input');
//	$req_str = '<get_schedule action="get_list" channel="1" schedule_type="0" date_time="20111121" start_time="093000" />';
	$created = date("Ymd");

	if (empty($req_str))
	{
		throw new Exception('요청값이 없습니다.');
	}

	libxml_use_internal_errors(true);
	$req_xml = simplexml_load_string($req_str);

	if (!$req_xml)
	{
		throw new Exception(libxml_get_last_error()->message);
	}

	file_put_contents(LOG_PATH.'/get_schedule_'.$created.'.html', date("Y-m-d H:i:s\t").$req_str."\n\n", FILE_APPEND);

//	if ($db->supports('transactions'))
//	{
//		$db->beginTransaction();
//	}

	$action	= $req_xml->get_schedule['action'];

	if( $action == 'update_status_schedule' )
	{
		$res = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

		$schedule_id = $req_xml->get_schedule->schedule_id;
		$status = $req_xml->get_schedule->status;
		$msg = $req_xml->get_schedule->msg;

		$r = $db->exec("update ingestmanager_schedule set status='$status' where schedule_id='$schedule_id'");
	

		$res->result->addAttribute('success', 'true');


	}
	else if( $action == 'add_schedule' )
	{
		$res = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");
		$schedules = $req_xml->get_schedule;

		foreach($schedules->children() as $schedule)
		{
			$ingest_system_ip	= $_SERVER['REMOTE_ADDR'];
			$channel			= $schedule->channel;
			$schedule_type		= $schedule->schedule_type;
			$date_time			= $schedule->date_time;
			$start_time			= $schedule->start_time;
			$duration			= $schedule->duration;
			$user_id			= $schedule->user_id;
			$content_type_id	= $schedule->content_type_id;
			$meta_table_id		= $schedule->meta_table_id;
			$category_id		= $schedule->category_id;
			$title = $db->escape(htmlspecialchars_decode($schedule->title));

			$custom = $schedule->custom;
			

			$check =  $db->queryRow("
				select * 
				from ingestmanager_schedule 
				where 
					INGEST_SYSTEM_IP='$ingest_system_ip'
				and CHANNEL='$channel' 
				and SCHEDULE_TYPE='$schedule_type' 
				and DATE_TIME='$date_time' 
				and START_TIME='$start_time' 
				and DURATION='$duration' 
				");
			if(!empty($check))
			{
				throw new Exception('동일한 스케줄이 존재합니다.');
			}

			
			$schedule_list = $db->queryAll("select * from ingestmanager_schedule where INGEST_SYSTEM_IP='$ingest_system_ip' and CHANNEL='$channel' and is_use='1'");

			if( duplicateCheck($schedule_list, $schedule_type, $date_time, $start_time, $duration ) )
			{
				throw new Exception('시간정보가 중복되는 스케줄이 존재합니다.');
			}

			$schedule_id = getSequence('IM_SCHEDULE_SEQ');
			$create_time = date("YmdHis");

			$r = $db->exec("insert into ingestmanager_schedule
			(	SCHEDULE_ID,
				INGEST_SYSTEM_IP,
				CHANNEL,
				SCHEDULE_TYPE,
				DATE_TIME,
				START_TIME,
				DURATION,
				CREATE_TIME,
				CATEGORY_ID,
				BS_CONTENT_ID,
				UD_CONTENT_ID,
				TITLE,
				USER_ID) values ('$schedule_id', '$ingest_system_ip', '$channel', '$schedule_type', '$date_time', '$start_time', '$duration', '$create_time', '$category_id', '$content_type_id', '$meta_table_id', '$title', '$user_id')");
		

			foreach($custom->children() as $metactrl)
			{
				$meta_field_id = $metactrl['metafieldid'];
				$meta_value = $db->escape(htmlspecialchars_decode($metactrl));

				$r = $db->exec("insert into im_schedule_metadata ( SCHEDULE_ID,UD_CONTENT_ID,BC_USR_META_FIELD_ID,USR_META_VALUE) values ('$schedule_id', '$meta_table_id', '$meta_field_id', '$meta_value')");
			
			}
		}

		$res->result->addAttribute('success', 'true');
	}
	else if(  $action == 'update_schedule' )
	{
		$res = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");
		$schedules = $req_xml->get_schedule;

		foreach($schedules->children() as $schedule)
		{
			$schedule_id			= $schedule->schedule_id;
			$meta_table_id			= $schedule->meta_table_id;
			$content_type_id		= $schedule->content_type_id;
			$user_id				= $schedule->user_id;

			$ingest_system_ip		= $_SERVER['REMOTE_ADDR'];
			$channel				= $schedule->channel;

			$update_set = '';
			$update_field = array();

			foreach($schedule as $key => $val)
			{
				if($key == 'schedule_id' || $key == 'custom' ) continue;

				if( $key == 'meta_table_id' ) $key = 'ud_content_id';
				if( $key == 'content_type_id' ) $key = 'bs_content_id';

				$val = $db->escape(htmlspecialchars_decode($val));

				$update_field [] = $key."='".$val."'";

			}
			$update_set = join(', ', $update_field);

			$schedule_list = $db->queryAll("select * from ingestmanager_schedule where INGEST_SYSTEM_IP='$ingest_system_ip' and CHANNEL='$channel' and is_use='1' and schedule_id!='$schedule_id'");

			if( duplicateCheck($schedule_list, $schedule_type, $date_time, $start_time, $duration ) )
			{
				throw new Exception('시간정보가 중복되는 스케줄이 존재합니다.');
			}


			if( !empty($update_set) )
			{
				$r = $db->exec(" update ingestmanager_schedule set ".$update_set." where schedule_id='$schedule_id' ");
			
			}

			$custom = $schedule->custom;

			foreach($custom->children() as $metactrl)
			{
				$meta_field_id = $metactrl['metafieldid'];
				$meta_value = $db->escape(htmlspecialchars_decode($metactrl));

				$r = $db->exec(" update im_schedule_metadata set USR_META_VALUE='$meta_value' where schedule_id='$schedule_id' and UD_CONTENT_ID='$meta_table_id' and BC_USR_META_FIELD_ID='$meta_field_id' ");
			
			}

		}

		$res->result->addAttribute('success', 'true');

	}
	else if( $action == 'delete_schedule' )
	{
		$res = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");

		$schedule_list = $req_xml->get_schedule->schedule_list;

		foreach($schedule_list->children() as $schedule_id)
		{

			$r = $db->exec("delete from ingestmanager_schedule where schedule_id='$schedule_id'");
			
		}

		$res->result->addAttribute('success', 'true');
	}
	else if( $action == 'get_list' )
	{
		$nowYmd = date("Ymd");
		$nowWeek = date("N");
		$nowTime = date("His");
		$nowDate = date("d");

		if( $nowWeek == 0 || $nowWeek == 6 )//주말 
		{
			$isWeekPlus =  " and ( date_time='$nowWeek' or date_time='8' ) ";
		}
		else //주중
		{
			$isWeekPlus =  " and ( date_time='$nowWeek' or date_time='7' ) ";
		}

		$remote_server_ip = $_SERVER['REMOTE_ADDR'];//요청한 서버 IP

		$where = " is_use='1' ";
		$where .= " and ingest_system_ip='$remote_server_ip' ";

		$forOnce = " ( schedule_type='0' and date_time='$nowYmd' and start_time > $nowTime ) ";//한번
		$forDate = " ( schedule_type='1' and start_time > $nowTime ) ";//매일
		$forWeek = " ( schedule_type='2' and start_time > $nowTime ".$isWeekPlus." ) ";//매주 //주중 주말 플러스
		$forMonth = " ( schedule_type='3' and substr(date_time, 7)='$nowDate' and start_time > $nowTime ) ";//매월

		$where .= " and ( $forOnce or $forDate or $forWeek or $forMonth  ) ";

		//검색조건
		$res = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><schedule_list /></response>");

		$query = "
		select
			*
		from
			ingestmanager_schedule
		where
			".$where."
		order by start_time, schedule_id";

		$schedule_list = $db->queryAll( $query );
	

		foreach ($schedule_list as $schedule)
		{
			$user_id = $schedule['user_id'];
			$category_id = $schedule['category_id'];
			$bs_content_id = $schedule['bs_content_id'];
			$ud_content_id = $schedule['ud_content_id'];

			$title = htmlspecialchars($schedule['title']);

			$res_content = $res->schedule_list->addChild('content'); //콘텐츠 노드
			$res_content->addChild('schedule_id', $schedule['schedule_id']);
			$res_content->addChild('user_id', $schedule['user_id']);
			$res_content->addChild('content_type_id' , $schedule['bs_content_id']);
			$res_content->addChild('meta_table_id' , $schedule['ud_content_id']);
			$res_content->addChild('duration' , $schedule['duration']);
			$res_content->addChild('channel' , $schedule['channel']);
			$res_content->addChild('schedule_type' , $schedule['schedule_type']);
			$res_content->addChild('date_time' , $schedule['date_time']);
			$res_content->addChild('start_time' , $schedule['start_time']);

			$res_content->addChild('title', $title );
			$res_content->addChild('category_id', $schedule['category_id']);
			$res_custom = $res_content->addChild('custom');	//메타데이터 노드

			$get_meta_list =  $db->queryAll("
			select
				mv.schedule_id schedule_id,
				mv.ud_content_id ud_content_id,
				mf.usr_meta_field_id bc_usr_meta_field_id,
				mv.usr_meta_value usr_meta_value,
				mf.usr_meta_field_title usr_meta_field_title
			from
				im_schedule_metadata mv,
				bc_usr_meta_field mf
			where
				mv.ud_content_id=mf.ud_content_id
			and mv.bc_usr_meta_field_id=mf.usr_meta_field_id
			and mv.schedule_id=".$schedule['schedule_id']." order by mv.BC_USR_META_FIELD_ID");
			

			foreach( $get_meta_list as $meta )
			{
				$value = htmlspecialchars($meta['usr_meta_value']);
				$res_ctrl = $res_custom->addChild('metactrl', $value );
				$res_ctrl->addAttribute('metafieldid', $meta['bc_usr_meta_field_id'] );
				$res_ctrl->addAttribute('name', $meta['usr_meta_field_title'] );
			}
		}
	}
	else
	{
		throw new Exception('정의되지 않은 action 입니다.');
	}



//	$db->commit();

	file_put_contents(LOG_PATH.'/get_schedule_'.$created.'.html', date("Y-m-d H:i:s\t").$res->asXML()."\n\n", FILE_APPEND);

	echo $res->asXML();
}
catch (Exception $e)
{
//	if ($db->inTransaction())
//	{
//		$db->rollback();
//	}

	$res = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?> \n<response><result /></response>");
	$res->result->addAttribute('success', 'false');
	$res->result->addAttribute('msg', $e->getMessage().$db->last_query);

	file_put_contents(LOG_PATH.'/get_schedule_'.$created.'.html', date("Y-m-d H:i:s\t").$res->asXML()."\n\n", FILE_APPEND);

	echo $res->asXML();
}


function duplicateCheck($schedule_list, $type , $date , $time , $duration){

	foreach($schedule_list as $schedule)
	{
		if($type == 0)//일회성 .. 시간만 체크 하면 됨
		{
			$targetWeek = date("W", strtotime( $date.$time ) );

			if( $schedule['schedule_type'] == 2 ) //주간 반복 스케줄들
			{
				if( date("W") == $schedule['date_time'] )//같은 요일일떄
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
				else if( ( date("W") == 0 || date("W") == 6 ) && ( $schedule['date_time'] == 8 )  ) //주말일때
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
				else if( ( date("W") == 1 || date("W") == 2 || date("W") == 3 || date("W") == 4 || date("W") == 5 )  && ( $schedule['date_time'] == 7 ) ) //평일일때
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
			else if( $schedule['schedule_type'] == 0 )
			{
				if( $schedule['date_time'] == $date ) //동일한 날짜
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



<?php
set_time_limit(0);
define('TEMP_ROOT', '/oradata/web/nps');

$_SERVER['DOCUMENT_ROOT'] = TEMP_ROOT;

require_once(TEMP_ROOT.'/lib/config.php');
require_once (TEMP_ROOT.'/lib/functions.php');
require_once(TEMP_ROOT.'/workflow/lib/task_manager.php');
$cur_date = date('YmdHis');
$created_time = date('YmdHis');

define('CREATED_TIME', $created_time);

$log_path = TEMP_ROOT.'/log/'.basename(__FILE__).'_'.$cur_date.'.log';
$log_path_error = TEMP_ROOT.'/log/'.basename(__FILE__).'_error_'.$cur_date.'.log';
try
{
//	$query = " select distinct content_id from bc_media where media_type='original' and media_id in (
//select media_id from bc_task where type='60' and CREATION_DATETIME between 20131128120000 and 20131129224000 and source like 'school3/%' and status='complete'
//) ";

$query = "select * from bc_task where type='60' and ( destination='ingest_rename' or destination='master_rename' or destination='pas_rename' ) and status='complete' and task_user_id='admin' and creation_datetime between 20131129233520 and 20131130000000
";

	file_put_contents($log_path, '시작 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);

	$datas = $db->queryAll($query);

	$user_id ='admin';

	foreach($datas as $data)
	{
		$source = $data['source'];
		$target = $data['target'];
		$media_id= $data['media_id'];
		continue;

		$target_array = explode('.', $target);
		$source_array = explode('.', $source);
		if(count($target_array) == 2 && count($target_array) == 2){
			//타겟 파일명 
			if($source_array[0].'_1' == $target_array[0]){
				file_put_contents($log_path, date("Y-m-d H:i:s").'] '.$source.' : '.$target."\n", FILE_APPEND);
				$content_info = $db->queryRow("select * from view_content where content_id=(select content_id from bc_media where media_id='$media_id')");
				$ud_content_id = $content_info['ud_content_id'];

				$content_id = $content_info['content_id'];
				if( empty($content_id) ){
					continue;
				}
				$insert_task = new TaskManager($db);
				switch($ud_content_id)
				{
					//미디어가 온라인일 경우에만 워크플로우가 등록되도록 처리필요.
					case 4000282:			//인제스트와 DAS다운로드의 메인의 Ingest폴더
					case 4000284:
					{
						$channel = "ingest_rename";
						$insert_task->start_task_workflow($content_id, $channel, $user_id );
						break;
					}
					case 4000345:		//편집 마스터와 와 방송마스터의 메인의 Master폴더
					case 4000346:
					{
						$channel = "master_rename";
						$insert_task->start_task_workflow($content_id, $channel, $user_id );
						break;
					}
					case 4000365:	//아카이브(PAS) 백업의 PAS폴더
					{
						$channel = "pas_rename";
						$insert_task->start_task_workflow($content_id, $channel, $user_id );
						break;
					}
				}
			}else{
				file_put_contents($log_path_error, date("Y-m-d H:i:s").'확인필요. '.'] '.$source.' : '.$target."\n", FILE_APPEND);
				continue;
			}
		}else{
			file_put_contents($log_path_error, date("Y-m-d H:i:s").'확인필요2. '.'] '.$source.' : '.$target."\n", FILE_APPEND);
			continue;
		}

		continue;		
	}

	file_put_contents($log_path, '종료 : '.date("Y-m-d H:i:s")."\n", FILE_APPEND);
}

catch ( Exception $e )
{
	file_put_contents($log_path_error, date("Y-m-d H:i:s").' '.$e->getMessage().' '.$db->last_query."\n", FILE_APPEND);
	echo $e->getMessage().' '.$db->last_query;
}


?>
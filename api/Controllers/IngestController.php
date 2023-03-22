<?php

namespace Api\Controllers;

use Carbon\Carbon;
use RuntimeException;
use Api\Http\ApiRequest;
use Cron\CronExpression;
use Api\Http\ApiResponse;
use Api\Services\LogService;
use Api\Services\TaskService;
use InvalidArgumentException;
use Api\Services\ContentService;
use Api\Types\ContentStatusType;
use Api\Controllers\BaseController;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class IngestController extends BaseController
{
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        // db 커넥션 연결
        $container->get('db');

        $this->contentService = new ContentService($container);
        $this->taskService = new TaskService($container);
        $this->logService = new LogService($container);
    }

    /**
     * 스케줄 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function getSchedule(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $params =  $request->all();
      
        $system_host = $params['system_host'];
        $channel = $params['channel'];

        $base_datetime = Carbon::createFromFormat('YmdHi', $params['current_date']);
        $from_datetime = Carbon::createFromFormat('YmdHi', $params['from_date']);
        $to_datetime = Carbon::createFromFormat('YmdHi', $params['to_date']);


            $schedules = array();

           // throw new RuntimeException('ddd');
            
            $result = DB::table('INGESTMANAGER_SCHEDULE')     
            ->where('IS_USE', 1)
            ->where('INGEST_SYSTEM_IP', $system_host )
            ->where('CHANNEL', $channel )
            ->get()->all();
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

            $return = array(
                'success' => true,
                'status' =>  0,
                'message' => "OK",
                'schedules' => $schedules
            );

            return response()->withJson($return)
            ->withStatus(200);
    }

    public function setQueued(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params =  $request->all();
     
        $id = $params['id'];
        $filename = $params['filename'];

        $schedule = DB::table('INGESTMANAGER_SCHEDULE')     
        ->where('schedule_id', $id )
        ->first();
       
        $metadata = DB::table('INGESTMANAGER_SCHEDULE_META')     
        ->where('schedule_id', $id )
        ->first();

        $ingestMeta = json_decode($metadata->metadata);
        // {"k_user_id":"admin","k_ud_content_id":"1","k_content_id":"","media_id":"","k_title":"\u3134\u313b\u3134\u3147",
        //     "cn":"\u3139\u3147\u3134\u3141\u3139\u3134\u3147\u3139","c_category_id":2017,"kwrd":"",
        //     "matr_knd":"","embg_at":"N","ext-comp-1680":"","embg_relis_dt":"","embg_resn":"","recptn_stle":"","thema_cl":"0","prsrv_pd_et_resn":"","brdcst_stle_se":"N","vido_ty_se":"B","progrm_code":"","progrm_nm":"","tme_no":"","subtl":"","brdcst_de":"","shooting_orginl_atrb":"","prod_se":"","season":"","wethr":"","origin":"","use_grad":"","watgrad":"","instt":"","kogl_ty":"","othbc_at":"Y"}
        // // $obj = new stdClass;

        // $obj->inserttype = 0;
        // $obj->requestmeta = new stdClass;
        // $obj->requestmeta->user_id = $schedule['user_id'];
        // $obj->requestmeta->flag = 'ingest';
        // $obj->requestmeta->metadata_type = 'id';
        // $obj->requestmeta->metadata = array(new StdClass);
        // $obj->requestmeta->metadata[0]->k_content_id = '';
        // $obj->requestmeta->metadata[0]->k_topic_content_id = '';
        // $obj->requestmeta->metadata[0]->k_ud_content_id = $schedule['ud_content_id'];
        // $obj->requestmeta->metadata[0]->k_title = $schedule['title'];
        // $obj->requestmeta->metadata[0]->c_category_id = $schedule['category_id'];
		// foreach ($metadata as $n => $row) {
	    //     $obj->requestmeta->metadata[0]->$row['bc_usr_meta_field_id'] = $row['usr_meta_value'];
		// }
        // $obj->requestmeta->filename = $filename;

        // $result = insertMetadata(json_encode($obj));

        $metaValues = $ingestMeta;
        //KTV 미디어ID 발급 등록시 발급한다     

        $metaClass = $this->container['metadata'];
       

       $user_id = $ingestMeta->k_user_id;
   
       $metaValues->media_id =  $this->contentService->getMediaId($schedule->bs_content_id);
        // $content_id = getSequence('SEQ_CONTENT_ID');
        $contentMeta = [
            //   'content_id' =>  $content_id,
            'category_id' =>  $ingestMeta->c_category_id,
            'bs_content_id' =>  $schedule->bs_content_id,
            'ud_content_id' =>  $ingestMeta->k_ud_content_id,                 
            'title' =>  $ingestMeta->k_title,                    
            'reg_user_id' =>  $user_id ,
            'status' => ContentStatusType::INGEST
        ];
        $contentStatus = [
        ];                
        //주조 전송
        if( !empty($metaValues->k_send_to_main) ){         
            $contentStatus['mcr_trnsmis_sttus'] = 'request';           
        }
        //부조 전송
        if( !empty($metaValues->k_send_to_sub) ){         
            $contentStatus['scr_trnsmis_sttus'] = 'request';
            $contentStatus['scr_trnsmis_ty'] = 'ab';
        }
        if( !empty($metaValues->k_send_to_sub_news) ){         
            $contentStatus['scr_trnsmis_sttus'] = 'request';   
            $contentStatus['scr_trnsmis_ty'] = 'news';           
        }
        $usrMeta  = (array) $metaValues ;
        
        $usrMeta = $metaClass::getDefValueRender('usr' ,$ingestMeta->k_ud_content_id , $usrMeta);

        $content =  $this->contentService->createUsingArray($contentMeta, $contentStatus , [], $usrMeta );
        $content_id = $content->content_id;

        if($usrMeta['use_prhibt_at'] =='Y'){
            $description = '사용금지설정-'.$usrMeta['use_prhibt_cn'];            
            $logData = [
                'action' => 'edit',
                'description' => $description,
                'content_id' => $content->content_id,
                'bs_content_id' => $content->bs_content_id,
                'ud_content_id' => $content->ud_content_id
            ];
            $user = new \Api\Models\User();
            $user->user_id = $user_id;
            $r = $this->logService->create($logData, $user);
        }

        $channel = 'vs2ingest';
        $task = $this->taskService->getTaskManager();
        $task_id = $task->insert_task_query_outside_data($content_id, $channel, 1, $user_id, $filename);

        $task_list_info = $task->get_task_list(null);	
        				
        if ( ! empty($task_list_info)) {
            
        $workflow = DB::table('bc_task_workflow')     
        ->where('register', $channel )
        ->first();
        
            $interface_id = $task->InsertInterface($workflow->user_task_name, 'USER', $user_id, 'USER', $user_id, $content_id, 'regist', $workflow->task_workflow_id);
            foreach ($task_list_info as $idx => $list_info) {
                //vs2인제스트에서 tgt 파라미터로 받아서 추가함 
                $task_list_info[$idx]['tgt_storage_info'] =$list_info['trg_storage_info'];
                $task->InsertInterfaceCH($interface_id, 'NPS', 'TASK', $list_info['task_id'], $content_id);
            }
        }
        $result =[
            'success' => 'true',
            'status'=>0,
            'message'=>"OK",
            'task_id' => $task_id,
            'content_id' => $content_id,
            'task_list_info' => $task_list_info
        ];

		return response()->withJson($result)
            ->withStatus(200);
    }
}

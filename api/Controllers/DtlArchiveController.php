<?php

namespace Api\Controllers;

use Api\Models\Task;
use Proxima\core\Unit;
use Api\Http\ApiRequest;
use Api\Models\DivaTape;
use Api\Http\ApiResponse;
use Api\Models\ArchiveTask;
use Api\Modules\DivaClient;
use Api\Models\ArchiveMedia;
use Api\Models\DivaTapeInfo;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class DtlArchiveController extends BaseController
{
    //private $container;
    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        //$this->container = $container;
        $this->container->get('db');
        // db 커넥션 연결       
    }

    /**
     * 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {   
        $params = $request->all();       
        return response()->ok();
    }

    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();

        $tape = new DivaTape();
        foreach($params as $key => $val)
        {
            if( !empty($val) ){
                $tape->$key = $val;
            }
        }

        $tape->save();
        return response()->ok();
    }
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
        $tape = DivaTape::find($params['id']);
        if(!$tape){
            return response()->error(); 
        }
        foreach($params as $key => $val)
        {
            if( !empty($val) ){
                $tape->$key = $val;
            }
        }
        $tape->save();
        return response()->ok();
    }
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
        $tape = DivaTape::find($params['id']);
        if(!$tape){
            return response()->error(); 
        }
        $tape->delete();
        return response()->ok();
    }

    public function getEvents(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
        
        $taskId = $args['task_id'];
        $results =[];

        //$task = Task::where("task_id",$taskId)->orWhere("root_task",$taskId)->first();
        $archiveTask = ArchiveTask::where("task_id",$taskId)->first();
        $requestId = $archiveTask['reqnum'];//'32489';
        //$requestId =32489;
        if(!empty($requestId)){
            $divaClient = new DivaClient();
            $events = $divaClient->getEvents($requestId);
            $results = $events['events'];
            if(!empty($results)){
                foreach($results as $key => $result)
                {
                    $eventDate = $result['eventDate'];
                    $results[$key]['eventDateRen'] = $divaClient->dateArrayToDateString( $eventDate);
                }
            }
        }
        return response()->ok($results);
    }

    public function getMedias(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
        
        $barcode = $args['barcode'];
        $medias = DivaTapeInfo::query();
        $medias->where('of_barcode', $barcode);
        $results = $medias->get();

        //         SELECT am.OBJECT_NAME,c.title, m.FILESIZE, cs.RESTORE_AT FROM 
        // ARCHIVE_MEDIAS am 
        // JOIN BC_CONTENT c ON am.CONTENT_ID=c.CONTENT_ID 
        // JOIN BC_CONTENT_STATUS cs ON c.CONTENT_ID=cs.CONTENT_ID
        // JOIN BC_MEDIA m ON c.CONTENT_ID=m.CONTENT_ID
        // WHERE m.MEDIA_TYPE='original' and  am.OBJECT_NAME='20130703V77601' AND am.DELETED_AT IS NULL ;

        foreach($results as $result)
        {
            $objectName =  $result['of_object_name'];
            $detail = ArchiveMedia::where('object_name',$objectName)
            ->join("BC_CONTENT c",function($join){
                $join->on('ARCHIVE_MEDIAS.content_id', '=', 'c.content_id');
            })->join("BC_MEDIA m",function($join){
                $join->where('media_type', '=', 'original');
                $join->on('c.content_id', '=', 'm.content_id');
            })->join("BC_CONTENT_STATUS cs",function($join){
                $join->on('c.content_id', '=', 'cs.content_id');
            })->select('ARCHIVE_MEDIAS.OBJECT_NAME','c.title', 'm.FILESIZE', 'cs.RESTORE_AT')->first();
            //$result['detail'] = $detail;

            $result['title'] =  $detail['title'];
            $result['filesize'] =  Unit::formatBytes($detail['filesize']);
            if($detail['restore_at'] == '1'){
                $detailRestoreAt = '리스토어';
            }else{
                $detailRestoreAt = '';
            }
            $result['status'] = $detailRestoreAt;
        }
        return response()->ok($results);
    }

    public function sync(ApiRequest $request, ApiResponse $response, array $args)
    {
        set_time_limit(3600);
          $settings = [
            'logging' => true,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('diva')
            ],
        ];       
        \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        //$container = app()->getContainer();
        //$this->container->get('db');
        $query =  DB::table('DP_TAPES','diva');
        //$tapes = DB::table('DP_TAPES','diva')->where('ta_set_id',2)->orderBy('tape_id' ,'asc');

        $page = 1;        
        $perPage = 500;
        $createdCount = 0;
        while (true) {
            $records = $query->simplePaginate($perPage, ['*'], 'page', $page);

            if (count($records) === 0) {         
                break;
            }          
          
            foreach ($records as $row => $record) {
                $numCnt = $row + 1 + (($page - 1) * $perPage);
 
                //신규 및 업데이트
                $tape = DivaTape::findByTaId($record->ta_id);
                if(!$tape){
                    $tape = new DivaTape();
                }

                $tape->tape_se = 'diva';
                if($record->ta_is_online == 'N'){
                    $tape->disprs_at = 'Y';
                }else{
                    $tape->disprs_at = 'N';
                }
                $tape->ta_id = $record->ta_id;
                $tape->ta_barcode= $record->ta_barcode;
                $tape->ta_acs= $record->ta_acs;
                $tape->ta_lsm= $record->ta_lsm;
                $tape->ta_media_type_tp_id= $record->ta_media_type_tp_id;
                $tape->ta_set_id= $record->ta_set_id;
                $tape->ta_is_online= $record->ta_is_online;
                $tape->ta_protected= $record->ta_protected;
                $tape->ta_enable_for_writing= $record->ta_enable_for_writing;
                $tape->ta_to_be_cleared= $record->ta_to_be_cleared;
                $tape->ta_enable_for_repack= $record->ta_enable_for_repack;
                $tape->ta_group_tg_id= $record->ta_group_tg_id;
                $tape->ta_remaining_size= $record->ta_remaining_size;
                $tape->ta_filling_ratio= $record->ta_filling_ratio;
                $tape->ta_fragmentation_ratio= $record->ta_fragmentation_ratio;
                $tape->ta_block_size= $record->ta_block_size;
                $tape->ta_last_written_block= $record->ta_last_written_block;
                $tape->ta_format= $record->ta_format;
                $tape->ta_eject_comment= $record->ta_eject_comment;
                $tape->ta_last_archive_date= $record->ta_last_archive_date;
                $tape->ta_first_mount_date= $record->ta_first_mount_date;
                $tape->ta_last_retention_date= $record->ta_last_retention_date;
                $tape->ta_first_insertion_date= $record->ta_first_insertion_date;
                $tape->ta_export_tape = $record->ta_export_tape;
                $tape->save();
            }

            $createdCount = $perPage * $page;
            $page++;
            
            //break;
        }

        // $query =  DB::table('DP_OBJECT_TAPE_INFOS','diva');
        // //$tapes = DB::table('DP_TAPES','diva')->where('ta_set_id',2)->orderBy('tape_id' ,'asc');

        // $page = 1;        
        // $perPage = 1000;
        // $createdCount = 0;
        // while (true) {
        //     $records = $query->simplePaginate($perPage, ['*'], 'page', $page);

        //     if (count($records) === 0) {         
        //         break;
        //     }          
          
        //     foreach ($records as $row => $record) {
        //         $numCnt = $row + 1 + (($page - 1) * $perPage);
 
        //         //신규 및 업데이트
        //         $tape = DivaTapeInfo::find($record->of_id);
        //         if(!$tape){
        //             $tape = new DivaTapeInfo();
        //         }

        //         $tape->of_id = $record->of_id;
        //         $tape->of_object_name = $record->of_object_name;
        //         $tape->of_category = $record->of_category;
        //         $tape->of_instance_order_number = $record->of_instance_order_number;
        //         $tape->of_request_type = $record->of_request_type;
        //         $tape->of_request_date = $record->of_request_date;
        //         $tape->of_barcode = $record->of_barcode;
        //         $tape->of_group_name = $record->of_group_name;
        //         $tape->save();
        //     }

        //     $createdCount = $perPage * $page;
        //     $page++;
            
        //     //break;
        // }
     
     
        return response()->ok($numCnt);
    }
    public function syncMedia(ApiRequest $request, ApiResponse $response, array $args)
    {
          $settings = [
            'logging' => true,
            'connections' => [
                \Api\Support\Helpers\DatabaseHelper::getSettings(),
                \Api\Support\Helpers\DatabaseHelper::getSettings('diva')
            ],
        ];       
        \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        //$container = app()->getContainer();
        //$this->container->get('db');
        $query =  DB::table('DP_OBJECT_TAPE_INFOS','diva');
        //$tapes = DB::table('DP_TAPES','diva')->where('ta_set_id',2)->orderBy('tape_id' ,'asc');

        $page = 1;        
        $perPage = 1000;
        $createdCount = 0;
        while (true) {
            $records = $query->simplePaginate($perPage, ['*'], 'page', $page);

            if (count($records) === 0) {         
                break;
            }          
          
            foreach ($records as $row => $record) {
                $numCnt = $row + 1 + (($page - 1) * $perPage);
 
                //신규 및 업데이트
                $tape = DivaTapeInfo::find($record->of_id);
                if(!$tape){
                    $tape = new DivaTapeInfo();
                }

                $tape->of_id = $record->of_id;
                $tape->of_object_name = $record->of_object_name;
                $tape->of_category = $record->of_category;
                $tape->of_instance_order_number = $record->of_instance_order_number;
                $tape->of_request_type = $record->of_request_type;
                $tape->of_request_date = $record->of_request_date;
                $tape->of_barcode = $record->of_barcode;
                $tape->of_group_name = $record->of_group_name;
                $tape->save();
            }

            $createdCount = $perPage * $page;
            $page++;
            
            //break;
        }
     
        return response()->ok($numCnt);
    }
    
}

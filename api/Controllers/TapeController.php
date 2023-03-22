<?php

namespace Api\Controllers;

use Proxima\core\Unit;
use Api\Http\ApiRequest;
use Api\Models\DivaTape;
use Api\Http\ApiResponse;
use Api\Modules\DivaClient;
use Api\Models\ArchiveMedia;
use Api\Models\DivaTapeInfo;
use Api\Services\ArchiveService;
use Api\Services\LogService;
use Api\Services\DivaApiService;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as DB;

class TapeController extends BaseController
{
    private $divaApiService;
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
        $this->divaApiService = new DivaApiService($container);
        $this->archiveService = new ArchiveService($container);
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
        $query = DivaTape::query();
        // "tape_se" => "diva"
        // "disprs_at" => "Y"
        // "search" => "xfdd"
        if( !empty($params['tape_se']) ){
            $query->where('tape_se', strtolower($params['tape_se']) );
        }
        if( !empty($params['disprs_at']) ){
            $query->where('disprs_at', $params['disprs_at'] );
        }
        if( !empty($params['ta_set_id']) ){
            $query->where('ta_set_id', $params['ta_set_id'] );
        }

        if( !empty($params['search']) ){
            $query->where('ta_barcode', 'like', "%{$params['search']}%"  );
        }

        if( $request->dir && $request->sort){

            $query->orderBy($request->sort, $request->dir );
        }
        $tapes = paginate($query);
       // dd($tapes);
        if(empty($tapes->total)){
            //미디어ID 이므로 검색
          
            $objectInfo = $this->divaApiService->getObjectInfo($params['search']);
           // dd( $objectInfo);
            if(!empty($objectInfo) && !empty($objectInfo['tapeInstances'])){
                $existTapes = [];
                foreach($objectInfo['tapeInstances'] as $tapeIns)
                {
                    foreach ($tapeIns['tapes'] as $tp) {
                        $existTapes [] = $tp['barcode'];
                    }
                }
                        
                if( !empty( $existTapes ) ){
                    $query->orWhereIn('ta_barcode',  $existTapes  );
                    if( $request->dir && $request->sort){
                        $query->orderBy($request->sort, $request->dir );
                    }
                    $tapes = paginate($query);
                }
            }
        }
        foreach($tapes as $key => $tape)
        {
            $tapes[$key]['ta_remaining_size_bf'] = $tape['ta_remaining_size'];
            if( empty($tape['ta_remaining_size'])){
                $tapes[$key]['ta_remaining_size'] = '0';
            }else{
                $tapes[$key]['ta_remaining_size'] = Unit::formatBytes($tape['ta_remaining_size']*1024*1024);
            }

            if($tape['ta_media_type_tp_id'] == '48'){
                $tapes[$key]['ta_total_size'] = Unit::formatBytes(2441405952*1024*1024);
                $tapes[$key]['ta_version'] = 'LTO-2.5T';
            }
            if( empty($tape['ta_total_size'])){
                $tapes[$key]['ta_total_size'] = '0';
            }else{
                $tapes[$key]['ta_total_size'] = Unit::formatBytes($tape['ta_total_size']*1024*1024);
            }
            $tapes[$key]['ta_version'] =  $tapes[$key]['ta_media_type'];
        }
        return response()->ok($tapes);
    }

        /**
     * 목록 조회
     *
     * @param \Api\Http\ApiRequest $request
     * @param Api\Http\ApiResponse $response
     * @param array $args
     * @return Api\Http\ApiResponse
     */
    public function search(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
        $query = DivaTape::query();
        $tapes = [];
        $existTapes = [];
        $mediaOnTapes = [];
        if (!empty($params['tape_se'])) {
            $query->where('tape_se', strtolower($params['tape_se']));
        }

        if (!empty($params['content_id'])) {
            $contentId = $params['content_id'];
            //$contentId = 31804;
            $archiveInfo = ArchiveMedia::where('content_id', $contentId)->orderBy('id', 'desc')->first();
            if (!empty($archiveInfo) && !empty($archiveInfo->object_name)) {
                $objectName = $archiveInfo->object_name;
                $objectInfo = $this->divaApiService->getObjectInfo($objectName);
                // dd( $objectInfo);
                if (!empty($objectInfo) && !empty($objectInfo['tapeInstances'])) {
             
                    foreach ($objectInfo['tapeInstances'] as $tapeIns) {
                        foreach ($tapeIns['tapes'] as $tp) {
                            $existTapes [] = $tp['barcode'];
                            $mediaOnTapes [$tp['barcode']] = $objectName;
                        }
                    }
                }
            }

            $migInfo = $this->archiveService->getMigTable()->where('content_id', $contentId)->first();
            if (!empty($migInfo) && !empty($migInfo->mediaid)) {
                $beforeObjectName =  $migInfo->mediaid;
                $objectInfo = $this->divaApiService->getObjectInfo($beforeObjectName);
                // dd( $objectInfo);
                if (!empty($objectInfo) && !empty($objectInfo['tapeInstances'])) {
                   
                    foreach ($objectInfo['tapeInstances'] as $tapeIns) {
                        foreach ($tapeIns['tapes'] as $tp) {
                            $existTapes [] = $tp['barcode'];
                            $mediaOnTapes [$tp['barcode']] = $beforeObjectName;
                        }
                    }
                }
            }

            //dd($existTapes);
            
                            
            if (!empty($existTapes)) {
                $query->whereIn('ta_barcode', $existTapes);
                if ($request->dir && $request->sort) {
                    $query->orderBy($request->sort, $request->dir);
                }
               // $query->dd();
                $tapes = paginate($query);
            }
        }

        if (!empty($tapes)) { 
            foreach ($tapes as $key => $tape) {
                $tapes[$key]['ta_remaining_size_bf'] = $tape['ta_remaining_size'];
                if (empty($tape['ta_remaining_size'])) {
                    $tapes[$key]['ta_remaining_size'] = '0';
                } else {
                    $tapes[$key]['ta_remaining_size'] = Unit::formatBytes($tape['ta_remaining_size']*1024);
                }

                if ($tape['ta_media_type_tp_id'] == '48') {
                    $tapes[$key]['ta_total_size'] = Unit::formatBytes(2441405952*1024);
                    $tapes[$key]['ta_version'] = 'LTO-2.5T';
                }
                if (empty($tape['ta_total_size'])) {
                    $tapes[$key]['ta_total_size'] = '0';
                } else {
                    $tapes[$key]['ta_total_size'] = Unit::formatBytes($tape['ta_total_size']*1024);
                }
                $tapes[$key]['ta_version'] =  $tapes[$key]['ta_media_type'];
                if(!empty($mediaOnTapes[$tape['ta_barcode']])){
                    $tapes[$key]['media_id'] = $mediaOnTapes[$tape['ta_barcode']];
                }               
            }
        }
        return response()->ok($tapes);
    }

    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
        $user = auth()->user();
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
        $tape->logging = true;
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
        $tape->logging = true;
        if(!$tape){
            return response()->error(); 
        }
        $tape->delete();
        return response()->ok();
    }

    public function getMedia(ApiRequest $request, ApiResponse $response, array $args)
    {
        $params = $request->all();
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
                \Api\Support\Helpers\DatabaseHelper::getSettings()
                //,
                //\Api\Support\Helpers\DatabaseHelper::getSettings('diva')
            ],
        ];       
        \Api\Support\Helpers\DatabaseHelper::getConnection($settings);
        $container = app()->getContainer();
        //$this->container->get('db');
        //$query =  DB::table('DP_TAPES','diva');
        //$tapes = DB::table('DP_TAPES','diva')->where('ta_set_id',2)->orderBy('tape_id' ,'asc');

        $tapes = $this->divaApiService->getAllTapes();
        
        foreach ($tapes as $row => $record) {
            //신규 및 업데이트
      
            $tape = DivaTape::findByTaId($record['id']);
            if(empty($tape)){
                $tape = new DivaTape();
               
            }
            $tape->logging = false;

            $tape->tape_se = 'diva';
            //소산여부
            if($record['status'] == 'OFFLINE'){
                $tape->disprs_at = 'Y';
            }else{
                $tape->disprs_at = 'N';
            }
            $tape->ta_id = $record['id'];
            $tape->ta_barcode= $record['barcode'];//->ta_barcode;
            $tape->ta_acs= $record['acs'];//->ta_acs;
            $tape->ta_lsm= $record['lsm'];//->ta_lsm;
            $tape->ta_media_type_tp_id= $record['mediaFormatId'];//->ta_media_type_tp_id;
            $tape->ta_set_id= $record['set'];//->ta_set_id;
            $tape->ta_is_online= $record['status'];//->ta_is_online;
            $tape->ta_protected= ($record['protected'] == true) ? 'Y': 'N';//->ta_protected;
            $tape->ta_enable_for_writing= ($record['writeEnabled']== true) ? 'Y': 'N';//->ta_enable_for_writing;
            $tape->ta_to_be_cleared= ($record['toBeCleared']== true) ? 'Y': 'N';//->ta_to_be_cleared;
            $tape->ta_enable_for_repack= ($record['repackEnabled']== true) ? 'Y': 'N';//->ta_enable_for_repack;
            $tape->ta_group= $record['group'];//->ta_group_tg_id;
            $tape->ta_remaining_size= $record['remainingSizeMB'];//->ta_remaining_size;
            $tape->ta_filling_ratio= $record['fillingRatio'];//->ta_filling_ratio;
            $tape->ta_fragmentation_ratio= $record['fragmentationRatio'];//->ta_fragmentation_ratio;
            $tape->ta_block_size= $record['blockSize'];//->ta_block_size;
            $tape->ta_last_written_block= $record['lastWrittenBlock'];//->ta_last_written_block;
            $tape->ta_format= $record['format'];//->ta_format;
            $tape->ta_eject_comment= $record['ejectComment'];//->ta_eject_comment;
            $tape->ta_last_archive_date= $this->divaApiService->dateArrayToDateString($record['lastArchiveDate']);//->ta_last_archive_date;
            $tape->ta_first_mount_date= $this->divaApiService->dateArrayToDateString($record['firstMountDate']);//->ta_first_mount_date;
            $tape->ta_last_retention_date= $this->divaApiService->dateArrayToDateString($record['lastRetentionDate']);//->ta_last_retention_date;
            $tape->ta_first_insertion_date= $this->divaApiService->dateArrayToDateString($record['firstInsertionDate']);//->ta_first_insertion_date;

            $tape->ta_media_type= $record['mediaType'];//->mediaType;
            $tape->ta_total_size= $record['totalSizeMB'];//->totalSizeMB;
            //$tape->ta_export_tape = $record['id'];//->ta_export_tape;
            $tape->save();
        }

        $numCnt = count($tapes);

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

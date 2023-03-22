<?php

namespace Api\Controllers;

use Carbon\Carbon;
use Api\Core\FilePath;
use Api\Http\ApiRequest;
use Api\Models\Category;
use Api\Types\JobStatus;
use Api\Http\ApiResponse;
use Api\Types\Fs\JobType;
use Api\Types\TaskStatus;
use Api\Types\UdContentId;
use Api\Models\UserContent;
use Api\Types\CategoryType;
use Api\Types\StorageIdMap;
use Api\Services\JobService;
use Api\Services\LogService;
use Api\Types\BsContentType;
use Api\Models\ContentStatus;
use Api\Services\FileService;
use Api\Services\TaskService;
use Api\Services\UserService;
use Api\Services\GroupService;
use Api\Services\MediaService;
use Api\Types\WorkflowChannel;
use Api\Services\ApiJobService;
use Api\Services\ArchiveService;
use Api\Services\ContentService;
use Api\Types\ContentStatusType;
use Api\Services\DTOs\ContentDto;
use Api\Controllers\BaseController;
use Api\Services\MediaSceneService;
use Api\Services\PermissionService;
use Api\Services\UserContentService;
use Psr\Container\ContainerInterface;
use Api\Services\DTOs\ContentStatusDto;
use Api\Support\Helpers\MetadataMapper;
use Api\Services\DTOs\ContentSysMetaDto;
use Api\Services\DTOs\ContentUsrMetaDto;
use Api\Services\DTOs\contentSearchParam;
use Api\Services\DTOs\DataDicCodeItemDto;
use Api\Support\Helpers\ValidationHelper;
use Illuminate\Database\Capsule\Manager as DB;

class ContentController extends BaseController
{
    /**
     * 콘텐츠 서비스
     *
     * @var \Api\Services\ContentService
     */
    private $contentService;
    private $mediaService;
    private $mediaSceneService;
    private $mapper;
    private $userService;    
    private $taskService;    
    private $groupService;    
    private $permissionService;
    private $archiveService;
    private $logService;
    private $userContentService;



    /**
     * 생성자는 필요할때만 정의하면 됨...
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->contentService = new ContentService($container);
        $this->mediaService = new MediaService($container);
        $this->mediaSceneService = new MediaSceneService($container);
        $this->mapper = new MetadataMapper($container);
        $this->taskService = new TaskService($container);
        $this->userService = new UserService($container);
        $this->groupService = new GroupService($container);
        $this->permissionService = new PermissionService($container);
        $this->jobService = new JobService($container);
        $this->archiveService = new ArchiveService($container);
        $this->logService = new LogService($container);
        $this->userContentService = new UserContentService($container);
    }

    /**
     * 콘텐츠 목록 조회(주문상세 내역에서 추가 후 조회 할때)
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function index(ApiRequest $request, ApiResponse $response, array $args)
    {
        /**
         * parma
         * $keyword 타이틀
         * $mediaId 미디어 아이디(media테이블의 media_id 아님, KTV 소재아이디)
         */
        $input = $request->all();
        $param = new contentSearchParam($input);
      
        // 클린본, 마스터본, 클립본
        // $param['ud_content_id'] = [
        //     UdContentId::CLEAN,
        //     UdContentId::CLIP,
        //     UdContentId::MASTER
        // ];

        $param->ud_content_id = [
            UdContentId::CLEAN,
            UdContentId::CLIP,
            UdContentId::MASTER
        ];
    
        $contents = $this->contentService->search($param);
        return $response->ok($contents);
    }

    /**
     * 매핑용 목록 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function listMap(ApiRequest $request, ApiResponse $response, array $args)
    {
        /**
         * parma
         * $keyword 타이틀
         * $mediaId 미디어 아이디
         */
        $input = $request->all();
        $param = new contentSearchParam($input);
        $keyword = $param->keyword;
        $mediaId = $param->mediaId;

        $contents = $this->contentService->getContentList($param);
        $contents = $this->mapper->contentsMapper($contents);

        return $response->ok($contents);
    }

    /**
     * 자식 콘텐츠 목록
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function listParent(ApiRequest $request, ApiResponse $response, array $args)
    {

        $contentId  = $args['content_id'];
        $contents = $this->contentService->getContentByParentContentId($contentId);
        return $response->ok($contents);
    }

    /**
     * 단건 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function show(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId  = $args['content_id'];
        $content = $this->contentService->getContentByContentId($contentId);
        $content = $this->mapper->addInfo($content);
        return $response->ok($content);
    }
    /**
     * 단건 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function showMap(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId  = $args['content_id'];
        $content = $this->contentService->getContentByContentId($contentId);
        $content = $this->mapper->contentMapper($content);
        return $response->ok($content);
    }

    /**
     * 콘텐츠 생성
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function create(ApiRequest $request, ApiResponse $response, array $args)
    {
        return $response->ok();
    }

    /**
     * 외부 콘텐츠 생성
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function createMap(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user       = auth()->user();
        $data       = $request->all();

        $flag       = $data['flag'];
        $filename   = $data['filename'];
        $jobId      = $data['job_id'];
        $metadata   = $data['metadata'][0];

        $regUserId = $user->user_id;

        //regist_instt
        // $bs_content_id  = 506;
        // $ud_content_id  = 3;
        //shooting_orginl_atrb => gvrndept
        //vido_ty_se => o
        //brdcst_stle_se => e

        
        if($flag == 'portal'){
            $channel = WorkflowChannel::REGISTER_PORTAL;
            $categoryId    = CategoryType::PORTAL;
            $metadata['prsrv_pd']= date("Ymd",strtotime('+1 days'));
        }else if($flag == 'ingest_stream'){
            $channel = WorkflowChannel::INGEST_STREAM;
            //$categoryId    = CategoryType::PORTAL;
            $metadata['prsrv_pd']= date("Ymd",strtotime('+14 days'));
        }else{
            $categoryId    = 100;
            api_abort_404('channel');
        }

        //정합성 체크
        $requiredFields = [
            'media_ty',
            'cntnts_ty',
            'title',
            'regist_user_id',
            'usr_meta-shooting_orginl_atrb',
            'usr_meta-vido_ty_se',
            'usr_meta-prod_step_se',
            'usr_meta-brdcst_stle_se'
        ]; 
        ValidationHelper::emptyValidate($metadata, $requiredFields );


        //추가 인제스트 인경우 체크
        if( $flag == 'ingest_stream' ){
            $requiredFields = [
                'ctgry_id'
            ];
            ValidationHelper::emptyValidate($metadata, $requiredFields );
        }

        if( !$filename && ($flag == 'portal' && !$jobId) ){
            api_abort_404('file info');
        }

        $metaList = $this->getExplodeMeta($metadata);                
        $listMap = [
            'content' => [],
            'usr_meta' => [],
            'sys_meta' => [],
            'status_meta' => []
        ];
        foreach($metaList as $key => $list){
            $listMap[$key] = $this->mapper->fieldMapper($list, $key, true);
        }
        
        //필수 
        //$rdcst_stle_se = 'P';
        //$regist_instt; => 카테고리
        //$vido_ty_se = 'O';

       // dd($listMap['content']['ud_content_id']);
       $udContentMap = $this->mapper->getUdContentMap(true);
       $bsContentMap =$this->mapper->getBsContentMap(true);

        $listMap['content']['ud_content_id']    = $udContentMap[$listMap['content']['ud_content_id']];
        $listMap['content']['bs_content_id']    = $bsContentMap[$listMap['content']['bs_content_id']];
        //$listMap['content']['status']           = $status;
        
        //$contentId = $this->contentService->getSequence('SEQ_CONTENT_ID');
        //$listMap['content']['content_id'] = $contentId;  

        if($flag == 'portal'){
            $regUser = $this->userService->findPortalUser($listMap['content']['reg_user_id']);
            if($regUser){
                $listMap['content']['reg_user_id'] = $regUser->user_id;
                $regUserId = $regUser->user_id;
            }else{
                api_abort_404('User ID');
            }
            $listMap['content']['category_id'] = $categoryId;
        }else{
            $regUserId =  $listMap['content']['reg_user_id'];
        }
     

        // if( !empty($listMap['usr_meta']['regist_instt']) ){
        //     $detailCategory = Category::where('parent_id', $categoryId )
        //     ->where('code', $listMap['usr_meta']['regist_instt'] )->select('category_id')->first();      
        //     if($detailCategory){
        //         $categoryId    =  $detailCategory->category_id;
        //     }
        // }
        

        $content = $this->contentService->createUsingArray($listMap['content'], $listMap['status_meta'], $listMap['sys_meta'], $listMap['usr_meta'] );
    
        $contentId = $content->content_id;
        if( !$contentId ){
            api_abort('system error', -101 );
        }

        $task = $this->taskService->getTaskManager();
        $taskId = $task->insert_task_query_outside_data($contentId, $channel, 1, $regUserId, $filename );
    
        if( !$taskId ){
            api_abort('system error', -102 );
        }

        if(!empty($jobId)){
            
            //http 업로드시 jobid가 들어오면 완료처리
            $job = $this->jobService->find($jobId);
            if($job){
                $job->started_at = \Carbon\Carbon::now(); 
                $job->progress = 100;
                $job->status = JobStatus::FINISHED;
                $job->finished_at = \Carbon\Carbon::now();    
                $job->save();
                  
                $job->log()->create([
                    'type' => 'info',
                    'message' => 'HTTP업로드'
                ]); 
            }
  
        }

        $data = [
            'flag' =>  $flag ,
            'job_id' =>  $jobId ,
            'cntnts_id' =>  $contentId ,
            'task_id' => $taskId
        ];
        return $response->ok($data);
    }

    function getExplodeMeta($metadata){
        $data = [];
        foreach($metadata as $key => $val){
            list($type, $field) = explode('-', $key);
            if ( !empty($field) ) {
                if( $type == 'usr_meta' ){
                    $data ['usr_meta'][$field] = $val;
                }else if(  $type == 'sys_meta'  ){
                    $data ['sys_meta'][$field] = $val;
                }else if(  $type == 'status_meta'  ){
                    $data ['status_meta'][$field] = $val;
                }else{
                    $data ['content'][$key] = $val;
                }
            }else{
                $data ['content'][$key] = $val;
            }
        }
        return $data;
    }

    /**
     * 콘텐츠 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function update(ApiRequest $request, ApiResponse $response, array $args)
    {
        return $response->ok();
    }
    /**
     * 콘텐츠 만료일정 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateExpiredDate(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'];
        $user = auth()->user();

        $data = $request->all();
        $expiredDate = $data['expired_date'];
        if( empty( $expiredDate )){
            $expiredDate = \Carbon\Carbon::now()->addDays(14)->format('Ymd');;
        }else{
            $expiredDate = explode("-", $expiredDate);
            $expiredDate = implode("", $expiredDate);
        }
        
        $content = $this->contentService->updateExpiredDate($contentId, $user, $expiredDate);
        
        if(!empty($content)){
            return $response->okMsg($content,'만료일자가 변경되었습니다.');
        }else{
            return $response->okMsg(null,'등록자 또는 관리자만이 변경할 수 있습니다.');
        };        
    }

    public function updateHidden(ApiRequest $request, ApiResponse $response, array $args){
        $contentId = $args['content_id'];
        $data = $request->all();
        $user = auth()->user();
        // $hidden = [
        //     'is_hidden'=>$data['is_hidden']
        // ];
        $isHidden = $data['is_hidden'];
        
        $content = $this->contentService->updateHidden($contentId, $isHidden, $user);

        return $response->ok();      
        
    }

    /**
     * 콘텐츠 DD 액션
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function contentDDEvent(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'];
        $user = auth()->user();

        $data = $request->all();
        $expiredDate = $data['expired_date'];
        if( empty( $expiredDate )){
            $expiredDate = \Carbon\Carbon::now()->addDays(14)->format('Ymd');;
        }else{
            $expiredDate = explode("-", $expiredDate);
            $expiredDate = implode("", $expiredDate);
        }
        
        $content = $this->contentService->updateExpiredDate($contentId, $user, $expiredDate);

        //로깅
        createLog('contentDDEvent', $contentId,null,null,null, $user );
        
        return $response->ok();      
    }

    public function getContentTypeList(ApiRequest $request, ApiResponse $response, array $args)
    {
        // TODO : 콘텐츠 유형 불러오기
        $data = UserContent::orderBy('show_order')->get();
        return $response->ok($data);
    }

    /**
     * 사용금지 목록 조회
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function getUseProhibitList(ApiRequest $request, ApiResponse $response, array $args)
    {
        $data = $request->all();
        
        $startDate = $data['start_date'];
        $endDate = $data['end_date'];
        $limit = 200;

        $query = DB::table('bc_content_status as bcs')
        ->join('bc_usrmeta_content as buc','buc.usr_content_id','=','bcs.content_id')
        ->join('bc_content as bc','bc.content_id','=','bcs.content_id')
        ->join('bc_ud_content as ud','ud.ud_content_id','=','bc.ud_content_id')
        ->join('bc_bs_content as bs','bs.bs_content_id','=','bc.bs_content_id')
        ->join('bc_member as bm','bm.user_id','=','bcs.use_prhibt_set_user_id')
        ->where('buc.use_prhibt_at','Y')
        ->whereBetween('bcs.use_prhibt_set_dt',[$startDate,$endDate])
        ->select('bc.title','bc.content_id','bcs.use_prhibt_set_dt','bcs.use_prhibt_set_resn','buc.media_id',
                'bm.user_id','bm.user_nm',
                'ud.ud_content_title','bs.bs_content_title');

        $query->where(function ($query) {
            $query->whereNull('bcs.use_prhibt_relis_dt');
            $query->orWhere('bcs.use_prhibt_relis_dt','<','bcs.use_prhibt_set_dt' );
        });

        // 콘텐츠 유형
        if(isset($data['content_type']) && !empty($data['content_type']) && $data['content_type'] !== 'all')
        {
            $query->where('ud.ud_content_id',$data['content_type']);
        }
        // 검색 키워드
        if(isset($data['search_keyword']) && !empty($data['search_keyword'])) {
            $searchKeyword = $data['search_keyword'];
            $searchType = $data['search_type'];
            switch($searchType) {
                case 'all':
                    $query->where(function($q) use ($searchKeyword) {
                        $q->where('bc.title','like',"%{$searchKeyword}%")
                        ->orWhere('bm.user_nm','like',"%{$searchKeyword}%")
                        ->orWhere('buc.media_id','like',"%{$searchKeyword}%")
                        ->orWhere('bcs.use_prhibt_set_resn','like',"%{$searchKeyword}%");
                    });
                    break;
                case 'title':
                    $query->where('bc.title','like',"%{$searchKeyword}%");
                    break;
                case 'user_nm':
                    $query->where('bm.user_nm','like',"%{$searchKeyword}%");
                    break;
                case 'media_id':
                    $query->where('buc.media_id','like',"%{$searchKeyword}%");
                    break;
                case 'use_prhibt_set_resn':
                    $query->where('bcs.use_prhibt_set_resn','like',"%{$searchKeyword}%");
                    break;
            }
        }
        $query->orderBy('bcs.use_prhibt_set_dt','DESC');
        $useProhibitList = $query->get()->all();
        
        $res = [
            'success' => true
        ];
        
        $res['data'] = $useProhibitList;
        $res['total'] = count($useProhibitList);
        return response()->withJson($res)
        ->withStatus(200);
    }
    /**
     * 사용금지 여부 설정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateProhibitedUse(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'];
        $user = auth()->user();

        $data = $request->all();
        
        $content = $this->contentService->updateProhibitedUse($contentId, $user, $data);

        if(!empty($content)){
            $usePrhibtAt = $data['use_prhibt_at'];            
            $usePrhibtSetResn = $data['use_prhibt_set_resn'];
            if($usePrhibtAt =='Y'){
                $description = '사용금지설정-'.$usePrhibtSetResn;
            }else{
                $description = '사용금지해제-'.$usePrhibtSetResn;
            }
            $logData = [
                'action' => 'edit',
                'description' => $description,
                'content_id' => $contentId,
                'bs_content_id' => $content->bs_content_id,
                'ud_content_id' => $content->ud_content_id
            ];
            $log = $this->logService->create($logData, $user);
            return $response->okMsg($content,'사용금지 설정이 변경되었습니다.');
        }else{
            return $response->okMsg(null,'등록자 또는 관리자만이 변경할 수 있습니다.');
        };
    }

    /**
     * 외부 콘텐츠 수정
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function updateMap(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'];
        $metadata = $request->all();
        $user = auth()->user();
      
        $content = $this->contentService->findOrFail($contentId);      
        
        if( $user->user_id == 'portal' && strstr($content->category_full_path , '/0/100/203' ) ){
            //부처영상의 한해 포털 계정은 수정 오픈
        }else if( $content->status == ContentStatusType::COMPLETE || $metadata['reg_user_id'] != $content->reg_user_id ){
            api_abort('not allowed', 400);
        }

        $metaList = $this->getExplodeMeta($metadata);                
        $listMap = [
            'content' => [],
            'usr_meta' => [],
            'sys_meta' => [],
            'status_meta' => []
        ];
        foreach($metaList as $key => $list){
            $listMap[$key] = $this->mapper->fieldMapper($list, $key, true);
        }

        $content = $this->contentService->updateUsingArray($contentId, $metaList['content'],  [],[], $metaList['usr_meta'] , $user);
        //$content = $this->contentService->update($contentId, $dto, $statusDto, $sysMetaDto, $usrMetaDto, $user);

        // $dto = new DataDicCodeItemDto($data);
        // $keys = array_keys($data);
        // $dto = $dto->only(...$keys);

        // $codeItem = $this->contentService->update($contentId, $dto, $user);
        // return $response->ok($codeItem);

        return $response->ok();
    }

    public function updateMapPush(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId = $args['content_id'];
        $user = auth()->user();
        $contentMap = $this->contentService->getContentForPush($contentId);
 
        if($contentMap){
            $apiJobService = new ApiJobService();
            $apiJobService->createApiJob( 'Api\Services\ContentService', 'update', $contentMap , $contentId );
        }
        return $response->ok();
    }
    

    /**
     * 외부콘텐츠 삭제 및 승인처리 (포털)
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function deleteMap(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId  = $args['content_id'];
        $reason     = $request->input("reason");        
        $registUserId     = $request->input("regist_user_id");
        $user = auth()->user();
      
        $content = $this->contentService->findOrFail($contentId);

        if($user->user_id == 'portal'){
            $regUser = $this->userService->findPortalUser($registUserId);
            if($regUser){
                $user = $regUser;
            }else{
                api_abort_404('User ID');
            }
        }else{
            //if( $user->user_id != $content->reg_user_id ){
                api_abort('not allowed permission', 400);
            //}
        }

        if( $this->contentService->isDeleted($content) ){
            api_abort('deleted content', 400);
        }
       
        //삭제목록 생성
        $contentDelete = $this->contentService->deleteRequest($contentId, $reason, $user);         
        
        $task = $this->taskService->getTaskManager();                
        $task->set_priority(400);
        $task->setStatus('scheduled');

        //삭제워크플로우 미디어별 처리
        $workflowMaps = $this->contentService->contentDeleteWorkflowMap($contentId);
        foreach ($workflowMaps as $channel => $media) {
            $this->mediaService->deleteReady($media->media_id);
            $taskId = $task->start_task_workflow($contentId, $channel, $user->user_id );
            if($media->media_type == 'original'){
                $originalTaskId =  $taskId;
            }
        }

        //콘텐츠 삭제처리
        $content = $this->contentService->delete($contentId, $user);        
        
        //삭제 승인
        $this->contentService->deleteAccept($contentDelete->id, $originalTaskId, $user);

        return $response->ok();
    }

    /**
     * 콘텐츠 삭제
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function delete(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        
        return $response->ok();
    }


    /**
     * 다운로드 준비
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return ApiResponse
     */
    public function prepareDownload(ApiRequest $request, ApiResponse $response, array $args)
    {
        $user = auth()->user();
        
        $contentId  = $args['content_id'];
        $profile = $request->input('profile');

        //$profile
        //original , proxy, proxy15m1080
        $content = $this->contentService->isRegistered($contentId);

        $medias = $this->mediaService->getMediaByContentId($contentId);
        if( empty($medias) ){
            api_abort_404('not found media');
        }
        
        //미디어가 경우 제한
        $ifEmptyNotAllowed = false;
        //작업 가능
        switch($profile){
            case 'original':
            case 'archive':
                //아카이브 확인
                //$profile = 'archive';
            break;

            case 'proxy':
                if( $content->category_id == CategoryType::HISTORY ){
                    $ifEmptyNotAllowed = true;
                }
            break;
            case 'proxy360':
                if( $content->category_id == CategoryType::HISTORY ){
                    $ifEmptyNotAllowed = true;
                }
            break;
            case 'proxy2m1080':
            case 'proxy15m1080':   
                //역사관 홈페이지 해상도 제한                      
                if( $content->category_id == CategoryType::HISTORY ||  $content->category_id == CategoryType::HOME ){
                    $ifEmptyNotAllowed = true;
                }
            break;

            default:
                api_abort_404('not allowed profile');
            break;
        }
        
        //대상 미디어유형 찾기
        $targetMedia = null;
        foreach($medias as $media){
            if( $media->media_type == $profile ){
                $targetMedia = $media;
            }
        }

        if( $ifEmptyNotAllowed && empty($targetMedia) ){
            //없는 경우 다운로드 제한
            api_abort_404('not allowed media');
        }

        if( !$targetMedia ){
            api_abort_404('not found media');
        }

        if( $targetMedia->status != 0 ){
            //리스토어 ? 트랜스코딩
            api_abort_404('deleted media');
        }

        if( empty($targetMedia->filesize) ){
            //생성중일때
            api_abort_404('creating media');
        }

        //편진용/원본/저해상도
        $fileService = new FileService($this->container);
        $file = $fileService->findByMediaIdAndStorageId($targetMedia->media_id, StorageIdMap::CACHE );
        //요청시 만료일 갱신
        $expired_at = \Carbon\Carbon::now()->addDays($fileService->nextExpiredAt);
        if( !empty($file) && TaskStatus::isCompleted($file->status)  ){
            //캐시에 있는경우 
            $targetFilePath = $file->file_path .'/'.$file->file_name;

            
            $publish = config('publish');
            
            $data = [
                'file_id' =>  $file->id,
                'file_path' => $publish['api_download_url'] . '/' .$targetFilePath,
                'expired_at' => $file->expired_at,
                'download_url' => $publish['api_download_url'] . '/' . $targetFilePath
            ];          
            $file->expired_at = $expired_at;
            $file = $file->save();
        }else if( !empty($file) ){
            //처리중이거나 오류
            if(  TaskStatus::isWorking($file->status)  ){
                //처리중인 작업ID 조회
                $task = $this->taskService->getByTrgFileId($file->id);
                if( !empty($task) ){
                    $data = [
                        'task_id' => $task->task_id
                    ];
                }else{
                    //파일정보는 있는데 작업 정보가 없는경우?
                    //신규
                    api_abort_404('task error');
                }
            }
        }else{
            $filePathInfo = new FilePath($targetMedia->path);
            $fileMeta = [
                'file_path'     => $filePathInfo->filePath,
                'file_name'     => $filePathInfo->filenameExt,
                'file_ext'      => $filePathInfo->fileExt,
                'ori_file_name' => $filePathInfo->filenameExt,
                'file_size'     => $targetMedia->filesize,
                'expired_at'    => $expired_at,
                'storage_id'    => StorageIdMap::CACHE,
                'media_id'      => $targetMedia->media_id,
                'content_id'    => $targetMedia->content_id
            ];
            $file = $fileService->create($fileMeta);

      
            $targetWorkflow = 'cms_to_cache';            

            $task = $this->taskService->getTaskManager();
            $array_param_info = [
                [
                    'force_src_media_id' => $targetMedia->media_id,
                    'trg_file_id' => $file->id
                ]
            ];
            $taskId = $task->start_task_workflow($contentId, $targetWorkflow, $user->user_id, $array_param_info );
            $data = [
                'task_id' => $taskId
            ];
        }
        return $response->ok($data);
    }

    /**
     * 프리뷰용 URL 조회
     */
    public function previewUrl(ApiRequest $request, ApiResponse $response, array $args)
    {      
        $contentId  = $args['content_id'];     
        $previewPath = $this->mediaService->getMediaProxyPath($contentId);
        $data = [        
            'srcUrl' =>  $previewPath
        ];
        return $response->ok($data);
    }

    /**
     * VOD 생성
     *
     * @param \Api\Http\ApiRequest $request
     * @param \Api\Http\ApiResponse $response
     * @param array $args
     * @return \Api\Http\ApiResponse
     */
    public function createVOD(ApiRequest $request, ApiResponse $response, array $args)
    {
        /**         
         * 1. 콘텐츠 검색
         * 2. 서비스영상이 모두 존재하는지 확인
         * 3. 서비스영상중 없는 프로파일에 대한 변환 작업 생성         
         */
        $contentId  = $args['content_id'];
        $user = auth()->user();
        
        //콘텐츠 정상확인 삭제 
        $content = $this->contentService->isRegistered($contentId);
        if(!$content){
            return $response->okMsg(null,"not found content or invalid content");
        }

        if( !$this->isVideo($content->bs_content_id) ){
            return $response->okMsg(null,"not video");
        }

        //미디어 여부 확인
        $medias = $this->mediaService->getMediaByContentId($contentId);
        
        //콘텐츠 정상확인

        //미디어 여부 확인
        //원본 존재 여부 확인
        //작업 시작


        //대상 미디어유형
        $typeToWorkflow = [
            //'proxy'         => 'create_proxy',//720p
            'proxy15m1080'  => 'create_proxy_15m1080_portal',
            'proxy2m1080'   => 'create_proxy_2m1080_portal',
            'proxy360'      => 'create_proxy_360_portal'
        ];

        $targetTypes = $this->findEmptyMediaType($contentId, $content->ud_content_id, $medias);
      
        if (!empty($targetTypes)) {

            //소스 존재 확인
            $targetMedia = $this->getOriginal($medias);
        
            if ($targetMedia) {
                //원본이 있는경우
                $resolution = $this->getResolutionFromSystem($contentId);
                $task = $this->taskService->getTaskManager();
                foreach ($targetTypes as $targetType) {
                    //HD w
                    if ($resolution[1] < 1080 && ($targetType == 'proxy15m1080' || $targetType == 'proxy2m1080')) {
                        continue;
                    }
                    $targetWorkflow = $typeToWorkflow[$targetType];
                    if (!empty($targetWorkflow)) {
                        $arrayParamInfo = [
                            [
                                'force_src_media_id' => $targetMedia->media_id
                            ]
                        ];
                        $task->set_priority(500);
                        $task->start_task_workflow($contentId, $targetWorkflow, $user->user_id, $arrayParamInfo);
                    }
                }
            }else{
	    	
		//임시 처리 나중에 제거
	    	return $response->okMsg(null,"not found storage");
	    	
                $archiveMedia = $this->contentService->isArchived($contentId);

                if($archiveMedia){
                    $this->archiveService->priority = 500;
                    $this->archiveService->restoreNear($contentId , $user);
                }else{
                    return $response->okMsg(null,"not found archive");
                }
            }
        }else{
            return $response->okMsg(null,"no type to work on");
        }

        return $response->ok();
    }


    public function loudnessMeasure(ApiRequest $request, ApiResponse $response, array $args)
    {
        $contentId  = $args['content_id'];
        $user = auth()->user();
        
        //콘텐츠 정상확인
        $content = $this->contentService->isRegistered($contentId);
        //미디어 여부 확인
        $medias = $this->mediaService->getMediaByContentId($contentId);

        $targetMedia = $this->getOriginal($medias);
        if( !$targetMedia ){
            return $response->error('리스토어가 필요합니다.');
        }
        $channel ='loudness_measure';
        $task = $this->taskService->getTaskManager();
        $array_param_info = [
            [
                'force_src_media_id' => $targetMedia->media_id
            ]
        ];
        $taskId = $task->start_task_workflow($contentId, $channel, $user->user_id, $array_param_info );
    
        if( !$taskId ){
            api_abort('system error', -102 );
        }
        $data = [        
            'content_id' =>  $contentId ,
            'task_id' => $taskId
        ];
        return $response->ok($data);

    }

    /**
     * 원본 해상도 정보 조회
     */
    public function getResolutionFromSystem($contentId){
        $sysMeta = $this->contentService->findContentSysMeta($contentId);
        if( !empty($sysMeta->sys_display_size) ){
            $size = $sysMeta->sys_display_size;
            $sizeArray = explode(' ', $size);
            //공백 제외
            $sizeVal = strtolower( array_shift($sizeArray) );
          
            $aspect = explode('x', $sizeVal);
            if( count($aspect)  < 2 ){
                $aspect = explode('*', $sizeVal);
            }
            $width = (int)$aspect[0];
            $height = (int)$aspect[1];
            return [
                $width,
                $height
            ];
        }

        return false;
    }

    public function findEmptyMediaType($contentId,$udContentId, $medias){
        //미디어 여부 확인
        $medias = $this->mediaService->getMediaByContentId($contentId);
        //1723

        $checkType = [
            //'proxy',//720p 기본생성이라고 판단
            'proxy15m1080',
            'proxy2m1080',
            'proxy360'
        ];

        $existType = [];


        foreach($medias as $media){
            if( in_array($media->media_type, $checkType )  ){
                ///dump($media->media_type);
                array_push($existType, $media->media_type );
            }
        }
        //작업 대상 프로파일
        $targetTypes = array_diff($checkType, $existType);
        return $targetTypes;
    }


    /**
     * 원본 미디어 조회
     *
     * @return $collection || false
     */
    public function getOriginal($medias){
        foreach($medias as $media){
            if( $media->media_type == 'original' && $media->status != 1){
                return $media;
            }
        }

        foreach($medias as $media){
            if( $media->media_type == 'archive' && $media->status != 1){
                return $media;
            }
        }
        
        return false;
    }

    /**
     * 원본 미디어 유형 비디오 여부
     *
     * @param [type] $bsContentId
     * @return boolean
     */
    public function isVideo($bsContentId){
        if($bsContentId == BsContentType::MOVIE){
            return true;
        }
        return false;
    }

    public function getNotNull($dto){
        $newData = [] ; 
        foreach($dto->toArray() as $key => $val){      
            if( $val != null ){
                $newData [] = $key;
            }
        }
        return $newData;
    }

    public function dtoMap($dto, $data){
        
        foreach ($dto->toArray() as $key => $val) {      
           
            if (isset($data[$key])) {
                $dto->$key =  $data[$key];
            }
        }
        return $dto;
    }

    public function createClip(ApiRequest $request, ApiResponse $response, array $args){
        $user       = auth()->user();
        $data       = $request->all();
        $contentId  = $args['content_id'];
     
        $newTitle = $data['title'];
        $newUdContentId = $data['ud_content_id'];
        $inOutList = json_decode($data['in_out_list'],true);

        $bfContent = $this->contentService->getContentByContentId($contentId);
        $bfContentData = $bfContent->toArray();

        if( !$this->contentService->isRegistered($contentId) ){
            api_abort('등록된 콘텐츠만 생성 가능합니다', 400 );
        }else{
            if( $bfContent->status != ContentStatusType::COMPLETE ){
                api_abort('콘텐츠 승인 후에 생성 가능합니다', 400 );
            }
        }

        if( strstr($bfContent->category_full_path, '/0/100/200') ||  strstr($bfContent->category_full_path, '/0/100/201') ||  strstr($bfContent->category_full_path, '/0/100/205') ){
            //AND ( c.category_full_path LIKE '/0/100/200%' OR c.category_full_path LIKE '/0/100/201%' OR c.category_full_path LIKE '/0/100/205%'  )
        }else{
            api_abort('구간추출이 제한된 카테고리 콘텐츠입니다.', 400 );
        }
        
        //콘텐츠ID 생성
        //부모 콘텐츠ID 생성
        //등록자 변경
        //생성일자
        //
        unset($bfContentData['content_id']);
        unset($bfContentData['created_date']);
        unset($bfContentData['expired_date']);
        unset($bfContentData['updated_at']);
        unset($bfContentData['status']);

        $regUserId = $user->user_id;

        $bfContentData['title']             = $newTitle;
        $bfContentData['ud_content_id']     = $newUdContentId;
        $bfContentData['parent_content_id'] = $contentId;
        $bfContentData['reg_user_id']       = $regUserId;

        $usrMeta = $bfContentData['usr_meta'];

        unset($bfContentData['usr_meta']);
        unset($bfContentData['sys_meta']);
        unset($bfContentData['status_meta']);
        
        unset($usrMeta['media_id']);

        // 사용금지 여부
        if($usrMeta['use_prhibt_at'] == 'Y'){
            api_abort('사용금지 된 콘텐츠 입니다. 아카이브팀에게 문의 바랍니다', 500);
        };
        
        
        // // 엠바고 해제일시
        if(!is_null($usrMeta['embg_relis_dt']) && ($usrMeta['embg_at'] == 'Y')){
            $embargoDateTimeStamp = strtotime($usrMeta['embg_relis_dt']);
            $nowDateTimeStamp = \Carbon\Carbon::now()->timestamp;
            $embargoDateTimeStamp = \Carbon\Carbon::createFromTimestamp($embargoDateTimeStamp);
            $nowDateTimeStamp = \Carbon\Carbon::createFromTimestamp($nowDateTimeStamp);
            if($embargoDateTimeStamp > $nowDateTimeStamp){
                api_abort('엠바고 해제일시가 지난 후 다운로드 해주세요.', 500);
            }
        };
        
        $filename = '';
        $mediaInfo = $this->mediaService->getMediaWithStorageByContentId($contentId);

        $originalMedia = null;
        $archiveMedia = null;
        foreach($mediaInfo as $media)
        {
            if( $media->media_type == 'original' && $media->filesize > 0 ){
                $filename = $media->path ;
                $filenameArray = explode('.', $filename);
                $filenameExt = strtolower(array_pop($filenameArray));
                $originalMedia = $media;
            }

            if( $media->media_type == 'archive' && $media->filesize > 0 ){
                $archiveMedia = $media;
            }
        }
        if( !$filename ){
            api_abort('원본이 없습니다', 400 );
        }
        $channel ='regist_clip';

        if($originalMedia->status == 1){
            if($archiveMedia->status == 0 ){
                $channel ='regist_clip_archive';
                $filename = $archiveMedia->path;
            }else{
                api_abort('아카이브된 영상입니다.<br />리스토어 후 생성 바랍니다.', 400);
            }
        }

        //미디어ID
        $newContent = $this->contentService->createUsingArray($bfContentData, [], [], $usrMeta );

        $newContentId = $newContent->content_id;
        if( !$contentId ){
            api_abort('system error', -101 );
        }

        $array_param_info = array(
            array('value' => $inOutList[0]['in_frame']),
            array('value' => $inOutList[0]['out_frame'])
        );

        $task = $this->taskService->getTaskManager();
        $taskId = $task->insert_task_query_outside_data($newContentId, $channel, 1, $regUserId, $filename ,null, $array_param_info );
    
        if( !$taskId ){
            api_abort('system error', -102 );
        }
        $data = [        
            'content_id' =>  $newContentId ,
            'task_id' => $taskId
        ];
        return $response->ok($data);
    }

    /**
     * 다운로드 전 권한 체크
     *
     * @param ApiRequest $request
     * @param ApiResponse $response
     * @param array $args
     * @return void
     */
    public function checkContent(ApiRequest $request, ApiResponse $response, array $args){
        $data       = $request->all();
        $user       = auth()->user();
        $userId = $user->user_id;

        $contentId  = $args['content_id'];
        $checkList = json_decode($data['check_list']);

        $mediaId = $data['media_id'];
        $media_type = $data['media_type'];
        
        $bfContent = $this->contentService->getContentByContentId($contentId);
        $bfContentData = $bfContent->toArray();
        
        $usrMeta = $bfContentData['usr_meta'];

        //미디어 권한 체크
        if( !empty($mediaId) ){
            $medias = $this->mediaService->getMediaByContentId($contentId);     
            foreach($medias as $media)
            {
                if( $media->media_id == $mediaId ){
                    $targetMedia = $media;
                }
            }
            if( empty($targetMedia) ){
                api_abort_404('media');
            }
            //전송용은 다운로드 권한 체크해서 
            if( $targetMedia->media_type == 'proxy15m1080' || $targetMedia->media_type == 'original' || $targetMedia->media_type == 'archive' ){
                //미디어 유형 확인
                $grant = 16;
             
                $isGrant = false;
                $udContentId = $bfContent->ud_content_id;
         
                $grantType = 'content_grant';

                $groups = $this->groupService->listByMemberId($user->member_id);
       
                foreach($groups as $group){
                    $groupGrants = $group->grants;                  
                    foreach($groupGrants as $groupGrant){
                        $groupGrantCheck = (int)$groupGrant->group_grant;
                
                        if(!empty($groupGrantCheck)){
                            if (($groupGrantCheck & $grant) == $grant)
                                $isGrant = true;
                        }
                    }
                }
                if( !$isGrant ){
                    api_abort('다운로드 권한이 없습니다', 403 );
                }
            }
        }

        
        if(  ( $media_type == 'archive' || $media_type == 'original' ) ){
            $serverHost = get_server_param('HTTP_HOST');
            $servers = explode(",", config('sms_auth')['domain']);  
            
            //내부 외부 구분
            if (in_array($serverHost, $servers)) {
                api_abort('다운로드 할 수 없는 유형입니다', 400 ,400);
            }else{
            }
        }


        // 사용금지 여부
        if(in_array('use',$checkList)){
            if($usrMeta['use_prhibt_at'] == 'Y'){
                api_abort('사용금지 된 콘텐츠 입니다. 아카이브팀에게 문의 바랍니다', 400);
            };
        }
        $groups = $this->groupService->listByMemberId($user->member_id);
        $embargoDownloadGrant = $this->permissionService->searchByPath('embargo_download', $user, $groups);

        if(empty($embargoDownloadGrant) && in_array('embargo',$checkList)){
            // // 엠바고 해제일시
            if(!is_null($usrMeta['embg_relis_dt']) && ($usrMeta['embg_at'] == 'Y')){
                $embargoDateTimeStamp = strtotime($usrMeta['embg_relis_dt']);
                $nowDateTimeStamp = \Carbon\Carbon::now()->timestamp;
                $embargoDateTimeStamp = \Carbon\Carbon::createFromTimestamp($embargoDateTimeStamp);
                $nowDateTimeStamp = \Carbon\Carbon::createFromTimestamp($nowDateTimeStamp);
                if($embargoDateTimeStamp > $nowDateTimeStamp){
                    api_abort('엠바고 해제일시가 지난 후 다운로드 해주세요.', 400);
                }

            };
        }

        return $response->ok();
    }

    public function updateType(ApiRequest $request, ApiResponse $response, array $args){
        $acceptIds = [1,2,3,7,9];
        $contentId = $args['content_id'];

        $data = $request->all();
        
        $udContentId = $data['ud_content_id'];

        if(!in_array($udContentId,$acceptIds)){
            api_abort('변경할 수 없는 유형입니다.', 400);
        }

        $user = auth()->user();
        $content = $this->contentService->findOrFail($contentId);
        
        if($content->ud_content_id == $udContentId){
            api_abort('변경할 유형을 선택해주세요.', 400);
        }

        $this->contentService->updateType($contentId, $udContentId);
        $logData = [
            'action' => 'edit',
            'description' => 'edit_content_type',
            'content_id' => $contentId,
            'bs_content_id' => $content->bs_content_id,
            'ud_content_id' => $content->ud_content_id
        ];
        $log = $this->logService->create($logData, $user);

        $oldUserContent = $this->userContentService->getUdContentByUdContentId($content->ud_content_id);
        $newUserContent = $this->userContentService->getUdContentByUdContentId($udContentId);

        $logDetailData = [
            'action' =>'edit',
            'usr_meta_field_code' => 'ud_content_id',
            'new_contents' => $newUserContent->ud_content_title,
            'old_contents' => $oldUserContent->ud_content_title
        ];
        $logDetail = $this->logService->createDetail($log->log_id, $logDetailData);
        return $response->ok();      
    }

    public function getPersonalInformationDetection(ApiRequest $request, ApiResponse $response, array $args)
    {

        $contentId  = $args['content_id'];
        $contents = $this->contentService->getPersonalInformationDetection($contentId);
        return $response->ok($contents);
    }

}

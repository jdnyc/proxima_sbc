<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\Content;
use Api\Models\Category;
use Api\Models\FolderMng;
use Api\Models\InDvdLInfo;
use Api\Types\UdContentId;
use Api\Types\CategoryType;
use Api\Models\ArchiveMedia;
use Api\Types\BsContentType;
use Api\Types\UdContentType;
use Api\Models\ContentDelete;
use Api\Models\ContentStatus;
use Api\Services\BaseService;
use Api\Models\ContentSysMeta;
use Api\Models\ContentUsrMeta;
use Api\Services\MediaService;
use Api\Services\ApiJobService;
use Api\Types\ContentStatusType;
use Api\Services\DTOs\ContentDto;
use Api\Services\DTOs\ContentStatusDto;
use Api\Support\Helpers\MetadataMapper;
use Api\Services\DTOs\ContentSysMetaDto;
use Api\Services\DTOs\ContentUsrMetaDto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Capsule\Manager as DB;

class ContentService extends BaseService
{
    public function getContentList($param)
    {
        //dbd();
        // $contents = Content::whereIn('content_id', [1, 2, 3, 4, 5, 6, 7])
        //     ->offset(2)
        //     ->limit(2)
        //     ->get(['content_id', 'title']);

        // $content = Content::find(1);
        //die();
        if ($param->is_deleted) {
            $is_deleted = $param->is_deleted;
        } else {
            $is_deleted = 'N';
        }

        $status = 2;
        $query = Content::query();
        $query->where('is_deleted', '=', $is_deleted);
        $query->where('status', '=', $status);
        if ($param->ud_content_id) {
            $query->where('ud_content_id', '=', $param->ud_content_id);
        }
        $query->with('statusMeta');
        $query->with('usrMeta');
        $query->with('sysMeta');
        $lists = paginate($query);
        return $lists;
    }

    /**
     * 콘텐츠 검색
     *
     * @param  $param
     * @return 
     */
    public function search($param)
    {
        // $isDeleted = $param['is_deleted'] ?? bool_to_yn(false);
        $isDeleted = $param->is_deleted ?? bool_to_yn(false);

        // 등록완료 상태
        $status = 2;
        $query = Content::query();
        $query->where('is_deleted', '=', $isDeleted);
        $query->where('status', '=', $status);
        
        // $udContentId = $param['ud_content_id'];
        $udContentId = $param->ud_content_id;
    
        if ($udContentId) {
            if(is_array($udContentId)) {
                $query->whereIn('ud_content_id', $udContentId);
            } else {
                $query->where('ud_content_id', '=', $udContentId);
            }
        }

        // $keyword = $param['keyword'];
        $keyword = $param->keyword;
        if($keyword) {
            $query->where('title', 'like', "{$keyword}%");
        }

        // $mediaId = $param['media_id'];
        $mediaId = $param->media_id;
        if($mediaId) {
            // 나중에 join으로 처리
        }

        $query->with('statusMeta');
        $query->with('usrMeta');
        $query->with('sysMeta');
        $lists = paginate($query);
        return $lists;
    }

    /**
     * contentId로 content조회
     *
     * @param [type] $contentId
     * @return $collection
     */
    public function getContentByContentId($contentId)
    {
        $query = Content::where('content_id', '=', $contentId);
        $query->with('statusMeta');
        $query->with('usrMeta');
        $query->with('sysMeta');

        $collection = $query->first();
        // $collection->usrMeta = \Api\Models\ContentUsrMeta::find($collection->content_id);
        // $collection->sysMeta = \Api\Models\ContentSysMeta::find($collection->content_id); 
        // $collection->statusMeta = \Api\Models\ContentStatus::find($collection->content_id); 

        return $collection;
    }
    /**
     * contentId로 content조회
     *
     * @param [type] $contentId
     * @return $collection
     */
    public function find($contentId)
    {
        $query = Content::where('content_id', '=', $contentId);
        $collection = $query->first();
        return $collection;
    }

    /**
     * 조회 또는 실패 처리
     *
     * @param integer $id
     * @return Content
     */
    public function findOrFail(int $id)
    {
        $collection = Content::find($id);
        if (!$collection) {
            api_abort_404('Content not found');
        }
        return $collection;
    }

    /**
     * 콘텐츠 삭제여부 
     *
     * @param [collection] $content
     * @return boolean
     */
    public function isDeleted($content)
    {
        if( $content->is_deleted == 'Y' ){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 콘텐츠 만료일자 변경
     *
     * @param integer $id
     * @param string $expired_date
     */
    public function updateExpiredDate(int $id, $user, $expired_date)
    {
        $content = $this->findOrFail($id);
        // 관리자 체크
        $hasAdmin = $user->hasAdminGroup();
        // 유저 아이디
        $userId = $user->user_id;
        // 등록자 아이디
        $regUserId = $content->reg_user_id;
        
        $status = $this->findStatusMeta($content->content_id);

        if ($status->restore_at == 1) {
        
            //리스토어인경우만 연장
            $content->expired_date = $expired_date; 
            $content->save();            
            return $content;
        }
        return false;
    }
    /**
     * 사용금지 여부 설정
     *
     * @param integer $id
     * @param [type] $user
     */
    public function updateProhibitedUse(int $id, $user, $data)
    {
        $content = $this->findOrFail($id);
        $contentStatus = $this->findStatusMeta($id);

        // 관리자 체크
        $hasAdmin = $user->hasAdminGroup();
        //아카이브 그룹 체크
        $hasArchiveGroup = $user->hasArchiveGroup();
        // 유저 아이디
        $userId = $user->user_id;
        // 등록자 아이디
        $regUserId = $content->reg_user_id;

        // 사용금지 여부 Y or N
        $usePrhibtAt = $data['use_prhibt_at'];
        $usePrhibtSetResn = $data['use_prhibt_set_resn'];
        // 등록자 이거나 관리자 일때 
        if (($userId === $regUserId) || $hasAdmin || $hasArchiveGroup ) {
            $usrContent = $this->findContentUsrMeta($id);
            $usrContent->use_prhibt_at = $usePrhibtAt;

            if($usePrhibtAt === "Y"){
                // 사용금지 설정 일시
                $contentStatus->use_prhibt_set_dt = date("YmdHis");
                // 사용금지설정 사용자 ID
                $contentStatus->use_prhibt_set_user_id = $userId;
                // 사용금지 설정 사유
                $contentStatus->use_prhibt_set_resn = $usePrhibtSetResn;
                $usrContent->use_prhibt_cn = $usePrhibtSetResn;
            }

            if($usePrhibtAt === "N"){
                // 사용금지 해제 일시
                $contentStatus->use_prhibt_relis_dt = date("YmdHis");
                // 사용금지해제사용자 ID
                $contentStatus->use_prhibt_relis_user_id = $userId;
            }

            $contentStatus->save();
            $usrContent->save();
            $this->container['searcher']->update($id);
            $contentMap = $this->getContentForPush($id);
            if($contentMap){
                $apiJobService = new ApiJobService();
                $apiJobService->createApiJob( __CLASS__, 'update', $contentMap , $id );
            }
            return $content;
        }else{
            return false;
        }
  
    }

    public function updateHidden($contentId, $isHidden, $user){
        $content = $this->findOrFail($contentId);
        // 관리자 체크
        // $hasAdmin = $user->hasAdminGroup();
        $content->is_hidden = $isHidden;
        $content->save();
        //검색엔진 업데이트 
        $this->container['searcher']->update($contentId);
    }

    public function updateType($contentId,$udContentId){
        $content = $this->findOrFail($contentId);
        // 관리자 체크
        // $hasAdmin = $user->hasAdminGroup();
        $content->ud_content_id = $udContentId;
        $content->save();
        //검색엔진 업데이트 
        $this->container['searcher']->update($contentId);
    }

    public function findStatusMeta(int $id)
    {
        $collection = ContentStatus::find($id);  
        if (!$collection) {
            $collection = new ContentStatus();
            $collection->content_id =$id;
            $collection->save();
        }
        return $collection;
    }

    public function findContentUsrMeta(int $id)
    {
        $collection = ContentUsrMeta::find($id);
        return $collection;
    }

    public function findContentSysMeta(int $id)
    {
        $collection = ContentSysMeta::find($id);
        return $collection;
    }
    /** contentId로 content조회
     *
     * @param [type] $contentId
     * @return void
     */
    public function getContentByParentContentId($contentId)
    {
        $content = Content::findOrFail($contentId);
        $parentContentId = $content->parent_content_id;
        
        // 해당 콘텐츠를 부모로 바라보고 있는 컨텐츠들
        if( !empty($parentContentId) ){
            //하위 콘텐츠인 경우
            $query = Content::where('is_deleted', '=', 'N')
            ->where(function ($query) use ($parentContentId) {
                $query->where('parent_content_id', '=', $parentContentId)
                      ->orWhere('content_id', '=',  $parentContentId);
            });
        }else{
            //상위 콘텐츠인 경우
            $query = Content::where('is_deleted', '=', 'N')
            ->where(function ($query) use ($contentId) {
                $query->where('parent_content_id', '=', $contentId)
                      ->orWhere('content_id', '=',  $contentId);
            });
        }
        $query->with('usrMeta');
        $query->orderBy('parent_content_id', 'desc');
        $collections = $query->get();

        // foreach($collections as $key => $collection){
        //     // 메인이 있다면 메인을 배열의 첫번쨰와 체인지
        //     if(!($collection->parent_content_id == $contentId)){
        //         $col = $collections[0];
        //         $collections[0] = $collection;
        //         $collections[$key] = $col;
        //     break;
        //     };
        // };
        

        return $collections;
    }

 /**
  * 콘텐츠 생성
  *
  * @param ContentDto $dto
  * @param ContentStatusDto $statusDto
  * @param ContentSysMetaDto $sysMetaDto
  * @param ContentUsrMetaDto $usrMetaDto
  * @param User $user
  * @return Content
  */
    public function create(ContentDto $dto = null, ContentStatusDto $statusDto = null, ContentSysMetaDto $sysMetaDto = null, ContentUsrMetaDto $usrMetaDto = null, User $user)
    {

        $content = new Content();
        if (!empty($dto)) {
            foreach ($dto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $content->$key = $val;
            }
        }
        $content->reg_user_id = $user->user_id;
        $content->updated_user_id = $user->user_id;

        if (is_null($content->is_deleted) ){
            $content->is_deleted = 'N';
        }

        if (is_null($content->is_hidden) ){
            $content->is_hidden = '0';
        }

        if (is_null($content->expired_date) ){
            $content->expired_date = '99991231';
        }

        $content->save();

        if (!empty($statusDto)) {
            $status = new ContentStatus();
            foreach ($statusDto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $status->$key = $val;
            }
            $status->save();
        }

        if (!empty($sysMetaDto)) {
            $sysMeta = new ContentSysMeta();
            foreach ($sysMetaDto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $sysMeta->$key = $val;
            }
            $sysMeta->save();
        }

        if (!empty($usrMetaDto)) {
            $usrMeta = new ContentUsrMeta();
            foreach ($usrMetaDto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $usrMeta->$key = $val;
            }
            $usrMeta->save();
        }

        // $contentMap = $this->getContentForPush($contentId);
        // $apiJobService = new ApiJobService();
        // $apiJobService->createApiJob( __CLASS__, __FUNCTION__, $contentMap );


        return $content;
    }

    /**
  * 콘텐츠 생성 array
  *
  * @param array $contentMeta
  * @param array $statusMeta
  * @param array $sysMeta
  * @param array $usrMeta
  * @return Content
  */
    public function createUsingArray($contentMeta, $statusMeta = [], $sysMeta = [], $usrMeta = [] )
    {
        $content = new Content($contentMeta);

        //기본값
        if( is_null($content->content_id) ){
            $content->content_id =  self::getSequence('SEQ_CONTENT_ID');
        }
        if (is_null($content->title) ){
            $content->title = 'no title';
        }
        if (is_null($content->is_deleted) ){
            $content->is_deleted = 'N';
        }
        if (is_null($content->is_hidden) ){
            $content->is_hidden = '0';
        }
        if ( !empty($content->category_id) && empty($content->category_full_path) ){
            $content->category_full_path = $this->getCategoryFullPath($content->category_id);
        }
        if( is_null($content->bs_content_id) ){
            $content->bs_content_id = BsContentType::MOVIE;
        }
        if( is_null($content->content_id) ){
            $content->ud_content_id = UdContentType::INGEST;
        }
        if( is_null($content->status)  ){
            $content->status = ContentStatusType::REGISTERING;  
        }
        if (is_null($content->expired_date) ){
            $content->expired_date = $this->createContentExpiredDate($content->ud_content_id, $content->category_full_path ,$content->category_id );
        }

        if( !empty($contentMeta['created_date']) ){
            $content->created_date = $contentMeta['created_date'];
        }
        //dump( $content);
        $content->save();      
        $contentId = $content->content_id;

        $status = new ContentStatus($statusMeta);
        $status->content_id = $contentId;
        $usr = new ContentUsrMeta($usrMeta);
        $usr->usr_content_id = $contentId;

        // foreach($usrMeta as $key => $val)
        // {
        //     $usr->$key = $val;
        // }
        $sys = new ContentSysMeta($sysMeta);
        $sys->sys_content_id = $contentId;

        if( is_null($usr->media_id) ){
            //미디어ID 발급
            $usr->media_id = $this->getMediaId($content->bs_content_id ); 
        }

        // dump( $usr);
        // dump( $sys);
        // dump( $status);

        $usr->save();
        $status->save();
        $sys->save();

        $contentMap = $this->getContentForPush($contentId);
        if($contentMap){            
            $apiJobService = new ApiJobService();
            $apiJobService->createApiJob( __CLASS__, 'create', $contentMap, $contentId );
        }

        return $content;
    }

   /**
    * 콘텐츠 수정
    *
    * @param int $contentId
    * @param ContentDto $dto
    * @param ContentStatusDto $statusDto
    * @param ContentSysMetaDto $sysMetaDto
    * @param ContentUsrMetaDto $usrMetaDto
    * @param User $user
    * @return Content
    */
    public function update($contentId, ContentDto $dto = null, ContentStatusDto $statusDto = null, ContentSysMetaDto $sysMetaDto = null, ContentUsrMetaDto $usrMetaDto = null, User $user)
    {
        $content = $this->findOrFail($contentId);  
        if (!empty($dto)) {
            foreach ($dto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $content->$key = $val;
            }
            
            if (is_null($dto->updated_at) ){
                $content->updated_at = date("YmdHis");
            }
            if (is_null($dto->last_modified_date) ){
                $content->last_modified_date = date("YmdHis");
            }
            if (is_null($dto->updated_user_id) ){
                $content->updated_user_id = $user->user_id;
            }
        }else{           
            $content->updated_at = date("YmdHis");       
            $content->last_modified_date = date("YmdHis");   
            $content->updated_user_id = $user->user_id;         
        }
        //dump( $content);
        $content->save();

        if (!empty($statusDto)) {
            $status = $this->findStatusMeta($contentId);
            foreach ($statusDto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $status->$key = $val;
            }
            $status->save();
        }

        if (!empty($sysMetaDto)) {
            $sysMeta = $this->findContentSysMeta($contentId);
            foreach ($sysMetaDto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $sysMeta->$key = $val;
            }
            $sysMeta->save();
        }

        if (!empty($usrMetaDto)) {
            $usrMeta = $this->findContentUsrMeta($contentId);
            foreach ($usrMetaDto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $usrMeta->$key = $val;
            }
            $usrMeta->save();
        }


        // $contentMap = $this->getContentForPush($contentId);
        // $apiJobService = new ApiJobService();
        // $apiJobService->createApiJob( __CLASS__, __FUNCTION__, $contentMap , $contentId );

        return $content;
    }
    /**
        * 콘텐츠 수정 array
        *
        * @param array $contentMeta
        * @param array $statusMeta
        * @param array $sysMeta
        * @param array $usrMeta
        * @return Content
        */
    public function updateUsingArray($contentId, $contentMeta, $statusMeta = [], $sysMeta = [], $usrMeta = [] , $user)
    {
        $content = $this->findOrFail($contentId);  
        $contentMeta = (object)$contentMeta;     
        //기본값       
        if (!is_null($contentMeta->title) ){
            $content->title = $contentMeta->title;
        }

        if (!is_null($contentMeta->expired_date) ){
            $content->expired_date = $contentMeta->expired_date;
        }
        if (!is_null($contentMeta->status) ){
            $content->status = $contentMeta->status;
        }

        if ( !empty($contentMeta->category_id) ){
            $content->category_id = $contentMeta->category_id;
            $content->category_full_path = $this->getCategoryFullPath($contentMeta->category_id);
        }

        $content->updated_at = date("YmdHis");
        $content->last_modified_date = date("YmdHis");
        $content->updated_user_id = $user->user_id;        
        $content->save();

        $usrCollection = $this->findContentUsrMeta($contentId);
        foreach($usrMeta as $key => $val){
            if( $key == 'media_id' ) continue;
            $usrCollection->$key = $val;
        }
        
        $usrCollection->save();

        //검색엔진 업데이트 
        $this->container['searcher']->update($contentId);
        
        $contentMap = $this->getContentForPush($contentId);
        if($contentMap){
            $apiJobService = new ApiJobService();
            $apiJobService->createApiJob( __CLASS__, 'update', $contentMap , $contentId );
        }

        return $content;
    }

    /**
     * 콘텐츠 삭제
     *
     * @param int $contentId
     * @param User $user
     * @return void
     */
    public function delete($contentId, User $user)
    {
        $content = $this->findOrFail($contentId);
        $ret = $content->delete();


        //검색엔진 업데이트 
        $this->container['searcher']->delete($contentId);
        

        $contentMap = $this->getContentForPush($contentId);
        if($contentMap){
            $apiJobService = new ApiJobService();
            $apiJobService->createApiJob( __CLASS__, 'update', $contentMap , $contentId );
        }

        return $ret;
    }

    
    /**
     * 콘텐츠 복원
     *
     * @param  $contentId 복원할  아이디

     * @return bool|null 복원 성공여부
     */
    public function restore($contentId)
    {
        $content = Content::where('content_id', $contentId)
            ->first();

        if (!$content) {
            api_abort_404('Content');
        }

        $content->is_deleted = 'N';
        $content->last_modified_date = date('YmdHis');
        $content->updated_at = date('YmdHis');
        $content->save();       

        //검색엔진 업데이트 
        $this->container['searcher']->update($contentId);        

        $contentMap = $this->getContentForPush($contentId);
        if($contentMap){
            $apiJobService = new ApiJobService();
            $apiJobService->createApiJob( __CLASS__, 'update', $contentMap , $contentId );
        }

        return $content;
    }

    /**
     * 콘텐츠 삭제 요청
     *
     * @param int $contentId
     * @param User $user
     * @return void
     */
    public function deleteRequest($contentId, $reason, User $user)
    {
        $content = $this->findOrFail($contentId);
        
        $delete = new ContentDelete();
        $delete->id = self::getSequence('SEQ_BC_DELETE_CONTENT_ID');
        $delete->content_id = $contentId;
        $delete->status = 'REQUEST';
        $delete->delete_type = 'CONTENT';
        $delete->reg_user_id = $user->user_id;
        $delete->reason = $reason ;
        $delete->save();

        return $delete;
    }


    /**
     * 콘텐츠 삭제요청 승인
     *
     * @param [type] $id
     * @param [type] $taskId
     * @param User $user
     * @return void
     */
    public function deleteAccept($id, $taskId, User $user)
    {
        $delete = ContentDelete::find($id);
        if( !$delete ){
            api_abort_404('deleteRequest');
        }        
       
        $delete->status = 'CONFIRM';
        $delete->execut_user_id = $user->user_id;
        $delete->task_id = $taskId;
        $delete->save();

        return $delete;
    }

     /**
     * 콘텐츠 삭제 작업 성공
     *
     * @param [type] $id
     * @param [type] $taskId
     * @return 
     */
    public function deleteCompleteByTaskId($taskId)
    {
        $delete = ContentDelete::where('task_id',$taskId)->first();
        if( $delete ){
            $delete->status = 'SUCCESS';
            $delete->save();
        }
        return $delete;
    }
    /**
     * 콘텐츠 타입이 오리지널일떼 삭제 작업 성공
     */
    public function deleteCompleteOriginal($contentId){
        $contentStatus = ContentStatus::find($contentId);
        if(!$contentStatus){
            api_abort_404('ContentStatus');
        }
        $contentStatus->restore_at = ContentStatusType::NOTRESTORE;
        $contentStatus->save();
        $this->container['searcher']->update($contentId);
        return $contentStatus;
        
    }

    /**
     * 콘텐츠 삭제 작업 실패
     *
     * @param [type] $id
     * @param [type] $taskId
     * @return 
     */
    public function deleteErrorByTaskId($taskId)
    {
        $delete = ContentDelete::where('task_id',$taskId)->first();
        if( $delete ){
            $delete->status = 'FAIL';
            $delete->save();
        }
        return $delete;
    }

    /**
     * 콘텐츠 정상 등록 여부 확인
     *
     * @param integer $id
     * @return $collection
     */
    public function isRegistered(int $id)
    {
        $collection = $this->findOrFail($id);
        if( $collection->is_deleted == 'N' && $collection->status >= 0 ){
            return $collection;
        }else{
            api_abort('invalid content',-105);
        }
    }

    
    public function getCategoryFullPath($id)
    {
        $categoryInfo =  Category::where('category_id', $id)->first();
        if(!$categoryInfo) return '/0';
        $parentId = $categoryInfo->parent_id;
        if ($parentId != -1 && $parentId !== 0 && !is_null($parentId)) {
            $selfId = $this->getCategoryFullPath($parentId);
        }

        return $selfId . '/' . $id;
    }

    /**
     * DTL 아카이브 여부
     *
     * @param integer $id
     * @return boolean
     */
    public function isArchived(int $id)
    {
        
        $archiveMedia = ArchiveMedia::where('content_id' , $id)->first();
        if( !empty($archiveMedia) ){
            return $archiveMedia;
        }else{
            return false;
        }
    }

    /**
     * KTV 미디어 아이디 발행
     *
     * @param [type] $mediaCode
     * @return void
     */
    /**
     * KTV 미디어 아이디 발행
     *
     * @param [type] $mediaCode
     * @return void
     */
    public function getMediaId($bs_content_id, $category_id = null, $ymd = null )
    {
        $seqName = 'SEQ_KTV_MEDIA_ID';
        $ymd = empty($ymd) ?  date("Ymd") : $ymd;
        $codeMap = array(
            '506' => 'M',
            '515' => 'A',
            '518' => 'I',
            '57078' => 'S',
            '57057' => 'D'
        );
        $mediaCode = $codeMap[$bs_content_id];// V / A / I / C 영상유형

        //테스트 기간 
        //$mediaCode = 'T';

        //mig용
        if($category_id == CategoryType::HOME ){
            $mediaCode = 'H';

            $lastMediaId = $this->getLastMediaId($ymd.$mediaCode);
            $getSeq = str_replace($ymd.$mediaCode,'', $lastMediaId);
            $getSeqNum = (int)$getSeq;
            if( empty($getSeqNum) ){
                $getSeqNum =1;
            }else{
                $getSeqNum =$getSeqNum + 1;
            }
            $seq = str_pad($getSeqNum,  5, "0", STR_PAD_LEFT);
            $mediaId = $ymd . $mediaCode . $seq ;
            return $mediaId;         
        }else if( $category_id == CategoryType::HISTORY ){
            $mediaCode = 'E';

            $lastMediaId = $this->getLastMediaId($ymd.$mediaCode);
            $getSeq = str_replace($ymd.$mediaCode,'', $lastMediaId);
            $getSeqNum = (int)$getSeq;
            if( empty($getSeqNum) ){
                $getSeqNum =1;
            }else{
                $getSeqNum =$getSeqNum + 1;
            }
            $seq = str_pad($getSeqNum,  5, "0", STR_PAD_LEFT);
            $mediaId = $ymd . $mediaCode . $seq ;
            return $mediaId;         
        }
  
        //매일 리셋
        //self::getCycleSeq($seqName);
        //seq 조회
        $seq = self::getSequence($seqName);
        //5자리 보정
        $seq = str_pad($seq,  5, "0", STR_PAD_LEFT);
        //sprintf('%05d', $seq);
        $mediaId = $ymd . $mediaCode . $seq ;
        return $mediaId;
    }

    public function getLastMediaId($prefix)
    {

        $maxMediaId = ContentUsrMeta::where('media_id','like', $prefix.'%')->max('media_id');     
        return $maxMediaId;
    }

    /**
     * 미디어ID로 파일명 생성
     *
     * @param [type] $mediaId
     * @param [type] $fileExt
     * @return void
     */
    public static function getFileName($mediaId, $fileExt)
    {
        $brodTypeCode = ''; //BRDCST_STLE_SE;//방송형태구분
        $prodStepCode = ''; //PROD_STEP_SE='';//제작단계구분
        $mediaCode = substr($mediaId, 8, 1);
        if ($mediaCode == 'V') {
            //$brodTypeCode = 'P'; //BRDCST_STLE_SE;//방송형태구분
            //$prodStepCode = 'M'; //PROD_STEP_SE='';//제작단계구분
        }
        $fileExt = strtolower($fileExt);
        $fileName = $mediaId . $brodTypeCode . $prodStepCode . '.' . $fileExt;
        return $fileName;
    }

    /**
     * 일자 기준으로 시퀀스 초기화
     * 로그 테이블에 날짜가 없을때 초기화
     * 이중화시 서버시간이 다를 경우 중복가능으로 주의
     *
     * @param [type] $seqName
     * @param integer $curDate
     * @return void
     */
    public static function getCycleSeq($seqName, $curDate = -1)
    {
        if ($curDate == -1) {
            $curDate = date("Ymd");
        }

        $isSeq = DB::table('USER_SEQUENCES')->where('sequence_name', '=', $seqName)->first();
        if (empty($isSeq)) {
            $r =  DB::statement("CREATE SEQUENCE {$seqName} INCREMENT BY 1 START WITH 1 MINVALUE 0 MAXVALUE 99999 CYCLE");
        }

        $resetInfo = DB::selectOne("select count(*) as cnt from log_seq_id where type='$seqName' and header='$curDate'");
        if ($resetInfo->cnt == 0) {
            $r = DB::statement("insert into log_seq_id values ('$seqName', '$curDate')");
            $r = DB::statement("DROP SEQUENCE {$seqName} ");
            $r = DB::statement("CREATE SEQUENCE {$seqName} INCREMENT BY 1 START WITH 1 MINVALUE 0 MAXVALUE 99999 CYCLE");
        }
        return true;
    }

    /**
     * 카테고리와 폴더 관리의 경로를 매핑해서 카테고리별 경로를 구함
     *
     * @param [type] $category_id
     * @param integer $limitStep
     * @param [type] $id
     * @return void
     */
    public function getFolderPath($category_id, $limitStep = 2, $id = null )
    {
        if($id){
            $pathInfo = FolderMng::where('id', $id )
            ->with('category')
            ->first();
        }else{
            $pathInfo = Category::where('category_id', $category_id )
            ->with('folder')
            ->first();
        }
        
        if( !empty($pathInfo) ){
            if( !$id && empty($pathInfo->folder) ){
                //카테고리 매핑 폴더가 없으면 상위 찾기               
                if( $pathInfo->parent_id > 0 && $pathInfo->dep > $limitStep ){
                    $fullPath = $this->getFolderPath( $pathInfo->parent_id  );                    
                }
                
            }else if( !$id && !empty($pathInfo->folder)  ){
                $pathInfo = $pathInfo->folder;
                $path = $pathInfo->folder_path;
                if( $pathInfo->parent_id > 0 && $pathInfo->step > $limitStep ){              
                    $fullPath = $this->getFolderPath( $category_id , $limitStep, $pathInfo->parent_id );
                }
            }else{
                $path = $pathInfo->folder_path;
                if( $pathInfo->parent_id > 0 && $pathInfo->step > $limitStep ){
                    $fullPath = $this->getFolderPath( $category_id , $limitStep, $pathInfo->parent_id );
                }
            }
        }
        $returnVal = str_replace('//', '/', $fullPath.'/'.$path);
        if(  $returnVal != '/'){
            $returnVal = rtrim($returnVal,'/');
        }
        return $returnVal;
    }

    /**
     * 콘텐츠 배포용 매핑콘텐츠 생성
     *
     * @param [type] $contentId
     * @return void
     */
    public function getContentForPush($contentId)
    {
        
        $mapper = new MetadataMapper($this->container);
        $content = $this->getContentByContentId($contentId);

        //동기화 대상 구분
        if( !in_array( $content->ud_content_id , [UdContentId::CLEAN,UdContentId::MASTER,UdContentId::CLIP,UdContentId::AUDIO,UdContentId::IMAGE])) return false;

        //정책에 의한 메타데이터 변경 공개여부등
        $changedMeta = $this->changePortalMeta($content, $content->usrMeta );

        if( !empty($changedMeta) ){
            foreach($changedMeta as $key => $val){
                $content->usrMeta->$key = $val;
            }
        }

        $contentMap = $mapper->contentMapper($content);

        $contentMap = \Api\Support\Helpers\FormatHelper::fixDateTimeFormat($contentMap);
        $contentMap = ['data' => [$contentMap] ];
        return $contentMap;
    }
    public function getMediaByContentIdAndOriginalType($contentId)
    {
        $content = $this->findOrFail($contentId);
        $media = $content->medias->where('media_type','original')->first();
        return $media;
    }

    /**
     * 포털용 메타데이터 상태 및 메타데이터에 따라 공개여부 변경 
     *
     * @param $contentId
     * @param $usrMetaInfo
     * @param array $changedMeta
     * @return $changedMeta
     */
    public function changePortalMeta($content, $usrMetaInfo, $changedMeta = [] )
    {
      
        if(!$content || !$usrMetaInfo ){
            return $changedMeta;
        }        
        //승인이 아니면 숨김
        if( $content->status == \Api\Types\ContentStatusType::COMPLETE ){
            //승인 이후에 수정하는건 돈케어
            // $changedMeta['othbc_at'] = 'Y' ;//공개여부
            // $changedMeta['reviv_posbl_at'] = 'Y';//재생가능여부 
            //승인 이후에는 변경된 다운로드 가능값을 따라감
            //$changedMeta['dwld_posbl_at'] = 'N';//다운로드가능여부
        }else{
            $changedMeta['othbc_at'] = 'N' ;//공개여부
            $changedMeta['reviv_posbl_at'] = 'N';//재생가능여부 
            $changedMeta['dwld_posbl_at'] = 'N';//다운로드가능여부
            $changedMeta['media_dwld_posbl_at'] = 'N';//다운로드가능여부
        }

        //소재종류가 프로그램이 아니면 비공개
        if( !empty($usrMetaInfo->matr_knd) && $usrMetaInfo->matr_knd != 'ZP' ){
            $changedMeta['othbc_at'] = 'N' ;//공개여부
            $changedMeta['reviv_posbl_at'] = 'N';//재생가능여부 
            $changedMeta['dwld_posbl_at'] = 'N';//다운로드가능여부
            $changedMeta['media_dwld_posbl_at'] = 'N';//다운로드가능여부
        }

        //오디오 이미지는 비공개처리
        if( $content->ud_content_id == UdContentType::AUDIO || $content->ud_content_id == UdContentType::IMAGE ){
            $changedMeta['othbc_at'] = 'N' ;//공개여부
            $changedMeta['reviv_posbl_at'] = 'N';//재생가능여부 
            $changedMeta['dwld_posbl_at'] = 'N';//다운로드가능여부
            $changedMeta['media_dwld_posbl_at'] = 'N';//다운로드가능여부
        }

        //사용제한 있어도 비공개
        if( $usrMetaInfo->brdcst_stle_se == 'S' || $usrMetaInfo->brdcst_stle_se == 'B' || $usrMetaInfo->use_prhibt_at == 'Y' || $usrMetaInfo->embg_at == 'Y' || !empty($usrMetaInfo->portrait)  || $usrMetaInfo->cpyrht_at == 'Y' ){
            //사용금지여부
            //엠바고여부
            //사용등급 : 사용불가, 기타 제외
            //초상권 있는경우 제외
            //저작권 있는경우 제외 저작권자
            //- 2,3에서 구매프로, 사용금지, 사용등급, 초상권, 저작권은 공개여부:N , 사용금지와 사용등급 정리필요          
            $changedMeta['othbc_at'] = 'N' ;//공개여부
            $changedMeta['reviv_posbl_at'] = 'N';//재생가능여부 
            $changedMeta['dwld_posbl_at'] = 'N';//다운로드가능여부     
            $changedMeta['media_dwld_posbl_at'] = 'N';//다운로드가능여부       
        }
        return $changedMeta;
    }

    /**
     * 자동 등록승인 처리 대상
     *
     * @param [type] $content
     * @param [type] $usrMetaInfo
     * @return void
     */
    public function isAutoAccept($content, $usrMetaInfo)
    {

        //부처영상인경우 등록대기
        if( strstr($content->category_full_path , '/0/100/203' )  ){
            return false;
        }

        if( $content->ud_content_id == UdContentType::INGEST  ||  $content->ud_content_id == UdContentType::IMAGE  || $content->ud_content_id == UdContentType::AUDIO  || $content->ud_content_id == UdContentType::CG ){
            return true;
        }

        if( empty($usrMetaInfo->brdcst_stle_se) || $usrMetaInfo->brdcst_stle_se == 'B' || $usrMetaInfo->brdcst_stle_se == 'S' || $usrMetaInfo->matr_knd != 'ZP' ){
            return true;
        }

        // if ( empty($usrMetaInfo->brdcst_stle_se) || ($usrMetaInfo->brdcst_stle_se == 'N') || ($usrMetaInfo->brdcst_stle_se != 'B' && $usrMetaInfo->brdcst_stle_se != 'S' && $usrMetaInfo->matr_knd == 'ZP')) {
        //      //방송형태: 구매, 지원이 아니고  소재형태:  프로그램인 것
        //     //등록대기
        //     //등록 요청 대상   
        // }else{
        //     return true;
        // }

        return false;
    }

    
    /**
     * 미디어 삭제 상태
     * 삭제 이면 true 아니면 false
     *
     * @param [type] $contentId
     * @return true 삭제 / false 존재
     */
    public function delMediaComplete($contentId)
    {
        $media = $this->getMediaByContentIdAndOriginalType($contentId);
        if($media->flag == "DMC"){
            return true;
        }else{
            return false;
        };
    }

    /**
     * 콘텐츠 삭제시 미디어별 워크플로우명을 리턴하는 함수 삭제대상 미디어만 리턴
     * @param $contentId
     * @return [array] $channelMapList
     */
    public function contentDeleteWorkflowMap($contentId)
    {
        $prefixChannel = 'delete_media_';
        $allowMediaTypes = [
            'original',
            'proxy',
            'proxy360',
            'proxy2m1080',
            'proxy15m1080',
            'thumb',
            // 'publish',
            // 'audio',
            // 'yt_thumb'
        ];
        $channelMapList = [];
        $content = $this->findOrFail($contentId);
        $mediaService = new MediaService($this->container);
        $medias = $mediaService->getMediaByContentId($contentId);
        
        foreach($medias as $media){
            if( $media->status == 0 && empty($media->flag) ){
                //삭제 대상이 아닌 목록만
                 //삭제 워크플로우 수행
                $mediaType = $media->media_type;    
                
                if( in_array($mediaType, $allowMediaTypes)){

                    $channelMap = $prefixChannel.$mediaType;
                    $channelMapList [$channelMap] = $media;
                }
            }
        }
        return $channelMapList;
    }

    /**
     * 메타데이터 파서
     *
     * @param [type] $metadata
     * @return void
     */
    public function getExplodeMeta($metadata){
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
     * 콘텐츠 만료일 정책 등로깃 생성
     *
     * @param [type] $udContentId
     * @param [type] $categoryFullPath
     * @param [type] $categoryId
     * @return void
     */
    public function createContentExpiredDate($udContentId = null, $categoryFullPath  = null, $categoryId  = null )
    {
        //기본
        $expiredDate = '99991231';
       
        //마스터본 1주일
        if( $udContentId == UdContentType::MASTER ){
            $expiredDate = date('Ymd', strtotime('+7 days'));
        }else if( $udContentId == UdContentType::INGEST || $udContentId == UdContentType::CLEAN || $udContentId == UdContentType::CLIP || $udContentId == UdContentType::NEWS ){
            $expiredDate = date('Ymd', strtotime('+14 days'));
        }else{
            $expiredDate = '99991231';
        }
        return $expiredDate;
    }

    /**
     * 시스템 메타데이터에서 xdcam 여부 판단
     *
     * @param [type] $sysMeta
     * @return boolean
     */
    public function isXDCAM($sysMeta)
    {
        if( strstr($sysMeta->sys_video_codec, 'XDCAMHD') || strstr($sysMeta->sys_video_codec, 'mpeg2video (4:2:2)') ){
            return true;
        }else{
            return false;
        }
    }

    public function getPersonalInformationDetection($contentId)
    {
        $indvdlinfo = InDvdLInfo::where('content_id', '=', $contentId)->first();
        return $indvdlinfo;
    }

}

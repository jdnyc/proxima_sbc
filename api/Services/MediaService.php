<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\Media;
use Api\Models\ContentDelete;
use Api\Services\BaseService;
use Api\Services\DTOs\MediaDto;
use Api\Support\Helpers\UrlHelper;
use Illuminate\Database\Capsule\Manager as DB;

class MediaService extends BaseService
{
    public function list($params)
    {
        $query = Media::query();
        $lists = paginate($query);
        return $lists;
    }
    /**
     * contentId로 media조회
     *
     * @param [type] $contentId
     * @return $collection
     */
    public function getMediaByContentId($contentId)
    {
        $query = Media::where('content_id','=',$contentId);
        $collection = $query->get();
        return $collection;
    }

    public function getMediaByContentIdAndType($contentId, $mediaType)
    {
        $query = Media::where('content_id','=',$contentId);
        $query->where('media_type', $mediaType);
        $collection = $query->first();
        return $collection;
    }

    public function getMediaWithStorageByContentId($contentId)
    {
        $query = Media::where('content_id','=',$contentId);
        $query->with('storage');
        $collection = $query->get();
        return $collection;
    }

    
    /**
     * 서비스용 원본 조회
     *
     * @param [type] $contentId
     * @return void
     */
    public function getMediaOriginal($contentId){
        $medias = $this->getMediaWithStorageByContentId($contentId);
        $find = null;
        if($medias){
            foreach ($medias as $media) {
                if ($media->media_type == 'original') {
                    $find = $media;
                }
            }
            if (!$find) {
                foreach ($medias as $media) {
                    if ($media->media_type == 'archive') {
                        $find = $media;
                    }
                }
            }
        }
        return $find;
    }

    /**
     * 서비스용 프록시 조회
     *
     * @param [type] $contentId
     * @return $media
     */
    public function getMediaProxy($contentId){
        $medias = $this->getMediaWithStorageByContentId($contentId);
        $find = null;
        if($medias){
            foreach ($medias as $media) {
                if ($media->media_type == 'proxy') {
                    $find = $media;
                }
            }
            if (!$find) {
                foreach ($medias as $media) {
                    if ($media->media_type == 'proxy360') {
                        $find = $media;
                    }
                }
            }
        }
        return $find;
    }

    public function getMediaProxyPath($contentId){

        $media = $this->getMediaProxy($contentId);

        if( empty($media) ){
            return '/tmp/notfound.jpg';  
        }
        
        $rootPath = $media->storage->virtual_path ;      
        if (empty($rootPath)) {
            $rootPath = '/data';
        }
        $rootPath = '/'.trim($rootPath,'/');
       
        if( !empty($media->path) ){
            $midPath = trim($media->path,'/');
        }else{
            return '/tmp/notfound.jpg';
        }

        $fullPath = $rootPath.'/'.$midPath;

        /**
         * 2019.12.20 hkkim
         * .env에 streaming url 이 설정되어 있으면
         * fullPath 앞에 url을 붙여준다.
         */
         $streamingUrl = '';
         if(\Api\Application::isPublicZone()) {
             $streamingUrl = config('streaming.public_url');
         } else {
             $streamingUrl = config('streaming.private_url');
         }
         $fullPath = UrlHelper::build($streamingUrl, $fullPath);

        return $fullPath;
    }


     /**
     * 상세 조회
     *
     * @param integer $id
     * @return \Api\Models\Media 생성된 테이블 객체
     */
    public function find(int $id)
    {
        $query = Media::query();
        return $query->find($id);
    }

    /**
     * 조회 후 실패 처리
     *
     * @param integer $id
     * @return $collection
     */
    public function findOrFail(int $id)
    {
        $collection = Media::find($id);
        if (!$collection) {
            api_abort_404('Media');
        }
        return $collection;
    }

    /**
     * 생성
     *
     * @param \Api\Services\DTOs\MediaDto 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\Media 생성된 테이블 객체
     */
    public function create(MediaDto $dto, User $user)
    {
        
        $collection = new Media();
        if (!empty($dto)) {
            foreach ($dto->toArray() as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $collection->$key = $val;
            }
        }      

        
        if (is_null($collection->filesize) ){
            $collection->filesize = 0;
        }
        if (is_null($collection->status) ){
            $collection->status = 0;
        }
        if (is_null($collection->expired_date) ){
            $collection->expired_date = '99981231000000';
        }

        if (is_null($collection->created_date)) {
            $collection->created_date = date("YmdHis");
        }

        $collection->save();

        return $collection;
    }

    public function deleteReady($mediaId)
    {
        $media = $this->findOrFail($mediaId);
        
        if($media->flag != DEL_MEDIA_COMPLETE_FLAG ){
            $media->flag = DEL_MEDIA_CONTENT_REQUEST_FLAG;       
            $media->save();
        }
        return $media;
    }

    public function deleteComplete($mediaId)
    {
        $media = $this->findOrFail($mediaId);

        $media->flag        = DEL_MEDIA_COMPLETE_FLAG;
        $media->status      = '1';
        $media->delete_date = date("YmdHis");        
        $media->save();
        
        return $media;
    }

    
    /**
     * 미디어 삭제 요청
     *
     * @param int $mediaId
     * @param User $user
     * @return void
     */
    public function deleteRequest($mediaId, $reason, User $user)
    {
        $media = $this->findOrFail($mediaId);
        
        $delete = new ContentDelete();
        $delete->id = self::getSequence('SEQ_BC_DELETE_CONTENT_ID');
        $delete->content_id = $media->content_id;
        $delete->status = 'REQUEST';
        $delete->delete_type = 'MEDIA';
        $delete->reg_user_id = $user->user_id;
        $delete->media_id = $mediaId;
        $delete->reason = $reason ;
        $delete->save();

        return $delete;
    }
}

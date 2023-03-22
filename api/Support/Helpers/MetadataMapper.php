<?php
namespace Api\Support\Helpers;

use Api\Services\MediaService;
use Api\Services\ContentService;
use Api\Services\MediaSceneService;

/**
 * 인터페이스용 콘텐츠 콜렉션 맵퍼
 */
class MetadataMapper
{
 
    private $fieldMap;

    //콘텐츠 필드 매핑셋
    private $contentFieldMap = [
        'content_id'=>'cntnts_id'
        ,'parent_content_id'=>'parnts_id'
        ,'bs_content_id'=>'media_ty'
        ,'status' => 'cntnts_sttus'
        ,'is_deleted'=>'delete_at'
        ,'updated_user_id'=>'updt_user_id'
        ,'updated_at'=>'updt_dt'
        ,'ud_content_id'=>'cntnts_ty'
        ,'reg_user_id'=>'regist_user_id'
        ,'created_date'=>'regist_dt'
        ,'title'=>'title'
        ,'category_id'=>'ctgry_id'
        ,'expired_date'=>'prsrv_pd'
        ,'medias' => 'medias'
        ,'catalog_images' => 'catalog_images'
        ,'status_meta' => 'status_meta'
        ,'usr_meta' => 'usr_meta'
        ,'sys_meta' => 'sys_meta'
    ];

    //미디어 필드 매핑셋
    private $mediaFieldMap = [
        'media_id' => 'media_seq_id',
        'media_type' => 'media_file_ty' ,
        'stream_hls_url' => 'strmg_hls_url',
        'stream_rtmp_url' => 'strmg_rtmp_url',
       // 'download_url' => 'dwld_url',
        'status' => 'media_sttus',
        'filesize' => 'filesize',
        'memo'  => 'atch_filename',
        'path' => 'media_middle_flpth',
        'http_url'=> 'http_url'
    ];

    //카달로그 이미지 필드 매핑셋
    private $catalogFieldMap = [
        'scene_id' => 'scenes_id',
        'start_frame' => 'begin_frme_indx',
        'show_order' => 'sort_ordr',
        'http_url'=> 'http_url'
    ];

    //미디어정보메타데이터 필드 매핑셋
    private $sysFieldMap =  [           
        "sys_frame_rate"=> "video_fps",
        "sys_display_size"=> "video_rsoltn",
        "sys_video_codec"=> "video_frmat",
        "sys_audio_codec"=> "audio_frmat",
        "sys_video_bitrate"=> "video_bitrate",
        "sys_audio_bitrate"=> "audio_bitrate",  
        "sys_audio_channel"=> "audio_chnnel",
        "sys_video_rt"=> 'video_duration',
        "sys_audio_samplrate" => 'audio_samplrate',
        "sys_video_frme"=> 'video_frme',
        "sys_rsoltn_se"=> 'rsoltn_se',
        "sys_video_asperto"=> 'video_asperto',
        "sys_video_wraper"=> 'video_wraper',
        "sys_audio_samplrate"=> 'audio_samplrate',
        "sys_clip_begin_time"=> 'clip_begin_time',
        "sys_clip_end_time"=> 'clip_end_time',
        "sys_image_rsoltn" => 'image_rsoltn'
    ];

    //콘텐츠상태 메타데이터 필드 매핑셋(제외)
    private $contentStatusRejectMap = [
        "content_id",
        "archive_status",
        "archive_date",
        "restore_date",
        "resolution",
        "original_link_yn",
        "loudness",
        "qc"
    ];

    //미디어 상태 메타데이터 매핑셋
    private $mediaStatusMap = [
        '0' => 'online',
        '1' => 'offline'
    ];

    //사용자메타데이터 필드 매핑셋(제외)
    private $usrFieldRejectMap = [
        'usr_content_id'
    ];

    //url prefix 
    private $prefixPath = "218.38.152.102";
    
    private $prefixRtmpPath = '218.38.152.87:1935';
    private $prefixHlsPath = '218.38.152.87:1935';
    private $prefixHlsProtocol = 'http';

    //미디어유형 매핑셋
    private $mediaTypeMap = [
        
        '506' => 'movie',
        '515' => 'sound',
        '518' => 'image',
        '57057' => 'document',
        '57078' => 'sequence'
    ];

    //콘텐츠 유형 매핑셋
    private $contentTypeMap = [
        
        '1' => 'ingest',
        '7' => 'original',
        '2' => 'clean',
        '3' => 'master',
        '4' => 'audio',
        '5'=> 'image' ,
        '8' => 'cg'
    ];

    public function __construct($container)
    {
        $this->prefixPath = config('publish')['api_http_url'];
        $this->prefixRtmpPath = config('publish')['api_stream_url'];
        $this->prefixHlsPath = config('publish')['api_stream_hls_url'];
        $this->prefixHlsProtocol = config('publish')['api_stream_protocol'];
        $this->contentService = new ContentService($container);
        $this->mediaService = new MediaService($container);
        $this->mediaSceneService = new MediaSceneService($container);
    }

    public function getUdContentMap($reverse = false)
    {
        if( !$reverse ){
            return $this->contentTypeMap;
        }else{
            $reverseMap = [];
            foreach($this->contentTypeMap as $key => $val){
                $reverseMap [$val] = $key;
            }
            return $reverseMap;
        }
    }

    
    public function getBsContentMap($reverse = false)
    {
        if( !$reverse ){
            return $this->mediaTypeMap;
        }else{
            $reverseMap = [];
            foreach($this->mediaTypeMap as $key => $val){
                $reverseMap [$val] = $key;
            }
            return $reverseMap;
        }
    }

    /**
     * 콘텐츠 목록 필드 맵퍼 / 기본, 사용자, 시스템, 미디어, 카달로그이미지 정보 추가
     *
     * @param [type] $contents
     * @return void
     */
    public function contentsMapper($contents, $addFieldType = ['usr','sys','media','status'] )
    {       
        $this->addFieldType = $addFieldType;
        $contents = $contents->map(function($content) {
            $content->bs_content_id = $this->mediaTypeMap[ $content->bs_content_id ];
            $content->ud_content_id = $this->contentTypeMap[ $content->ud_content_id ];

            if( in_array('media', $this->addFieldType) ){
                $medias = $this->mediaService->getMediaByContentId($content->content_id);
                
                $targetMediaId =null;
                $medias = $medias->map(function ($media) {
                    $media->status = empty($media->status) ? 0: $media->status;
                    if( empty($media->filesize) ){
                        $media->status = 1;
                    }
                    $media->status =  $this->mediaStatusMap[ $media->status ];

                   
                    if ($media->media_type == 'original') {
                    } else {

                        $media = $this->mediaRenderPath($media);   
                    }
                    return $media;
                });
                
                $mediasMeta = $medias->toArray();
                foreach ($mediasMeta as $key => $media) {
                    if ($media['media_type'] == 'proxy') {
                        $targetMediaId = $media['media_id'];
                    }
                }
              
                $medias = $this->collectionsFieldMap($medias, $this->mediaFieldMap);
                $content->medias = $medias;

                if ($targetMediaId) {
                    $catalogImages = $this->mediaSceneService->getMediaSceneByMediaId($targetMediaId);
                    $catalogImages = $catalogImages->map(function ($catalog) {
                        if(strstr($catalog->path , 'kdf')){
                            $midPath = '/mig-thumb';
                        }else{
                            $midPath = '/data';
                        }
                        $catalog->http_url = $this->prefixPath.$midPath."/".$catalog->path;      
                        return $catalog;
                    });
                    $catalogImages = $this->collectionsFieldMap($catalogImages, $this->catalogFieldMap);
                    $content->catalog_images = $catalogImages;
                } else {
                    $content->catalog_images = [];
                }
            }

            if ( $content->sysMeta && in_array('sys',$this->addFieldType) ) {
                $content->sys_meta = $this->collectionFieldMap($content->sysMeta, $this->sysFieldMap);
            }
            if ( $content->usrMeta && in_array('usr',$this->addFieldType)) {
                $content->usr_meta = $this->collectionFieldMap($content->usrMeta, $this->usrFieldRejectMap, true);
            }
            if (  $content->statusMeta &&  in_array('status',$this->addFieldType)) {
                $content->status_meta = $this->collectionFieldMap($content->statusMeta, $this->contentStatusRejectMap, true);
            }
            
            return $content;
        });
        
        $contents = $this->collectionsFieldMap($contents, $this->contentFieldMap);

        return $contents;
    }

    /**
     * 단건 콘텐츠 필드 맵퍼 / 기본, 사용자, 시스템, 미디어, 카달로그이미지 정보 추가
     *
     * @param [type] $content
     * @return void
     */
    public function contentMapper($content, $addFieldType = ['usr','sys','media','status'] )
    {
        $this->addFieldType = $addFieldType;
        $content->bs_content_id = $this->mediaTypeMap[ $content->bs_content_id ];
        $content->ud_content_id = $this->contentTypeMap[ $content->ud_content_id ];

        if (in_array('media', $this->addFieldType)) {
            $medias = $this->mediaService->getMediaByContentId($content->content_id);
            
            $mediasMeta = $medias->toArray();        
            $targetMediaId = $this->getMediaIdForCatalogImage($mediasMeta);

            $medias = $this->mediasMapper($medias);
            $content->medias = $medias;

            if ($targetMediaId) {
                $catalogImages = $this->mediaSceneService->getMediaSceneByMediaId($targetMediaId);
                $catalogImages = $this->catalogImagesMapper($catalogImages);
                $content->catalog_images = $catalogImages;
                if( !$content->catalog_images )  $content->catalog_images = [];
            } else {
                $content->catalog_images = [];
            }
        }
        if (in_array('sys', $this->addFieldType)) {
            $content->sys_meta = [];
            $content->sys_meta = $this->collectionFieldMap($content->sysMeta, $this->sysFieldMap);
            if( empty($content->sys_meta) )  $content->sys_meta = [];
        }
        if (in_array('usr', $this->addFieldType)) {
            $content->usr_meta = [];
            $content->usr_meta = $this->collectionFieldMap($content->usrMeta, $this->usrFieldRejectMap, true);
            if( empty($content->usr_meta) )  $content->usr_meta = [];
        }
        if (in_array('status', $this->addFieldType)) {
            $content->status_meta = [];
            $content->status_meta = $this->collectionFieldMap($content->statusMeta, $this->contentStatusRejectMap, true);
            if( empty($content->status_meta) )  $content->status_meta = [];
        }
        $content = $this->collectionFieldMap($content, $this->contentFieldMap);

        return $content;
    }

    
    public function addInfo($content, $addFieldType = ['usr','sys','media','status'])
    {
        $this->addFieldType = $addFieldType;

        if (in_array('media', $this->addFieldType)) {
            $medias = $this->mediaService->getMediaWithStorageByContentId($content->content_id);
        
            $mediasMeta = $medias->toArray();
            $targetMediaId = $this->getMediaIdForCatalogImage($mediasMeta);
            $content->medias = $medias;

            if ($targetMediaId) {
                $catalogImages = $this->mediaSceneService->getMediaSceneByMediaId($targetMediaId);
                $content->catalog_images = $catalogImages;
            } else {
                $content->catalog_images = [];
            }
        }
        return $content;
    }

    /**
     * 카달로그 이미지용 키 조회, 미디어 배열 목록에서 조회
     *
     * @param [type] $mediasMeta
     * @return void
     */
    public function getMediaIdForCatalogImage($mediasMeta)
    {
          
        $targetMediaId = null;
        foreach($mediasMeta as $key => $media){
            if( $media['media_type'] == 'proxy'){
                $targetMediaId = $media['media_id'];
            }
        }
        return $targetMediaId;
    }

    public function mediaRenderPath($media)
    {
   // 이관 역사관스토리지	121	/ehistory
                // 저해상도 스토리지	105	/data
                // ASIS 섬네일이미지	118	/mig-thumb
                // ASIS 저해상도 스토리지	119	/mig-proxy
                // 이관 홈페이지스토리지	120	/homepage
               
        $streamPath = '';
        if( $media->storage_id == 121 ){
            $midPath = '/ehistory';
            $streamPath = '/ehistory/_definst_/mp4:';
        }
        if( $media->storage_id == 105 ){
            $midPath = '/data';
            $streamPath = '/vod-proxy/_definst_/mp4:';
        }
        if( $media->storage_id == 119 ){
            $midPath = '/mig-proxy';
            $streamPath = '/vod/_definst_/mp4:';
        }
        if( $media->storage_id == 118 ){
            $midPath = '/mig-thumb';
            $streamPath = '/vod/_definst_/mp4:';
        }
        if( $media->storage_id == 120 ){
            $midPath = '/homepage';
            $streamPath = '/homepage/_definst_/mp4:';
        }
        if( $media->storage_id == 125 ){
            $midPath = '/data-h';
            $streamPath = '/vod-h/_definst_/mp4:';
        }
        $media->http_url = $this->prefixPath.$midPath.'/'.$media->path;

        $pathArray = explode('.', $media->path);
        $pathExt = array_pop($pathArray);

        if ( $pathExt == 'mp4' || $pathExt == 'mp3' || $pathExt == 'm4a'  ) {
            if( $pathExt == 'm4a' ){
                $streamPath = str_replace('mp4:' ,'', $streamPath );
            }
            if( $pathExt == 'mp3' ){
                $streamPath = str_replace('mp4:' ,'mp3:', $streamPath );
            }
            if($streamPath){
                $media->stream_hls_url = $this->prefixHlsProtocol."://".$this->prefixHlsPath.$streamPath.$media->path.'/playlist.m3u8';
                $media->stream_rtmp_url = "rtmp://".$this->prefixRtmpPath.$streamPath.$media->path;
            }
            //rtmp://10.10.50.176:1935/vod/_definst_/mp4:2013/07/20130707V78610NP.mp4
        }

        return $media;
    }

    public function mediasMapper($medias)
    {
                   
        $medias = $medias->map(function ($media) {
            
            $media->status = empty($media->status) ? 0: $media->status;               
            $media->status =  $this->mediaStatusMap[ $media->status ];

            if( empty($media->filesize) ){
                $media->status = 1;
            }

            if($media->media_type == 'original' ){                    
            }else{
                $media = $this->mediaRenderPath($media);                
            }
            return $media;
        });
        $medias = $this->collectionsFieldMap($medias, $this->mediaFieldMap);
        return $medias;
    }

    /**
     * 카달로그 이미지 맵퍼
     *
     * @param [type] $catalogImages
     * @return void
     */
    public function catalogImagesMapper($catalogImages)
    {
        $catalogImages = $catalogImages->map(function ($catalog) { 
            if(strstr($catalog->path , 'kdf')){
                $midPath = '/mig-thumb';
            }else{
                $midPath = '/data';
            }
            $catalog->http_url = $this->prefixPath.$midPath."/".$catalog->path;                    
            return $catalog;
        });
        $catalogImages = $this->collectionsFieldMap($catalogImages, $this->catalogFieldMap);
        return $catalogImages;
    }
    
    /**
     * 콜렉션즈 필드명 매핑
     *
     * @param [type] $lists
     * @param [type] $fieldMap
     * @return void
     */
    public function collectionsFieldMap($lists, $fieldMap)
    {
        $this->fieldMap = $fieldMap;
         $lists = $lists->map(function($list) {
             $newKeys = [];
             foreach($this->fieldMap as $key => $val){
                 $list->$val = $list->$key;
                 $newKeys [] = $val;
             }     
             $list = $list->only( $newKeys );
             return $list;
         });
         return $lists ;
     }
 
     /**
      * 콜렉션 필드명 매핑
      *
      * @param [type] $list
      * @param [type] $fieldMap
      * @param boolean $isReject
      * @return void
      */
    public function collectionFieldMap($list, $fieldMap , $isReject = false )
    {
        $this->fieldMap = $fieldMap;

        if(empty($list)) return $list; 

        $newKeys = [];
        if( $isReject ){
            $newKeys = array_keys( $list->toArray() );

            $newKeys = array_flip($newKeys);
            foreach ($this->fieldMap as $key => $val) {
                unset($newKeys[$val]);
            }
            $newKeys = array_flip($newKeys);
        
            $list = $list->only( $newKeys );
        }else{
            foreach ($this->fieldMap as $key => $val) {
                $list->$val = $list->$key;
                $newKeys [] = $val;
            }
            $list = $list->only( $newKeys );       
        }

        return $list ;
    }


    /**
     * 신규 필드명을 실제 필드명으로 변경
     *
     * @param [type] $data
     * @param [reverse] 매핑 역여부
     * @param [subType] 매핑 필드명
     * @return void
     */
    public function fieldMapper($data , $subType = 'content', $reverse = false)
    {
        if( !$data ) return false;
        $renderData = [];
        foreach($data as $key => $value)
        {   
            if( $subType == 'content' ){
                foreach ($this->contentFieldMap as $realField => $newField) {
                    if ($key == $newField) {
                        if (is_array($value)) {                          
                            $value = $this->fieldMapper($value, $newField, $reverse);                            
                            $renderData [$realField] = $value;
                        } else {
                            $renderData [$realField] = $value;
                        }
                    }
                }
            }else if( $subType == 'sys_meta' ){
                foreach ($this->sysFieldMap as $realField => $newField) {
                    if ($key == $newField) {
                        if (is_array($value)) {                          
                            $value = $this->fieldMapper($value, $newField, $reverse);                            
                            $renderData [$realField] = $value;
                        } else {
                            $renderData [$realField] = $value;
                        }
                    }
                }
            }else{
                
                if( is_array($value) ){                       
                    $value = $this->fieldMapper($value, $reverse);
                    
                    $renderData [$key] = $value;
                }else{
                    $renderData [$key] = $value;
                }
            }
        }
        return $renderData;
    }

}
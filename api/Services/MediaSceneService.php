<?php

namespace Api\Services;

use Api\Models\MediaScene;
use Api\Services\BaseService;
use Illuminate\Database\Capsule\Manager as DB;

class MediaSceneService extends BaseService
{
    public function list($params)
    {
        //dbd();
        // $contents = Content::whereIn('content_id', [1, 2, 3, 4, 5, 6, 7])
        //     ->offset(2)
        //     ->limit(2)
        //     ->get(['content_id', 'title']);

        // $content = Content::find(1);
        //die();
        $query = MediaScene::query();
        $lists = paginate($query);
        //$lists = $query->get();
        //dd($contents->pluck('title'));
        // return $contents;
        return $lists;
    }

    public function getMediaSceneByMediaId($mediaId)
    {
        $query = MediaScene::where('media_id','=',$mediaId);
        $collection = $query->get();
        return $collection;
    }

    /**
     * 생성
     *
     * @param \Api\Services\DTOs\MediaDto 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\Media 생성된 테이블 객체
     */
    public function create(array $scene)
    {        
        $collection = new MediaScene();
        if (!empty($scene)) {
            foreach ($scene as $key => $val) {
                if ($key == "root") {
                    continue;
                }
                $collection->$key = $val;
            }
        }
        if( !$collection->scene_id ){
            $collection->scene_id = $this->getSequence('SEQ');
        }
        $collection->save();
        return $collection;
    }

    /**
     * 미디어ID로 삭제
     *
     * @param [type] $mediaId
     * @return void
     */
    public function deleteByMediaId($mediaId)
    {
        $query = MediaScene::where('media_id','=',$mediaId);
        if( !empty($query) ){            
            $query->delete();
        }
        return true;
    }

    /**
     * 삭제 후 일괄 추가
     *
     * @param [type] $datas
     * @param [type] $mediaId
     * @return void
     */
    public function delAndCreate($datas, $mediaId)
    {
        $return = [];
        $this->deleteByMediaId($mediaId);
        foreach($datas as $data){
            $return [] = $this->create($data);
        }
        return $return;
    }

}

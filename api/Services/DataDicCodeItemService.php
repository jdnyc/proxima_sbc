<?php

namespace Api\Services;

use Api\Models\User;
use Api\Services\BaseService;
use Api\Models\DataDicCodeSet;
use Api\Models\DataDicCodeItem;
use Api\Services\ApiJobService;
use Illuminate\Support\Facades\DB;
use Api\Services\DTOs\DataDicCodeItemDto;
use Api\Traits\DataDicDomainIncludeTrait;
use Api\Services\DTOs\DataDicCodeItemSearchParams;


class DataDicCodeItemService extends BaseService
{

    use DataDicDomainIncludeTrait;

    public function list(DataDicCodeItemSearchParams $params)
    {
        $keyword = $params->keyword;
        $is_deleted = $params->is_deleted;
        //쿼리 조건 삭제여부
        $query = DataDicCodeItem::query();
        if ($is_deleted) {
            $query->onlyTrashed();
        }
        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');


        if (!is_null($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code_item_nm', 'like', "%{$keyword}%")
                    ->orWhere('code_item_code', 'like', "%{$keyword}%");
            });
        }

        $codeItems = $query->get();
        
        return $codeItems;
    }

    // connect By 쓰면 페이징이 안되어서
    public function listByCodeSetId(DataDicCodeItemSearchParams $params)
    {
        $codeSetId = $params->code_set_id;
        //쿼리 조건 삭제여부
        $query = \Illuminate\Database\Capsule\Manager::table('dd_code_item')
            ->whereNull("delete_dt")
            // ->where('code_set_id',$codeSetId)
            ->whereRaw('code_set_id = ? CONNECT BY PRIOR ID=DECODE(ID,PARNTS_ID,NULL,PARNTS_ID) START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, ID', [$codeSetId]);
        // $items = paginate($query);
        // $query->offset($offset)->limit($limit);
        // $items = $query->get();
        return $query;
    }

    public function getCodeItemByCodeSetCode($codeSetCode,$itemCode){
        $codeSet = DataDicCodeSet::where('code_set_code', $codeSetCode)->first();
        if(empty($codeSet)){
            return false;
        }
        
        $codeSetId = $codeSet->id;
        return $this->findCodeItemByCodeSetId($codeSetId,$itemCode);
    }
    /**
     * 데이터 사전 코드아이템 상세 조회(code_item id로 조회)
     * @param integer $id
     * @return DataDicCodeItem
     */
    public function find($id)
    {
        $query = DataDicCodeItem::query();

        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'updater');


        return $query->find($id);
    }
    public function findCodeItemByCodeSetId($codeSetId,$codeItemCode){
        $query = DataDicCodeItem::query();
        $query->where('code_set_id',$codeSetId)->where('code_itm_code','=',$codeItemCode)->select('code_set_id','code_itm_nm','code_itm_code','code_path');
        $codeItem = $query->first();
        return $codeItem;
    }

    public function findCodeItemByCodeItemCode($codeItemCode){
        $query = DataDicCodeItem::query();
        $query->where('code_itm_code','=',$codeItemCode)->select('code_set_id','code_itm_nm','code_itm_code','code_path');
        $codeItem = $query->first();
        return $codeItem;
    }
    /**
     * (코드셋 아이디로)데이터 사전 코드아이템 조회
     *
     * @param int $codeSetId
     * @return DataDicCodeItem[]
     */
    public function getCodeItemsByCodeSetId($codeSetId)
    {
        // $query =DataDicCodeItem::raw("select * from DD_CODE_ITEM where CODE_SET_ID = ".$codeSetId." and DD_CODE_ITEM.DELETE_DT is null CONNECT BY PRIOR ID=DECODE(ID, PARNTS_ID, NULL, PARNTS_ID)
        // START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, DP");
        
        $query = \Illuminate\Database\Capsule\Manager::table('dd_code_item')
            ->whereNull("delete_dt")
            // ->where('code_set_id',$codeSetId)
            ->whereRaw('code_set_id = ? CONNECT BY PRIOR ID=DECODE(ID,PARNTS_ID,NULL,PARNTS_ID) START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, ID', [$codeSetId]);
        // $rtn = new DataDicCodeItem();
        // //foreach($items as $item){
        //     $rtn->map($items);
        // //}
        
        
        $items = $query->get();
        
        return $items;
    }


    /**
     * 코드로 하위 목록 조회
     *
     * @param [type] $codeItemCode
     * @return void
     */
    public function findChildrenByCodeItemCode($codeItemCode)
    {
        $codeItem = $this->findCodeItemByCodeItemCode($codeItemCode);
        if(!$codeItem){
            return false;
        }
        $codePath = $codeItem->code_path;
        $query = DataDicCodeItem::where('code_path','like',$codePath.'%' );
       
        return $query->get();
    }
    /**
     * 코드 셋 삭제시 코드 아이템 같이 삭제
     *
     * @param [type] $codeSetId
     * @return void
     */
    public function deleteCodeItemsByCodeSetId($codeSetId)
    {
        $codeItems = $this->getCodeItemsByCodeSetId($codeSetId);

        foreach ($codeItems as $codeItem) {
            $codeItemDel = $this->findOrFail($codeItem->id);
            $ret = $codeItemDel->delete();
        };
        return $ret;
    }


    /**
     * 데이터 사전 코드아이템 상세 조회 또는 $실패 처리
     *
     * @param integer $id
     * @return DataDicCodeItem
     */
    public function findOrFail($id)
    {
        $codeItem = $this->find($id);
        if (!$codeItem) {
            api_abort_404('DataDicCodeItem');
        }
        return $codeItem;
    }

    /**
     * 아이디 최댓값 
     *  
     * @return void
     */
    public function idMaxValue()
    {
        $query = DataDicCodeItem::query();
        $maxId = $query->max('id');

        return $maxId;
    }

    /**
     * 데이터 사전 코드아이템 생성
     *
     * @param \Api\Services\DTOs\DataDicCodeItemeDto $data codeItem 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicCodeItem 생성된 테이블 객체
     */
    public function create(DataDicCodeItemDto $dto, User $user)
    {

        $codeItem = new DataDicCodeItem();

        $codeItem->code_set_id = $dto->code_set_id;
        $codeItem->code_itm_nm = $dto->code_itm_nm;
        $codeItem->code_itm_code = $dto->code_itm_code;
        $codeItem->use_yn = $dto->use_yn;
        if ($dto->parnts_id == null) {
            $codeItem->parnts_id = 0;
        } else {
            $codeItem->parnts_id = $dto->parnts_id;
        };

        $codeItem->sort_ordr = $this->idMaxValue();
        $codeItem->dc = $dto->dc;
        $codeItem->dp = $dto->dp;
        $codeItem->code_path = $dto->code_path;
        $codeItem->regist_user_id = $user->user_id;
        $codeItem->updt_user_id = $user->user_id;
        $codeItem->save();

        $apiJobService = new ApiJobService();
        $apiJobService->createApiJob( __CLASS__, __FUNCTION__, $codeItem->toArray() , $codeItem->id );


        return $codeItem;
    }
    /**
     * 데이터 사전 코드아이템 수정
     * 
     * @param integer 수정할 코드아이템 아이디
     * @param \Api\Services\DTOs\DataDicCodeItemDto $dto 테이블 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicCodeItem 수정된 테이블 객체
     */
    public function update(int $codeItemId, DataDicCodeItemDto $dto, User $user)
    {
        $codeItem = $this->findOrFail($codeItemId);

        foreach ($dto->toArray() as $key => $val) {
            if (!($key == "root")) {
                $codeItem->$key = $val;
            };
        }
        // if ($dto->parnts_id == null) {
        //     $codeItem->parnts_id = 0;
        // } else {
        //     $codeItem->parnts_id = $dto->parnts_id;
        // };

        $codeItem->regist_user_id = $user->user_id;
        $codeItem->updt_user_id = $user->user_id;
        $codeItem->save();

        $apiJobService = new ApiJobService();
        $apiJobService->createApiJob( __CLASS__, __FUNCTION__, $codeItem->toArray() , $codeItem->id );

        return $codeItem;
    }

    /**
     * 테이블 삭제
     *
     * @param \Api\Models\DataDicCodeItem 삭제할 테이블
     * @param array $data codeItem 생성 데이터
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $codeItemId, User $user)
    {
        $codeItem = $this->findOrFail($codeItemId);

        $ret = $codeItem->delete();

        $apiJobService = new ApiJobService();
        $apiJobService->createApiJob( __CLASS__, 'update', $codeItem->toArray() , $codeItem->id );
        
        return $ret;
    }

    /**
     * 데이터 사전 코드아이템 복원
     *
     * @param integer $codeItemId 복원할 코드아이템 아이디
     * @param User $user
     * @return bool|null 복원 성공여부
     */
    public function restore(int $codeItemId)
    {
        $codeItem = DataDicCodeItem::onlyTrashed()
            ->where('id', $codeItemId)
            ->first();

        if (!$codeItem) {
            api_abort_404('DataDicCodeItem');
        }

        $ret = $codeItem->restore();

        return $ret;
    }



    public function makeNodes($codes, $targetNode = null , $defaultRow = [] )
    {
       $nlist = array(
           array( 'children' => array() )
       );
       $raw = array(&$nlist[0]);
       if(isset($codes))
      {
        $selectNode = null;
           foreach($codes as $q => $code)
           {
             if(!is_null($targetNode) && ($targetNode == $code->code_itm_code) ){
                if(!$selectNode) $selectNode = $code;
                $expanded = true;
             }else{
               $expanded = false;
             }      
                $default =  $defaultRow;  
                
                $default['id'] = $code->code_itm_code;
                $default['idx'] = $code->id;
                $default['code'] = $code->code_itm_code;
                $default['text'] = $code->code_itm_nm;
                $default['leaf'] = true;
                $default['dp']  = $code->dp;
                $default['code_path']  = $code->code_path;
                $default['expanded'] = $expanded;
               $raw[$code->parnts_id]['children'][$code->id] = $default;
               $raw[$code->id] = &$raw[$code->parnts_id]['children'][$code->id];
           }
           $nlist[0] = self::objectToArray( $nlist[0] );
           
           $nlist[0]['children'][0]['selectNode'] = $selectNode ;
       }

       return $nlist[0]['children'];
    }

    /**
     * 재귀생성한 객체를 배열화
     * 자식노드 확인후 리프여부 처리
     *
     * @param [type] $list
     * @return void
     */
    public function objectToArray( $list ){

      if( !empty( $list['children'] ) ){
        $childrenArray = [];
        $list['leaf'] = false;
        foreach($list['children'] as $children){          
          $children = self::objectToArray( $children );
          $childrenArray [] = $children;
        }
        $list['children']  = $childrenArray;     
        return $list;
      }else{
        return $list;
      }
    }
}

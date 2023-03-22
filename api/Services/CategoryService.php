<?php

namespace Api\Services;

use Api\Models\User;
use Api\Models\Category;
use Api\Services\BaseService;
use Api\Services\DTOs\CategoryDto;
use Illuminate\Database\Capsule\Manager as DB;

class CategoryService extends BaseService
{
     /**
     * 목록 조회
     *
     * @param $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */

    public function list($params)
    {
        $keyword = $params->keyword;

        //쿼리 조건 삭제여부
        $query = Category::query();
      
        //$lists = paginate($query);
        $lists = $query->get()->all();
        return $lists;
    }

    public function listAll($rootId)
    {
        // $query =DataDicCodeItem::raw("select * from DD_CODE_ITEM where CODE_SET_ID = ".$codeSetId." and DD_CODE_ITEM.DELETE_DT is null CONNECT BY PRIOR ID=DECODE(ID, PARNTS_ID, NULL, PARNTS_ID)
        // START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, DP");
        // SELECT * FROM bc_category WHERE NVL(IS_DELETED,0)=0
        // CONNECT BY PRIOR category_id=DECODE(category_id,PARENT_ID,NULL,PARENT_ID) START WITH PARENT_ID=0 ORDER SIBLINGS BY show_order, category_id
        $items = \Illuminate\Database\Capsule\Manager::table('bc_category')
            //->whereNull("delete_dt")
            // ->where('code_set_id',$codeSetId)
            ->whereRaw(' PARENT_ID=? CONNECT BY PRIOR category_id=DECODE(category_id,PARENT_ID,NULL,PARENT_ID) START WITH PARENT_ID=0 ORDER SIBLINGS BY show_order, category_id ', [$rootId])
            ->get();
        // $rtn = new DataDicCodeItem();
        // /foreach($items as $item){
        //     $rtn->map($items);
        // //}
        //dd( $items);
        return $items;
    }

    /**
     * 상세 조회
     *
     * @param integer $id
     * @return DataDicField
     */
    public function find(int $id)
    {
        $query = Category::query();
        return $query->find($id);
    }
    /**
     * 상세 조회 또는 실패 처리
     *
     * @param integer $id
     * @return DataDicField
     */
    public function findOrFail(int $id)
    {
        $field = $this->find($id);
        if (!$field) {
            api_abort_404('DataDicField');
        }
        return $field;
    }

    /**
     * 생성
     *
     * @param 생성 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicField 생성된 필드 객체
     */
    public function create(CategoryDto $dto, User $user)
    {
        $collection = new Category();       
        foreach ($dto->toArray() as $key => $val) {
            if (!($key == "root")) {
                $collection->$key = $val;
            };
        }
        $collection->category_id = $this->getSequence('SEQ_BC_CATEGORY_ID'); 
        $collection->save();
        return $collection;
    }

    /**
     * 수정
     *
     * @param integer 수정할 아이디
     * @param $dto 수정 데이터
     * @param \Api\Models\User $user 사용자 객체
     * @return \Api\Models\DataDicField 수정된 필드 객체
     */
    public function update(int $id, $dto, User $user)
    {
        $collection = $this->findOrFail($id);     
        $collection = new Category();
        foreach ($dto->toArray() as $key => $val) {
            if (!($key == "root")) {
                $collection->$key = $val;
            };
        }      
        $collection->save();

        return $field;
    }

    /**
     * 삭제
     *
     * @param integer $fieldId 삭제할 필드 아이디
     * @param User $user
     * @return bool|null 삭제 성공여부
     */
    public function delete(int $id, User $user)
    {
        $collection = $this->findOrFail($id);
        $ret = $field->delete();

        return $ret;
    }   

    public function makeNodes($codes, $targetNode = null )
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
             if(!is_null($targetNode) && ($targetNode == $code->category_id) ){
                if(!$selectNode) $selectNode = $code;
                $expanded = true;
             }else{
               $expanded = false;
             }
             $id = $code->parent_id.'/'.$code->category_id;
           
               $raw[$code->parent_id]['children'][$id] = array(
                'id' => $id,
                'idx' => $code->category_id,
                'code' => $code->code,
                'text' => $code->category_title,
                'leaf' => true,
                'dp'  => $code->dep,
                'code_path'  => $code->category_path,
                'expanded' => $expanded
              );
              dump($raw[$code->parent_id]['children'][$id]);
               $raw[$id] = &$raw[$code->parent_id]['children'][$id];
           }
           dump( $selectNode );
           dd($nlist[0]);
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

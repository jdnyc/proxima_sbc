<?php
namespace Api\Services;

use Api\Models\CodeType;
use Api\Models\Code;
use Api\Services\BaseService;

class SysCodeService extends BaseService
{
    private $sysCodeList;

    public function list($params = [])
    {
        //$codeType = 'DD_DOWN_TY';
        $query = CodeType::with(['sysCodes' => function ($query) {
            $query->where('use_yn','=', 'Y');
        }]);
        if( !empty($params['codeType']) ){
            $query->where('code' , '=', $params['codeType'] );
        }
        $lists = $query->get();
        return $lists;
    }

    

    /**
     * 시스템 코드 매핑 목록 
     *
     * @return array
     */
    public function codeMapByCodeType($codeType){

        $list = [];
        if( !$this->sysCodeList ){
            $this->setSysCodeList($this->list());
        }


        $codes = $this->getSysCodeList()->where('code', $codeType)->first();
        
        if( !empty($codes) ){
            $list = $codes->sysCodes->mapWithKeys(function ($item) {
                return [$item['code'] => $item['ref1']];
            });
        }
        return $list;
    }

    public function getSysCodeList(){
        return $this->sysCodeList;
    }
    public function setSysCodeList($sysCodeList){
        $this->sysCodeList = $sysCodeList;
    }

    public static function test(){       
    }
}

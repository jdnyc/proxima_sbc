<?php

namespace Api\Support\Helpers;

use Illuminate\Database\Capsule\Manager as DB;
use Api\Models\DataDicCodeItem;
use Api\Models\DataDicCodeSet;


class CodeHelper
{
    /**
     * (코드셋 아이디로)데이터 사전 코드아이템 조회
     *
     * @param int $codeSetId
     * @return DataDicCodeItem[]
     */
    public static function getCodeItemsByCodeSetId($codeSetId)
    {
        // $query =DataDicCodeItem::raw("select * from DD_CODE_ITEM where CODE_SET_ID = ".$codeSetId." and DD_CODE_ITEM.DELETE_DT is null CONNECT BY PRIOR ID=DECODE(ID, PARNTS_ID, NULL, PARNTS_ID)
        // START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, DP");

        $items = \Illuminate\Database\Capsule\Manager::table('dd_code_item')
            ->whereNull("delete_dt")
            // ->where('code_set_id',$codeSetId)
            ->whereRaw('code_set_id = ? CONNECT BY PRIOR ID=DECODE(ID,PARNTS_ID,NULL,PARNTS_ID) START WITH PARNTS_ID=0 ORDER SIBLINGS BY SORT_ORDR, ID', [$codeSetId])
            ->get();
        // $rtn = new DataDicCodeItem();
        // //foreach($items as $item){
        //     $rtn->map($items);
        // //}
        return $items;
    }

    public static function findCodeItemBycodeSetCodeAndCodeItemCode($codeSetCode, $codeItemCode)
    {

        $codeSetQuery = DataDicCodeSet::query();
        $codeSetQuery->where('code_set_code', '=', $codeSetCode);
        $codeSet = $codeSetQuery->first();
        $codeSetId = $codeSet->id;

        $query = DataDicCodeItem::query();
        $query->where('code_itm_code', '=', $codeItemCode)->where('code_set_id', $codeSetId)->select('code_set_id', 'code_itm_nm', 'code_itm_code');
        $codeItem = $query->first();
        return $codeItem;
    }
}

<?php
namespace Api\Services;

use Api\Models\BisCode;
use Api\Models\BisPlan;
use Api\Models\BisEpisode;
use Api\Models\BisProgram;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;

class BisEpisodeService extends BaseService
{
    public function list($params)
    {
    }

    public function searchByPgmId($pgm_id, $params =null)
    {
        $query = BisEpisode::query();
        $query->where('pgm_id', '=', "{$pgm_id}");
        if ( !empty( $params) ) {
            foreach ($params as $key => $value) {
                if (!empty($value)) {                    
                    if($key == 'keyword'){
                        $query->where(function ($q) use ($value) {
                            $q->where('epsd_nm', 'like', "%{$value}%")
                                ->orWhere('epsd_no', 'like', "%{$value}%");
                        });
                    }else{
                        $query->where($key, '=', "{$value}");
                    }
                }
            }
        }
        $lists = paginate($query);

        //필드 매핑
        $defaultMap = [
            'pgm_id' => 'progrm_code',
            'pgm_nm'  => 'progrm_nm',
            'epsd_no'  => 'tme_no',
            'keyword'  => 'kwrd',//키워드
            'epsd_nm'  => 'subtl',//부제
            'main_role'  => 'cast',//출연자
            'info2'  => 'cn',//내용
            'makepd'  => 'prod_pd_nm',//pd
            'prd_clf'  => 'prod_se',
            'delib_grd' => 'watgrad',
            'rec_ymd' => 'shooting_de',
            'rec_place'  => 'shooting_place'//촬영장소 
        ];

        //시청등급 코드 매핑
        $watgradRender = [
            '00' => 'all',
            '07' => 'ageof7',
            '12' => 'ageof12',
            '15' => 'ageof15',
            '19' => 'ageof19',
            '99' => 'exception'
        ];

        //제작기획 정보 추가
        if( $lists->count() > 0){
            foreach($lists as $key => $list){
                
                foreach($defaultMap as $source => $target){
                    if ( !empty( $lists[$key]->$source ) ) {
                        if($target == 'watgrad'){
                            $newVal = $watgradRender[$lists[$key]->$source];
                        }else{
                            $newVal = $lists[$key]->$source;
                        }
                        $lists[$key]->$target = $newVal ;
                    }
                }

                $planInfo = BisPlan::where('pgm_id', $list->pgm_id )->where('epsd_no',$list->epsd_no )
                ->select(['pln_no',
                'pln_title',
                'prd_dept_cd',
                'prd_dept_nm',
                'chan_cd',
                'pgm_id',
                'pgm_nm',
                'purpose',
                'outline',
                'sponsor',
                'documents',
                'cp_nm',
                'pd_nm',
                'mc_nm',
                'sc_nm',
                'scl_clf',
                'pln_ymd',
                'brd_bgn_ymd',
                'brd_end_ymd',
                'prd_loc',
                'brd_run',
                'pln_hm',
                'pln_wk_day',
                'wk_brd_cnt',
                'prgres_clf',
                'remark',
                'epsd_no',
                'epsd_no2',
                'rec_ymd',
                'rec_hm'])->first();
                if( !empty($planInfo) ){                    
                    $lists[$key]->plan_info = $planInfo;
                    $lists[$key]->cn = $planInfo->documents;//주요내용
                    $lists[$key]->prod_pd_nm = $planInfo->pd_nm;//pd명
                    $lists[$key]->brdcst_de = $planInfo->brd_bgn_ymd;//방송일자                   
                    $lists[$key]->cast = $planInfo->remark;
                }
            }
        }
        return $lists;
    }

    public function find($id)
    {
        $ids = explode(',', $id);
        $query = BisEpisode::query();
        $query->where('pgm_id' ,'=', $ids[0]);
        $query->where('epsd_no' ,'=', $ids[1]);
        //$query->selectRaw("UF_GET_COMCD('*SC010', scl_clf ) as SCL_CLF_NNM");
        

        //$this->includeUser($query, 'registerer');
        //$this->includeUser($query, 'updater');

        return $query->get();
    }

    public function findOrFail($id)
    {
        $program = $this->find($id);
        if (!$program) {
            api_abort_404('BisEpisode');
        }
        return $program;
    }

    public function findByCode( $hcode , $dcode = null ){
        
        $query = BisCode::query()->where('hcode', '=', $hcode)->where('dcode', '<>', '*')->select('dcode', 'dname');
        if( $dcode != null ){
           return self::findByCodeValue($list, $code);
        }else{
            $lists = $query->get()->toArray();
        }
        return $lists;
    }

    public function findByCodeValue($lists , $code){
        $rtn = $code;
        foreach($lists as $list){
            if( $list['dcode'] == $code ){
                $rtn = $list['dname'];
            }
        }
        return $rtn;
    }
}

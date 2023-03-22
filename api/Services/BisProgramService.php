<?php
namespace Api\Services;

use Api\Models\BisProgram;
use Api\Models\BisCode;
use Api\Services\BaseService;
use Illuminate\Support\Facades\DB;

class BisProgramService extends BaseService
{
    public function list($params)
    {      
      //  dd($params);
        $pgm_id = $params->pgm_id;
        $pgm_nm = $params->pgm_nm; 
        $dvs_yn = $params->dvs_yn; 
        $use_yn = $params->use_yn; 
        //dbd(false);
        $query = BisProgram::with("channels");
        $query->whereHas("channels", function($q) use ($use_yn){         
            $q->where('chan_cd', "CH_D");
            if ($use_yn) {
                $q->where('use_yn', $use_yn);
            }
        });
        // 검색
        if ( !is_null($pgm_id) || !is_null($pgm_nm) ) {
            $query->where(function ($q) use ($pgm_nm) {
                $q->where('pgm_id', 'like', "%{$pgm_nm}%")
                    ->orWhere('pgm_nm', 'like', "%{$pgm_nm}%");
            });
        }

        if ( !empty($dvs_yn) ) {  
            $query->where('dvs_yn', $dvs_yn);           
        }

        $lists = paginate($query);

        //편성구분-정규/특집
        $scl_clf_code = self::findByCode('*SC010');
        $prd_clf_code = self::findByCode('*SC002');

        $prdClfRender = [
            '001' => 'makingself',
            '002' => 'makingoutorder',
            '007' => 'makingoutorderpart',
            '004' => 'purchase',
            '006' => 'purchase',
            '999' => 'etc',
            '008' => 'organizationopen',
        ];

        $defaultMap = [
            'pgm_id' => 'progrm_code',
            'pgm_nm'  => 'progrm_nm',
            'prd_ym'  => 'prod_de' ,
            'brd_form'  => 'brdcst_stle_se'
        ];
        // 01	온라인
        // 02	제작
        // 03	텔레시네
        $cmsPgmTypeRender = [
            '01' => 'O',
            '02' => 'B',
            '03' => 'T'
        ];

        foreach($lists as $key => $list){
            foreach($defaultMap as $source => $target){
                if ( !empty( $lists[$key]->$source ) ) {              
                    $newVal = $lists[$key]->$source;               
                    $lists[$key]->$target = $newVal ;
                }
            }
            $lists[$key]->scl_clf =  $list->channels->scl_clf ;
            $lists[$key]->prod_se =  $prdClfRender[$list->channels->prd_clf] ;
            $lists[$key]->scl_clf_nm = self::findByCodeValue($scl_clf_code, $list->channels->scl_clf );
            $lists[$key]->prd_clf_nm = self::findByCodeValue($prd_clf_code, $list->channels->prd_clf );  
            if( !empty($cmsPgmTypeRender[$list->cms_pgm_typ]) ){
                $lists[$key]->vido_ty_se  =  $cmsPgmTypeRender[$list->cms_pgm_typ] ;
            }
        }
        return $lists;
    }

    public function search($key, $value , $operator ='like')
    {
        $query = BisProgram::query();
        if($operator == 'like'){
            $query->where($key, 'like', "%{$value}%");
        }else{
            $query->where($key, '=', "{$value}");
        }
        $query->with("channels");
        
        return $query->get();
    }

    public function find($id)
    {
        $query = BisProgram::with("channels");
        
        //$query->selectRaw("UF_GET_COMCD('*SC010', scl_clf ) as SCL_CLF_NNM");
        

        //$this->includeUser($query, 'registerer');
        //$this->includeUser($query, 'updater');

        return $query->find($id);
    }

    public function findOrFail($id)
    {
        $program = $this->find($id);
        if (!$program) {
            api_abort_404('BisProgram');
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

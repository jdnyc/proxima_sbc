<?php

namespace Api\Services;

use Api\Models\Newsdsc;
use Api\Models\Newsmas;
use Api\Models\Broaddsc;
use Api\Models\Broadmas;
use Api\Models\Moviedsc;
use Api\Models\Moviemas;
use Api\Models\Newstape;
use Api\Models\Sfilmdsc;
use Api\Models\Sfilmmed;
use Api\Models\Shotlist;
use Api\Models\Stapedsc;
use Api\Models\Stapemed;
use Api\Models\Movietape;
use Api\Types\ManageType;
use Api\Models\BisProgram;
use Api\Models\Newscondsc;
use Api\Models\Newsconmed;
use Api\Services\BaseService;
use Api\Models\ContentSysMeta;
use Api\Models\KtvNewsmas;
use Api\Services\BisProgramService;
use Psr\Container\ContainerInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Capsule\Manager as DB;


class MaterialService extends BaseService
{
    public function list($params){
        
        
        $telecineTySe = $params->input('telecine_type');  
        if($telecineTySe == ManageType::BROADMAS){
            $list = $this->listByBroad(($params));
        }else if($telecineTySe == ManageType::STAPEMED){
            $list = $this->listByStape($params);
        }else if($telecineTySe == ManageType::NEWSCONMET){
            $list = $this->listByNewsconmed($params);
        }else if($telecineTySe == ManageType::SFILMMED){
            $list = $this->listBySfilmmed($params);
        }else if($telecineTySe == ManageType::MOVIEMAS){
            $list = $this->listByMoviemas($params);
        }else if($telecineTySe == ManageType::NEWSMAS){
            $list = $this->listByNewsmas($params);
        }else if($telecineTySe == ManageType::KTV_NEWSMAS){
            $list = $this->listByKtvNewsmas($params);
        }
        
       
       
        return $list;
    }

    public function listByKtvNewsmas($params){
        $tapeNo = $params->input('tape_no'); 
        
        $query = KtvNewsmas::from('KTV_NEWSMAS KM')
        ->leftJoin('KTV_NEWSTAPE KT',function($join){
            $join->on('KM.id_no', '=', 'KT.id_no');
        })
        ->orderBy('KT.tape_no')
        ->select('KT.tape_no',
                    'KM.PD',
                  'KM.finishdate',
                  'KM.colorcode',
                  'KM.sizecode',
                  'KM.id_no'
                //   'MM.makecode'
                  );
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
        if(!empty($tapeNo)){
            $query->where('KT.tape_no', 'like',"%{$tapeNo}%");
        };    
        $lists = paginate($query);

        foreach ($lists as$key => $list) {
           
            $lists[$key]->manage_no = $list->tape_no;
            // $lists[$key]->title = $this->title;
            $lists[$key]->tape_hold_at = 'Y';
            $lists[$key]->telecine_ty_se ='K';
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }

            if ($list->colorcode == '1') {
                $lists[$key]->color = '컬러';
                $lists[$key]->clor = 'color';
            }else if($list->colorcode == '2') {
                $lists[$key]->color = '흑백';
                $lists[$key]->clor = 'monochrome';
                
            }else{
                $lists[$key]->clor = '';
                $lists[$key]->color = '';
            }
            
            if(!empty(trim($list->finishdate)) && strlen(trim($list->finishdate))==8 ){
                $formatDate = \Carbon\Carbon::createFromFormat('Ymd', $list->finishdate);
                $lists[$key]->shootingdate = $formatDate->format('Y-m-d');
                $lists[$key]->shooting_de = $formatDate->format('Ymd');
            }
            
            
            $lists[$key]->prod_pd_nm = $list->pd;
            $lists[$key]->hono = $list->id_no;



        }

        return $lists;
    }

    public function listByNewsmas($params){
        $tapeNo = $params->input('tape_no'); 
        $query = Newsmas::from('NEWSMAS NM')
                ->leftJoin('NEWSTAPE NT',function($join){
                    $join->on('NM.id_no', '=', 'NT.id_no');
                })
                ->orderBy('NT.tape_no')
                ->select(
                    'NT.tape_no',
                    'NM.pd',
                    'NM.finishdate',
                    'NM.colorcode',
                    'NM.sizecode',
                    'NM.id_no'
                );
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
        if(!empty($tapeNo)){
            $query->where('NT.tape_no', 'like',"%{$tapeNo}%");
        };    
        $lists = paginate($query);
        
       
        
        
        foreach ($lists as$key => $list) {
           
            $lists[$key]->manage_no = $list->tape_no;
            // $lists[$key]->title = $this->title;
            $lists[$key]->tape_hold_at = 'Y';
            $lists[$key]->telecine_ty_se ='D';
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }

            if ($list->colorcode == '1') {
                $lists[$key]->color = '컬러';
                $lists[$key]->clor = 'color';
            }else if($list->colorcode == '2') {
                $lists[$key]->color = '흑백';
                $lists[$key]->clor = 'monochrome';
                
            }else{
                $lists[$key]->clor = '';
                $lists[$key]->color = '';
            }
            
            if(!empty(trim($list->finishdate)) && strlen(trim($list->finishdate))==8 ){
                $formatDate = \Carbon\Carbon::createFromFormat('Ymd', $list->finishdate);
                $lists[$key]->shootingdate = $formatDate->format('Y-m-d');
                $lists[$key]->shooting_de = $formatDate->format('Ymd');
            };
            
            
            $lists[$key]->prod_pd_nm = $list->pd;
            $lists[$key]->hono = $list->id_no;

        }

        return $lists;

                
    }

    public function listByMoviemas($params){
        $tapeNo = $params->input('tape_no'); 
        $query = Moviemas::from('MOVIEMAS MM')
                ->leftJoin('MOVIETAPE MT',function($join){
                    $join->on('MM.id_no', '=', 'MT.id_no');
                })
                ->orderBy('MT.tape_no')
                ->select('MT.tape_no',
                            'MM.PD',
                          'MM.title',
                          'MM.finishdate',
                          'MM.colorcode',
                          'MM.sizecode',
                          'MM.id_no'
                        //   'MM.makecode'
                          );
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
        if(!empty($tapeNo)){
            $query->where('MT.tape_no', 'like',"%{$tapeNo}%");
        };    
        
        $lists = paginate($query);
        
       
        
        
        foreach ($lists as$key => $list) {
            $lists[$key]->tape_no = trim($list->tape_no);
            $lists[$key]->manage_no = trim($list->tape_no);
            // $lists[$key]->title = $list->title;
            $lists[$key]->tape_hold_at = 'Y';
            
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }

            if ($list->colorcode == '1') {
                $lists[$key]->color = '컬러';
                $lists[$key]->clor = 'color';
            }else if($list->colorcode == '2') {
                $lists[$key]->color = '흑백';
                $lists[$key]->clor = 'monochrome';
                
            }else{
                $lists[$key]->clor = '';
                $lists[$key]->color = '';
            }
            
            if(!empty($list->finishdate) && strlen(trim($list->finishdate))==8 ){
                $formatDate = \Carbon\Carbon::createFromFormat('Ymd', $list->finishdate);
                $lists[$key]->shootingdate = $formatDate->format('Y-m-d');
                $lists[$key]->shooting_de = $formatDate->format('Ymd');
            };
            
            
            $lists[$key]->prod_pd_nm = $list->pd;
            $lists[$key]->hono = $list->id_no;

        }

        return $lists;

    }

    public function listBySfilmmed($params){
        $tapeNo = $params->input('tape_no');  
        $query = Sfilmmed::query();
        
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
        if(!empty($tapeNo)){
            $query->where('can_no', 'like',"%{$tapeNo}%");
        };

        $lists = paginate($query);

        
        foreach ($lists as$key => $list) {
            $lists[$key]->tape_no = $list->can_no;
            $lists[$key]->manage_no = $list->can_no;
            $lists[$key]->subtl = $list->subtitle;
            // $lists[$key]->title = $list->title;
            $lists[$key]->tape_hold_at = 'Y';
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }

            if ($list->colorcode == '1') {
                $lists[$key]->color = '컬러';
                $lists[$key]->clor = 'color';
            }else if($list->colorcode == '2') {
                $lists[$key]->color = '흑백';
                $lists[$key]->clor = 'monochrome';
                
            }else{
                $lists[$key]->clor = '';
                $lists[$key]->color = '';
            }

        }   

        
        return $lists;
    }

    public function listByNewsconmed($params){
        $tapeNo = trim($params->input('tape_no'));  
        
        $query = Newsconmed::query();
        $dscQuery = Newscondsc::query();
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
        if(!empty($tapeNo)){
            $query->where('tape_no', 'like',"%{$tapeNo}%");
        };

        $lists = paginate($query);

        
        foreach ($lists as$key => $list) {
            $lists[$key]->manage_no = $list->tape_no;
            $lists[$key]->subtl = $list->subtitle;
            // $lists[$key]->title = $this->subtitle;
            // $lists[$key]->_title = $this->subtitle;
            $lists[$key]->tape_hold_at = 'Y';
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }

            
        }   

        
        return $lists;
    }

    public function listByBroad($params){
    
        $tapeNo = $params->input('tape_no');  
        
        
        $query = Broadmas::from('broadmas bm');
                        // ->leftJoin(DB::raw("(SELECT T1.tape_no, 
                        //                             T1.id_no ,
                        //                             T1.SCENECONTENT
                        //                     FROM broaddsc T1
                        //                     JOIN (SELECT bd.tape_no, bd.id_no, 
                        //                                 min(SCENENO) AS min
                        //                         FROM BROADDSC bd
                        //                         GROUP BY bd.tape_no , bd.id_no
                        //                         ) T2 ON T1.TAPE_NO = T2.Tape_no AND  T1.id_no = T2.id_no AND T1.SCENENO = T2.min
                        //                     ) bd2"
                        //                     ),function($join){
                        //                         $join->on('bm.tape_no', '=', 'bd2.tape_no')->on('bm.id_no', '=', 'bd2.id_no');
                        //                     });
                     
        // $query->select(
        //     'procode',
        //     'tape_no',
        //     'id_no',
        //     'subtitle',
        //     'vol_no',
        //     'procode',
        //     'broaddate'
        // );
        // $query->addSelectRaw();
        // $bisQuery = BisProgram::query();
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
         
        $bisProgramService = new BisProgramService(app()->getContainer());
        
        if(!empty($tapeNo)){
            $query->where('tape_no', 'like',"%{$tapeNo}%");
        };
    
        // dump($query->first());
        
        $lists = paginate($query);

        foreach($lists as $key => $list){
            
            $procode = $list->procode;
            $tapeNo = $list->tape_no;
            $idNo = $list->id_no;
            $subTitle = $list->subtitle;
            $volNo = $list->vol_no;
            $lists[$key]->program_nm = null;
            $lists[$key]->manage_no = $list->tape_no;
            $lists[$key]->subtl = $subTitle;
            $lists[$key]->hono = $volNo;
            // $lists[$key]->procode = null;
            $lists[$key]->progrm_code = null;

            $lists[$key]->tape_hold_at = 'Y';
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }
            if (!empty($list->broaddate) && strlen(trim($list->broaddate))==8) {
                $formatDate = \Carbon\Carbon::createFromFormat('Ymd', $list->broaddate);
                $lists[$key]->broaddate = $formatDate->format('Y-m-d');
                $lists[$key]->brdcst_de = $formatDate->format('Ymd');
            }
            
            if(!empty($procode)){
                // $bisPgmInfo = $bisQuery->find($procode);
                
                $bisPgmInfo = $bisProgramService->find($procode);
                
                if(!empty($bisPgmInfo)){
                    $lists[$key]->program_nm = $bisPgmInfo->pgm_nm;
                    $lists[$key]->progrm_code = $bisPgmInfo->pgm_id;
                }
            };


            // if(!empty($tapeNo) && !empty($idNo)){
            //     $dscQuery = Broaddsc::query();
            //     $dscInfo = $dscQuery->where('tape_no', $tapeNo)->where('id_no', $idNo)->orderBy('sceneno')->first();
               
            //     if(!empty($dscInfo)){
            //         $lists[$key]->scenecontent = $dscInfo->scenecontent;
            //     }
            // }

        };
        return $lists;
    }

    public function listByStape($params){
    
        $tapeNo = $params->input('tape_no');  
     
        
        $query = Stapemed::query();
        $query->where(function ($query) {
            $query->whereNull('delcode'  );
            $query->orWhere('delcode','0' );
        });
        // $bisQuery = BisProgram::query();
        $dscQuery = Stapedsc::query();
        $bisProgramService = new BisProgramService(app()->getContainer());
        if(!empty($tapeNo)){
            $query->where('tape_no', 'like',"%{$tapeNo}%");
        };
        $lists = paginate($query);

        foreach ($lists as$key => $list) {
            $tapeNo = $list->tape_no;
            $subTitle = $list->subtitle;
            // $procode = $list->procode;
            // $idNo = $list->id_no;
            $serialNo = $list->serial_no;
            
            $lists[$key]->manage_no = $list->tape_no;
            $lists[$key]->subtl = $subTitle;
            $lists[$key]->hono = $serialNo;
            if (!empty($list->hshotdate) && strlen(trim($list->hshotdate))==8) {
                $formatDate = \Carbon\Carbon::createFromFormat('Ymd', $list->hshotdate);
                if (!empty($formatDate)) {
                    $lists[$key]->shootingdate = $formatDate->format('Y-m-d');
                    $lists[$key]->shooting_de = $formatDate->format('Ymd');
                }
            }
            

            if ( !empty($list->temp1 ) ) {
                $lists[$key]->vido_jrnlst = $list->temp1;
            }

            $lists[$key]->tape_hold_at = 'Y';
            $lists[$key]->telecine_ty_se ='E';
            if($list->medcode == '1'){
                $lists[$key]->med = '베타캠';
                 $lists[$key]->tape_knd = 'betacam';
            }else if($list->medcode == '2'){
                $lists[$key]->med = 'DCT';
                 $lists[$key]->tape_knd = 'dct';
            }else if($list->medcode == '3'){
                $lists[$key]->med = '디지베타';
                 $lists[$key]->tape_knd = 'digibeta';
            }else if($list->medcode == '4'){
                $lists[$key]->med = 'DVD';
                 $lists[$key]->tape_knd = 'dvd';
            }else{
                $lists[$key]->med = '';
            };

            if ($list->sizecode == 'A') {
                $lists[$key]->size = '소';
                            $lists[$key]->tape_mg = 'little';
            }else if($list->sizecode == 'B') {
                $lists[$key]->size = '중';
                            $lists[$key]->tape_mg = 'middle';
            }else if($list->sizecode == 'C'){
                $lists[$key]->size = '대';
                            $lists[$key]->tape_mg = 'big';
            }else{
                $lists[$key]->size = '';
            }

            // if(!empty($tapeNo) && !empty($idNo)){
            //     $dscInfo = $dscQuery->where('tape_no', $tapeNo)->orderBy('sceneno')->first();
            //     if(!empty($dscInfo)){
            //         $lists[$key]->scenecontent = $dscInfo->scenecontent;
            //     }
            // }

        }
        return $lists;

    }

    public function scenesByBroad($params){
        $idNo = null;
        $contentId = null;
        $tapeNo = null;
     
        if(is_array($params)){
            if(array_key_exists('id_no',$params)){
                $idNo = $params['id_no'];
            }
      
            if(array_key_exists('tape_no',$params)){
                $tapeNo = $params['tape_no'];
            }
        }else{
            $tapeNo = $params->input('tape_no');  
            $idNo = $params->input('id_no');  

        }
        $dscQuery = Broaddsc::query();
        $dscQuery->where(function ($dscQuery) {
            $dscQuery->whereNull('delcode'  );
            $dscQuery->orWhere('delcode','0' );
        });
        $dscInfo = $dscQuery->where('tape_no', $tapeNo)->where('id_no',$idNo)->orderBy('sceneno')->get();
        return $dscInfo;
    }
    public function scenesByStape($params){
        $idNo = null;
        $contentId = null;
        $tapeNo = null;
        if(is_array($params)){
            if(array_key_exists('tape_no',$params)){
                $tapeNo = $params['tape_no'];
            }
        }else{
            $tapeNo = $params->input('tape_no');  
        }
    
          $dscQuery = Stapedsc::query();
        $dscInfo = $dscQuery->where('tape_no', $tapeNo)->orderBy('sceneno')->get();
        
        return $dscInfo;
    }

    public function scenesByNewsconmet($params){
           if(is_array($params)){
            if(array_key_exists('tape_no',$params)){
                $tapeNo = $params['tape_no'];
            }
            
        }else{
                $tapeNo = $params->input('tape_no');
        }
        $dscQuery = Newscondsc::query();
        $dscInfo = $dscQuery->where('tape_no', $tapeNo)->orderBy('sceneno')->get();
        return $dscInfo;

    }

    public function scenesBySfilmdsc($params){
        if(is_array($params)){
            if(array_key_exists('tape_no',$params)){
                $tapeNo = $params['tape_no'];
            }
            
        }else{
            $tapeNo = $params->input('tape_no');
        }
        $dscQuery = Sfilmdsc::query();
        $dscInfo = $dscQuery->where('can_no', $tapeNo)->orderBy('sceneno')->get();
        
        return $dscInfo;

    }

    public function scenesByMoviemas($params){
        
        if(is_array($params)){
            if(array_key_exists('id_no',$params)){
                $idNo = $params['id_no'];
            }
            
        }else{
            $idNo = $params->input('id_no');
        }
        $dscQuery = Moviedsc::query();
        $dscInfo = $dscQuery->where('id_no', $idNo)->orderBy('sceneno')->get();
        
        return $dscInfo;
    }

    public function scenesByktvNewsmas($params){
        if (is_array($params)) {
            if (array_key_exists('id_no', $params)) {
                $idNo = $params['id_no'];
            }
        }else{
            $idNo = $params->input('id_no');  
        }
        
        $dscQuery = Newsdsc::query();
        $dscInfo = $dscQuery->where('id_no', $idNo)->orderBy('sceneno')->get();
        return $dscInfo;
    }

    public function scenesByNewsmas($params){
        if (is_array($params)) {
            if (array_key_exists('id_no', $params)) {
                $idNo = $params['id_no'];
            }
        }else{
            $idNo = $params->input('id_no');  
        }
        
        $dscQuery = Newsdsc::query();
        $dscInfo = $dscQuery->where('id_no', $idNo)->orderBy('sceneno')->get();
        return $dscInfo;
    }

    public function scenes($params){
  
        if(is_array($params)){
            if(array_key_exists('content_id',$params)){
                $contentId = $params['content_id'];
            }
            if (array_key_exists('telecine_type', $params)) {
                $telecineTySe = $params['telecine_type'];
            } 
        }else{
            $contentId = $params->input('content_id');
            $telecineTySe = $params->input('telecine_type');    
        }
     
        if($telecineTySe == ManageType::BROADMAS){
            $list = $this->scenesByBroad(($params));
        }else if($telecineTySe == ManageType::STAPEMED){
            $list = $this->scenesByStape($params);
        }else if($telecineTySe == ManageType::NEWSCONMET){
            $list = $this->scenesByNewsconmet($params);
        }else if($telecineTySe == ManageType::SFILMMED){

            $list = $this->scenesBySfilmdsc($params);
        }else if($telecineTySe == ManageType::MOVIEMAS){
            $list = $this->scenesByMoviemas($params);
        }else if($telecineTySe == ManageType::NEWSMAS){
            $list = $this->scenesByNewsmas($params);
        }else if($telecineTySe == ManageType::KTV_NEWSMAS){
            $list = $this->scenesByktvNewsmas($params);
        }
        
     
        // $sysmeta = ContentSysMeta::find($contentId);
        
        $frameRate = 30;
        $startSec = 0;
        $endSec = 1;
        $inFrame = 0;
        $outFrame = 30;
        $duration = 30; // 재생길이
        $color = '#FF0000';
        // $
    
        if(empty($list)){
            return;
        }
        foreach($list as $info){
            $startSec += 1;

            
            if($startSec >= 600){
                $startSec = 1;
            }

            if(($startSec >= 60) && ($startSec % 60)){
                $outFrame -= 2;
            }


            $shotlist = new Shotlist();
            $shotlist->list_id = $this->getSequence('SEQ_RP_LIST_ID');
            $shotlist->color = $color;
            $shotlist->type = 'shot_list';
            $shotlist->content_id = $contentId;
            // $shotlist->in_frame = $inFrame;
            // $shotlist->out_frame = $outFrame;
            $shotlist->in_frame = 0;
            $shotlist->out_frame = 0;
            $shotlist->duration = 0;
            $shotlist->list_user_id = auth()->user()->user_id;
            $shotlist->start_frame = 0;
            // $shotlist->end_frame = $outFrame - $inFrame;
            // $shotlist->in_frame = $inFrame;
            // $shotlist->out_frame = $outFrame;
            $shotlist->end_frame = 0;
            $shotlist->in_frame = 0;
            $shotlist->out_frame = 0;
            $shotlist->comments = $info->scenecontent;
            $shotlist->title = '';
         
            
            if(($telecineTySe == ManageType::NEWSCONMET) || 
                ($telecineTySe == ManageType::SFILMMED) || 
                ($telecineTySe == ManageType::NEWSMAS) || 
                ($telecineTySe == ManageType::MOVIEMAS) ||
                ($telecineTySe == ManageType::KTV_NEWSMAS)
                ){
                if($info->seqcode == '0'){
                    $shotlist->title = '장면';
                }else if($info->seqcode == '0'){
                    $shotlist->title = '꼭지';
                }                
            }

            $inFrame = $outFrame;
            $outFrame += $frameRate;
            
            $shotlist->save();
        }
        return $list;
    }
}
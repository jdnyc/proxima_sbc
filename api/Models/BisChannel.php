<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * BIS 프로그램 조회
 * a.pgm_id              as PROG_ID						--프로그램 ID  
 * , 'TV'                as MEDIA_DIV          --매체구분
 * , a.pgm_nm            as PROG_NAME          --프로그램 명 
 * , a.pgm_onm           as PROG_ORG_NM        --프로그램 원제 
 * , scpgmchan.scl_clf           as PROG_DIV           --편성구분
 * , scpgmchan.prd_clf           as PROD_DIV           --제작구분
 * , scpgmchan.jenr_clf          as GENRE_DIV          --장르구분 
 * , scpgmchan.stry_clf          as CONTENTS_DIV       --내용구분
 * , a.tot_cnt           as TOT_EPIS_CNT       --총 제작편수 
 * , a.delib_grd         as WATCH_AGE          --심의등급 
 * , b.brd_bgn_ymd       as BROAD_SDATE        --방송시작일 
 * , b.brd_end_ymd       as BROAD_EDATE        --방송종료일  
 * , b.pln_wk_day        as BROAD_DAY          --방송 요일
 * , b.pln_hm            as BROAD_STIME        --방송 시작 시간 
 * , cisbis.uf_cal_brdhm(b.pln_hm,a.brd_run,'T') as BROAD_ETIME        --방송 종료 시간 
 * , a.brd_run           as BROAD_RUNTIME      --방영 시간
 * , a.prd_cntry1        as PROD_COUNTRY       --제작국가  
 * , a.prod_list         as PROD_LIST          --제작진 리스트
 * , a.main_role         as MAIN_PLAYER_LIST   --주연 리스트 
 * , a.supp_role         as SUB_PLAYER_LIST    --조연 리스트 
 * , a.pgm_info          as PROG_DESC          --프로그램 설명  
 * , b.purpose           as PROG_PLAN          --기획의도  
 * , a.regr              as REG_ID             --등록자 
 * , a.reg_dt            as REG_DT             --등록일시   
 * , a.modr              as MOD_ID             --수정자  
 * , a.mod_dt            as MOD_DT             --수정일시 
 * @property int 
 * @property string 
 */
class BisChannel extends BaseModel
{
    //use SoftDeletes;
    protected $connection = 'bis';

    protected $table = 'scpgmchan';

    protected $primaryKey = 'pgm_id,chan_cd';
    protected $keyType = 'string';
    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';

    public $sortable = ['pgm_id'];
    public $sort = 'pgm_id';
    public $dir = 'desc';
    

    // protected $fillable = [   
    // ];
}

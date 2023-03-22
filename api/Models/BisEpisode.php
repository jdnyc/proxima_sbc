<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

// cisbis.scpgmepsd a a.pgm_id  = b.pgm_id and a.epsd_id = b.epsd_id and a.epsd_no = b.epsd_no
// cisbis.scsclmst b where aa.chan_cd = 'CH_D'   and aa.brd_ymd  = ( select max(bb.brd_ymd)from cisbis.scsclmst where bb.chan_cd = 'CH_D' and bb.pgm_id  = aa.pgm_id and bb.epsd_id = aa.epsd_id and bb.epsd_no = aa.epsd_no )
// cisbis.scpgmmst c and a.pgm_id  = c.pgm_id
// 	  a.pgm_id       as PROG_ID          -- 프로그램 ID
//      ,a.epsd_id      as EPIS_ID         -- 회차
// 	 ,a.epsd_no      as EPIS_CNT         -- 회차 순번
// 	 ,a.epsd_end_yn  as FINAL_EPIS_YN    -- 최종회차 여부
// 	 ,a.epsd_use_yn  as SERVICE_YN       -- 서비스 여부
// 	 ,a.epsd_nm      as EPIS_TITLE       -- 회차 제목
// 	 ,a.epsd_nm      as EPIS_SUB_TITLE   -- 부회차 제목
// 	 ,a.info1        as ADD_CONTENTS     -- 회차 부가 내용
// 	 ,a.info2        as BROAD_CONTENTS   -- 회차 내용
//      ,b.brd_ymd      as BROAD_DATE       -- 방송일자
// 	 ,b.brd_hm       as BROAD_TIME       -- 방송 시간
// 	 ,b.brd_run      as BROAD_SEC        -- 방송 분
// 	 ,b.delib_grd    as WATCH_AGE        -- 시청등급
//      ,b.caption_yn   as EPIS_CAPTION_YN  -- 자막 유무
//      ,c.director     as PROD_LIST        -- 제작진 리스트
// 	 ,c.main_role    as MAIN_PLAYER_LIST -- 주연 리스트
// 	 ,c.supp_role    as SUB_PLAYER_LIST  -- 조연 리스트
/**
 * BIS 회차
 * 
 * @property int 
 * @property string 
 */
class BisEpisode extends BaseModel
{
    //use SoftDeletes;
    protected $connection = 'bis';
   
    protected $table = 'scpgmepsd';

    protected $primaryKey = 'pgm_id,epsd_no';
    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';

    public $sortable = ['epsd_no'];
    public $sort = 'epsd_no';
    public $dir = 'desc'; 

    // protected $fillable = [
    //     'bank_deposit',
    //     'bank_nm',
    //     'bank_num',
    //     'cancel_date',
    //     'card_num',
    //     'copy_date',
    //     'delivery',
    //     'delivery_amt',
    //     'delivery_date',
    //     'memo',
    //     'memo1',
    //     'memo2',
    //     'order_date',
    //     'order_num',
    //     'purpose',
    //     'receipt_amt',
    //     'receipt_date',
    //     'repay_date',
    //     'status',
    //     'usepo'
    // ];
}

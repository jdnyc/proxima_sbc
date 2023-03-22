<?php

namespace Api\Models;

use Api\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;


// SELECT PLN_NO,--pk
// PLN_TITLE,--기획서명
// PRD_DEPT_CD,--제작부서
// PRD_DEPT_NM,--제작부서명
// CHAN_CD,--채널코드
// PGM_ID,--프로그램id
// PGM_NM,--프로그램명
// PURPOSE,--핵심메시지
// OUTLINE,--제작방향
// SPONSOR,--스폰서
// DOCUMENTS,--문서
// CP_NM,--cp명
// PD_NM,--pd명
// MC_NM,--mc명
// SC_NM,--sc명
// SCL_CLF,--??
// PLN_YMD,--기획일자
// BRD_BGN_YMD,--방송시작일
// BRD_END_YMD,--방송종료일
// PRD_LOC,--촬영장소
// BRD_RUN,--방송길이
// PLN_HM,--방송시간
// PLN_WK_DAY,--방송요일
// WK_BRD_CNT,--방송요일갯수
// PRGRES_CLF,
// REMARK,--비고
// EPSD_NO,--회차번호
// EPSD_NO2,--회차번호?
// REC_YMD,--녹화일
// REC_HM--녹화시간
// FROM PDPLNMST 
/**
 * BIS 제작기획서
 * 
 * @property int 
 * @property string 
 */
class BisPlan extends BaseModel
{
    //use SoftDeletes;
    protected $connection = 'bis';
   
    protected $table = 'pdplnmst';

    protected $primaryKey = 'pln_no';
    protected $keyType = 'string';
    const CREATED_AT = null;//'regist_dt';
    const UPDATED_AT = null;//'updt_dt';
    const DELETED_AT = null;//'delete_dt';';

    public $sortable = ['pln_no'];
    public $sort = 'pln_no';
    public $dir = 'desc'; 
}

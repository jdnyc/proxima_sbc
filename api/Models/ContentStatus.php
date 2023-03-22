<?php
namespace Api\Models;

use Api\Models\BaseModel;
use Api\Models\ContentUsrMeta;
use Illuminate\Database\Eloquent\Model;

class ContentStatus extends BaseModel
{
    protected $table = 'bc_content_status';

    protected $primaryKey = 'content_id';

    const CREATED_AT = null;
    const UPDATED_AT = null;

    
    protected $guarded = [];

    public $sort = 'content_id';

    public $sortable = ['content_id'];

    protected $casts = [
        'content_id' => 'integer'
    ];

    protected $fillable = [        
        'content_id',
        'archive_status',
        'archive_date',
        'restore_date',
        'resolution',
        'original_link_yn',
        'loudness',
        'qc',
        'use_prhibt_relis_dt',
        'use_prhibt_relis_user_id',
        'use_prhibt_set_dt',
        'use_prhibt_set_user_id',
        'archv_end_dt',
        'archv_requst_at',
        'archv_rqester',
        'archv_regist_requst_ty',
        'archv_begin_dt',
        'archv_sttus',
        'catlg_end_dt',
        'restore_at',
        'mcr_trnsmis_sttus',
        'mcr_trnsmis_end_dt',
        'mcr_trnsmis_begin_dt',
        'qc_cnfirm_at',
        'qc_cnfrmr',
        'begin_frme_indx',
        'scr_trnsmis_sttus',
        'scr_news_trnsmis_sttus',
        'scr_trnsmis_end_dt',
        'scr_trnsmis_begin_dt',
        'transcd_end_dt',
        'trnsmis_requst_dt',
        'trnsmis_rqester',
        'trnsmis_sttus',
        'trnsmis_at',
        'use_prhibt_set_resn',
        'archv_exctn',
        'video_encd_sttus',
        'catlg_begin_dt',
        'catlg_sttus',
        'ingest_dt',
        'ingest_place',
        'ingest_exctn',
        'archv_trget_at',
        'restore_end_dt',
        'restore_begin_dt',
        'restore_sttus',
        'transcd_begin_dt',
        'transcd_sttus',
        'bfe_video_id',
        'dtl_archv_sttus',
        'dtl_archv_begin_dt' ,
        'dtl_archv_end_dt',
        'restore_at',
        'scr_trnsmis_ty',
        'indvdlinfo_at'
    ];

}

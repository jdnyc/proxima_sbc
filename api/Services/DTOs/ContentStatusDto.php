<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 콘텐츠 DTO
 * 
 * @property int $content_id id
 */
final class ContentStatusDto extends DataTransferObject
{
    public $content_id;
    public $archive_status;
    public $archive_date;
    public $restore_date;
    public $resolution;
    public $original_link_yn;
    public $loudness;
    public $qc;
    public $use_prhibt_relis_dt;
    public $use_prhibt_relis_user_id;
    public $use_prhibt_set_dt;
    public $use_prhibt_set_user_id;
    public $archv_end_dt;
    public $archv_requst_at;
    public $archv_rqester;
    public $archv_regist_requst_ty;
    public $archv_begin_dt;
    public $archv_sttus;
    public $catlg_end_dt;
    public $restore_at;
    public $mcr_trnsmis_sttus;
    public $mcr_trnsmis_end_dt;
    public $mcr_trnsmis_begin_dt;
    public $qc_cnfirm_at;
    public $qc_cnfrmr;
    public $begin_frme_indx;
    public $scr_trnsmis_sttus;
    public $scr_trnsmis_end_dt;
    public $scr_trnsmis_begin_dt;
    public $transcd_end_dt;
    public $trnsmis_requst_dt;
    public $trnsmis_rqester;
    public $trnsmis_sttus;
    public $trnsmis_at;
    public $use_prhibt_set_resn;
    public $archv_exctn;
    public $video_encd_sttus;
    public $catlg_begin_dt;
    public $catlg_sttus;
    public $ingest_dt;
    public $ingest_place;
    public $ingest_exctn;
    public $archv_trget_at;
    public $restore_end_dt;
    public $restore_begin_dt;
    public $restore_sttus;
    public $transcd_begin_dt;
    public $transcd_sttus;

    public $bfe_video_id;
    public $dtl_archv_sttus;
    public $dtl_archv_begin_dt;
    public $dtl_archv_end_dt;
}
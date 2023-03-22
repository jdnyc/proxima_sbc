<?php

namespace Api\Services\DTOs;

use Spatie\DataTransferObject\DataTransferObject;
use Respect\Validation\Validator as v;

/**
 * 콘텐츠 DTO
 * 
 * @property int $content_id id
 */
final class ContentUsrMetaDto extends DataTransferObject
{
    public $usr_content_id;
    public $origin;
    public $cpyrhtown;
    public $incdnt_indict_at;
    public $prsn_se_1;
    public $prsn_se_2;
    public $prsn_lc_2;
    public $all_vido_at;
    public $hmpg_cntnts_id;
    public $dta_se;
    public $brdcst_stle_se;
    public $vido_ty_se;
    public $prod_step_se;
    public $shooting_orginl_atrb;
    public $progrm_code;
    public $progrm_nm;
    public $tme_no;
    public $brdcst_de;
    public $kwrd;
    public $media_id;
    public $recptn_stle;
    public $thema_cl;
    public $matr_knd;
    public $embg_at;
    public $embg_relis_dt;
    public $use_prhibt_at;
    public $prsrv_pd_et_resn;
    public $memo;
    public $authr;
    public $potogrf_nation;
    public $potogrf_cty;
    public $dta_detail_id;
    public $embg_resn;
    public $prod_se;
    public $outord_makr;
    public $prod_pd_nm;
    public $cast;
    public $shooting_dirctr;
    public $shooting_place;
    public $cpyrht_cn;
    public $use_grad;
    public $watgrad;
    public $telecine_ty_se;
    public $manage_no;
    public $video_frme;
    public $video_duration;
    public $cpyrht;
    public $lang_se;
    public $recrd_de;
    public $brdcst_time_hm;
    public $photo_id;
    public $photo_detail_id;
    public $sumry;
    public $audio_se;
    public $voice_ennc;
    public $photo_detail_se;
    public $photo_sn;
    public $event_nm;
    public $event_purps;
    public $relate_issue;
    public $atdrn_nm;
    public $event_place;
    public $prsn_choise_1;
    public $prsn_1;
    public $prsn_lc_1;
    public $prsn_2;
    public $prsn_choise_2;
    public $photo_cl;
    public $vido_jrnlst;
    public $film_no;
    public $hono;
    public $ehistry_id;
    public $clor;
    public $season;
    public $wethr;
    public $shooting_de;
    public $sbjslct_jrnlst;
    public $tape_mg;
    public $dirctr;
    public $prod_de;
    public $stndrd;
    public $instt;
    public $kogl_ty;
    public $portrait;
    public $othbc_at;
    public $ptogrfer;
    public $uci;
    public $cn_place;
    public $cn_incdnt;
    public $creat_instt;
    public $vido_pd_vj;
    public $hmpg_othbc_at;
    public $dta_occrrnc_se;
    public $photo_se;
    public $ehistry_thema_cl;
    public $cn;
    public $scenario;
    public $tape_hold_at;
    public $rtat_se;
    public $photo_occrrnc_se;
    public $desk_cp_nm;
    public $tape_knd;
    public $subtl;
    public $ehistry_origin;
    public $ehistry_video_no;
    public $clip_ordr;
    public $web_kwrd;
    public $web_scenario;
    public $regist_instt;
    
    public $reviv_posbl_at;    
    public $dwld_posbl_at;
}
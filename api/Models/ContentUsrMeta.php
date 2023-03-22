<?php
namespace Api\Models;

use Illuminate\Database\Eloquent\Model;

class ContentUsrMeta extends Model
{
    protected $table = 'bc_usrmeta_content';

    protected $primaryKey = 'usr_content_id';

    
    const CREATED_AT = null;
    const UPDATED_AT = null;
    
    protected $guarded = [];

    //public $sort = 'usr_content_id';

    public $sortable = ['usr_content_id'];

    protected $casts = [
        'usr_content_id' => 'integer'
    ];

    protected $fillable = [   
        'usr_content_id',
        'origin',
        'cpyrhtown',
        'incdnt_indict_at',
        'prsn_se_1',
        'prsn_se_2',
        'prsn_lc_2',
        'all_vido_at',
        'hmpg_cntnts_id',
        'dta_se',
        'brdcst_stle_se',
        'vido_ty_se',
        'prod_step_se',
        'shooting_orginl_atrb',
        'progrm_code',
        'progrm_nm',
        'tme_no',
        'brdcst_de',
        'kwrd',
        'media_id',
        'recptn_stle',
        'thema_cl',
        'matr_knd',
        'embg_at',
        'embg_relis_dt',
        'use_prhibt_at',
        'use_prhibt_cn',
        'prsrv_pd_et_resn',
        'memo',
        'authr',
        'potogrf_nation',
        'potogrf_cty',
        'dta_detail_id',
        'embg_resn',
        'prod_se',
        'outord_makr',
        'prod_pd_nm',
        'cast',
        'shooting_dirctr',
        'shooting_place',
        'cpyrht_cn',
        'use_grad',
        'watgrad',
        'telecine_ty_se',
        'manage_no',
        'video_frme',
        'video_duration',
        'cpyrht',
        'lang_se',
        'recrd_de',
        'brdcst_time_hm',
        'photo_id',
        'photo_detail_id',
        'sumry',
        'audio_se',
        'voice_ennc',
        'photo_detail_se',
        'photo_sn',
        'event_nm',
        'event_purps',
        'relate_issue',
        'atdrn_nm',
        'event_place',
        'prsn_choise_1',
        'prsn_1',
        'prsn_lc_1',
        'prsn_2',
        'prsn_choise_2',
        'photo_cl',
        'vido_jrnlst',
        'film_no',
        'hono',
        'ehistry_id',
        'clor',
        'season',
        'wethr',
        'shooting_de',
        'sbjslct_jrnlst',
        'tape_mg',
        'dirctr',
        'prod_de',
        'stndrd',
        'instt',
        'kogl_ty',
        'portrait',
        'othbc_at',
        'ptogrfer',
        'uci',
        'cn_place',
        'cn_incdnt',
        'creat_instt',
        'vido_pd_vj',
        'hmpg_othbc_at',
        'dta_occrrnc_se',
        'photo_se',
        'ehistry_thema_cl',
        'cn',
        'scenario',
        'tape_hold_at',
        'rtat_se',
        'photo_occrrnc_se',
        'desk_cp_nm',
        'tape_knd',
        'subtl',
        'ehistry_origin',
        'ehistry_video_no',
        'clip_ordr',
        'web_kwrd',
        'web_scenario',
        'regist_instt',
        'othbc_at',
        'reviv_posbl_at',
        'dwld_posbl_at',
        'cpyrht_at',
        'media_dwld_posbl_at'
    ];
}

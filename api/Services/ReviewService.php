<?php

namespace Api\Services;

use Api\Services\BaseService;
use Api\Models\Review;
use Api\Services\ContentService;
use Api\Models\ContentStatus;
use Api\Types\ReviewType;
use Api\Types\ContentReviewStatusType;
use Api\Types\ContentStatusType;
use Api\Types\ReviewStatus;
use Api\Models\Category;


use Api\Models\DataDicCodeSet;
use Api\Models\UserContent;
use Illuminate\Database\Capsule\Manager as DB;
use Api\Support\Helpers\CodeHelper;

class ReviewService extends BaseService
{
    /**
     * Review list
     *
     * @return void
     */
    public function list()
    {
        $query = Review::query();

        $reviews = $query->get();

        return  $reviews;
    }
    public function find($id)
    {
        $query = Review::query();


        return $query->find($id);
    }

    public function findByContentId($contentId, $reviewType)
    {
        $review = Review::where('content_id', $contentId)
            ->where('review_ty_se', $reviewType)
            ->orderBy('id', 'desc')
            ->first();


        return $review;
    }

    public function findOrFail($id)
    {
        $reviews = $this->find($id);
        if (!$reviews) {
            api_abort_404('Request');
        }
        return $reviews;
    }

    public function getByReviewList($input, $user)
    {
        $query = Review::query();
        $query->join('bc_usrmeta_content', 'reviews.content_id', '=', 'bc_usrmeta_content.usr_content_id');
        $query->join('bc_content', 'reviews.content_id', '=', 'bc_content.content_id');
        $query->join('bc_member', 'reviews.regist_user_id', '=', 'bc_member.user_id');
        $query->join('bc_sysmeta_movie','reviews.content_id','=','bc_sysmeta_movie.sys_content_id');
        $query->join('bc_content_status', 'reviews.content_id', '=', 'bc_content_status.content_id');
        
        $query->select(
            // 'reviews.title', 
            'bc_content_status.indvdlinfo_at',
            'bc_content_status.edit_count',
            'reviews.cn',
            'reviews.id',
            'reviews.updt_user_id',
            'reviews.review_user_id',
            'reviews.regist_user_id',
            'reviews.regist_dt',
            'reviews.updt_dt',
            'reviews.compt_dt',
            'reviews.content_id',
            'reviews.review_reqest_sttus',
            'reviews.review_ty_se',
            'reviews.reject_cn',
            'reviews.reject_dt',
            'reviews.title as review_title',
            // 'reviews.*',
            'bc_usrmeta_content.media_id',
            'bc_content.ud_content_id',
            'bc_content.category_id',
            'bc_content.category_full_path',
            'bc_content.title',
            'bc_content.is_deleted',
            'bc_content.reg_user_id',
            'bc_member.user_nm as regist_user_nm',
            'bc_usrmeta_content.regist_instt',
            'bc_usrmeta_content.progrm_nm',
            'bc_usrmeta_content.tme_no',
            'bc_usrmeta_content.brdcst_stle_se',
            'bc_usrmeta_content.matr_knd',
            'bc_usrmeta_content.prod_se',
            'bc_sysmeta_movie.sys_video_rt',
            DB::raw('(select ud_content_title from bc_ud_content where ud_content_id = bc_content.ud_content_id) as ud_content_title'),
            DB::raw('(select case bc_category.parent_id
                            when 203 then bc_category.category_title 
                            else null
                            end
                        from bc_category 
                        where category_id = bc_content.category_id) as miryfc_video'),
            DB::raw("CASE
                       WHEN bc_content.ud_content_id = 1 THEN 
                            CASE 
                                WHEN TRUNC(TO_DATE(TO_CHAR(TO_TIMESTAMP(reviews.regist_dt)+15)) - TO_DATE(SYSDATE)) > 0 THEN 
                                    TRUNC(TO_DATE(TO_CHAR(TO_TIMESTAMP(reviews.regist_dt)+15)) - TO_DATE(SYSDATE))
                            ELSE 0
                        END
                    ELSE NULL
                    END AS delete_count"),
            DB::raw("(SELECT count(*) FROM review_logs WHERE reviews.content_id = content_id AND reviews.review_ty_se = review_type AND action = 'reject') as reject_count_r")
            // DB::raw("(
            // SELECT * 
            // FROM 
            // (SELECT count(*) as reject_count_r FROM review_logs WHERE reviews.content_id = content_id AND reviews.review_ty_se = review_type AND action = 'reject')
            // )")
            // DB::raw("(SELECT reject_count_r 
            //         FROM (SELECT count(*) as reject_count_r FROM review_logs WHERE reviews.content_id = content_id AND reviews.review_ty_se = review_type AND action = 'reject')
            //         )")
            // DB::raw("(SELECT count(*) AS reject_count_r FROM review_logs WHERE content_id = 32882 AND review_type = 'content' AND action = 'reject')")
        );
        
        
        $this->includeUser($query, 'registerer');
        $this->includeUser($query, 'review_user_nm');

        // 삭제되지 않은 것만
        $query->where('bc_content.is_deleted', '=', 'N');

        switch ($input['type']) {
            case ReviewType::INGEST:
                $groupPermissions = $user->hasIngestGroup();
                break;
            case ReviewType::CONTENT:
                $groupPermissions = $user->hasContentGroup();
                break;
            default:
                $groupPermissions = false;
                break;
        };
        $isAdmin = $user->hasAdminGroup();
        
        if($input['type'] == ReviewType::CONTENT){
            if($isAdmin || $user->hasIngestGroup()){
                $query->addSelect('bc_member.phone as regist_user_phone'); 
            }else{
                $query->addSelect(DB::raw("null as regist_user_phone")); 
                
            }
        }
        
        // 심의 유형
        if (!($input['type'] == 'All')) {
            $query->where('review_ty_se', $input['type']);
        }

        // regist_user_id 등록자
        // 내 심의 체크로 true가 들어오면 세션 유저 아이디로 조회
        if ($input['myReview'] == 'true') {
            if ($groupPermissions) {
                $query->where(function ($q) use ($user) {
                    $q->where('regist_user_id', $user->user_id)
                        ->orWhere('review_user_id', $user->user_id);
                });
            } else {
                $query->where('regist_user_id', $user->user_id);
            }
        }


        //review_reqest_sttus 진행상태
        // 진행상태 전체가 아니면 ..
        if (!($input['status'] == 'all'))
            $query->where('review_reqest_sttus', $input['status']);


        $startDate = dateToStr($input['start_date'], 'YmdHis');
        $endDate = dateToStr($input['end_date'], 'Ymd') . '235959';
        
        
        // 홈으로->콘텐츠 관리: 개인정보검출 검색필터(콤보박스) 
        // 검출일때는 "Y"만, 미검출일때는  "NULL","N" 값 출력
        // indvdlinfo_combo가 'all'이 아니라면 
        if ($input['indvdlinfo_combo'] != 'all') {
            // 검출이라면
            if ($input['indvdlinfo_combo'] == 'Y'){
                $query->where('bc_content_status.indvdlinfo_at', $input['indvdlinfo_combo']);
            }else {
                // 그 외 검출이 아니면
                $query->where(function ($query) {
                    $query->whereNull('bc_content_status.indvdlinfo_at');
                    $query->orWhere('bc_content_status.indvdlinfo_at', $input['indvdlinfo_combo']);
                });
            }
        }

        if(!(is_null($input['start_date']) || $input['start_date'] == "") && !(is_null($input['end_date']) || $input['end_date'] == "")){
            $query->whereBetween('regist_dt', [$startDate, $endDate]);
        }
        
        // $reviews = $query->with('review_log')->get();
        // $reviews = $query->get();
        $query->with('usrMeta');
        // $query->with('sysMeta');
        if ($input['search_text'] !== '') {
            $searchText = trim($input['search_text']);
            switch ($input['search_combo']) {
                case 'title':
                    $query->where('bc_content.title', 'like', "%{$input['search_text']}%");
                    break;
                case 'media_id':
                    $query->where('media_id', 'like', "%{$input['search_text']}%");
                    break;
                case 'regist_user_id':
                    $query->where(function ($q) use ($input) {
                        $q->where('regist_user_id', 'like', "%{$input['search_text']}%")
                            ->orWhere('bc_member.user_nm', 'like', "%{$input['search_text']}%");
                    });
                    break;
                case 'progrm_nm':
                    $query->where('progrm_nm', 'like', "%{$input['search_text']}%");
                    break;
                case 'tme_no':
                    $query->where('tme_no', "{$input['search_text']}");
                    break;
            }
        }
        if($input['search_combo'] == 'miryfc_video'){
            if ($input['miryfc_video'] !== 'All') {
                $query->where('bc_usrmeta_content.regist_instt', '=', $input['miryfc_video']);
            }
            $query->where('bc_content.category_full_path', 'like', '/0/100/203/%');
        }
        
        if ($input['brdcst_stle_se'] !== 'All') {
            $query->where('bc_usrmeta_content.brdcst_stle_se', '=', $input['brdcst_stle_se']);
        }

        if ($input['matr_knd'] !== 'All') {
            $query->where('bc_usrmeta_content.matr_knd', '=', $input['matr_knd']);
        }

        if (!($input['content_type'] == 'All')) {
            $codeItem  = CodeHelper::findCodeItemBycodeSetCodeAndCodeItemCode('CNTNTS_TY', $input['content_type']);
            if ($codeItem) {
                $contentTypeNm = $codeItem->code_itm_nm ?? '';
                $contentId = UserContent::query()->where('ud_content_title', $contentTypeNm)->first();
                $query->where('ud_content_id', $contentId->ud_content_id);
            }
        }
        // 반려횟수 필터링
        if(isset($input['firstRejectCount']) && !is_null($input['firstRejectCount']) && !is_null($input['lastRejectCount'])){
            $whereRejectCountStr = "(SELECT count(*) FROM review_logs WHERE reviews.content_id = content_id AND reviews.review_ty_se = review_type AND action = 'reject') BETWEEN {$input['firstRejectCount']} AND {$input['lastRejectCount']}";
            $query->whereRaw($whereRejectCountStr);
        };


        $request = request();

        if (is_null($request->sort)) {
            $query->orderByDesc('regist_dt');
        }
        
        $reviews = paginate($query);

        foreach ($reviews as $review) {

            $status = $review->review_reqest_sttus;
            $review->status_count = $review->review_log()->where('action', $status)->count();
            $review->reject_count = $review->review_log()->where('action', 'reject')->count();
            // 방송형태구분
            $brdcstStleSeCodeItem = $review->brdcst_stle_se;
            $codeItem = CodeHelper::findCodeItemBycodeSetCodeAndCodeItemCode('BRDCST_STLE_SE', $brdcstStleSeCodeItem);
            $review->brdcst_stle_se_nm = $codeItem->code_itm_nm ?? '';

            // 등록기관
            $registInstt = $review->regist_instt;
            $insttCodeItem = CodeHelper::findCodeItemBycodeSetCodeAndCodeItemCode('INSTT', $registInstt);
            $review->regist_instt_nm =$insttCodeItem->code_itm_nm ?? '';

            // 등록기관
            $matrKnd = $review->matr_knd;
            $insttCodeItem = CodeHelper::findCodeItemBycodeSetCodeAndCodeItemCode('MATR_KND', $matrKnd);
            $review->matr_knd_nm =$insttCodeItem->code_itm_nm ?? '';

            $prodSeNm = '';
            if($review->prod_se != null){
                $prodSeCode = CodeHelper::findCodeItemBycodeSetCodeAndCodeItemCode('PROD_SE', $review->prod_se);
                $prodSeNm = $prodSeCode->code_itm_nm;
            }
            $review->prod_se_nm =$prodSeNm;
        }

        return $reviews;
    }

    /**
     * 심의 요청시 심의 생성
     *
     * @param title,content_id,user_id,user_nm,dc Object $reviewData
     * @param \Api\Models\User $user 사용자 객체
     * @return void
     */
    public function create($reviewData,  $user)
    {

        $review = $this->findByContentId($reviewData->content_id, $reviewData->review_ty_se);
        if (empty($review)) {
            $review = new Review();
        }
        $review->title = $reviewData->title;
        $review->content_id = $reviewData->content_id;
        $review->cn = $reviewData->cn;
        // $review->updt_user_id;
        $review->review_user_id = $reviewData->user_id;
        // $review->review_user_id = $reviewData->user_nm;
        $review->regist_user_id = $user->user_id;
        // $review->compt_dt;
        $review->review_ty_se = $reviewData->review_ty_se;

        $review->save();

        return $review;
    }
    /**
     * ord_status update
     *
     * @param String $ordId 의뢰 아이디
     * @param String $changeStatus 바꿀 진행 상태
     * @return void
     */
    public function updateStatus($id, $data, $user)
    {
        
        $reviews = $this->findOrFail($id);
        
        $reviews->review_ty_se == 'content' ? $isContent = true : $isContent = false;

        $reviews->review_reqest_sttus = $data['change_status'];
        
        switch ($data['change_status']) {
            case ReviewStatus::APPROVAL:
                $reviews->compt_dt = dateToStr('now', 'YmdHis');
                $reviews->review_user_id = $user->user_id;
                break;
            case ReviewStatus::REJECT:
                $reviews->reject_dt = dateToStr('now', 'YmdHis');
                $reviews->review_user_id = $user->user_id;
                break;
            case ReviewStatus::REQUEST:
                $reviews->reject_dt = null;
                $reviews->compt_dt = null;
                $reviews->review_user_id = null;
                break;
        }
        $reviews->save();


        return $reviews;
    }

    /**
     * 의뢰 등록자 배정
     *
     * @param String $ordId 의뢰 아이디
     * @param String $updateCharger 배정할 등록자
     * @return void
     */
    public function updateCharger($id, $data)
    {

        $request = $this->findOrFail($id);


        $request->review_user_id = $data['change_status'];
        $request->save();

        return $request;
    }
    /**
     * 반려 내용 추가
     *
     * @param String $ordId 의뢰 아이디
     * @param String $updateCharger 배정할 등록자
     * @return void
     */
    public function updateReviewCn($id, $data, $userId)
    {

        $request = $this->findOrFail($id);

        $request->reject_cn = $data['reject_cn'];
        $request->review_user_id = $userId;
        $request->save();

        return $request;
    }
    public function reviewCnNull($id){
        $request = $this->findOrFail($id);

        $request->reject_cn = null;
        $request->save();
        return $request;
    }

    /**
     * 콘텐츠 등록 자동 승인 처리
     *
     * @param [type] $reviewData
     * @param [type] $user
     * @return void
     */
    public function createAndComplete($reviewData,  $user)
    {
        $review = $this->findByContentId($reviewData->content_id, $reviewData->review_ty_se);
        if (empty($review)) {
            $review = new Review();
        }

        $review->review_ty_se = $reviewData->review_ty_se;

        $review->title = $reviewData->title;
        $review->content_id = $reviewData->content_id;
        $review->cn = $reviewData->cn;
        $review->regist_user_id = $user->user_id;

        $review->review_user_id = $reviewData->review_user_id;
        $review->reject_cn = $reviewData->reject_cn;
        $review->review_reqest_sttus =  \Api\Types\ReviewStatus::APPROVAL;
        $review->compt_dt = dateToStr('now', 'YmdHis');

        $review->save();

        return $review;
    }
}

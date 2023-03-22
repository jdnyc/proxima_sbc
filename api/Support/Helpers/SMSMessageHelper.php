<?php

namespace Api\Support\Helpers;

use Api\Types\MemberStatus;
use Api\Types\ReviewStatus;
use Api\Types\FolderMngRequestStatus;

/**
 * 앱 내의 문자 메시지 헬퍼 클래스
 */
class SMSMessageHelper
{
    /**
     * 사용자 등록 요청 시 메세지 생성
     *
     * @param \Api\Models\MemberRequest $memberRequest 사용자 등록 요청
     */
    public static function makeMsgMemberRegRequested($memberRequest)
    {
        $msg = "[KTV CMS] 사용자 계정 신청이 등록되었습니다.\n" .
            " - 신청자 : {$memberRequest->user_nm}({$memberRequest->user_id})";
        return $msg;
    }

    /**
     * 담당자 승인상태 변경 시 메세지 생성
     *
     * @param \Api\Models\MemberRequest $memberRequest 사용자 등록 요청
     */
    public static function makeMsgMemberRegPdStatusChanged($memberRequest)
    {
        // 승인 아니면 반려겠지...
        $charger = $memberRequest->charger;
        $statusStr = $memberRequest->pd_status === MemberStatus::APPROVAL ? '승인' : '반려';
        $msg = "[KTV CMS] 사용자 계정 신청이 담당자에 의해 {$statusStr} 되었습니다.\n" .
            "관리자 승인 바랍니다.\n" .
            " - 신청자 : {$memberRequest->user_nm}({$memberRequest->user_id})\n" .
            " - 승인자 : {$charger->user_nm}";
        return $msg;
    }

    /**
     * 관리자 등록상태 변경 시 메세지 생성
     *
     * @param \Api\Models\MemberRequest $memberRequest 사용자 등록 요청
     */
    public static function makeMsgMemberRegAdminStatusChanged($memberRequest)
    {
        // 승인 아니면 반려겠지...
        $msg = '';
        if ($memberRequest->status === MemberStatus::APPROVAL) {
            $msg = "[KTV CMS] 사용자 계정이 등록 되었습니다.\n" .
                " - 사용자명 : {$memberRequest->user_nm}\n" .
                " - 사용자아이디 : {$memberRequest->user_id}";
        } else {
            $msg = "[KTV CMS]사용자 계정 신청이 반려 되었습니다.\n" .
                " - 신청자 : {$memberRequest->user_nm}\n" .
                " - 사용자아이디 : {$memberRequest->user_id}";
        }

        return $msg;
    }

    /**
     * 제작폴더 신청 메시지 
     *
     * @param $type 
     */
    public static function makeMsgCreateFolderRequest($request,$requester)
    {
        // 승인  반려
        $msg = '';
        if ($request->status === FolderMngRequestStatus::REQUEST) {
            //요청
            $msg = "[KTV CMS] 제작폴더 신청이 요청 되었습니다.\n" .
            " - 프로그램명 : {$request->folder_path_nm}\n".
            " - 사유 : {$request->dc}\n".
            " - 신청자 : {$requester->user_nm}\n";
        }else if ($request->status === FolderMngRequestStatus::APPROVAL) {
            $msg = "[KTV CMS] 제작폴더 신청이 승인 되었습니다.\n" .
            " - 프로그램명 : {$request->folder_path_nm}\n";
        } else if( $request->status === FolderMngRequestStatus::REJECT ){
            $msg = "[KTV CMS] 제작폴더 신청이 반려 되었습니다.\n" .
            " - 프로그램명 : {$request->folder_path_nm}\n" ;          
        }

        return $msg;
    }
   
    /**
     * 콘텐츠 등록 상태변경시[반려] 메세지 생성
     *
     * @param \Api\Models\Review $review 콘텐츠 등록
     * @param \Api\Models\Content $content 콘텐츠
     * @return void
     */
    public static function makeMsgReviewStatusChangeReject($review,$content,$reviewUser,$rejectCount)
    {
        $registDt = $review->regist_dt;
        $reviewStatus = $review->review_reqest_sttus;
        $reviewUserId = $review->review_user_id;
        $reviewUserPhone = $reviewUser->phone;
        if($reviewStatus === ReviewStatus::REJECT){
            $msg = "[KTV CMS] 콘텐츠 등록이 반려되었습니다.\n" .
            "내용 수정 후 '재 등록 요청' 해주세요.\n" .
            " - 콘텐츠 제목 : {$content->title}\n" .
            " - 반려횟수 : {$rejectCount}회\n" .
            " - 등록일 : {$registDt->format('Y-m-d H:i:s')}\n" .
            " - 심의자 : {$reviewUserId}\n" .
            " - 심의자 연락처: {$reviewUserPhone}";
        }
        return $msg;
    }

    /**
     * 작업 오류 메시지 생성
     *
     * @param string $log
     * @param \Api\Models\Content $content 콘텐츠
     * @return void
     */
    public static function makeMsgTaskError($log,$content)
    {
        $msg = "[KTV CMS] 콘텐츠 등록중 오류 발생되었습니다.\n" .
            " - 콘텐츠 제목 : {$content->title}\n" .
            " - 미디어ID : {$content->media_id}\n".
            " - 오류내용 : {$log}\n" ;
        return $msg;
    }
        /**
     * 외부 사용자 로그인시 인증번호 메세지
     *
     * @return void
     */
    public static function makeMsgAuthNumber($authNum){
        $msg = "[KTV CMS] 인증번호를 입력해주세요.\n" .
        " - 인증번호 : {$authNum}\n";
        return $msg;
    }

    
    /**
     * 작업 오류 메시지 생성
     *
     * @param string $type
     * @param string $path
     * @return $msg
     */
    public static function makeMsgPortalError($type,$path)
    {
        $msg = "[KTV CMS] 포털전송중 오류 발생되었습니다.\n" .
            " - 포털전송유형 : {$type}\n" .
            " - 정보 : {$path}\n" ;
        return $msg;
    }

     /**
     * 작업 라우드니스 측정 메시지 생성
     *
     * @param string $log
     * @param \Api\Models\Content $content 콘텐츠
     * @return void
     */
    public static function makeMsgTaskLoudness($log,$content)
    {
        $msg = "[KTV CMS] 콘텐츠 등록 라우드니스 측정 범위 초과\n" .
            " - 콘텐츠 제목 : {$content->title}\n" .
            " - 미디어ID : {$content->media_id}\n".
            " - 내용 : {$log}\n" ;
        return $msg;
    }

    /**
     * 영상 프레임레이트 오류 알림 메시지 생성
     *
     * @param [type] $log
     * @param [type] $content
     * @return void
     */
    public static function makeMsgFrameRate($log,$content){
        $msg = "[KTV CMS] 콘텐츠 등록 영상 프레임레이트 오류 \n" .
        " - 콘텐츠 제목 : {$content->title}\n" .
        " - 미디어ID : {$content->media_id}\n".
        " - 내용 : {$log}\n" ;
        return $msg;
    }
}

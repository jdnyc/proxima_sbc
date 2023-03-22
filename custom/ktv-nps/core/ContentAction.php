<?php
/**
 * 콘텐츠 목록(Ariel.NPS.Media.php)에 Custom 동작을 주입하기 위한 파일
 */
namespace ProximaCustom\core;
use ProximaCustom\core\ViewCustom;

require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

/**
 * 콘텐츠 액션 커스터마이징 클래스
 */

class ContentAction
{
    /**
     * showType ud_content_id 값
     * 1:원본
     * 2:클린본
     * 3:마스터본
     * 4:오디오
     * 5:이미지
     * 7:클립본
     * 8:CG
     * 9:뉴스편집본
     */
    // '리스토어 요청',
    // '파일삭제',
    // '삭제',
    // '아카이브 삭제 요청',
    // '다운로드',
    // '영상변환',
    // 'SNS 게시',
    // '아카이브 요청',
    // '심의 요청',
    // '주조전송',
    // '부조전송',
    // '삭제기간 연장',
    // '사용금지',
    // '엑셀파일 다운'

    public static $actionItems = [
        [
            'label'=> '리스토어 요청',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.restoreRequest();',
            'actionItems' => [],
            'itemId' => 'restoreRequest'
            // 'showType' => [1,2,3,7,9]
        ],
        [
            'label'=> '파일삭제 요청',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.deleteFile("delete");',
            'actionItems' => [],
            'itemId' => 'deleteFileRequest'
            // 'showType' => [1,2,3,7,9]
        ],
        [
            'label'=> '파일삭제',
            'grant' => 4,
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.deleteFile("forceDelete");',
            'actionItems' => [],
            'itemId' => 'deleteFile'
            // 'showType' => [1,2,3,7,9]
        ],
        [
            'label'=> '아카이브 삭제 요청',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.archiveDeleteRequest();',
            'actionItems' => [],
            'itemId' => 'archiveDeleteRequest'
            // 'showType' => [1,2,3,7,9]
        ],
        [
            'label'=> '다운로드',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.download();',
            'actionItems' => [],
            'itemId' => 'download',
            'showType' => [1,2,3,4,5,7,8,9]
        ],
        [
            'label'=> '영상변환',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'itemId'=>'ConversionVideo',
            'actionItems' => [],
            'showType' => [1,2,3,7,9],
            'menu' => [    
                [
                    'label'=> '저해상도(H.264 640 x 360 1M)',
                    'handler' => 'Ext.ContentActions.ConversionVideo("360");'
                ],
                [
                    'label'=> '고해상도(H.264 1920x1080 2M)',
                    'handler' => 'Ext.ContentActions.ConversionVideo("2m1080");'
                ],
                [
                    'label'=> '전송(H.264 1920x1080 15M)',
                    'handler' => 'Ext.ContentActions.ConversionVideo("15m1080");'
                ]
            ]

        ],
        [
            'label'=> '수동변환',
            'grant' => 2048,
            'menuItem' => true,  
            'button' => false, 
            'itemId'=>'CustomConversionVideo',
            'actionItems' => [],
            'showType' => [1,2,3,7,9],
            'menu' => []

        ],
        [
            'label'=> '콘텐츠 유형 변경',
            'menuItem' => true,  
            'button' => false,
            'itemId'=>'updateContentType',
            'handler' => 'Ext.ContentActions.updateContentType();',
        ],
        [
            'label'=> 'Mplayout 전송',
            'grant' => 4096,
            'menuItem' => true,  
            'button' => false, 
            'itemId'=>'sendToMplayout',
            'actionItems' => [],
            'showType' => [1,2,3,7,9],
            'handler' => 'Ext.ContentActions.sendToMplayout("2m1080");'
        ],
        [
            'label'=> 'SNS 게시',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.publishSns();',
            'actionItems' => [],
            'itemId' => 'publishSns'
            // 'showType' => [3,7,9]
        ],
        [
            'label'=> '아카이브 요청',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.archiveRequest();',
            'itemId' => 'archiveRequest',
            'actionItems' => [],
            // 'showType' => [1]
        ],
        [
            'label'=> '주조전송',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'transferContent("transfer_to_maincontrol");',
            'actionItems' => [],
            'itemId' => 'transfer_to_maincontrol',
            // 'showType' => [3]
        ],[
            'label'=> '부조전송',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'actionItems' => [],
            'itemId' => 'transmission_zodiac',
            'menu' => [              
                [
                    'label'=> '뉴스부조 전송',
                    'handler' => 'transferContent("transmission_zodiac_news");',
                ],[
                    'label'=> 'A/B부조 전송',
                    'handler' => 'transferContent("transmission_zodiac_ab");',
                ]
            ]
            // 'showType' => [3,9]
        ],
        [
            'label'=> '삭제기간 연장',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.extendedUse();',
            'itemId' => 'extendedUse',
            'actionItems' => [],
            // 'showType' => [1,2,3,7,9]
        ],
        [
            'label'=> '사용금지',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.prohibitedUse();',
            'itemId' => 'prohibitedUse',
            'actionItems' => [],
            'showType' => [1,2,3,4,5,7,8,9]
        ],
        [
            'label'=> '엑셀파일 다운',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false,
            'handler' => 'Ext.ContentActions.downloadExcel();',
            'actionItems' => [],
            'itemId' => 'downloadExcel',
            'showType' => [1,2,3,4,5,7,8,9]
        ],
        // [
        //     'label'=> 'SNS 게시',
        //     'grant' => '1',
        //     'menuItem' => true,  
        //     'button' => false, 
        //     'icon' => '/led-icons/drive_go.png',
        //     'handler' => 'Ext.ContentActions.noticeSns();',
        //     'actionItems' => [],
        //     'showType' => [3,7]
        // ],

        [
            'label'=> '라우드니스 측정',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false, 
            'hidden' => true,
            'handler' => 'Ext.ContentActions.loudnessMeasure();',
            'actionItems' => [],
            'showType' => [1,2,3,7,9]
        ],
        [
            'label'=> '라우드니스 조회',
            'grant' => '1',
            'menuItem' => true,  
            'button' => false, 
            'hidden' => 'true',
            'handler' => 'Ext.ContentActions.loudnessViewer();',
            'actionItems' => [],
        ],
        [
            'label'=> '관리자 메뉴',
            'grant' => 1024,
            'menuItem' => true,  
            'button' => false,
            'actionItems' => [],
            'menu' => [              
                [
                    'label'=> '섬네일 재생성',
                    'handler' => 'Ext.ContentActions.adminMenu("create_thumb");'
                ],[
                  'label'=> '중해상도, 섬네일, 카달로깅 재생성',
                  'handler' => 'Ext.ContentActions.adminMenu("create_proxy");'
              ]
            //   ,[
            //       'label'=> '리스토어 (변환X)',
            //       'icon' => '/led-icons/drive_go.png',
            //       'handler' => 'Ext.ContentActions.adminMenu("file_restore");'
            //   ],[
            //       'label'=> '원본(메인) 수동 삭제',
            //       'icon' => '/led-icons/drive_go.png',
            //       'handler' => 'Ext.ContentActions.adminMenu("delete_media_original");'
            //   ],[
            //       'label'=> '원본(아카이브) 수동 삭제',
            //       'icon' => '/led-icons/drive_go.png',
            //       'handler' => 'Ext.ContentActions.adminMenu("delete_media_archive");'
            //   ]
            ]
        ],
        // ,
        // [
        //     'label' => 'XDCAM 전송',
        //     'grant' => GRANT_CJO_XDCAM_TRANSFER,
        //     'menuItem' => true,
        //     'button' => false,
        //     'icon' => '/led-icons/xdcam.png',
        //     'handler' => 'transferXDCAM();',
        //     'actionItems' => []
        // ],
        // [
        //     'label' => '콘텐츠 유형 변경',
        //     'grant' => GRANT_CJO_CHANGE_UD_CONTENT,
        //     'menuItem' => true,
        //     'button' => false,
        //     'icon' => '/led-icons/page_refresh.png',
        //     'handler' => 'changeUserContentType();',
        //     'actionItems' => []
        // ],
        // [
        //     'label' => '콘텐츠 상태 초기화(등록대기)',
        //     'grant' => GRANT_CJO_CHANGE_UD_CONTENT,
        //     'menuItem' => true,
        //     'button' => false,
        //     'icon' => '/led-icons/arrow_undo.png',
        //     'handler' => 'changeContentStatus();',
        //     'actionItems' => []
        // ]
         [
             'label'=> '콘텐츠 숨김',
             'menuItem' => true,  
             'button' => false, 
             'itemId' => 'contentHidden',
             'listeners' => '{
                 showmenu:Ext.ContentEvents.contentHidden
             }'
            ]
    ];

    /**
     * 콘텐츠 우클릭 메뉴 항목 추가
     *
     * @param mixed $userId 사용자 아이디
     * @param mixed $udContentId 사용자 정의 콘텐츠 아이디
     * @return void
     */
    public static function renderContextMenuItems($userId, $udContentId)
    {        
        foreach(self::$actionItems as $actionItem) {
            if(!$actionItem['menuItem'])
                continue;          

            //우클릭 권한이 없으면 항목을 만들지 않는다.
            if (!checkAllowUdContentGrant($userId, $udContentId, (int)$actionItem['grant'])) {
                continue;
            }

            if( !isset($actionItem['hidden']) ){
                $hidden = $actionItem['hidden'];
            }else{
                $hidden = 'false';
            }

            if(!is_null($actionItem['listeners'])){
                $listeners = $actionItem['listeners'];
            }else{
                $listeners = 'new Object()';
            }

            if($actionItem['itemId'] == 'CustomConversionVideo' ){
                $codes = getCodeInfo('CUSTOM_TR_CODE');
                $menu = [];
                if( !empty($codes) ){
                    foreach($codes as $profileCode)
                    {
                        array_push($menu, [
                            'label'=> $profileCode['name'],
                            'handler' => 'Ext.ContentActions.ConversionVideo("'.$profileCode['code'].'");'
                        ]);
                    }
                }
                $actionItem['menu'] = $menu ;
            }
            

            
            // $showTypeCheck = false;
            // if($actionItem['showType'] != null){
            //     foreach($actionItem['showType'] as $showType){
                    
            //         if(($udContentId == $showType)){
            //             $showTypeCheck = true;
            //         };
            //     };
                
                
            //     if($showTypeCheck){
            //         echo ", {
            //             hidden: '{$hidden}',
            //             text: '{$actionItem['label']}',
            //             icon: '{$actionItem['icon']}',
            //             handler: function () {
            //                 {$actionItem['handler']}
            //             }
            //         }";
            //     }
            // }else{
                
                if( !empty($actionItem['menu']) ){
                    $menuList = [];
                    foreach($actionItem['menu'] as $menu)
                    {
                        $menuList [] = "{
                            text: '{$menu['label']}',
                            icon: '{$menu['icon']}',
                            handler: function () {
                                {$menu['handler']}
                            },
                            listeners:{$listeners}
                        }";
                    }
                    echo ", {
                        hidden: '{$hidden}',
                        text: '{$actionItem['label']}',
                        icon: '{$actionItem['icon']}',
                        itemId: '{$actionItem['itemId']}',
                        menu: [".join(',', $menuList)."],
                        listeners:{$listeners}
                    }";
                }else{
                    echo ", {
                    hidden: '{$hidden}',
                    text: '{$actionItem['label']}',
                    icon: '{$actionItem['icon']}',
                    itemId: '{$actionItem['itemId']}',
                    handler: function () {
                        var userId = '{$userId}';
                        {$actionItem['handler']}
                    },
                    listeners:{$listeners}
                }";
                }
            // };
   
        }     
    }

    /**
     * 다운로드가 가능한 권한인지 확인
     *
     * @param string $ip
     * @return void
     */
    public static function canDownloadIp($ip){
        
        if(preg_match('/192.168.41/', $ip) || preg_match('/192.168.42/', $ip)) return true;
            
        return false;
    }

     /**
     * 커스텀 함수를 콘텐츠 검색에 추가
     *
     * @return void
     */
    public static function renderCustomFunctions()
    {
        global $db;

        // $customRootWebpath = CUSTOM_ROOT_WEBPATH;   
        
        // $userContents = \Proxima\models\content\UserContent::all();

        // $reviewRequiredUserContentCodes = [
        //     'preprod', 'clean', 'pgm', 'vod'
        // ];        

        // $reviewRequiredUserContentIds = [];
        // $reviewRequiredUserContentNames = [];

        // $vcrListWritableUserContentCodes = [
        //     'preprod', 'clean', 'vod'
        // ];

        // $vcrListWritableUserContentIds = [];
        // $vcrListWritableUserContentNames = [];

        // foreach($userContents as $userContent) {
        //     // 사전제작, 클린, 지난방송, VOD 콘텐츠만 심의 요청 가능
        //     if( in_array( strtolower($userContent->get('ud_content_code')), $reviewRequiredUserContentCodes ) ) {
        //         $reviewRequiredUserContentIds[] = $userContent->get('ud_content_id');
        //         $reviewRequiredUserContentNames[] = $userContent->get('ud_content_title');
        //     }

        //     // 사전제작, 클린, VOD 콘텐츠만 VCR리스트 작성 가능
        //     if( in_array( strtolower($userContent->get('ud_content_code')), $vcrListWritableUserContentCodes ) ) {
        //         $vcrListWritableUserContentIds[] = $userContent->get('ud_content_id');
        //         $vcrListWritableUserContentNames[] = $userContent->get('ud_content_title');
        //     }
        // }

        // $canHighresDownload = 'false';
        // // 다운로드 권한 체크
        // if ( allowVisible(array(ADMIN_GROUP, NPS_GROUP, CHANNEL_GROUP, PD_GROUP, PREPROD_PD_GROUP, STUDIO_CONTROL_GROUP)) 
        //     || self::canDownloadIp($_SERVER['REMOTE_ADDR']) )
        // {
        //     $canHighresDownload = 'true';
        // }

        // echo "var canHighresDownload = {$canHighresDownload};";

        // $contentStatuses = [
        //     'complete' => CONTENT_STATUS_COMPLETE,
        //     'approved' => CONTENT_STATUS_REVIEW_ACCEPT
        // ];

        // // 심의 요청
        // echo "var reviewRequiredContentsIds = [".implode(', ', $reviewRequiredUserContentIds)."];\n";
        // echo "var reviewRequiredContentsNames = ['".implode('\', \'', $reviewRequiredUserContentNames)."'];\n";

        // // VCR리스트 작성 가능 콘텐츠
        // echo "var vcrListWritableUserContentIds = [".implode(', ', $vcrListWritableUserContentIds)."];\n";
        // echo "var vcrListWritableUserContentNames = ['".implode('\', \'', $vcrListWritableUserContentNames)."'];\n";

        // echo "var userId = '{$_SESSION['user']['user_id']}';";

        // // 강제 전송 권한 체크
        // if ( allowVisible(array(SUPER_TRANSFER_GROUP)) ) {            
        //     echo "var isChannelAdmin = true;";
        // } else {
        //     echo "var isChannelAdmin = false;";
        // }

        // echo "function requestReview() {

        //     var records = getSelectedContents();

        //     var contents = [];

        //     for(var i=0; i<records.length; i++) {
        //         var record = records[i];
        //         var contentType = parseInt(record.get('ud_content_id'));
                
        //         //수정일 : 2015.04.24
        //         //작성자  :김형기
        //         //내용 : VOD콘텐츠를 심의 가능하도록 수정
        //         if(reviewRequiredContentsIds.indexOf(contentType) < 0)
        //         {
        //             Ext.Msg.alert('확인', reviewRequiredContentsNames.join(',') + ' 콘텐츠만 심의요청 할 수 있습니다.');
        //             return;
        //         }
        
        //         var state = record.get('status');                
        //         if ( state != {$contentStatuses['complete']} )
        //         {
        //             Ext.Msg.alert('확인', '심의비대상(등록완료) 콘텐츠만 심의 요청이 가능합니다.');                    
        //             return;
        //         }

        //         contents.push({
        //             content_id: record.get('content_id'),
        //             title: record.get('title')
        //         });
        //     }

        //     // 기본 수신자를 정보을 가져오기 위한 값. 첫 배열에서 가져옴.(변경 필요)
        //     var bd_str_dtm = records[0].get('usr_broad_datetime'),
        //         pgm_cd = records[0].get('usr_pgm_code');
        
        //     function openReviewRequest(btnId){
        //         Ext.Ajax.request({
        //             url: '{$customRootWebpath}/javascript/ext.ux/review/review-request-window.js',
        //             method: 'GET',
        //             callback: function(opts, success, response){
        //                 if(success)
        //                 {                            
        //                     renderReviewRequestWindow(response, btnId);
        //                 }
        //                 else
        //                 {
        //                     Ext.Msg.alert('서버 오류', response.statusText);
        //                 }
        //             }
        //         });
        //     }

        //     function renderReviewRequestWindow(response, btnId) {
        //         try
        //         {
        //             var w = null;
        //             if (btnId != 'ok')
        //             {
        //                 w = Ext.Msg.wait('심의 요청 위한 기본 수신자 정보를 불러오는 중 입니다...');
        //             }
        //             var winFormReviewRequest = Ext.decode(response.responseText);

        //             winFormReviewRequest.contents = contents;    
                    
        //             if( !Ext.isEmpty(pgm_cd) && !Ext.isEmpty(bd_str_dtm) ) {
        //                 Ext.Ajax.request({
        //                     url: '{$customRootWebpath}/store/review/get_default_receiver_list.php', 
        //                     method: 'GET',
        //                     params: {
        //                         bd_str_dtm: bd_str_dtm,
        //                         pgm_cd: pgm_cd
        //                     },
        //                     callback: function(opts, success, response){
        //                         if(success)
        //                         {
        //                             try
        //                             {
        //                                 var rtn = Ext.decode(response.responseText);
        //                                 if (rtn.success)
        //                                 {
        //                                     var receiverStore = winFormReviewRequest.getReceiverStore();
                                            
        //                                     var data = rtn.data;
        //                                     for(var i=0; i<data.length; i++) {                                                  
                                                
        //                                         var tmp = new receiverStore.recordType({
        //                                             empno: data[i].empno,
        //                                             kor_nm: data[i].kor_nm,
        //                                             email: data[i].email,
        //                                             dept_nm: data[i].dept_nm,
        //                                             text: data[i].text
        //                                         });

        //                                         receiverStore.add(tmp);
        //                                     }

        //                                     if (w) w.hide();
        //                                     winFormReviewRequest.show();
        //                                 }
        //                                 else
        //                                 {
        //                                     Ext.Msg.alert('확인', rtn.msg);
        //                                 }
        //                             }
        //                             catch(e)
        //                             {
        //                                 Ext.Msg.alert(e['name'], e['message']);
        //                             }
        //                         }
        //                         else
        //                         {
        //                             Ext.Msg.alert('서버 오류', response.statusText);
        //                         }
        //                     }
        //                 });
        //             } else {

        //                 winFormReviewRequest.show();

        //             }
        //         }
        //         catch(e)
        //         {
        //             Ext.Msg.alert(e['name'], e['message']);
        //         }
        //     }        
            
        //     if ( Ext.isEmpty(bd_str_dtm) || Ext.isEmpty(pgm_cd) )
        //     {
        //         Ext.Msg.show({
        //             title: '확인', 
        //             msg: '기본 수신자를 가져오기 위한 정보가 없습니다.<br />기본 수신자 없이 진행됩니다.', 
        //             buttons: Ext.Msg.OK,
        //             fn: openReviewRequest
        //         });
        //         return;
        //     }
        
        //     openReviewRequest();

        // }\n";

        //심의하기
        echo "
            function doReview() {
                var sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel();
                var rs = [];
                var _rs = sm.getSelections();
                Ext.each(_rs, function(r, i, a){
                    rs.push(r.get('content_id'));
                });
                Ext.Ajax.request({
                    url: '/custom/cjos/javascript/ext.ux/Ariel.BatchReviewMetaWindow.php',
                    params: {
                        content_ids: Ext.encode(rs)
                    },
                    callback: function(option,success,response){
                        if(success){
                            var result = Ext.decode(response.responseText);
                        }
                        else{
                            Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
                        }
                    }
                });
            }
        ";

        // 다운로드
        echo "var downloadCount = 0;\n
            function downloadAllHttp(downloadUrls) {
                if(downloadUrls.length <= 0)
                    return;
                if(downloadCount >= downloadUrls.length)
                    return;
                var urlArr = downloadUrls[downloadCount].split('?');	
                var param = {filePath: urlArr[0], fileName: urlArr[1]};
            
                var	hiddenIFrameId = 'fileDownloadFrame',
                iframe = document.getElementById(hiddenIFrameId);
            
                if (iframe === null) {
                    iframe = document.createElement(\"iframe\");
                    iframe.name = hiddenIFrameId;
                    iframe.id = hiddenIFrameId;
                    iframe.style.display = 'none';
                    document.body.appendChild(iframe);
                }
                iframe.src = 'http://10.26.101.21:8080/custom/cjos/store/download_file.php?param='+encodeURIComponent(Ext.encode(param));
            
                downloadCount++;
                setTimeout(function(){
                    downloadAllHttp(downloadUrls);
                }, 1000);
            }\n";

        echo "function broadDataExists(records) {
            var isExists = false;    
            for(var i = 0; i<records.length; i++) {
                var record = records[i];
                var contentType = parseInt(record.get('ud_content_id'));
                if(reviewRequiredContentsIds.indexOf(contentType) >= 0)
                {
                    isExists = true;
                    break;
                }
            }                    
            return isExists;
        }\n";

        echo "function movDataExists(records)
        {
            var isExists = false;
            for(var i=0; i<records.length; i++) {
                var bs_content_id = records[i].get('bs_content_id');
                if(bs_content_id == 506) {
                    isExists = true;
                    break;
                }
            }            
            return isExists;
        }\n";

        echo "function doDownload( records, isWorkpanelClear ) {
            //방송영상이 아니면 사유를 묻지 않는다.
            var workpanelClear = false;
            if(isWorkpanelClear != undefined) {
                workpanelClear = isWorkpanelClear;
            }

            var contentIds = [];
            for(var i=0; i<records.length; i++) {
                var record = records[i];
                contentIds.push(record.get('content_id'));
            }
        
            //다운로드 폼
            var downloadForm = new Ext.form.FormPanel({
                frame: true,
                //width: 280,
                autoWidth: true,
                labelWidth: 280,
                defaults: {
                    width: 280
                },
                items: [			
                    new Ext.form.Label({
                        html: '다운로드 할 영상을 선택해 주세요.<br/><b><font color=\"red\">저장영상(720p)은 HD 지난방송에만 존재합니다.</font></b>'
                    })]
            });
        
            var conTypeOriginal = 'original';
            var conTypeProxy = 'proxy';
            var conTypeArchive = 'archive';
        
            //다운로드 윈도우
            var downloadWindow = new Ext.Window({
                id: 'download-window',
                title: '콘텐츠 다운로드',
                layout: 'fit',
                height: 130,
                width: 350,
                modal: true,
                resizable: false,
                items: [downloadForm],
                buttons: [{
                    text: '고해상도',
                    handler: function(b, e){
                        downloadContent(contentIds, conTypeOriginal, workpanelClear, '고해상도파일다운로드');
                    }
                },{
                    text: '저해상도',
                    handler: function(b, e){
                        //방송영상이면 사유를 입력해야 함.
                        if( broadDataExists(records) )
                        {
                            createCauseWindow(contentIds, conTypeProxy, workpanelClear).show();
                            b.ownerCt.ownerCt.close();
                        }				
                        else
                        {					
                            downloadContent(contentIds, conTypeProxy, workpanelClear, '');
                        }
                    }
                },{
                    text: '저장영상(720p)',
                    handler: function(b, e){				
                        //지난방송에 해당되므로 무조건 사유 입력해야 함.
                        createCauseWindow(contentIds, conTypeArchive, workpanelClear).show();
                        b.ownerCt.ownerCt.close();
                    }
                },{
                    text: '취소',
                    handler: function(b, e){
                        b.ownerCt.ownerCt.close();
                    }
                }]
            });        	
       
            //관리자, cg, 채널운영자, nps, 심의, pd, 부조 권한자는 고해상도까지 다운로드 가능하고
            //그 이외에는 저해상도만 다운로드 가능해야 한다.
            if(movDataExists(records) && !canHighresDownload)
            {
                downloadWindow.buttons.splice(0,1);
            }
            downloadWindow.show();
        }\n";        

        echo "function createCauseWindow(ids, type, isWorkpanelClear){
            var causeWin = new Ext.Window({
                modal: true,
                width: 300,
                height: 150,
                title: '다운로드 사유',
                border: false,
                layout: 'border',
        
                items: [{
                    region: 'center',
                    xtype: 'textarea',
                    frame: true,
                    allowBlank: false,
                    name: 'report_download',
                    emptyText: '다운로드 사유를 적어주세요.'
                }],
        
                listeners: {
                    show: function(self){
                        self.get(0).focus(false, 500);
                    }
                },
        
                buttons: [{
                    text: '다운로드',
                    handler: function(b, e){
                        var	s = b.ownerCt.ownerCt.get(0);
        
                        if (!s.isValid()) {
                            Ext.Msg.alert('확인', '다운로드 사유를 입력하세요');
                            return;
                        }				
                        var reason = s.getValue();
                        downloadContent(ids, type, isWorkpanelClear, reason);
                        b.ownerCt.ownerCt.close();
                    }
                },{
                    text: '취소',
                    handler: function(b, e){
                        b.ownerCt.ownerCt.close();
                    }
                }]
            });
            return causeWin;
        }\n";

        echo "function downloadContent(ids, type, isWorkpanelClear, reason, req_type){            
            var isWindowsOS = Ext.isWindows;

            if(req_type === undefined) {
                req_type = 'I';
            }

            isWindowsOS = false;
                    
            Ext.Ajax.request({
                url: '/store/download/get_download_list.php',
                params: {
                    download_type: 'content',
                    content_ids: ids.join(','),
                    reason: reason,
                    media_type: type,
                    is_win_os: isWindowsOS,
                    req_type: req_type
                },
                callback: function(opts, success, resp){
                    //console.log(resp.responseText);
                    if (success)
                    {
                        try
                        {
                            var rtn = Ext.decode(resp.responseText);
                            if (rtn.success)
                            {
                                if(!Ext.isEmpty(rtn.message)) {
                                    Ext.Msg.alert('확인', rtn.message);
                                }
                                //console.log(rtn.data);
                                if (isWorkpanelClear)
                                {
                                    Ext.getCmp('workpanel').getStore().removeAll();
                                }
                                if (isWindowsOS)
                                {
                                    launchMediaDownloaderApp(rtn.data);
                                }
                                else
                                {
                                    //console.log(rtn.data);
                                    downloadCount = 0;
                                    downloadAllHttp(rtn.data);
                                }
                            }
                            else
                            {
                                Ext.Msg.alert('다운로드 리스트 생성 중 오류', rtn.msg);
                            }
                        }
                        catch (e)
                        {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    }
                    else
                    {
                        Ext.Msg.alert('서버 통신 오류', resp.statusText);
                    }
        
                    var wndDownload = Ext.getCmp('download-window')
                    if(wndDownload != null)
                    {
                        wndDownload.close();
                    }
                }
            });	
        }\n";

        echo "function downloadPFRMedia(mediaIds) {
            var isWindowsOS = Ext.isWindows;

            Ext.Ajax.request({
                url: '/store/download/get_download_list.php',
                params: {
                    download_type: 'pfr',
                    media_ids: mediaIds.join(','),                    
                    is_win_os: isWindowsOS
                },
                callback: function(opts, success, resp){
                    //console.log(resp.responseText);
                    if (success)
                    {
                        try
                        {
                            var rtn = Ext.decode(resp.responseText);
                            if (rtn.success)
                            {
                                if(!Ext.isEmpty(rtn.message)) {
                                    Ext.Msg.alert('확인', rtn.message);
                                }
                                //console.log(rtn.data);
                                if (isWindowsOS)
                                {
                                    launchMediaDownloaderApp(rtn.data);
                                }
                                else
                                {
                                    //console.log(rtn.data);
                                    downloadCount = 0;
                                    downloadAllHttp(rtn.data);
                                }
                            }
                            else
                            {
                                Ext.Msg.alert('다운로드 리스트 생성 중 오류', rtn.msg);
                            }
                        }
                        catch (e)
                        {
                            Ext.Msg.alert(e['name'], e['message']);
                        }
                    }
                    else
                    {
                        Ext.Msg.alert('서버 통신 오류', resp.statusText);
                    }
                }
            });	
        }\n";

        echo "function downloadContentList() {
            
            var records = getSelectedContents();
            
            if ( !checkSelected( records ) ) return;

            doDownload( records );

        }\n";

        echo "function launchMediaDownloaderApp(mediaIds) {
            
            var params = [];
            params.push({key: 'media_ids', value: mediaIds});
            
            launchApp('gemiso.media-downloader://args?', params);
        }\n;";

        //CJO 전송기능을 모아놓은 메뉴(is_show=N)
        //CJO, 팝업메뉴 설정에 있는 전송 워크플로우들을 동시에 수행할 수 있도록 체크박스로 구성된 UI가 있음
        //$workflow_info = $db->queryAll("select * from bc_task_workflow where type='c' and is_show='N' and activity='1' order by register");
        $transfer_checkbox = array();
        // foreach($workflow_info as $wi) {
        //     //2017-12-28 이승수, CJO, 전송 팝업 창에 TCOM 관련 전송 항목 노출되면 안됨
        //     //보여야 할 항목 - Harris 전송(채널운영실)- Harris 전송(부조)- 스튜디오 릴레이서버 전송- T-COM 송출 전송
        //     if(!in_array($wi['register'], array('ftp_transfer_GWR_A','ftp_transfer_GWR_B','ftp_transfer_GWR_E','ftp_transfer_tcom_apc_GWR_A'))) {
        //         continue;
        //     }
        //     // 2018.05.04 hkkim 강제전송그룹만 릴레이서버 전송을 할 수 있다.
        //     if($wi['register'] == 'ftp_transfer_GWR_E' && !allowVisible(array(SUPER_TRANSFER_GROUP))) {                
        //         continue;
        //     }
        //     $transfer_checkbox[] = "{xtype: 'checkbox', boxLabel: '".$wi['user_task_name']."', name: '".$wi['register']."'}";
        // }
        $transfer_checkbox = implode(',',$transfer_checkbox);
        echo "function transferContent(channel) {
            
            var records = getSelectedContents();

            //var usePrhibtCheck = false;
            //if(channel == 'transfer_to_maincontrol'){
            //    Ext.each(records,function(record){
            //        var usePrhibtAt = record.get('usr_meta').use_prhibt_at;
            //        if(usePrhibtAt == 'Y'){
            //            usePrhibtCheck = true;
            //        }
            //    });
            //}
            //if(usePrhibtCheck){
            //    return Ext.Msg.alert('알림', '선택한 목록중 사용금지된 콘텐츠가 있습니다.');
            //}
            transferContentList(records, channel );
        }\n"; 
        
        echo "function transferContentList(records, channel ) {

            var contentIds=[];
            var notAllowSend = false;
            var regCompleteContentExists = false;
            var msg = '';
            Ext.each(records, function(r){
                contentIds.push( r.get('content_id') );
            });

            var sel = {
                content_list : Ext.encode(contentIds),
                channel: channel
            };
                                       
           
            Ext.Ajax.request({
                url: '/custom/ktv-nps/store/check_overwrite.php',
                params: sel,
                callback: function(opt, success, response) {
                 
                    var res = Ext.decode(response.responseText);
                    if(res.success) {
                        var msg = '선택한 전송처로 전송작업을 시작합니다.';
                        var btn_type = Ext.Msg.OKCANCEL;
                        if(!Ext.isEmpty(res.msg)) {
                            msg = res.msg;
                            btn_type = Ext.Msg.YESNOCANCEL;
                        }
                        Ext.Msg.show({
                            title: '확인',
                            msg: msg,
                            modal: true,
                            icon: Ext.MessageBox.QUESTION,
                            buttons: btn_type,
                            fn: function(btnId) {
                                if(btnId=='yes' || btnId=='ok') {
                                    //전체 content_id 작업시작
                                    Ext.Ajax.request({
                                        url: '/custom/ktv-nps/store/start_transfer_task.php',
                                        params: sel,
                                        callback: function(opt, success, response) {
                                            var res = Ext.decode(response.responseText);
                                            if(res.success) {
                                                //MSG00057 전송이 시작되었습니다
                                                Ext.Msg.alert( _text('MN00003'), _text('MSG00057'));                                                
                                            } else {
                                                Ext.Msg.alert( _text('MN00003'), res.msg);
                                            }
                                        }
                                    });
                                } else if(btnId=='no') {
                                    //중복 아닌 content_id만 작업시작
                                    sel.content_list = Ext.encode(res.accept_content_ids);
                                    Ext.Ajax.request({
                                        url: '/custom/ktv-nps/store/start_transfer_task.php',
                                        params: sel,
                                        callback: function(opt, success, response) {
                                            var res = Ext.decode(response.responseText);
                                            if(res.success) {
                                                //MSG00057 전송이 시작되었습니다
                                                Ext.Msg.alert( _text('MN00003'), _text('MSG00057'));                                               
                                            } else {
                                                Ext.Msg.alert( _text('MN00003'), res.msg);
                                            }
                                        }
                                    });
                                }
                            }
                        });
                    } else {
                        Ext.Msg.alert( _text('MN00003'), res.msg);
                    }
                }
            });   

        }\n"; 

        // 콘텐츠 목록에서 선택된 콘텐츠로 VCR리스트를 작성할 때 사용
        echo "function writeVCRListWithContentAction() {
            writeVCRListWithContent(null);
        }\n";
        
        // 콘텐츠 목록에서 선택된 콘텐츠를 작업패널에 추가 한 후 VCR리스트를 작성할 때
        // 또는 VCR리스트를 재활용 할 때 사용
        // PD_GROUP, FD_GROUP, CHANNEL_GROUP, ADMIN_GROUP, REVIEW_GROUP만 사용
        echo "function writeVCRListWithContent(records) {            
              
            // 작업 패널에서 호출 될 때는 값이 채워져서 들어온당

            if(records == undefined || records == null) {
                // 콘텐츠 목록에서 우클릭 메뉴로 호출 될 때는 null로 들어온당
                records = getSelectedContents();                
            }           

            var contents = [];

            // 선택한 콘텐츠 VCR 리스트 만들기
            var today = new Date().format('Ymd'),
                successRun = false,
                hasAllowList = false,
                alertMsg = [],
                ids = [];
            
            //부조 영상 체크
            var check_ids = [];	
            for(var i=0; i<records.length; i++) {
                var record = records[i];

                check_ids.push(record.get('content_id'));
            }
        
            //수정일 : 2011.02.28
            //작성자 : 김형기
            //내용 : VCR리스트 작성 시 부조에 해당영상이 존재하는지 체크 하도록 변경
            Ext.Ajax.request({
                url: '/custom/cjos/store/harris/harris_check.php',
                params: {
                    contentIds: check_ids.join(',')
                },
                success: function(response, opts){
                    try {
                        var r = Ext.decode(response.responseText);
                        if(r.success) {	
                            var notExistsHarris = [];
                            if(r.notExistsHarris != '') {
                                notExistsHarris = r.notExistsHarris.split(',');
                            }
                            for(var i=0; i<records.length; i++) {
                                var record = records[i];
                                //수정일 : 2010.11.02
                                //작성자 : 김형기
                                //내용 : vcr리스트에 등록된 파일을 재활용하지 못하는 문제 해결
                                var contentType = parseInt(record.get('ud_content_id'));
                                var status = record.get('status');
                                var modelExpireDate = record.get('usr_modelexpiredate');
                                var title = record.get('title');
                                var contentId = record.get('content_id');
                                var vcrListId = record.get('vcr_list_id');
                                var vcrListNumber = record.get('vcr_list_no');
                                var fromVCRList = record.get('from_vcr_list');    
                                fromVCRList = fromVCRList === undefined ? false : fromVCRList;
                                
                                var checkXDCAMResult = checkXDCAM(record);                                
        
                                //수정일 : 2011.10.28
                                //작성자 : 김형기
                                //내용 : 동영상 사용자 정의 콘텐츠도 VCR리스트를 작성할 수 있도록 수정
                                //수정일 : 2015.04.24
                                //작성자  :김형기
                                //내용 : VOD콘텐츠도 VCR리스트를 작성할 수 있도록 수정
                                if (vcrListWritableUserContentIds.indexOf(contentType) < 0) {
                                    alertMsg.push('\"' + title + '\"은(는) VCR리스트 작성을 할 수 없는 형식의 콘텐츠 입니다.<br/>VCR리스트는 사전제작, 클린, VOD 콘텐츠만 작성 가능 합니다.');
                                    hasAllowList = true;
                                } else if ( status != null && status == 5 ) {
                                    alertMsg.push('\"' + title + '\"은(는) 반려된 콘텐츠로 VCR리스트 작성을 할 수 없습니다.');
                                    hasAllowList = true;
                                } else if ( modelExpireDate != null && 
                                            modelExpireDate.format('Ymd') < today ) {
                                    alertMsg.push('\"' + title + '\"은(는) 모델초상권 사용기간이 지난 콘텐츠로 VCR리스트 작성을 할 수 없습니다.');
                                    hasAllowList = true;
                                } else if( !fromVCRList && !checkXDCAMResult.isXDCAM) {
                                    alertMsg.push('\"' + title + '\"은(는) 송출에 부적합한 콘텐츠로 VCR리스트 작성을 할 수 없습니다.<br/>' + checkXDCAMResult.message);
                                    hasAllowList = true;                                    
                                } else if(notExistsHarris.length > 0) {							
                                    var count = notExistsHarris.length;
                                    var allow = true;
                                    for(var j; j<count; j++) {
                                        if(contentId == notExistsHarris[j])
                                        {
                                            allow = false;
                                            break;
                                        }
                                    }
                                    if(allow) {
                                        ids.push(makeContentIdParam(contentId, vcrListId, vcrListNumber));  
                                    } else {
                                        alertMsg.push('\"' + r.get('title') + '\"은(는) 부조서버에 존재하지 않습니다.<br/>부조서버 존재 여부를 확인해 주세요.');
                                        hasAllowList = true;
                                        ids.push(makeContentIdParam(contentId, vcrListId, vcrListNumber));
                                    }
                                } else {
                                    ids.push(makeContentIdParam(contentId, vcrListId, vcrListNumber));
                                }
                            };
                            
                            var args = { mode: 'write_content', contentIds: ids.join(';'), userId: userId, isAdmin: isAdmin, isPD: isPD };
                            if (hasAllowList) {
                                Ext.Msg.show({
                                    title: '확인', 
                                    msg: alertMsg.join('<br />'),
                                    buttons: Ext.Msg.OK,
                                    fn: function(btnId){
                                        if (ids.length == 0) {
                                            Ext.Msg.alert('확인', '등록가능한 콘텐츠가 없습니다.');
                                        } else {
                                            launchVCRListApp(args);
                                        }
                                    }
                                });
                            } else {
                                launchVCRListApp(args);
                            }
                        } else {
                            Ext.Msg.alert('오류', r.message+'(writeVCRListWithContent)');
                        }
                    } catch(e) {
                        Ext.Msg.alert(e['name'], e['message']+'(writeVCRListWithContent)<br/>'+response.responseText);
                        return;
                    }
                },
                failure: function(response, opts){
                    Ext.Msg.alert('오류', '서버오류(' + response.status + ')');
                }
            });	
        }\n
        function makeContentIdParam(contentId, vcrListId, vcrListNumber) {        
            var contentIdParam = '';    
            if(vcrListId === undefined || vcrListId === '') {
                contentIdParam = contentId;
            } else {
                contentIdParam = contentId + '@' + vcrListId + '@' + vcrListNumber;
            }
            return contentIdParam;
        }\n";


        // // XDCAM 전송
        // echo "function transferXDCAM()
        // {
        //     var records = getSelectedContents();
        //     var title	= new Date().format('YmdHis');
           
        //     Ext.Ajax.request({
        //         url: '{$customRootWebpath}/javascript/ext.ux/xdcam-transfer.js',
        //         callback: function(opts, success, resp){
        //             if (success) 
        //             {
        //                 try
        //                 {                                            
        //                     var showXDCAMTransferWin = Ext.decode(resp.responseText);                                            
        //                     showXDCAMTransferWin(records, title);
        //                 }
        //                 catch (e)
        //                 {
        //                     Ext.Msg.alert(e['name'], e['message']);
        //                 }
        //             }
        //             else 
        //             {
        //                 Ext.Msg.alert('서버 오류', resp.statusText);
        //             }
        //         }
        //     });
        // }\n";

        // // 콘텐츠 유형 변경
        // echo "function changeUserContentType()
        // {
        //     var records = getSelectedContents();            
            
        //     Ext.Ajax.request({
        //         url: '{$customRootWebpath}/javascript/ext.ux/content-type-transform.js',
        //         callback: function(opts, success, resp){
        //             if (success) 
        //             {
        //                 try
        //                 {                                            
        //                     var showContentTypeTransformWin = Ext.decode(resp.responseText);                                            
        //                     showContentTypeTransformWin(records);
        //                 }
        //                 catch (e)
        //                 {
        //                     Ext.Msg.alert(e['name'], e['message']);
        //                 }
        //             }
        //             else 
        //             {
        //                 Ext.Msg.alert('서버 오류', resp.statusText);
        //             }
        //         }
        //     });
        // } \n";

        // // 콘텐츠 상태 초기화(등록완료로 변경)
        // echo "function changeContentStatus()
        // {
        //     var records = getSelectedContents();

        //     var contentIds = [];
        //     for(var i=0; i < records.length; i++) {
        //         contentIds.push(records[i].get('content_id'));
        //     }
            
        //     Ext.Ajax.request({
        //         url: '{$customRootWebpath}/store/change_content_status.php',
        //         method: 'POST',
        //         jsonData: {
        //             content_ids: contentIds,
        //             content_status: " . CONTENT_STATUS_REG_READY . "
        //         },
        //         callback: function(opts, success, response){
        //             if (success)  {
        //                 try {                                            
        //                     var res = Ext.decode(response.responseText);
        //                     if(res.success) {                                
        //                         Ext.Msg.alert( _text('MN00003'), '콘텐츠 상태가 성공적으로 변경되었습니다.');
        //                         // 콘텐츠 목록 갱신
        //                         Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
        //                     } else {
        //                         Ext.Msg.alert( _text('MN00003'), res.msg);
        //                     }
        //                 } catch (e) {
        //                     Ext.Msg.alert(e['name'], e['message']);
        //                 }
        //             } else {
        //                 Ext.Msg.alert('서버 오류', resp.statusText);
        //             }
        //         }
        //     });
        // } \n";

        // js 파일 직접 로드
        $scriptPaths = [
            '/javascript/contentActions.js'
        ];

        foreach($scriptPaths as $scriptPath ) {
            $script = ViewCustom::getScriptData($scriptPath);
            echo $script;            
        }

    }

    /**
     * 콘텐츠 QC상태확인 함수 - 2018.03.20 Alex
     * @param mixed $content_ids 콘텐츠ID 목록
     * @param mixed $metadatas 메타데이터 정보
     * @return $metadatas
     */

     public static function getQulictyCheckStatus($content_ids, $metadatas) {
        global $db;

        //현재 검색건에 대해 QC정보 검색
        $query = "
            SELECT  *
            FROM    BC_MEDIA_QUALITY_INFO
            WHERE   CONTENT_ID IN (".join(',', $content_ids).")
        ";
        $qc_list = $db->queryAll($query);
        //echo $query;exit;
    
        /*ERROR COUNT가 0보다 크면 QC error로 표기 */
        foreach( $metadatas as $key => $data )
        {
            $content_id = $data['content_id'];
            $v = '';
            foreach($qc_list as $list)
            {
                if($list['content_id'] == $content_id) {
                    if($list['error_count'] > 0) {
                        $metadatas[$key]['qc_error_yn'] = 'Y';
                    } else {
                        $metadatas[$key]['qc_error_yn'] = 'N';
                    }
                }
            } 
            
        }
         return $metadatas;
     }


     /**
     * 콘텐츠 Loudness 확인 함수 - 2018.04.26 Alex
     * @param mixed $content_ids 콘텐츠ID 목록
     * @param mixed $metadatas 메타데이터 정보
     * @return $metadatas
     */

    public static function getLoudnessStatus($content_ids, $metadatas) {
        global $db;

        //현재 검색건에 대해 Loudness정보 검색
        $query = "
            SELECT  *
            FROM    TB_LOUDNESS
            WHERE   CONTENT_ID IN (".join(',', $content_ids).")
        ";
        $loudness_list = $db->queryAll($query);
        
        foreach( $metadatas as $key => $data )
        {
            $content_id = $data['content_id'];
            $v = '';
            foreach($loudness_list as $list)
            {
                if($list['content_id'] == $content_id) {
                    if(!empty($list['integrate'])) {
                        $metadatas[$key]['loudness'] = 'Y';
                    } else {
                        $metadatas[$key]['loudness'] = 'N';
                    }
                }
            } 
        }
        return $metadatas;
     }
}


<?php
// session_start();
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
// require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

// $user_id = $_SESSION['user']['user_id'];
$select = $_GET['select'] ?? '';
?>

Ariel.Nps.Statistic = Ext.extend(Ext.Panel, {
    layout: 'border',
    autoScroll: true,
    border:false,
    initComponent: function(config) {
        Ext.apply(this, config || {});
        var that = this;
        this.items= [{
                    id: 'statistic_tp',
        xtype: 'treepanel',
        region: 'west',
        //title: _text('MN00293'),
        width: 280,
        boxMinWidth: 280,
        border: false,
        bodyStyle: 'border-right: 1px solid #d0d0d0',
        //split: true,
        //collapsible: true,
        autoScroll: true,
        rootVisible :false,
        cls:'tree_menu',
        lines:false,
        listeners: {
            afterrender: function(self){
                var node = self.getRootNode().findChild('id', '<?=$select?>');
                if (node)
                {
                    node.fireEvent('click', node);
                }
                //self.expandAll();
            },
            click: function(node, e){
                var url = node.attributes.url;

                if(!url) return;

                Ext.Ajax.request({
                    url: url,
                    timeout: 0,
                    callback: function(opts, success, response){
                        try
                        {
                            Ext.getCmp('admin_statistic_contain').removeAll(true);
                            Ext.getCmp('admin_statistic_contain').add(Ext.decode(response.responseText));
                            Ext.getCmp('admin_statistic_contain').setTitle(node.attributes.title);

                            Ext.getCmp('admin_statistic_contain').doLayout();
                        }
                        catch (e)
                        {
                            Ext.Msg.alert(e['name'], opts.url+'<br />'+e['message']);
                        }
                    }
                });
            }
        },
        root: {
            id:'admin',
            //>>text: '통계',
            text: _text('MN00293'),
            expanded: true,
            children: [
                {
                    //>>text: '대시보드(일일)',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-columns" style="font-size:18px;"></i></span>&nbsp;'+ '대시보드 (일일)',
                    title: '대시보드 (일일)',
                    url: '',
                    expend: true,
                    leaf: true
                },  
                {
                    //>>text: '사용자',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-user" style="font-size:18px;"></i></span>&nbsp;'+ '사용자',
                    title: '사용자',
                    expanded: false,
                    children: [
                        {
                            //>>text: '접속자',
                            title : '접속자',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'접속자',
                            url: '/pages/statistics/loginUserStatistics.js',
                            leaf: true
                        }
                    ]
                },
                {
                    //>>text: '운영',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-bars" style="font-size:18px;"></i></span>&nbsp;'+ '운영',
                    title: '운영',
                    expanded: false,
                    children: [
                        {
                            //>>text: '사용신청 승인',
                            title : '사용신청 승인',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'사용신청 승인',
                            url: '/pages/statistics/userApprovalStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '의뢰',
                            title : '의뢰',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'의뢰',
                            url: '/pages/statistics/requestStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '제작폴더 신청',
                            title : '제작폴더 신청',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'제작폴더 신청',
                            url: '/store/statistics_new/content/folderRequestStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '방송 심의',
                            title : '방송 심의',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'방송 심의',
                            url: '/store/statistics_new/content/reviewStatistics.js',
                            leaf: true
                        },
                    ]
                },
                {
                    //>>text: '콘텐츠 등록',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-inbox" style="font-size:18px;"></i></span>&nbsp;'+ '콘텐츠 등록',
                    title: '콘텐츠 등록',
                    expanded: false,
                    children: [
                        {
                            //>>text: '콘텐츠 통계',
                            title : '콘텐츠 통계',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'콘텐츠 통계',
                            url: '/pages/statistics/contentStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '콘텐츠 등록 승인',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'콘텐츠 등록 승인',
                            title: '콘텐츠 등록 승인',
                            url: '/store/statistics_new/content/contentReviewStatistics.js',
                            expend: true,
                            leaf: true
                        },              
                        {
                            //>>text: '유형별',
                            title : '유형별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'유형별',
                            url: '/pages/statistics/contentTypeStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '출처별',
                            title : '출처별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'출처별',
                            url: '/pages/statistics/contentSourceStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '포맷별',
                            title : '포맷별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'포맷별',
                            url: '/pages/statistics/contentFormatStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '프로그램별',
                            title : '프로그램별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'프로그램별',
                            url: '/pages/statistics/contentProgramStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '회차별',
                            title : '회차별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'회차별',
                            url: '/pages/statistics/contentEpisodeStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '부서별',
                            title : '부서별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'부서별',
                            url: '/pages/statistics/contentDepartmentStatistics.js',
                            leaf: true
                        }
                    ]
                },
                //{
                    //>>text: '콘텐츠 등록 승인',
                //    text: '<span style="position:relative;top:1px;"><i class="fa fa-check-circle-o" style="font-size:18px;"></i></span>&nbsp;'+'콘텐츠 등록 승인',
                //    title: '콘텐츠 등록 승인',
                //    url: '/store/statistics_new/content/contentReviewStatistics.js',
                //    expend: true,
                //    leaf: true
                //},
                {
                    //>>text: '콘텐츠 변환',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-retweet" style="font-size:18px;"></i></span>&nbsp;'+'콘텐츠 변환',
                    title: '콘텐츠 변환',
                    expanded: false,
                    children: [
                        {
                            //>>text: '영상변환 통계',
                            title : '영상변환 통계',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'영상변환 통계',
                            url: '/pages/statistics/videoConvertStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '유형별',
                            title : '유형별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'유형별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '출처별',
                            title : '출처별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'출처별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '포맷별',
                            title : '포맷별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'포맷별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '프로그램별',
                            title : '프로그램별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'프로그램별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '회차별',
                            title : '회차별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'회차별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '부서별',
                            title : '부서별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'부서별',
                            url: '',
                            leaf: true
                        }
                    ]
                },
                {
                    //>>text: '콘텐츠 삭제',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:18px;"></i></span>&nbsp;'+'콘텐츠 삭제',
                    title: '콘텐츠 삭제',
                    expanded: false,
                    children: [
                        {
                            //>>text: '원본 삭제',
                            title : '원본 삭제',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'원본 삭제',
                            url: '/pages/statistics/contentOriginalDeletedStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '아카이브 삭제',
                            title : '아카이브 삭제',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'아카이브 삭제',
                            url: '/pages/statistics/contentArchiveDeletedStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '유형별',
                            title : '유형별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'유형별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '출처별',
                            title : '출처별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'출처별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '포맷별',
                            title : '포맷별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'포맷별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '프로그램별',
                            title : '프로그램별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'프로그램별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '회차별',
                            title : '회차별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'회차별',
                            url: '',
                            leaf: true
                        },
                        {
                            //>>text: '부서별',
                            title : '부서별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'부서별',
                            url: '',
                            leaf: true
                        }
                    ]
                },
                {
                    //>>text: '아카이브 통계',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-archive" style="font-size:18px;"></i></span>&nbsp;'+'아카이브 통계',
                    title: '아카이브 통계',
                    expanded: false,
                    children: [
                        {
                            //>>text: '일별',
                            title : '일별',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'일별',
                            url: '/pages/statistics/dailyArchiveStatistics.js',
                            leaf: true
                        },
                        {
                            //>>text: '주간',
                            title : '주간',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'주간',
                            url: '/pages/statistics/weekArchiveStatistics.js',
                            leaf: true
                        },
                    ]
                },
                {
                    //>>text: '리스토어 통계',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-mail-reply" style="font-size:18px;"></i></span>&nbsp;'+'리스토어 통계',
                    title: '리스토어 통계',
                    url: '/pages/statistics/restoreStatistics.js',
                    expend: false,
                    leaf: true
                },
                {
                    //>>text: '전송',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-paper-plane-o" style="font-size:18px;"></i></span>&nbsp;'+'전송',
                    title: '전송',
                    expanded: false,
                    children: [
                        {
                            //>>text: '외부(포털) 업로드',
                            title : '외부(포털) 업로드',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'외부(포털) 업로드',
                            url: '/store/statistics/content/content_type_pie_regist_tmp.js',
                            leaf: true
                        },
                        {
                            //>>text: '다운로드 통계',
                            title : '다운로드 통계',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'다운로드 통계',
                            url: '/pages/statistics/downloadStatistics.js',
                            leaf: true
                        },
                    ]
                },
                {
                    //>>text: '운영 통계',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-bars" style="font-size:18px;"></i></span>&nbsp;'+'운영 통계',
                    title: '운영 통계',
                    url: '/pages/statistics/operationStatistics.js',
                    expend: true,
                    leaf: true
                },
                {
                    //>>text: '시스템 사용량',
                    text: '<span style="position:relative;top:1px;"><i class="fa fa-gears" style="font-size:18px;"></i></span>&nbsp;'+'시스템 사용량',
                    title: '시스템 사용량',
                    url: '',
                    expanded: false,
                    children: [
                        {
                            //>>text: '작업 현황',
                            title : '작업 현황',
                            text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+'작업 현황',
                            url: '/store/statistics/content/content_type_pie_regist_tmp.js',
                            leaf: true
                        }
                    ]
                }
            ],
        }
    },{
        region: 'center',
        id: 'admin_statistic_contain',
        //title: '&nbsp;',
        headerAsText: false,
        layout: 'fit',
        border: false
    }]

        Ariel.Nps.Statistic.superclass.initComponent.call(this);
    }
});
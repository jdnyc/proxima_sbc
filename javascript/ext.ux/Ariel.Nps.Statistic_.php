<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = $_SESSION['user']['user_id'];

?>

Ariel.Nps.Statistic_ = Ext.extend(Ext.Panel, {
            layout: 'border',
            autoScroll: true,

            initComponent: function(config) {
                Ext.apply(this, config || {});
                var that = this;
                this.items= [{
                    		id: 'statistic_tp_',
				xtype: 'treepanel',
				region: 'west',
				title: _text('MN00293'),
				width: 250,
				boxMinWidth: 250,
				split: true,
				collapsible: true,
				autoScroll: true,
				listeners: {
					afterrender: function(self){
						var node = self.getRootNode().findChild('id', '<?=$_GET['select']?>');
						if (node)
						{
							node.fireEvent('click', node);
						}
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
									Ext.getCmp('admin_statistic_contain_').removeAll(true);
									Ext.getCmp('admin_statistic_contain_').add(Ext.decode(response.responseText));
									Ext.getCmp('admin_statistic_contain_').setTitle(node.attributes.text);

									Ext.getCmp('admin_statistic_contain_').doLayout();
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
					id:'admin_',
					icon:'/led-icons/folder.gif',
					//>>text: '통계',
					text: '<?=_text('MN00293')?>',
					expanded: true,
					children: [{
						hidden: true,
						//>>text: '인제스트 통계',
						text: '<?=_text('MN00323')?>',
						icon:'/led-icons/folder.gif',
						//url: '/store/statistics/ingest/ingest_meta_statistic.php',
						expend: true,
						children:[{
							//>>text: '인제스트 작업 통계',
							text: '<?=_text('MN00225')?>',
							//icon: '/led-icons/application_view_columns.png',
							icon:'/led-icons/folder.gif',
							url: '/store/statistics/ingest/ingest_work_statistic.php',
							leaf: true
						},{
							//>>text: '인제스트 메타데이터 통계',
							text: '<?=_text('MN00224')?>',
							//icon: '/led-icons/application_view_columns.png',
							icon:'/led-icons/folder.gif',
							url: '/store/statistics/ingest/ingest_meta_statistic.php',
							leaf: true
						}]
					},{
						//>>text: '사용자 통계',
						text: '<?=_text('MN00324')?>',
						icon:'/led-icons/folder.gif',
						expanded: true,
						// 2010-12-08 주석처리 by CONOZ url: '/pages/statistics/user_group.js',
						//url: '/pages/statistics/user_group.php',
						children: [{
							//>>text: '지정 기간 접속하지 않은 사용자',
							text: '<?=_text('MN00259')?>',
							//icon: '/led-icons/application_view_columns.png',
							// 2010-12-08 주석처리 by CONOZ url: '/pages/statistics/user_logoff.js',
							icon:'/led-icons/folder.gif',
							url: '/pages/statistics/user_logoff.php',
							leaf: true
						},{
							//>>text: '지정 기간 가입한 사용자',
							text: '<?=_text('MN00257')?>',
							//icon: '/led-icons/application_view_columns.png',
							// 2010-12-08 주석처리 by CONOZ url: '/pages/statistics/user_join.js',
							icon:'/led-icons/folder.gif',
							url: '/pages/statistics/user_join.php',
							leaf: true
						},{
							//>> text: '지정 기간 사용자별 접속 로그',
							text: '<?=_text('MN00258')?>',
							//icon: '/led-icons/application_view_columns.png',
							icon:'/led-icons/folder.gif',
							url: '/pages/statistics/user_history.php',
							leaf: true
						},{
							//>>text: '사용자 순위별 통계',
							text: '<?=_text('MN00194')?>',
							icon:'/led-icons/folder.gif',
							children: [{
								//>>text: '가장 많이 조회한 사용자',
								text: '<?=_text('MN00315')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								url: '/pages/statistics/user_best_read.js',
								leaf: true
							},{
								//>>text: '가장 많이 등록한 사용자',
								text: '<?=_text('MN00312')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								url: '/pages/statistics/user_best_regist.js',
								leaf: true
							},{
								//>>text: '가장 많이 로그인한 사용자',
								text: '<?=_text('MN00313')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								url: '/pages/statistics/user_best_login.js',
								leaf: true
							},
							{
								//>>text: '가장 많이 삭제한 사용자',
								text: '<?=_text('MN00314')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								url: '/pages/statistics/user_best_del.js',
								leaf: true
							}]
						}]
					},{
						//>>text: '개인별 통계',MN00211
						text: '<?=_text('MN00211')?>',
						icon:'/led-icons/folder.gif',
						expanded: true,
						children: [{
							//>>text: '기간별 로그인 횟수',
							text: '<?=_text('MN00151')?>',
							//icon: '/led-icons/chart_line.png',
							icon:'/led-icons/folder.gif',
							//qtip: '(월 단위:최소1개월)',
							leaf: true,
						//	2010-11-01 (차트아래 그리드 추가 by LSY)
						//	url: '/store/statistics/personal/login_select.php'
							url: '/store/statistics/personal/login_select_grid.js'
						},{
							//>>text: '작업 별 통계',MN00232
							text: '<?=_text('MN00232')?>',
							//icon: '/led-icons/application_view_columns.png',
							icon:'/led-icons/folder.gif',
							//>>qtip: '등록/수정/조회/삭제/다운로드/카탈로그/트랜스코딩/트랜스퍼',
							leaf: true,
							url: '/pages/statistics/job_kind.php'
						},{
							//>>text: '조회 리스트',MN00250
							text: '<?=_text('MN00250')?>',
							//icon: '/led-icons/application_view_columns.png',
							icon:'/led-icons/folder.gif',
							leaf: true,
							url: '/pages/statistics/read_content.php'
						}]
					},{
						//>>text: '콘텐츠 통계',
						text: '<?=_text('MN00325')?>',
						icon:'/led-icons/folder.gif',
						expanded: true,
						children: [{
							//>>text: '콘텐츠 타입별',
							text: '<?=_text('MN00282')?>',
							icon:'/led-icons/folder.gif',
							url: '/store/statistics/content/content_type_pie.js',
							children: [{
								//>>text: '콘텐츠 타입별 등록 현황',
								text: '<?=_text('MN00283')?>',
								//icon:'/led-icons/chart_pie.png',
								icon:'/led-icons/folder.gif',
								qtip: '등록 현황',
								// 2010-10-25 주석처리 (차트아래 그리드 추가 by CONOZ)
								// url: '/store/statistics/content/content_type_pie_regist.php',
								url: '/store/statistics/content/content_type_pie_regist_tmp.js',
								leaf: true
							},{
								//>>text: '콘텐츠 타입별 조회 현황',
								text: '<?=_text('MN00285')?>',
								//icon:'/led-icons/chart_pie.png',
								icon:'/led-icons/folder.gif',
								qtip: '조회 현황',
								url: '/store/statistics/content/content_type_pie_grid_read.js',
								//2010-11-02 (차트 아래 그리드 추가 by CHH)
								//url: '/store/statistics/content/content_type_pie_read.php',
								leaf: true
							}]
						},{
							//>>text: '사용자 정의 콘텐츠별',
							text: '<?=_text('MN00199')?>',
							icon:'/led-icons/folder.gif',
							url: '/store/statistics/content/user_type_pie.php',
							children: [
							{
								hidden: true,
								text: '사용자 정의 콘텐츠별 승인 현황',
								icon:'/led-icons/folder.gif',
								qtip: '승인 현황',
								url: '/store/statistics/content/user_type_approve.js',
								leaf: true
							},
							{
								//>>text: '사용자 정의 콘텐츠별 등록 현황',
								text: '<?=_text('MN00200')?>',
								//icon:'/led-icons/chart_pie.png',
								icon:'/led-icons/folder.gif',
								qtip: '등록/조회/다운로드',
								//url: '/store/statistics/content/user_type_pie_regist.php',
								//2010-11-02 (차트 아래 그리드 추가 by CHH)
								url: '/store/statistics/content/user_type_pie_regist.js',
								leaf: true
							},{
								//>>text: '사용자 정의 콘텐츠별 조회 현황',
								text: '<?=_text('MN00202')?>',
								//icon:'/led-icons/chart_pie.png',
								icon:'/led-icons/folder.gif',
								qtip: '등록/조회/다운로드',
								//url: '/store/statistics/content/user_type_pie_read.php',
								//2010-11-02 (차트 아래 그리드 추가 by CHH)
								url: '/store/statistics/content/user_type_pie_read.js',
								leaf: true
							}]
						}
						,{
							//>>text: '최근 작업 콘텐츠',
							text: '<?=_text('MN00265')?>',
							icon:'/led-icons/folder.gif',
							children: [{
								//>>text: '가장 최근에 등록된 콘텐츠',
								text: '<?=_text('MN00317')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								qtip: '최근등록 순위',
								url: '/pages/statistics/ranking_regist.js',
								leaf: true
							},{
								//>>text: '가장 최근에 수정된 콘텐츠',
								text: '<?=_text('MN00319')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								qtip: '최근수정 순위',
								url: '/pages/statistics/ranking_edit.js',
								leaf: true
							},{
								//>>text: '가장 최근에 삭제된 콘텐츠',
								text: '<?=_text('MN00318')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								qtip: '최근삭제 순위',
								url: '/pages/statistics/ranking_del.js',
								leaf: true
							},{
								//>>text: '가장 최근에 다운로드된 콘텐츠',
								text: '<?=_text('MN00316')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								qtip: '최근다운로드 순위',
								url: '/pages/statistics/ranking_download.js',
								leaf: true
							}
							,{
								//>>text: '가장 최근에 조회된 콘텐츠',
								text: '<?=_text('MN00320')?>',
								//icon: '/led-icons/application_view_columns.png',
								icon:'/led-icons/folder.gif',
								qtip: '최근조회 순위',
								url: '/pages/statistics/read_ranking.js',
								leaf: true
							}]
						}]
					}]
				}
			},{
				region: 'center',
				id: 'admin_statistic_contain_',
				title: '&nbsp;',
				layout: 'fit'
			}]

                         Ariel.Nps.Statistic.superclass.initComponent.call(this);
            }
});
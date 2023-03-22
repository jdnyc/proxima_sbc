<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = $_SESSION['user']['user_id'];

$interwork_loudness =  $db->queryOne("
	SELECT	COALESCE((
				SELECT	USE_YN
				FROM	BC_SYS_CODE A
				WHERE	A.TYPE_ID = 1
				AND		A.CODE = 'INTERWORK_LOUDNESS'), 'N') AS USE_YN
	FROM	(
			SELECT	USER_ID
			FROM	BC_MEMBER
			WHERE	USER_ID = '".$user_id."') DUAL
");


?>

Ariel.Nps.WorkManagement = Ext.extend(Ext.Panel, {
	layout: 'border',
	autoScroll: true,
	border: false,
	initComponent: function(config) {
		Ext.apply(this, config || {});
		var that = this;
		this.items= [{
			id: 'work_tp',
			xtype: 'treepanel',
			region: 'west',
			//>>title: '시스템',
			//title: '<?=_text('MN00093')?>',
			width: 280,
			boxMinWidth: 280,
			border: false,
			bodyStyle: 'border-right: 1px solid #d0d0d0',
			//split: true,
			//collapsible: true,
			plugins: [Ext.ux.PanelCollapsedTitle],
			autoScroll: true,
			rootVisible :false,
			cls:'tree_menu',
			lines: false,
			listeners: {
				afterrender: function(self) {
					var node = self.getRootNode().findChild('id', '<?=$_GET['select']?>');
					if (node) {
						node.fireEvent('click', node);
					}
					//treepannel root만 보임
					//self.getrootNode();
					//self.expandAll();
				},
				click: function(node, e){
					var url = node.attributes.url;

					if ( ! url) return;

					Ext.Ajax.request({
						url: url,
						timeout: 0,
						callback: function(opts, success, response) {
							try {
								Ext.getCmp('admin_work_contain').removeAll(true);
								Ext.getCmp('admin_work_contain').add(Ext.decode(response.responseText));
								//Ext.getCmp('admin_work_contain').setTitle(node.attributes.text);
								//Ext.getCmp('admin_work_contain').setTitle(node.attributes.title);

								Ext.getCmp('admin_work_contain').doLayout();
							} catch (e) {
								Ext.Msg.alert(e['name'], opts.url+'<br />'+e['message']);
							}
						}
					});
				}
			},
			root:{
				id:'admin',				
				//>> text: '시스템관리',
				text: '<?=_text('MN00207')?>',
				expanded: true,
				children: [{
					//>>text: '권한 관리',
					text:'<span style="position:relative;top:1px;"><i class="fa fa-users" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02038')?>',
					title: '<?=_text('MN02038')?>',
					expanded: true,
					children: [{
						//>>text: '사용자 관리',
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN00191')?>',
						title: '<?=_text('MN00191')?>',						
						url: '/pages/menu/config/user/user.php',
						leaf: true
					},{
						//권한 관리
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN02038')?>',
						title: '<?=_text('MN02038')?>',						
						url: '/pages/menu/config/grant/grant.php',
						leaf: true
					},{
						//메뉴 권한
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN01119')?>',
						title: '<?=_text('MN01119')?>',
						url: '/pages/menu/config/menu_grant/menu_grant.php',						
						leaf: true
					},{
						//사용자 접속이력
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?='사용자 접속이력'?>',
						title: '사용자 접속이력',
						url: '/pages/statistics_new/user_login_history.js',
						leaf: true
					}]
				},{
					//사용자 신청 관리
					text:'<span style="position:relative;top:1px;"><i class="fa fa-users" style="font-size:18px;"></i></span>&nbsp;사용자 신청 관리',
					expend: true,
					leaf: true,
					url: '/pages/menu/config/user_request/Custom.UserRequest.js'
				},{
					hidden: true,
					text: '제작프로그램 관리',
					
					children: [{
						hidden: true,
						text: '환경 설정',
						url: '/pages/menu/setting/category.js',
						leaf: true
					}, {
						title: '제작프로그램 설정',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;제작프로그램 설정',
						url: '/pages/menu/setting/program.js',
						leaf: true
					}, {
						title: '싱크 관리',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;싱크 관리',
						url: '/pages/menu/setting/program_sync.js',
						leaf: true
					}]
				},{
					hidden: true,
					text: '제작종료 관리',					
					url: '/pages/menu/config/shutdown/index.php',
					leaf: true
				},{
					hidden : true,
					title: '토픽 관리',
					text: '<span style="position:relative;top:2px;"><i class="fa fa-exclamation-circle" style="font-size:18px;"></i></span>&nbsp;토픽 관리',
					url: '/pages/menu/config/Topic/index.php',
					leaf: true
				},{
					hidden: true,
					text: '전송 관리',
					
					url: '/pages/menu/config/interface/index.php',
					leaf: true
				},{
					hidden: true,
					//>>text: '서버 모니터링',
					text: '서버 모니터링',					
					url: '/store/sys_monitor/data_view.js',
					leaf: true
				},{
					//>>text: '메타데이터 관리',
					text: '<span style="position:relative;top:3px;"><i class="fa fa-table" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN00165')?>',
					title: '<?=_text('MN00165')?>',
					expanded: true,
					children: [{
						//>>text: '사용자 메타데이터',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN00192')?>',
						title: '<?=_text('MN00192')?>',
						url: '/pages/menu/config/custom/UserMetadataPanel.php',
						leaf: true
					},{
						//>>text: '시스템 메타데이터',
						hidden : true,//2015-11-06 upload_other 문서 시스템 메타데이터 추가위해 숨김 취소//2015-12-20 숨김
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN00207')?>',
						title: '<?=_text('MN00207')?>',
						url: '/pages/menu/config/custom/ContentMetadataPanel.js',
						leaf: true
					}]
				}
				,{
					//>>text: '워크플로우 관리',
					text: '<span style="position:relative;top:3px;"><i class="fa fa-server" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN00326')?>',
					title: '<?=_text('MN00326')?>',
					hidden: true,
					children: [{
						//>>text: '작업흐름 설정',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN00327')?>',
						title: '<?=_text('MN00327')?>',
						url: '/javascript/ext.ux/Ariel.WorkflowSet.js',
						leaf: true
					},{
						//>>text: '작업/모듈 설정',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN00328')?>',
						title: '<?=_text('MN00328')?>',
						url: '/javascript/ext.ux/Ariel.TaskRuleSet.php',
						leaf: true
					},{
						//>>text: '스토리지 설정',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN00329')?>',
						title: '<?=_text('MN00329')?>',
						url: '/javascript/ext.ux/Ariel.TaskStorageSet.js',
						leaf: true
					},{
						//>>text: '모듈타입 설정',
						// 작업 유형 설정
						//수정일 : 2011.12.11
						//작성자 : 김형기
						//내용 : 용어 변경(TASK TYPE -> 작업 유형)
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;작업 유형 설정',
						//title: '작업 유형 설정',
						text: '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:13px;"></i></span>&nbsp;<?=_text('MN02040')?>',
						title: '<?=_text('MN02040')?>',
						url: '/javascript/ext.ux/Ariel.ModuleSet.js',
						leaf: true
					},{
						hidden: true,
						text: '사용자 스토리지 설정',
						url: '/javascript/ext.ux/Ariel.UDTaskStorageSet.php',
						leaf: true
					}]
				},{
					//>>text: '워크플로우 관리',
					text:'<span style="position:relative;top:3px;"><i class="fa fa-server" style="font-size:18px;"></i></span> <?=_text('MN00326')?>',
					title: '<?=_text('MN00326')?>',
					expanded: true,
					children: [{
						//>>text: 'Preset',
						text: '<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span> <?=_text('MN01070')?>',
						title: '<?=_text('MN01070')?>',
						url: '/javascript/ext.ux/Ariel.WorkflowSet.php?workflow_type=p',
						leaf: true
					},{
						//>>text: '작업흐름 설정',
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span> <?=_text('MN00327')?>',
						title: '<?=_text('MN00327')?>',
						url: '/javascript/ext.ux/Ariel.WorkflowSet.php?workflow_type=i',
						leaf: true
					},{
						//>>text: 'Context Menu Workflow',//우클릭 메뉴 설정
						hidden: true, //GS인증 한정 숨김
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span> <?=_text('MN01069')?>',
						title: '<?=_text('MN01069')?>',
						url: '/javascript/ext.ux/Ariel.WorkflowSet.php?workflow_type=c',
						leaf: true
					},{
						//>>text: '스토리지 설정',
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span> <?=_text('MN00329')?>',
						title: '<?=_text('MN00329')?>',
						url: '/javascript/ext.ux/Ariel.TaskStorageSet.js',
						leaf: true
					},
					/* move from system to configuration
					{
						//>>text: '모듈타입 설정',
						// 작업 유형 설정
						//수정일 : 2011.12.11
						//작성자 : 김형기
						//내용 : 용어 변경(TASK TYPE -> 작업 유형)
						//MN01027 작업유형 설정
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span> <?=_text('MN02040')?>',
						title: '<?=_text('MN02040')?>',
						url: '/javascript/ext.ux/Ariel.ModuleSet.js',
						leaf: true
					},
					*/
					{
						//>>text: '작업/모듈 설정',
						text:'<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span> <?=_text('MN00328')?>',
						title: '<?=_text('MN00328')?>',
						url: '/javascript/ext.ux/Ariel.TaskRuleSet_pre.php',
						
						leaf: true
					}]
				}, {
					//>>text: '모니터링',
					text:'<span style="position:relative;top:3px;"><i class="fa fa-desktop" style="font-size:18px;"></i></span> <?=_text('MN02193')?>',
					title: '<?=_text('MN00326')?>',
					expanded: true,
					children: [{
						id:'work_monitor',
						//text: '작업 관리',
						text: '<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN00231')?>',
						title: '<?=_text('MN00231')?>',
						url: '/pages/menu/config/monitor/Monitor.php',
						leaf: true
					},{
						id:'work_monitor_new',
						//text: '작업 관리 new',
						hidden: true,
						text: '<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;'+_text('MN02377'),
						title: _text('MN02377'),
						url: '/pages/menu/config/monitor/monitorNew.php',
						leaf: true
					},{
						id:'agent_monitor',
						//text: '에이전트 모니터링', // CJ오쇼핑의 경우 Agent 모니터링은 안쓰기때문에 숨김처리 및 타이틀 변경 - 2018.01.15 Alex
						text: '<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN00381')?>',
						title: '<?=_text('MN02144')?>',
						url: '/pages/menu/config/monitor/Agent.js',
						leaf: true
					}]
				},{
					//Auto Process Management
					text:'<span style="position:relative;top:3px;"><i class="fa fa-clock-o" style="font-size:18px;"></i></span> <?=_text('MN02273')?>',
					title: '<?=_text('MN02273')?>',
					<?php
						if( $arr_sys_code['interwork_flashnet']['use_yn'] == 'Y' ){
						} else {
							echo 'hidden : true,';
						}
					?>
					children: [{//FlashNet 아카이브 설정
						<?php
							if( ARCHIVE_USE_YN == 'Y'){
							} else {
								echo 'hidden : true,';
							}
						?>
						//text: 'FlashNet',
						text: '<span style="position:relative;top:3px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN01063')?>',
						title: '<?=_text('MN01063')?>',
						url: '/pages/menu/config/archive/php/archive_management.php',
						leaf: true
					}]
				}, {
					hidden: true,
					//>>text: 'ALTO 관리',
					text: '<?=_text('MN00397')?>',
					url: '/pages/menu/alto/index.js',
					leaf: true
				}
				,{
					id: 'board',
					//>> text: '공지사항 관리',MN00145
					text: '<span style="position:relative;top:3px;"><i class="fa fa-list-alt" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN00145')?>',
					title: '<?=_text('MN00145')?>',
					url: '/pages/menu/config/notice/notice.js',
					leaf: true
				}
				,{
					hidden: true,
					text: '옵션',
					url: '/pages/menu/config/options/index.js',
					leaf: true
				},{
					//$$ 코드 관리
					hidden: true,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-code" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN01009')?>',
					title: '<?=_text('MN01009')?>',
					url: '/pages/menu/config/code/code.js',
					leaf: true
				},{
					//text: '미디어 삭제 관리',
                    text: '<span style="position:relative;top:3px;"><i class="fa fa-minus-circle" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02044')?>',
					title: '<?=_text('MN02044')?>',
					url: '/pages/menu/contents_management/contents_delete.php',
					leaf: true
				},{
					//text: '아카이브 승인 관리',
					text: '<span style="position:relative;top:3px;"><i class="fa fa-database" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02526')?>',
					title: '<?=_text('MN02526')?>',
					<?php
						if( ARCHIVE_USE_YN == 'Y'){
						} else {
							echo 'hidden : true,';
						}
					?>
					url: '/pages/menu/contents_management/content_archive_target.php',
					leaf: true
				},{
					//text: '아카이브 승인 관리',
					text: '<span style="position:relative;top:3px;"><i class="fa fa-database" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02411')?>',
					title: '<?=_text('MN02411')?>',
					<?php
						if( ARCHIVE_USE_YN == 'Y'){
						} else {
							echo 'hidden : true,';
						}
					?>
					url: '/pages/menu/contents_management/content_archive.php',
					leaf: true
				},{
					//Harris
					text: '<span style="position:relative;top:3px;"><i class="fa fa-server" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02478')?>',
					title: '<?=_text('MN02478')?>',
					<?php
						if( $arr_sys_code['interwork_harris']['use_yn'] == 'Y'){
						} else {
							echo 'hidden : true,';
						}
					?>
					id: 'work_management_menu_harris',
					url: '/pages/menu/harris/harris_mgmt.php',
					leaf: true
				},{
					//text: '보도정보',
					text: '<span style="position:relative;top:3px;"><i class="fa fa-rss" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02145')?>',
					<?php
						if( INTERWORK_ZODIAC == 'Y'){
						} else {
							echo 'hidden : true,';
						}
					?>
                    hidden: true,
					title: '<?=_text('MN02145')?>',
					url: '/pages/request_zodiac/listTransmission.php',//pages/request_zodiac/listTransmission.php,
					leaf: true
				}
				,{
					//SNS
					text: '<span style="position:relative;top:3px;">&nbsp<i class="fa fa-mobile" style="font-size:20px;"></i></span>&nbsp;&nbsp;<?=_text('MN02324')?>',
					<?php
						if( $arr_sys_code['interwork_sns']['use_yn'] == 'Y'){
						} else {
							echo 'hidden : true,';
						}
					?>
					title: '<?=_text('MN02324')?>',
					url: '/pages/sns/sns_list.php',
					leaf: true
				}
//				,{
//					//text: 'Loudness',
//					text: '<span style="position:relative;top:3px;"><i class="fa fa-volume-up" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02250')?>',
//					<?php
//						if( $interwork_loudness == 'Y'){
//						} else {
//							echo 'hidden : true,';
//						}
//					?>
//					title: '<?=_text('MN02250')?>',
//					url: '/pages/menu/config/loudness/loudness_configuration.php',
//					leaf: true
//				}
				,{
					hidden: true,
					id:'contents_management',
					text: '콘텐츠 관리',
					children: [{
						// text: 콘텐츠 삭제관리
						text: '미디어 삭제 관리',
						url: '/pages/menu/contents_management/contents_delete.php',
						leaf: true
					},{
						//text:'삭제정보',
						hidden: true,
						text: '<?=_text('MN00134')?>',
						url: '/pages/statistics/delete_info_online.php',
						leaf: true
					}]
				},{
					hidden: true,
					text: '삭제관리',
					url: '/pages/menu/contents_management/contents_delete_list.php',
					leaf: true
				},{
					text: '<span style="position:relative;top:3px;"><i class="fa fa-clock-o" style="font-size:18px;"></i></span>&nbsp;'+_text('MN02370'),
					title:'인제스트 스케쥴 관리',
					hidden: true,
					url: '/pages/menu/config/ingest_schedule/ingest_schedule.php',
					leaf: true
				},
				{
					text: '<span style="position:relative;top:3px;"><i class="fa fa-clock-o" style="font-size:18px;"></i></span>&nbsp;ODS_L Test',
					url: '/pages/archive/ods_l_test/ods_l_test_page.php',
					<?php 
						if($arr_sys_code['interwork_oda_ods_l']['use_yn'] == 'Y' && $arr_sys_code['interwork_oda_ods_l']['ref5'] == 'test' ){
						} else {
							echo 'hidden : true,';
						}
					?>
					leaf: true
				},
				{
					text: '<span style="position:relative;top:3px;"><i class="fa fa-folder-open-o" style="font-size:18px;"></i></span>&nbsp;와치폴더 메타관리',
					url: '/pages/menu/watchfolder/watchfolder_mgmt.php',
					<?php 
						if($arr_sys_code['interwork_watch_confirm']['use_yn'] == 'Y'){
						} else {
							echo 'hidden : true,';
						}
					?>
					leaf: true
				}]
			}
		},{
			region: 'center',
			id: 'admin_work_contain',
			//title: '&nbsp;',
			headerAsText: false,
			border : false,
			layout: 'fit'
		}]

		Ariel.Nps.WorkManagement.superclass.initComponent.call(this);
	}
});
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = $_SESSION['user']['user_id'];


?>

Ariel.Nps.SystemManagement = Ext.extend(Ext.Panel, {
	layout: 'border',
	autoScroll: true,
	border:false,
	initComponent: function(config) {
		Ext.apply(this, config || {});
		var that = this;
		this.items= [{
			id: 'syste_dev',
			xtype: 'treepanel',
			region: 'west',
			//>>title: 'Configuration',
			//title: '<?=_text('MN02194')?>',
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
								Ext.getCmp('admin_dev').removeAll(true);
								Ext.getCmp('admin_dev').add(Ext.decode(response.responseText));
								//Ext.getCmp('admin_dev').setTitle(node.attributes.text);
								Ext.getCmp('admin_dev').setTitle(node.attributes.title);

								Ext.getCmp('admin_dev').doLayout();
							} catch (e) {
								Ext.Msg.alert(e['name'], opts.url+'<br />'+e['message']);
							}
						}
					});
				}
			},
			root: {
				//>> text: 'Configuration',
				text: '<?=_text('MN02194')?>',
				expanded: false,
				children: [{
					//>>text: '시스템 메타데이터',
					text: '<span style="position:relative;top:3px;"><i class="fa fa-table" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN00208')?>',
					title: '<?=_text('MN00208')?>',
					url: '/pages/menu/config/custom/ContentMetadataPanel.js',
					leaf: true
				},
				{
					//>>text: '모듈타입 설정',
					// 작업 유형 설정
					//수정일 : 2011.12.11
					//작성자 : 김형기
					//내용 : 용어 변경(TASK TYPE -> 작업 유형)
					//MN01027 작업유형 설정
					text:'<span style="position:relative;top:3px;"><i class="fa fa-code" style="font-size:18px;"></i></span> <?=_text('MN02040')?>',
					title: '<?=_text('MN02040')?>',
					url: '/javascript/ext.ux/Ariel.ModuleSet.js',
					leaf: true
				},
				
				{
					//System workflow
					text: '<span style="position:relative;top:3px;"><i class="fa fa-server" style="font-size:18px;"></i></span> <?=_text('MN02322')?>',
					title: '<?=_text('MN02322')?>',
					url: '/javascript/ext.ux/Ariel.WorkflowSet.php?workflow_type=s',
					leaf: true
				},
				
				{
					//$$ 시스템 코드 관리
					hidden: true,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-cog" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN01009')?>',
					title: '<?=_text('MN01009')?>',
					url: '/pages/menu/config/code/code.js',
					leaf: true
				},{
					//$$ 시스템 코드 관리
					hidden: false,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-cog" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN01009')?>',
					title: '<?=_text('MN01009')?>',
					url: '/pages/menu/config/code/code_new.js',
					leaf: true
				},{
					//$$ 시스템 기능 사용 설정
					hidden: false,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-check-square-o" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02204')?>',
					title: '<?=_text('MN02204')?>',
					url: '/pages/menu/config/code/system_code.js',
					leaf: true
				},{
					//$$ 권한 관리 
					text: '<span style="position:relative;top:3px;"><i class="fa fa-book" style="font-size:18px;"></i></span>&nbsp;권한 관리',
					title: '권한 관리',
					url: '/pages/menu/config/custom/permission.js',
					leaf: true
				}]
			}
		},{
			region: 'center',
			id: 'admin_dev',
			//title: '&nbsp;',
			border : false,
			headerAsText: false,
			layout: 'fit'
		}]

		Ariel.Nps.SystemManagement.superclass.initComponent.call(this);
	}
});
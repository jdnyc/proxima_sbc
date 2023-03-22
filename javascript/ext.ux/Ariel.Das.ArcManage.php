<?php

use Proxima\core\Session;
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

Session::init();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$user_id = $_SESSION['user']['user_id'];
$is_admin = $_SESSION['user']['is_admin'];
$groups = $_SESSION['user']['groups'];
?>
Ext.ns('Ariel.Das');
Ariel.Das.ArcManage = Ext.extend(Ext.Panel, {
	layout: 'border',
	autoScroll: true,

	initComponent: function(config) {
		Ext.apply(this, config || {});
		var that = this;

		this.items=[{
			xtype: 'treepanel',
			region: 'west',
			title: '아카이브 관리',
			width: 280,
			boxMinWidth: 280,
			split: true,
			collapsible: true,
			autoScroll: true,
			rootVisible :false,
			cls:'tree_menu',
			lines:false,
			listeners: {
				afterrender: function(self){
					var node = self.getRootNode().findChild('id', '<?=$_GET['select']?>');
					if (node)
					{
						node.fireEvent('click', node);
					}
				},
				click: function(node, e){
					var url = Script.numberingScript(node.attributes.url);

					if(!url) return;
					Ext.Ajax.request({
						url: url,
						timeout: 0,
						callback: function(opts, success, response) {
							try {                                
								var obj = Ext.decode(response.responseText);
							
								Ext.getCmp('admin_archive_panel').removeAll(true);
								Ext.getCmp('admin_archive_panel').add(obj);
								//Ext.getCmp('admin_archive_panel').setTitle(node.attributes.title);
		
								Ext.getCmp('admin_archive_panel').doLayout();
							} catch (e) {
								Ext.Msg.alert(e['name'], opts.url+'<br />'+e['message']);
							}
						}
					});
				}
			},
			root: {
				icon:'/led-icons/folder.gif',
				text: '아카이브 관리',
				expanded: true,
				children: [{                    
                    hidden: true,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-calendar" style="font-size:18px;"></i></span>&nbsp;아카이브 기간정책 설정',
					title: '아카이브 설정',
					icon:'/led-icons/folder.gif',
					url: '/pages/menu/archive_management/archive_treegrid.php',
					//hidden: true,
					leaf: true
				},{
                    hidden: true,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-calendar" style="font-size:18px;"></i></span>&nbsp;리스토어 기간정책 설정',
					title: '리스토어 설정',
					icon:'/led-icons/folder.gif',
					url: '/pages/menu/archive_management/restore_treegrid.php',
					leaf: true
				},{
					text: '<span style="position:relative;top:3px;"><i class="fa fa-pencil-square-o" style="font-size:18px;"></i></span>&nbsp;아카이브/리스토어 요청 관리',
					title: '아카이브/리스토어 요청 관리',
					icon: '/led-icons/folder.gif',
					url: '/pages/menu/archive_management/request_manage.js',
					leaf: true
				},{
                    //hidden: true,
					text: '<span style="position:relative;top:3px;"><i class="fa fa-pencil-square-o" style="font-size:18px;"></i></span>&nbsp;아카이브 테이프 관리',
					title: '아카이브 테이프 관리',				
					url: '/pages/menu/archive_management/offlineTapeList.js',
					leaf: true
				},{
					//text: '미디어 삭제 관리',
                    text: '<span style="position:relative;top:3px;"><i class="fa fa-minus-circle" style="font-size:18px;"></i></span>&nbsp;<?=_text('MN02044')?>',
					title: '<?=_text('MN02044')?>',
					url: '/pages/menu/contents_management/contents_delete.php',
					leaf: true
				},{
					text: '<span style="position:relative;top:3px;"><i class="fa fa-ban" style="font-size:18px;"></i></span>&nbsp;사용금지 관리',
                    title: '사용금지 관리',
                    url: '/custom/ktv-nps/js/archiveManagement/useProhibitListGrid.js',
                    leaf: true
                }]
			}
		},{
			region: 'center',
			id:'admin_archive_panel',
			title: '&nbsp;',
			layout: 'fit'
		}];

		Ariel.Das.ArcManage.superclass.initComponent.call(this);
	}
});
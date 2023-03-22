<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

//checkLogin();//로그인 체크
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>등록 대기</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<link rel="stylesheet" type="text/css" href="/ext/resources/css/ext-all-das.css" />

	<style type="text/css">
		.modified{ background-color:#FFFFBB; }
		.reaccept{ background-color:#FFC519; }
		.refuse{ background-color:#f19898; }

	#images-view .x-panel-body{
		background: white;
		font: 11px Arial, Helvetica, sans-serif;
	}
	#images-view .thumb{
		background: #dddddd;
		padding: 3px;
	}
	#images-view .thumb img{
		height: 60px;
		width: 80px;
	}
	#images-view .thumb-wrap{
		float: left;
		margin: 4px;
		margin-right: 0;
		padding: 5px;
	}
	.comments{
		background-color: blue;
	}
	#images-view .thumb-wrap span{
		display: block;
		overflow: hidden;
		text-align: center;
	}

	#images-view .x-view-over{
		border:1px solid #dddddd;
		background: #efefef url(../../resources/images/default/grid/row-over.gif) repeat-x left top;
		padding: 4px;
	}

	#images-view .x-view-selected{
		background: #eff5fb url(images/selected.gif) no-repeat right bottom;
		border:1px solid #99bbe8;
		padding: 4px;
	}
	#images-view .x-view-selected .thumb{
		background:transparent;
	}

	#images-view .loading-indicator {
		font-size:11px;
		background-image:url('../../resources/images/default/grid/loading.gif');
		background-repeat: no-repeat;
		background-position: left;
		padding-left:20px;
		margin:10px;
	}
	</style>
</head>
<body>
	<script type="text/javascript" src="/ext/adapter/ext/ext-base.js"></script>
	<script type="text/javascript" src="/ext/ext-all-debug.js"></script>
	<script type="text/javascript" src="/javascript/lang.php"></script>

	<script type="text/javascript" src="/ext/examples/ux/BufferView.js"></script>

	<script type="text/javascript" src="/flash/flowplayer/example/flowplayer-3.2.4.min.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.TreeCombo.js"></script>
	<script type="text/javascript" src="/javascript/ext.ux/Ext.ux.grid.PageSizer.js"></script>
	<script type="text/javascript" src="/pages/waitlist/wait_panel.php"></script>

	<script type="text/javascript">

	Ext.onReady(function(){

		new Ext.Viewport({
			title: '등록대기리스트',
			border:	false,
			layout:	'fit',
			items: [{
				id: 'wait_tab',
				xtype: 'tabpanel',
				activeTab: 0,
				plain: true,
				defaults: {
					autoScroll: true
				},
				listeners: {
					afterrender: function(self){
						//console.log('afterrender');
					},
					close: function(self){
						//console.log('close');
					},
					beforedestroy: function(self){
						//console.log('deforedestory');
					},
					hide: function(self){
						//console.log('hide');
					},
					show: function(self){
						//console.log('show');
					},
					tabchange: function(self, p){
						var s = Ext.getCmp('wait_tab').getActiveTab().get(0).getStore();
						s.reload();
					},
					beforetabchange: function(self, newTab, currentTab){
						if (currentTab)
						{
							//console.log(currentTab.initialConfig.items.getTopToolbar());
							//currentTab.initialConfig.items.stopAutoReload();
						//	currentTab.initialConfig.items.getTopToolbar().get(15).setValue(false);
						}
					}
				},
				items: [{
					title: '등록대기',
					layout: 'fit',
					items: new Ariel.Wait.Panel({
						store : new Ext.data.JsonStore({
							url: '/store/get_content.php',
							totalProperty: 'total',
							idProperty: 'content_id',
							root: 'results',
							fields: [
								{name: 'category_id'},
								{name: 'category_full_path'},
								{name: 'bs_content_id'},
								{name: 'ud_content_id'},
								{name: 'content_id'},
								{name: 'title'},
								{name: 'is_deleted'},
								{name: 'reg_user_id'},
								{name: 'expired_date'},
								{name: 'last_modified_date'},
								{name: 'created_date'},
								{name: 'status'},
								{name: 'last_accessed_date'},
								{name: 'parent_content_id'}
							],
							listeners: {
								load: function(self){

								},
								beforeload: function(self, opts){
									var g = Ext.getCmp('wait_tab').getActiveTab().get(0);

									self.baseParams = {
										list_type: 'wait',
										limit: 20,
										start: 0
									}
								}
							}
						}),
						columns: [
							new Ext.grid.RowNumberer(),
							{header: 'content_id', dataIndex: 'content_id' , hidden: true },
							{header:'제목', dataIndex: 'title' },
							{header:'등록자', dataIndex: 'reg_user_id' },
							{header:'등록일', dataIndex: 'created_date'  }
						]
					})
				}]
			}]
		});
	});

	</script>
</body>
</html>
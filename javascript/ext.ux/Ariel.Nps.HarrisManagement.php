<?php
/**Harris 목록화면 좌측 해리스 서버목록 갱신관련 부분 */
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$user_id = $_SESSION['user']['user_id'];

$server_all = $db->queryAll("select server_uid,display_name
						  from harris_setting 
						  order by server_uid");
?>

Ariel.Nps.HarrisManagement = Ext.extend(Ext.Panel, {
	layout: 'border',
	autoScroll: true,
	border:false,

	initComponent: function(config) {
		Ext.apply(this, config || {});
		var that = this;

		this.items= [{
			margins: '0 0 0 0',
			xtype: 'panel',
			collapsible: true,
			collapseMode: 'header',
			bodyStyle:{
				"background-color":"#FFFFFF"
			},
			plugins: [Ext.ux.PanelCollapsedTitle],
			title: '<span class="user_span"><span class="icon_title"><i class="fa fa-server"></i></span><span class="user_title">'+_text('MN02546')+'</span></span>',
			region: 'west',
			width: 250,
			minWidth : 250,
			maxWidth : 300,
			border:false,
			split: true,
			layout: 'fit',
			id: 'harris_server',
			listeners : {
				afterrender : function(self){
				},
				collapse : function(self){
					self.setTitle(_text('MN02546'));
				},
				beforeexpand : function(self)
				{
					self.setTitle('<span class="user_span"><span class="icon_title"><i class="fa fa-server"></i></span><span class="user_title">'+_text('MN02546')+'</span><span title="FTP storage settings" class="icon_title2" onclick="show_harris_storage();"><i class="fa fa-cog"></i></span></span>');
				},
				bodyresize: function( p, width, height ){
				}
			},
			items:[{
				xtype: 'tabpanel',
				cls: 'proxima_tabpanel_customize proxima_customize proxima_customize_progress',
				activeTab: 0,
				border:false,
				items: [
			<?php
			$agency_tree_panel_arr = array();
			foreach($server_all as $sa) {
				$server_id = $sa['server_uid'];
				$server_nm = $sa['display_name'];
				$agency_tree_panel_arr[] = "
				{
					xtype: 'treepanel',
					id: 'harris_agency_tree_".$server_id."',
					rootVisible: true,
					title: '".$server_nm."',
					server_num: ".$server_id.",
					border:false,
					boxMinWidth: 250,
					autoScroll: true,
					listeners: {
						click: function(node, e){
							var url = node.attributes.url;

							if(node.attributes.text == 'All' && (typeof node.attributes.agency == 'undefined' || node.attributes.agency == null)) {
								var searchVal = Ext.getCmp('search_harris_agency_".$server_id."').getValue().trim();
								if(searchVal != '') {
									url = url + '&allAgency=' + searchVal;
								}
							}

							if(!url) return;

							Ext.Ajax.request({
								url: url,
								callback: function(opts, success, response){
									try
									{
										Ext.getCmp('harris_list').removeAll(true);
										Ext.getCmp('harris_list').add(Ext.decode(response.responseText));
										Ext.getCmp('harris_list').setTitle('<span class=\"user_span\"><span class=\"icon_title\"><i class=\"\"></i></span><span class=\"main_title_header\">'+node.ownerTree.title+' - '+node.attributes.text+'</span></span>');

										Ext.getCmp('harris_list').doLayout();
									}
									catch (e)
									{
										alert(e['name']+': '+e['description']);
									}
								}
							});
						},
						contextmenu: function(node, e){
							node.select();
							var c = node.getOwnerTree().contextMenu;
							c.contextNode = node;
							c.showAt(e.getXY());
						},
						afterrender: function(self) {
							var root = self.getRootNode();
						}
					},
					tbar:{
						layout: 'hbox',
						items:[{
							xtype: 'textfield',
							id: 'search_harris_agency_".$server_id."',
							serverId: '".$server_id."',
							flex:1,
							enableKeyEvents: true,
							emptyText: _text('MSG02534'),
							listeners: {
								keyup: function(self, e) {
									var treePanel = Ext.getCmp('harris_agency_tree_".$server_id."');
									var treeLoader = treePanel.getLoader();
									treeLoader.load(treePanel.getRootNode());
								}
							}
						}]
					},
					root:{
						text: 'All',
						expanded: true,
						icon: '',
						url: '/pages/menu/harris/list_data.php?server=".$server_id."'
					},
					loader: new Ext.tree.TreeLoader({
						url: '/pages/menu/harris/getHarrisAgency.php',
						requestMethod: 'GET',
						listeners: {
							beforeload: function (treeLoader, node, callback){
								treeLoader.baseParams.serverId = $server_id;
								treeLoader.baseParams.search = Ext.getCmp('search_harris_agency_".$server_id."').getValue();
							},

							load: function (treeLoader, node, callback){
								Ext.getCmp('harris_agency_tree_".$server_id."').getRootNode().expand();
							}
						}
					}),
					contextMenu: new Ext.menu.Menu({
						items: [{
							cmd: 'refresh_agency',
							icon: '/led-icons/arrow_refresh.png',
							//text: 'Agency 목록 재작성'
							text: _text('MN02547')
						}],
						listeners: {
							itemclick: {
								fn: function(item, e){
									var agency = Ext.getCmp('harris_agency_tree_".$server_id."').getSelectionModel().getSelectedNode().attributes.text,
										alertMsg;
									if (agency == 'All')
									{
										alertMsg = _text('MSG02521');
									}
									else
									{
										alertMsg = _text('MSG02522')+'('+agency+')';
									}

									Ext.Msg.show({
										title: _text('MN00021'),//'Warning',
										msg: alertMsg,
										icon: Ext.Msg.WARNING,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId){
											if (btnId == 'ok')
											{
												var r = item.parentMenu.contextNode.getOwnerTree();
												switch (item.cmd)
												{
													case 'refresh_agency':
														r.send_refresh_agency(item.parentMenu.contextNode);
													break;
												}
											}
										}
									});
								},
								scope: this
							}
						}
					}),

					send_refresh_agency: function(node){
						var agency = node.attributes.text;
						var server = node.ownerTree.title;
						Ext.Ajax.request({
                            url: '/interface/harris/php/harris_refresh_agency_list.php',
							params: {
								agency: agency,
								server: server
							},
							callback: function(opts, success, response){
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if (!r.success)
										{
											//Error
											Ext.Msg.alert(_text('MN00022'), r.msg);
										}
										else
										{
											Ext.Msg.alert( _text('MN00023'), _text('MSG02523'));
										}
									}
									catch(e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
								}
							}
						});
					}
				}
				";
			}
			echo implode(',', $agency_tree_panel_arr);
			?>
				]
			}]
		},{
			region: 'center',
			title: '&nbsp;',
			id: 'harris_list',
			cls: 'grid_title_customize proxima_customize',
			layout:'fit'
		}]

		Ariel.Nps.HarrisManagement.superclass.initComponent.call(this);
	}
});

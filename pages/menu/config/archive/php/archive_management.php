<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");
fn_checkAuthPermission($_SESSION);
$user_id = $_SESSION['user']['user_id'];

?>
(function(){

	function archive_setup_win(ud_content_id, that) {
		var win = new Ext.Window({
			width: 400,
			height: 330,
			layout: 'fit',
			modal: true,
			title : _text('MN01064'),
			cls: 'change_background_panel',
			buttonAlign: 'center',
			items: [{
				id: 'archive_setup_form',
				xtype: 'form',
				url: '/pages/menu/config/archive/php/set_up_config.php',
				border: false,
				padding: 5,
				defaults: {
					labelWidth: 100
				},
				items: [{
					xtype:'hidden',
					name: 'ud_content_id',
					value: ud_content_id
				},{
					xtype: 'hidden',
					name: 'action',
					value: 'archive'
				},{
					xtype: 'treecombo',
					id: 'archive_setup_category',
					anchor: '95%',
					fieldLabel: _text('MN00387'),
					autoScroll: true,
					pathSeparator: ' > ',
					rootVisible: false,
					treeWidth: 300,
					name: 'c_category_id',
					loader: new Ext.tree.TreeLoader({
						url: '/store/get_archive_categories.php',
						baseParams: {
							ud_content_id : ud_content_id
						}
					}),
					root: new Ext.tree.AsyncTreeNode({
						id: '0',
						text: 'Archive',
						expanded: true
					})
				},{
					xtype:'combo',
					fieldLabel: _text('MN01058'),
					name: 'is_archive',
					anchor: '95%',
					mode: 'local',
					forceSelection: true,
					editable : false,
					allowBlank: false,
					displayField: 'name',
					valueField: 'value',
					hiddenName: 'is_archive',
					triggerAction: 'all',
					store: new Ext.data.ArrayStore({
						fields : [ 'name', 'value' ],
						data : [ [_text('MN00001'), 'Y'], [_text('MN00002'), 'N'] ]
					}),
					listeners: {
						select: function( combo, record, index) {
							var form = combo.ownerCt;
							if(record.data.value == 'Y') {
								form.items.items[4].setDisabled(false);
								form.items.items[5].setDisabled(false);
								form.items.items[6].setDisabled(false);
								form.items.items[7].setDisabled(false);
								form.items.items[8].setDisabled(false);
							} else {
								form.items.items[4].setDisabled(true);
								form.items.items[5].setDisabled(true);
								form.items.items[6].setDisabled(true);
								form.items.items[7].setDisabled(true);
								form.items.items[8].setDisabled(true);
							}
						}
					}
				},{
					xtype: 'combo',
					readOnly: false,
					anchor: '95%',
					triggerAction: 'all',
					fieldLabel: _text('MN01057'),
					allowBlank: false,
					name : 'archive_group',
					editable : false,
					forceSelection: true,
					displayField : 'name',
					valueField : 'code',
					hiddenName: 'archive_group',
					store : new Ext.data.JsonStore({
						url:'/store/get_archive_group.php',
						autoLoad: true,
						root: 'data',
						fields: [
							'code','name'
						]
					})
				},{
					xtype: 'numberfield',
					fieldLabel: _text('MN01066'),
					anchor: '95%',
					maxValue: 100,
					minValue: 0,
					allowBlank: false,
					name: 'archive_priority'
				},{
					xtype: 'numberfield',
					fieldLabel: _text('MN01059'),
					anchor: '95%',
					allowBlank: false,
					minValue: 0,
					maxValue: 9999,
					name: 'archive_time'
				},{
					xtype: 'numberfield',
					fieldLabel: _text('MN01060'),
					anchor: '95%',
					minValue: 0,
					maxValue: 9999,
					allowBlank: false,
					name: 'storage_delete_time'
				},{
					xtype: 'numberfield',
					fieldLabel: _text('MN01062'),
					anchor: '95%',
					allowBlank: false,
					minValue: 0,
					maxValue: 9999,
					name: 'archive_delete_time'
				}]
			}],
			buttons: [{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
				scale: 'medium',
				handler: function(b, e) {
					var archive_form = Ext.getCmp('archive_setup_form').getForm();
					var category = Ext.getCmp('archive_setup_category').treePanel.getSelectionModel().getSelectedNode();

					if(Ext.isEmpty(category)) {
						Ext.Msg.alert(_text('MN00023'), _text('MSG00122'));
					}

					archive_form.submit({
						params: {
							category: category.attributes.id
						},
						success: function(form, action) {
							if(action.result.success == false) {
								Ext.Msg.alert(_text('MN00022'), _text('MSG00085'));
							} else {
								Ext.Msg.alert(_text('MN00023'), action.result.msg);
								b.ownerCt.ownerCt.close();
								that.refresh(that);
							}
						},
						failure: function(form, action) {
							switch (action.failureType) {
								case Ext.form.Action.CLIENT_INVALID :
									Ext.Msg.alert(_text('MN00023'), _text('MSG00125'));
								break;
								case Ext.form.Action.CONNECT_FAILURE :
									Ext.Msg.alert(_text('MN00023'), 'CONNECT_FAILURE');
								break;
								case Ext.form.Action.SERVER_INVALID :
									Ext.Msg.alert(_text('MN00023'), 'SERVER_INVALID');
								break;
							}
						}
					});
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function (b, e) {
					b.ownerCt.ownerCt.close();
				}
			}]
		}).show();

		return win;
	}

	function restore_setup_win(ud_content_id, that) {
		var win = new Ext.Window({
			width: 400,
			height: 200,
			layout: 'fit',
			modal: true,
			title : _text('MN01065'),
			cls: 'change_background_panel',
			buttonAlign: 'center',
			items: [{
				id: 'restore_setup_form',
				xtype: 'form',
				url: '/pages/menu/config/archive/php/set_up_config.php',
				border: false,
				padding: 5,
				defaults: {
					labelWidth: 100
				},
				items: [{
					xtype:'hidden',
					name: 'ud_content_id',
					value: ud_content_id
				},{
					xtype: 'hidden',
					name: 'action',
					value: 'restore'
				},{
					xtype: 'treecombo',
					id: 'restore_setup_category',
					anchor: '95%',
					fieldLabel: _text('MN00387'),
					autoScroll: true,
					pathSeparator: ' > ',
					rootVisible: false,
					treeWidth: 300,
					name: 'c_category_id',
					loader: new Ext.tree.TreeLoader({
						url: '/store/get_archive_categories.php',
						baseParams: {
							ud_content_id : ud_content_id
						}
					}),
					root: new Ext.tree.AsyncTreeNode({
						id: '0',
						text: 'Archive',
						expanded: true
					})
				},{
					xtype: 'numberfield',
					minValue: 0,
					maxValue: 100,
					anchor: '95%',
					allowBlank: false,
					fieldLabel: _text('MN01067'),
					name: 'restore_priority'
				/*	listeners: {
						render: function(c) {
							new Ext.ToolTip({
								target: c.getEl(),
								html: _text('MSG01019')
							});
						}
					}
				*/
				},{
					xtype: 'numberfield',
					anchor: '95%',
					allowBlank: false,
					minValue: 0,
					maxValue: 9999,
					fieldLabel: _text('MN01061'),
					name: 'restore_delete_time'
				}]
			}],
			buttons: [{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
				scale: 'medium',
				handler: function(b, e) {
					var restore_form = Ext.getCmp('restore_setup_form').getForm();
					var category = Ext.getCmp('restore_setup_category').treePanel.getSelectionModel().getSelectedNode();

					if(Ext.isEmpty(category)) {
						Ext.Msg.alert(_text('MN00023'), _text('MSG00122'));
					}

					restore_form.submit({
						params: {
							category: category.attributes.id
						},
						success: function(form, action) {
							if(action.result.success == false) {
								Ext.Msg.alert(_text('MN00022'), _text('MSG00085'));
							} else {
								Ext.Msg.alert(_text('MN00023'), action.result.msg);
								b.ownerCt.ownerCt.close();
								that.refresh(that);
							}
						},
						failure: function(form, action) {
							switch (action.failureType) {
								case Ext.form.Action.CLIENT_INVALID :
									Ext.Msg.alert(_text('MN00023'), _text('MSG00125'));
								break;
								case Ext.form.Action.CONNECT_FAILURE :
									Ext.Msg.alert(_text('MN00023'), 'CONNECT_FAILURE');
								break;
								case Ext.form.Action.SERVER_INVALID :
									Ext.Msg.alert(_text('MN00023'), 'SERVER_INVALID');
								break;
							}
						}
					});
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function (b, e) {
					b.ownerCt.ownerCt.close();
				}
			}]
		}).show();

		return win;
	}

	Ext.ns('Ariel.ArchiveConfig');

	Ariel.ArchiveConfig.TreePanel = Ext.extend(Ext.Panel, {
		border: false,
		cls: 'proxima_customize_gridtree',
		layout: 'fit',
		listeners: {
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			var that = this;

			this.treegrid = this.buildTreeGrid(this.ud_content_id, that);

			this.refresh = function(that){
				that.treegrid.getLoader().on("beforeload", function(treeLoader, node){
				});

				that.treegrid.getLoader().load( that.treegrid.getRootNode() );
			}

			this.items = [
				this.treegrid
			];

			Ariel.ArchiveConfig.TreePanel.superclass.initComponent.call(this);
		},

		buildTreeGrid: function(ud_content_id, that) {
			return new Ext.ux.tree.TreeGrid({
				id: 'archive_config_tree_grid_'+ud_content_id,
				layout : 'fit',
				border: false,
				columns : [
					{header : _text('MN00387'), dataIndex : 'category_title', width:280},
				//	{header : 'Category ID', dataIndex : 'category_id', width:80, hidden:true},
					{header : _text('MN01057'), dataIndex : 'archive_group', width : 130},
					{header : _text('MN01058'), dataIndex : 'is_archive', width: 100},
					{header : _text('MN01066'), dataIndex : 'archive_priority', width: 100},
					{header : _text('MN01059'), dataIndex : 'archive_time', width: 165},
					{header : _text('MN01060'), dataIndex : 'storage_delete_time', width: 165},
					{header : _text('MN01062'), dataIndex : 'archive_delete_time', width: 165},
					{header : _text('MN01067'), dataIndex : 'restore_priority', width: 120},
					{header : _text('MN01061'), dataIndex : 'restore_delete_time', width: 165}
				],
				loader: new Ext.tree.TreeLoader({
					listeners: {
						load: function( self,  node, response ){
							var tab_id = 'archive_config_tree_grid_'+ud_content_id;
							var archive_config_tree_grid_element = document.getElementById(tab_id).getElementsByClassName('x-treegrid-col');
							for(var i = 0; i<archive_config_tree_grid_element.length; i++){
								var node_i = archive_config_tree_grid_element[i];
								if(node_i.childNodes.length == 4){
									node_i.className = 'grid_tree_remove_border x-treegrid-col';
								}
							}
						}
					},
					dataUrl : '/pages/menu/config/archive/php/get_tree_grid_data.php?ud_content_id='+ud_content_id				
				}),
				sm: new Ext.grid.RowSelectionModel({
					singleSelect : true
				}),
				tbar : [{
					xtype : 'button',
					//text : _text('MN01064'),
					//icon : '/led-icons/drive_disk.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span class="fa-stack" title="'+_text('MN01064')+'" style="position:relative;top:1px;"><i class="fa fa-database fa-stack-1x" style="margin-top: 2px;color: white;font-size: 13px;"></i><i class="fa fa-arrow-down fa-stack" style="color:red;width: 1px !important;margin-right: 9px;"></i></span>',
					handler : function(){
						archive_setup_win(ud_content_id, that);
					}
				},{
					xtype : 'button',
					//text : _text('MN01065'),
					//icon : '/led-icons/drive_disk.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span class="fa-stack" title="'+_text('MN01065')+'" style="position:relative;top:1px;"><i class="fa fa-database fa-stack-1x" style="margin-top: 2px;color: white;font-size: 13px;"></i><i class="fa fa-arrow-up fa-stack" style="color:red;width: 1px !important;margin-right: 9px;"></i></span>',
					handler : function(){
						restore_setup_win(ud_content_id, that);
					}
				},'-',{
					xtype : 'button',
					//text : _text('MSG02112'),//'Sync Volume Information',
					//icon : '/led-icons/arrow_refresh.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MSG02112')+'"><i class="fa fa-hdd-o" style="font-size:13px;color:white;"></i></span>',
					handler : function(){
						Ext.Msg.show({
							icon: Ext.Msg.QUESTION,
							title: '<?=_text('MN00024')?>',
							msg: _text('MSG02113'),//'This will sync volume information from FlashNet. This may take a while.',
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'cancel') return;

								Ext.Ajax.request({
									url: '/pages/menu/config/archive/php/sync_sgl_volume.php',
									callback: function(self, success, response){
										if (success) {
											try {
												var result = Ext.decode(response.responseText);
												Ext.Msg.alert(_text('MN00023'), result.msg );
											} catch (e) {
												Ext.Msg.alert(e['name'], e['message']);
											}
										} else {
											//>>Ext.Msg.alert('서버 오류', response.statusText);
											Ext.Msg.alert(_text('MN00022'), response.statusText);
										}
									}
								});
							}
						});
					}
				},{
					xtype : 'button',
					//text : '<?=_text('MN01073')?>',
					//icon : '/led-icons/arrow_refresh.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN01073')+'"><i class="fa fa-users" style="font-size:13px;color:white;"></i></span>',
					handler : function() {
						Ext.Msg.show({
							icon: Ext.Msg.QUESTION,
							title: '<?=_text('MN00024')?>',
							msg: '<?=_text('MSG01016')?>',
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'cancel') return;

								Ext.Ajax.request({
									url: '/pages/menu/config/archive/php/sync_sgl_group.php',
									callback: function(self, success, response){
										if (success) {
											try {
												var result = Ext.decode(response.responseText);
												Ext.Msg.alert(_text('MN00023'), result.msg );
											} catch (e) {
												Ext.Msg.alert(e['name'], e['message']);
											}
										} else {
											//>>Ext.Msg.alert('서버 오류', response.statusText);
											Ext.Msg.alert(_text('MN00022'), response.statusText);
										}
									}
								});
							}
						});
					}
				},{
					xtype : 'button',
					hidden : true,
					//text : _text('MN00139'),
					//icon : '/led-icons/arrow_refresh.png',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
					handler : function(){
						that.refresh(that);
					}
				}]
				
			});
		}
	});



	return {
		xtype: 'tabpanel',
		cls: 'proxima_tabpanel_customize',
		activeTab: 0,
		border: false,
		items: [
		<?php
			$tabs = $db->queryAll("
						SELECT *
						FROM BC_UD_CONTENT
						ORDER BY SHOW_ORDER
					");
			foreach ($tabs as $tab) {
				$_tabs[] = "{
						title: '".$tab['ud_content_title']."',
						id: ".$tab['ud_content_id'].",
						ud_content_id: ".$tab['ud_content_id'].",
						layout: 'fit',
						items: new Ariel.ArchiveConfig.TreePanel({ud_content_id : ".$tab['ud_content_id']."})
				}";
			}

			echo join(", \n", $_tabs);
		?>
		]
	};

})()
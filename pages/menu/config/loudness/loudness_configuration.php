<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
$user_id = $_SESSION['user']['user_id'];

?>
(function(){

	function loudness_set_up (ud_content_id, that) {
		var win = new Ext.Window({
			width: 400,
			height: 200,
			layout: 'fit',
			modal: true,
			title : _text('MN02250'),
			items: [{
				xtype: 'form',
				name: 'loudness_set_up_config',
				url: '/pages/menu/config/loudness/php/set_up_config.php',
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
					value: 'add'
				},{
					xtype: 'treecombo',
					anchor: '95%',
					fieldLabel: _text('MN00387'),
					autoScroll: true,
					pathSeparator: ' > ',
					rootVisible: false,
					treeWidth: 300,
					id:'loudness_category',
					name: 'c_category_id',
					loader: new Ext.tree.TreeLoader({
						url: '/pages/menu/config/loudness/php/get_categories.php',
						baseParams: {
							ud_content_id : ud_content_id
						}
					}),
					root: new Ext.tree.AsyncTreeNode({
						id: '0',
						text: 'NPS',
						expanded: true
					})
				},{
					xtype: 'checkboxgroup',
					hideLabel: true,
					columns: 1,
					vertical: true,
					items: [
						{boxLabel: _text('MN02270'), inputValue: 'Y', name: 'is_loudness', id: 'is_loudness', checked: true},
						{boxLabel: '1', inputValue: 'Y', name: 'is_correct', id: 'is_correct', checked: true, hidden: true},
						{boxLabel: _text('MN02271'), inputValue: 'Y', name: 'apply_child', id: 'apply_child', checked: true}
					]
				}]
			}],
			buttons: [{
				scale: 'medium',
				text: _text('MN00046'),
				handler: function(b, e) {
					var loudness_form = b.ownerCt.ownerCt.find('name', 'loudness_set_up_config')[0];
					var category = loudness_form.find('name', 'c_category_id')[0].treePanel.getSelectionModel().getSelectedNode();

					if(Ext.isEmpty(category)) {
						Ext.Msg.alert(_text('MN00023'), _text('MSG00122'));
					}

					loudness_form.getForm().submit({
						params: {
							category: category.attributes.id,
							user_id: '<?=$user_id?>'
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
				text: _text('MN00004'),
				scale: 'medium',
				handler: function (b, e) {
					b.ownerCt.ownerCt.close();
				}
			}]
		}).show();

		return win;
	}
	
	Ext.ns('Ariel.LoudnessConfig');

	Ariel.LoudnessConfig.TreePanel = Ext.extend(Ext.Panel, {
		border: false,
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

			Ariel.LoudnessConfig.TreePanel.superclass.initComponent.call(this);
		},

		buildTreeGrid: function(ud_content_id, that) {
			return new Ext.ux.tree.TreeGrid({

				layout : 'fit',
				columns : [
					{header : _text('MN00387'), dataIndex : 'category_title', width:280},
					{header : 'Category ID', dataIndex : 'category_id', width:80, hidden:true},
					{header : _text('MN02270'), dataIndex : 'is_loudness', width : 130,
						tpl: new Ext.XTemplate('{is_loudness:this.render_enable}', {
							render_enable: function(v) {
								switch(v) {
									case 'N' :
										v = '';
									break;
									default :
										v = v;
									break;	
								}
								return v;
							}
						}) 
					},
					{header : 'is_correct', dataIndex : 'is_correct', width: 100, hidden: true},
					{header : 'reg_user_id', dataIndex : 'reg_user_id', width: 100, hidden: true},
					{header : 'reg_datetime', dataIndex : 'reg_datetime', width: 100, hidden: true}
				],
				sm: new Ext.grid.RowSelectionModel({
					singleSelect : true
				}),
				tbar : [{
					xtype : 'button',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-cogs" style="font-size:13px;"></i></span>&nbsp;'+'<?=_text('MN02250')?>',
					handler : function(){
						loudness_set_up(ud_content_id, that);
					}
				},{
					xtype : 'button',
					text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+'<?=_text('MN00139')?>',
					handler : function(){
						that.refresh(that);
					}
				}],
				dataUrl : '/pages/menu/config/loudness/php/get_tree_grid_data.php?ud_content_id='+ud_content_id
			});
		}
	});



	return {
		xtype: 'tabpanel',
		activeTab: 0,
		items: [
		<?php
			$tabs = $db->queryAll("
						SELECT	*
						FROM	BC_UD_CONTENT
						WHERE	BS_CONTENT_ID IN (".MOVIE.", ".SOUND.")
						ORDER BY SHOW_ORDER
					");
			foreach ($tabs as $tab) {
				$_tabs[] = "{
						title: '".$tab['ud_content_title']."',
						id: ".$tab['ud_content_id'].",
						ud_content_id: ".$tab['ud_content_id'].",
						layout: 'fit',
						items: new Ariel.LoudnessConfig.TreePanel({ud_content_id : ".$tab['ud_content_id']."})
				}";
			}

			echo join(", \n", $_tabs);
		?>
		]
	};

})()
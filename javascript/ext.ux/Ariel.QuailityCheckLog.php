<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/store/metadata/buildMediaQualityMeta.php');

?>
Ext.ns('Ariel');

Ariel.QualityCheckLog = Ext.extend(Ext.Panel, {
	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	defaults:{
		margins:'5 5 5 5'
	},

	initComponent: function(config){
		Ext.apply(this, config || {});
		var that = this;	
		
		this.items = [{
			xtype: 'grid',
			cls: 'proxima_customize',
			stripeRows: true,
			width: '100%',
			flex: 1,
			loadMask: true,
			store: new Ext.data.JsonStore({
				//idProperty: 'media_id',
				url: '/store/media_quality_store.php',
				root: 'data',
				fields: [
						{name: 'media_id'},
						{name: 'media_type'},
						{name: 'quality_type'},
						{name: 'start_tc'},
						{name: 'end_tc' },
						{name: 'show_order' },
						{name: 'no_error' },
						{name: 'quality_id'},
						{name: 'sound_channel'}
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						try {
							var r = Ext.decode(response.responseText, true);
							if(!r.success) {
								Ext.Msg.alert(_text('MN00023'), response.responseText);
							}
						} catch(e) {
								Ext.Msg.alert(_text('MN00023'), response.responseText);
						}
					}
				}
			}),
			listeners: {
				viewready: function(self) {
					
					self.getStore().load({
						params: {
							content_id: self.ownerCt.content_id
						}
					});

					if(!Ext.isEmpty(self.ownerCt.hidden_confirm) && self.ownerCt.hidden_confirm == 'true'){
						Ext.getCmp('qc_confirm').hide(true);
					}
				},
				rowclick: function(self, rowIndex, e) {
				}
			},
			tbar: [{
				xtype: 'button',
				id: 'qc_confirm',
				//text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02368'),
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN02368')+'"><i class="fa fa-check" style="font-size:13px;color:white;"></i></span>',
				handler: function(b, e){
					Ext.Msg.show({
						title: _text('MN00024'),//Confirmination
						msg: _text('MSG02124'),//Mark QC list as not error.
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.OKCANCEL,
						fn: function(btnId){
							if(btnId=='ok')
							{
								Ext.Ajax.request({
									url: '/store/media_quality_action.php',
									params: {
										action:'edit',
										content_id: that.content_id
									},
									callback: function(opts, success, response){
										that.items.get(0).getStore().reload();
									}
								});
							}
						}
					});
				}
			}],
			viewConfig: {
				loadMask: true,
				forceFit : true,
				emptyText : _text('MSG00148')
			},
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false
				},
				columns: [
					new Ext.grid.RowNumberer(),
					{header: '파일 용도', dataIndex: 'media_type' , hidden: true},
					{header: 'media_id', dataIndex: 'media_id' , hidden: true},
					{header: 'Quality '+_text('MN00222'), dataIndex: 'quality_type' },
					{header: 'Start TC', dataIndex: 'start_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
							var h = parseInt( value / 3600 );
							var i = parseInt(  (value % 3600) / 60 );
							var s = (value % 3600) % 60;

							h = String.leftPad(h, 2, '0');
							i = String.leftPad(i, 2, '0');
							s = String.leftPad(s, 2, '0');
							var time = h+':'+i+':'+s;
							return time;
					}},
					{header: 'End TC', dataIndex: 'end_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
							var h = parseInt( value / 3600 );
							var i = parseInt(  (value % 3600) / 60 );
							var s = (value % 3600) % 60;

							if(h==0 && i==0 && s==0) return;

							h = String.leftPad(h, 2, '0');
							i = String.leftPad(i, 2, '0');
							s = String.leftPad(s, 2, '0');
							var time = h+':'+i+':'+s;
							return time;
					}},
					
					{header: 'quality_id', dataIndex: 'quality_id', hidden:true},
					{header: _text('MN02299'), dataIndex: 'sound_channel', hidden: true},//'채널'
					//user confirm
					{header: _text('MN02369'), dataIndex: 'no_error', renderer: function(value, metaData, record, rowIndex, colIndex, store){
						if(value == '1') {
							return 'O';
						} else {
							return 'X';
						}
					}}
				]
			})
		},{
			title: _text('MN'),//'검토의견',
			hidden : true,
			flex: 1,
			layout: 'fit',
			width: '100%',
			items: [{
					xtype: 'textarea',
					layout: 'fit'
			}]
		}];


		this.listeners = {
		};

		Ariel.QualityCheckLog.superclass.initComponent.call(this);
	}
});
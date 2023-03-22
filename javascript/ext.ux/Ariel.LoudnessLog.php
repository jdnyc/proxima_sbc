<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
Ext.ns('Ariel');

function render_state(v, metaData, r){
	switch(v) {
		case '1' :
			v = _text('MN01039');
		break;
		case '2' :
			if(r.data.measurement_state === 'P') {
				v = _text('MN02267');
			} else {
				v = _text('MN02268');
			}
		break;
		case '3' :
			v = _text('MN01049');
		break;
		case '13' :
			v = _text('MN00262');
		break;
		default :
			v = _text('MN02177');
		break;	
	}

	return v
}

function render_measure_state(v) {
	switch(v) {
		case 'P' :
			v = _text('MN02257');
		break;
		case 'D' :
			v = _text('MN02258');
		break;
	}
	
	return v;
}

function render_type(v){
	switch(v) {
		case 'M' :
			v = _text('MN02243');
		break;
		case 'A' :
			v = _text('MN02244');
		break;
	}

	return v
}


Ariel.LoudnessLog = Ext.extend(Ext.Panel, {
	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	defaults:{
		//margins:'5 5 5 5'
		margins:'0 0 0 0'
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
				url: '/store/loudness/get_loudness_list.php',
				idProperty: 'loudness_id',
				root: 'data',
				fields: [
						'loudness_id',
						'content_id',
						'jobuid',
						'state',
						'task_id',
						'req_user_id',
						'req_user_nm',
						'req_type',
						'measurement_state',
						'truepeak',
						'integrate',
						'momentary',
						'shortterm',
						'loudnessrange',
						{name: 'req_datetime', type: 'date', dateFormat: 'YmdHis'}
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
			tbar: [{
				xtype: 'displayfield',
				value: _text('MN02263') + ': '
			},{
				xtype: 'displayfield',
				value: '<span style="color:white">' + _text('MN02264') + '(-23 LKFS) / ' + _text('MN02265') + '(-23 LKFS) / </span><span style="color:blue">' + _text('MN02266') + '(-24 LKFS)</span>'
			}],
			listeners: {
				viewready: function(self) {
					
					self.getStore().load({
						params: {
							content_id: self.ownerCt.content_id
						}
					});
				},
				rowclick: function(self, rowIndex, e) {
					var rowRecord = self.getSelectionModel().getSelected();
					var loudness_panel = self.ownerCt;
					var loudness_log_grid = loudness_panel.find('name', 'loudness_log_grid')[0];
					var loudness_log_area = loudness_panel.find('name', 'loudness_log_area')[0];
					
					loudness_log_grid.getStore().load({
						params: {
							loudness_id : rowRecord.get('loudness_id')
						}
					});
				}
			},
			viewConfig: {
				loadMask: true
			},
			colModel: new Ext.grid.ColumnModel({
				columns: [
					new Ext.grid.RowNumberer(),
					{ header: _text('MN02247'), dataIndex: 'loudness_id', hidden: true }//Loudness ID
					,{ header: _text('MN02248'), dataIndex: 'jobUid', hidden: true}//JobUid
					,{ header: _text('MN02246'), dataIndex: 'req_datetime', width:130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'}//req_datetime
					,{ header: _text('MN00222'), dataIndex: 'req_type', renderer: render_type, align: 'center' }//req_type
					,{ header: _text('MN00138'), dataIndex: 'state', width:90, renderer: render_state, align: 'center' }//Status
					,{ header: _text('MN02259'), dataIndex: 'measurement_state', width:70, renderer: render_measure_state, align: 'center', hidden: true }//Status
					,{ header: _text('MN00218'), dataIndex: 'req_user_nm', width:70, align: 'center' }//req_user_nm
					,{ header: _text('MN02252'), dataIndex: 'truepeak', align: 'center'}
					,{ header: _text('MN02253'), dataIndex: 'integrate', align: 'center'}
					,{ header: _text('MN02254'), dataIndex: 'momentary', align: 'center'}
					,{ header: _text('MN02255'), dataIndex: 'shortterm', align: 'center'}
					,{ header: _text('MN02256'), dataIndex: 'loudnessrange', align: 'center'}
				]
			})
		},{
			flex: 2,
			width: '100%',
			xtype: 'grid',
			cls: 'proxima_customize',
			stripeRows: true,
			name: 'loudness_log_grid',
			store: new Ext.data.JsonStore({
				url: '/store/loudness/get_loudness_detail_list.php',
				idProperty: 'loudness_log_id',
				root: 'data',
				fields: [
						'loudness_measurement_log_id',
						'loudness_id',
						'timestamp',
						'truepeak',
						'integrate',
						'momentary',
						'shortterm',
						'loudnessrange',
						'status'
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
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true,
					align: 'center'
				},
				columns: [
					new Ext.grid.RowNumberer(),
					{header: _text('MN02251'), dataIndex: 'timestamp', align: 'center', width: 80},
					{header: _text('MN02252'), dataIndex: 'truepeak'},
					{header: _text('MN02253'), dataIndex: 'integrate'},
					{header: _text('MN02254'), dataIndex: 'momentary'},
					{header: _text('MN02255'), dataIndex: 'shortterm'},	
					{header: _text('MN02256'), dataIndex: 'loudnessrange'},
					{header: _text('MN00138'), dataIndex: 'status', renderer: render_measure_state}
				]
			}),
			viewConfig: {
				loadMask: true,
				forceFit:true
			}
		}];

		this.listeners = {
		};

		Ariel.LoudnessLog.superclass.initComponent.call(this);
	}
});
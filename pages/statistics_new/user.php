<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
fn_checkAuthPermission($_SESSION);
?>

(function(){
	var myMask = new Ext.LoadMask(Ext.getBody(), {msg:"loading..."});

	var store = new Ext.data.JsonStore({
		url: '/pages/statistics_new/user_store.php',
		root: 'data',
		totalProperty: 'total',
		idProperty: 'user_id',
		fields:[
			'member_id',
			'user_id',
			'user_nm',
			{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
			{name: 'last_login_date', type: 'date', dateFormat: 'YmdHis'},
			'count_login',
			'regist_content_cnt',
			'contents_size',
			'format_contents_size',
			{name: 'last_content_regist_date', type: 'date', dateFormat: 'YmdHis'},
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};

				Ext.apply(opts.params, {
					search_f: Ext.getCmp('st_search_f').getValue(),
					search_v: Ext.getCmp('st_search_v').getValue(),
					search_sdate: Ext.getCmp('st_start_date').getValue().format('Ymd')+'000000',
					search_edate: Ext.getCmp('st_end_date').getValue().format('Ymd')+'235959'
				});
			},
			load: function(store, records, opts){
				myMask.hide();
			}
		}
	});

	return {
		border: false,
		loadMask: true,
		//cls: 'proxima_customize',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00324')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		border: false,
		layout: 'card',
		activeItem: 0,
		autoScroll: true,

		tbar: [{
			xtype: 'combo',
			id: 'st_search_f',
			width: 130,
			triggerAction: 'all',
			editable: false,
			mode: 'local',
			store: [
				['user_id', _text('MN00188')],
				['user_nm', _text('MN00223')],
				['user_create_date', _text('MN02364')],
				['last_login', _text('MN00104')],
				['last_regist', _text('MN02363')]
			],
			value: 'user_nm',
			listeners: {
				afterrender: function(self){
					self.ownerCt.get(1).show();
					self.ownerCt.get(2).hide();
					self.ownerCt.get(3).hide();
					self.ownerCt.get(4).hide();
				},
				select: function(self, r, i){
					if(i == 0 || i == 1)//user id, user_name
					{
						self.ownerCt.get(1).show();
						self.ownerCt.get(2).hide();
						self.ownerCt.get(3).hide();
						self.ownerCt.get(4).hide();
					}
					else if(i == 2 || i == 3 || i == 4)
					{
						self.ownerCt.get(1).hide();
						self.ownerCt.get(2).show();
						self.ownerCt.get(3).show();
						self.ownerCt.get(4).show();
					}
				}
			}
		},{
			xtype: 'textfield',
			id: 'st_search_v',
			listeners: {
				specialKey: function(self, e){
					var w = self.ownerCt.ownerCt;
					if (e.getKey() == e.ENTER && !Ext.isEmpty(self.getValue()))
					{
						e.stopEvent();
						Ext.getCmp('user_statistic_list').getStore().load();
					}
				}
			}
		},{
			hidden: true,
			xtype: 'datefield',
			width: 100,
			id: 'st_start_date',
			format: 'Y-m-d',
			altFormats: 'Y-m-d|Ymd',
			value: new Date(),
			maxValue: new Date().clearTime(),
			end_datefield_id: 'end_date',
			autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'}
		},"<?=_text('MN00183')?>",{
			hidden: true,
			xtype: 'datefield',
			width: 100,
			id: 'st_end_date',
			format: 'Y-m-d',
			altFormats: 'Y-m-d|Ymd',
			value: new Date(),
			maxValue: new Date().clearTime(),
			autoCreate: {tag: 'input', type: 'text', size: '10', autocomplete: 'off', maxlength: '10'}
		},{
			//icon: '/led-icons/find.png',
			//text: "<?=_text('MN00037')?>",
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00059')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
			handler: function(btn, e){
				Ext.getCmp('user_statistic_list').getStore().load();
			}
		},{
			xtype : 'button',
			//text : '엑셀출력',
			//icon : '/led-icons/doc_excel_table.png',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN02359')+'"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
			handler : function(self, e){
				var search_text, sesarch_menu, search_value1, search_value2, grid, search_type;
				search_text = Ext.getCmp('st_search_f').getValue();
				if( search_text == 'user_id' || search_text == 'user_nm')
				{
					search_type = 'text';
					search_value1 = Ext.getCmp('st_search_v').getValue();
					search_value2 = '';
				}
				else
				{
					search_type = 'date';
					search_value1 = Ext.getCmp('start_date').getValue();
					search_value2 = Ext.getCmp('start_date').getValue();
				}

				grid = Ext.getCmp('user_statistic_list');
				if(grid.store.totalLength == 0 )
				{
					Ext.Msg.alert( _text('MN00023'), _text('MSG02051'));//'출력하실 내용이 없습니다.'
					return;
				}
				else
				{
					excelData(search_type, '/pages/statistics_new/user_store.php', grid.colModel, search_text, search_value1, search_value2);
				}
			}
		}],

		items: [{
			xtype: 'grid',
			id: 'user_statistic_list',
			stripeRows: true,
			border: false,
			loadMask: true,
			store: store,
			cm: new Ext.grid.ColumnModel({
				columns:[
					{header: _text('MN00188'), dataIndex: 'user_id', width: 150, renderer: function(v, metaData, record){ return v+'('+record.get('user_nm')+')' }, align: 'center'},
					{header: _text('MN02364'), dataIndex: 'created_date', width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center'},
					{header: _text('MN02360'), dataIndex: 'count_login', width: 100, align: 'right'},
					{header: _text('MN00104'), dataIndex: 'last_login_date', width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center'},
					{header: _text('MN02361'), dataIndex: 'regist_content_cnt', width: 120, align: 'right'},
					{header: _text('MN02362'), dataIndex: 'format_contents_size', width: 130, align: 'right'},
					{header: _text('MN02363'), dataIndex: 'last_content_regist_date', width: 120, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center'}
				]
			}),
			view: new Ext.ux.grid.BufferView({
				scrollDelay: false,
				emptyText:_text('MSG00148'),//'결과 값이 없습니다.',
				forceFit:true
			}),
			listeners: {
				afterrender: function(self){
					self.getStore().load();
				}
			}
		}]

	}
})()
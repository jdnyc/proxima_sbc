<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

?>
(function(){

	var delPagesize = 25;//페이지 제한

	Ext.override(Ext.PagingToolbar, {
		doload : function(start){
			var o = {}, pn = this.getParams();
			o[pn.start] = start;
			o[pn.limit] = this.pageSize;
			if(this.fireEvent('beforechange', this, o) !== false){
				var options = Ext.apply({}, this.store.lastOptions);
				options.params = Ext.applyIf(o, options.params);
				this.store.load(options);
			}
		}
	});

	var store = new Ext.data.JsonStore({//스토어
		url: '/store/history/getDeleteHistory.php',
		root: 'data',
		totalProperty: 'total',
		fields: [
			{name: 'content_id'},
			{name: 'description'},
			{name: 'ud_content_title'},
			{name: 'title'},
			{name: 'user_nm'},
			{name: 'content_is_deleted'},
			{name: 'media_status'},
			{name: 'created_date', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			beforeload: function(self){
				var sdate = Ext.getCmp('deleteHistory_sdate').getValue().format('Ymd000000');
				var edate = Ext.getCmp('deleteHistory_edate').getValue().format('Ymd240000');

				self.baseParams = {
					limit: delPagesize,
					start: 0,
					sdate: sdate,
					edate: edate
				}
			}
		}
	});

	var deleteHistoryList = new Ext.grid.GridPanel({
		id:'deleteHistoryList',
		stripeRows: true,
		border: false,
		store: store,
		loadMask: true,
		sm: new Ext.grid.RowSelectionModel({
			singleSelect: false
		}),
		tbar: [
			_text('MN00105')
		,{
			xtype: 'datefield',
			width: 90,
			id: 'deleteHistory_sdate',
			editable: false,
			format: 'Y-m-d',
			allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners:{
				select: function(self, date){
					var edate = self.ownerCt.items.items[3];
					edate.setMinValue(date);
				},
				render: function(self){
					var d = new Date();
					//self.setMaxValue(d.format('Y-m-d'));
					//self.setValue(new Date().add(Date.DAY, -30));
					self.setValue(d.add(Date.MONTH, -3).format('Y-m-d'));
				}
			}
		},'~',{
			xtype:'datefield',
			width: 90,
			id: 'deleteHistory_edate',
			editable: false,
			format: 'Y-m-d',
			allformats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
			listeners:{
				select: function(self, date){
					var sdate = self.ownerCt.items.items[1];
					sdate.setMaxValue(date);
				},
				render: function(self){
					var d = new Date();
					self.setMinValue(d.format('Y-m-d'));
					self.setValue(d);
				}
			}
		},'-',{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				Ext.getCmp('deleteHistoryList').getStore().reload();
			}
		},'-',{
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;" title="'+'복구'+'"><i class="fa fa-undo" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				var selections = Ext.getCmp('deleteHistoryList').getSelectionModel().getSelections();

				var contentIds = [];
				for(var i=0; i < selections.length; i++) {
					var contentId = selections[i].get('content_id');
					contentIds.push(contentId);
				}
				
				Ext.Ajax.request({
					url: '/store/history/restore_deleted_contents.php',
					params: {
						contentIds: Ext.encode(contentIds)
					},
					callback: function(opts, success, resp){
						if (success) {
							try {								
								var r = Ext.decode(resp.responseText);
								if (r.success) {
									Ext.Msg.alert('정보', '복구가 완료되었습니다.');
								} else {
									Ext.Msg.alert('알림', r.msg);
								}
							}
							catch (e) {
								Ext.Msg.alert(e['name'], e['message']);
							}
						} else {
							Ext.Msg.alert( _text('MN01098'), resp.statusText);//'서버 오류'
						}
					}
				});
			}
		}],
		columns: [
			new Ext.grid.RowNumberer(),
			{header: _text('MN00276'), dataIndex: 'ud_content_title',width: 100},//유형
			{header: _text('MN00278'), dataIndex: 'title',width: 300},//제목
			{header: _text('MN01097'), dataIndex: 'description',width: 200},//사유
			{header: _text('MN00218'), dataIndex: 'user_nm',width: 80, align:'center'},//요청자
			{header: '콘텐츠 삭제 여부', dataIndex: 'content_is_deleted',width: 140, align:'center'},//콘텐츠 삭제 여부
			{header: '미디어 상태', dataIndex: 'media_status',width: 120, align:'center'},//미디어 상태
			{header: _text('MN00105'), width: 140, dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align:'center'}//삭제일자
		],
		viewConfig: {
			emptyText: _text('MSG00148')//결과 값이 없습니다
		},
		bbar : new Ext.PagingToolbar({
			store: store,
			pageSize: delPagesize
		}),
		listeners: {
			afterrender: function(self) {
				store.load();
			}
		}
	});

	return{
		border: false,
		loadMask: true,
		layout: 'fit',
		title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02553')+'</span></span>',
		cls: 'grid_title_customize proxima_customize',
		items: [
			deleteHistoryList
		]
	};
})()
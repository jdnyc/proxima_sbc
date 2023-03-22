var myPageSize_notice = 5;

var notice_store = new Ext.data.JsonStore({
//	autoLoad: true,
	url: '/pages/mypage/php/notice_store.php',
	root: 'data',
	totalProperty: 'total',
	baseParams: {
		start: 0,
		limit: myPageSize_notice
	},
	fields: [
		{name: 'notice_id'},
		{name: 'notice_title'},
		{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
		{name: 'notice_content'}
	],
	listeners: {
		load: function(self){
		}
	}
});
var notice_grid = new Ext.grid.GridPanel({//공지사항 그리드
	id:'notice_grid',
	//!!title:'공지사항',
	title: _text('MN00144'),	
	store: notice_store,
	loadMask: true,
	defaults : {
		sortable: true,
		align: 'left'
	},
	sm: new Ext.grid.RowSelectionModel({
		singleSelect: true
	}),
	columns: [
		new Ext.grid.RowNumberer(),
		/*!!
		{ header: '제 목', dataIndex: 'notice_title', width:300 },
		{ header: '내 용', dataIndex: 'notice_content', width:500 },
		{ header: '작성일자', dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d'), width:200 }
		*/
		{ header: _text('MN00249'), dataIndex: 'notice_title', width:300 },
		{ header: _text('MN00067'), dataIndex: 'notice_content', width:500 },
		{ header: _text('MN00230'), dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d'), width:200 }
		

	],
	viewConfig: {
		forceFit: true,
		emptyText: _text('MSG00148')
		//!!emptyText: '검색 결과가 없습니다.'
	},
	tbar: [_text('MN00150'),{//!!기간
		xtype: 'datefield',
		id: 'notice_start_date',
		editable: false,
		format: 'Y-m-d',
		listeners: {
			render: function(self){
				self.setMaxValue(new Date().format('Y-m-d'));
				self.setValue(new Date().add(Date.DAY, -6).format('Y-m-d'));
			}
		}
	},
	_text('MN00183')//!!'부터'
	,{
		xtype: 'datefield',
		id: 'notice_end_date',
		editable: false,
		format: 'Y-m-d',
		listeners: {
			render: function(self){
				self.setMaxValue(new Date().format('Y-m-d'));
				self.setValue(new Date().format('Y-m-d'));
			}
		}
	},'-',{
		icon: '/led-icons/find.png',
		//text: '조회',
		text: _text('MN00059'),
		handler: function(btn, e){
			var reloader = Ext.getCmp('notice_grid').getStore();
			Ext.apply(reloader.baseParams, {
				start_date: Ext.getCmp('notice_start_date').getValue().format('Ymd000000'),
				end_date: Ext.getCmp('notice_end_date').getValue().format('Ymd240000')
			});
			notice_store.reload();
		}
	},'-',{
		xtype: 'textfield',
		id: 'notice_search_field',
		//!!emptyText: '검색할 제목을 입력하세요.'
		emptyText: _text('MSG00015')
	},{
		xtype: 'button',
		icon: '/led-icons/find.png',
		//text: '조회',
		text: _text('MN00059'),
		handler: function(b, e){
			var reloader = Ext.getCmp('notice_grid').getStore();
			Ext.apply(reloader.baseParams, {
				search: Ext.getCmp('notice_search_field').getValue()
			});
			notice_store.reload();
		}
	}],
	listeners: {
		rowdblclick: function(self, idx, e){
			var record = self.getSelectionModel().getSelected();

			var win = new Ext.Window({//공지사항 내용 창
				layout:'fit'
				//!!title:'공지사항',
				,title: _text('MN00144')
				,width:500
				,height:350
				,modal: true
				,closeAction:'hide'
				,resizable: false
				,plain: true

				,items: [{
					xtype: 'form',
					border: false,
					frame: true,
					padding: 10,
					labelWidth: 60,
					defaults: {
						anchor: '100%'
					},
					items: [{
						xtype: 'textfield',
						//!!fieldLabel: '제목',
						fieldLabel: _text('MN00249'),
						readOnly: true,
						value: record.get('notice_title')
					},{
						xtype: 'datefield',
						//!!fieldLabel: '작성일자',
						fieldLabel: _text('MN00230'),
						format: 'Y-m-d',
						readOnly: true,
						value: record.get('created_date')
					},{
						xtype: 'textarea',
						height: 180,
						readOnly: true,
						//!!fieldLabel: '내용',
						fieldLabel: _text('MN00067'),
						value: record.get('notice_content')
					}]
				}]
				,buttons: [{
					//!!text:'닫기'
					text:_text('MN00031')
					,handler: function(){
						win.hide();
					}
				}]
			});
			win.show();
		}
	},
	bbar: new Ext.PagingToolbar({
		store: notice_store,
		pageSize: myPageSize_notice
	})
});

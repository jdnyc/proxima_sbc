(function(){


	var PageSize = 25;//페이지 제한

	var interfaceStore = new Ext.data.JsonStore({
		url: '/interface/info_view/viewInterfaceStore.php',
		root: 'data',
		autoLoad: true,
		totalProperty: 'total',
		baseParams : {
			type: 'user_id',
			view_type: 'list',
			user_type: 'USER',
			user_id : 'all',
			limit : PageSize
		},
		fields: [
			{name: 'interface_id'},
			{name: 'interface_title'},
			{name: 'interface_from_type'},
			{name: 'interface_from_id'},
			{name: 'interface_target_type'},
			{name: 'interface_target_id'},
			{name: 'interface_from_name'},
			{name: 'interface_target_name'},
			{name: 'status'},
			{name: 'title'},
			{name: 'interface_base_content_id'},
			{name: 'create_date', type: 'date', dateFormat: 'YmdHis'}
		],
		listeners: {
			load: function(self, records, opt){
			}
		}
	});

	var grid = {
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: interfaceStore,
			tbar: ['->','일자 : ',{
				xtype: 'datefield',
				editable: true,
				name: 'start',
				width: 100,
				format: 'Y-m-d',
				listeners: {
					render: function(self){
						self.setMaxValue(new Date().format('Y-m-d'));
					}
				}
			},
			_text('MN00183'),{
				xtype: 'datefield',
				editable: true,
				name: 'end',
				format: 'Y-m-d',
				width: 100,
				listeners: {
					render: function(self){
						self.setMaxValue(new Date().format('Y-m-d'));
					}
				}
			},'-',{
				icon: '/led-icons/find.png',
				handler: function(btn, e){
					var values = new Array();
					Ext.each(btn.ownerCt.findByType('datefield'), function(r){
						if( !Ext.isEmpty( r.getValue() )){
							if(r.name == 'start'){
								values.push(r.getValue().format('Ymd')+'000000');
							}else if(r.name == 'end'){
								values.push(r.getValue().format('Ymd')+'240000');
							}
						}else{
							values.push('');
						}
					});
					interfaceStore.load({
						params: {
							searchDate: Ext.encode(values)
						}
					});
				}
			}],
			bbar : {
				xtype: 'paging',
				pageSize: PageSize,
				buttonAlign:'center',
				displayInfo: true,
				store: interfaceStore
			},
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect : function(self,rowIndex,r) {
					}
				}
			}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false,
					align: 'center'
				},
				columns: [
					 new Ext.grid.RowNumberer(),
					{header: '작업 명', dataIndex: 'interface_title', width:200 },
					{header: '콘텐츠 제목', dataIndex: 'title', width:200 },
					{header: '요청자', dataIndex: 'interface_from_name', width: 100},
					//{header: '확인자', dataIndex: 'interface_target_name', width: 100},
					{header: '상태', dataIndex: 'status', width: 100, renderer: function(value, metaData, record, rowIndex, colIndex, store){
						switch(value){
							case 'complete':
								value = _text('MN00011');
							break;
							case 'down_queue':
							case 'watchFolder':
							case 'queue':
								value = _text('MN00039');
							break;
							case 'error':
								value = _text('MN00012');
							break;
							case 'processing':
							case 'progressing':
								value = _text('MN00262');
							break;
							case 'cancel':
								value = _text('MN00004');
							break;
							case 'canceling':
								value = _text('MN00004');
							break;
							case 'canceled':
								value = _text('MN00004');
							break;

							case 'retry':
								value = _text('MN00006');
							break;
							case 'regiWait':
								value = _text('MN00360');
							break;

							case 'accept':
								value =  '승인';
							break;
							case 'refuse':
								value =  '반려';
							break;
						}
						metaData.attr = 'ext:qtip="'+value+'"';
						return value;
					}},
					{header: _text('MN00102'), dataIndex: 'create_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}

				]
			}),
			viewConfig: {
				emptyText: _text('MSG00148')
			},
			listeners: {
				rowclick : function( self,  rowIndex, e ) {
				}
			}
		};
	return {
		border: false,
		loadMask: true,
		layout: 'fit',
		items: [
			grid
		]
	};
})()
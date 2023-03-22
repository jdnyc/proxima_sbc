(function(){

	new Ext.Window({
		id: 'winSearchOnlyItem',
		width: 400,
		height: 265,
		modal: true,
		border: false,
		resizeHandles: 'e',
		title: '아이템 추가',
		layout: 'fit',
		
		_doSearch: function() {
			if (!Ext.getCmp('fOnlyItemCD').isValid()) return;

			Ext.getCmp('result_only_item_list').store.reload({
				params: {
					item_cd: Ext.getCmp('fOnlyItemCD').getValue()
				}
			});
		},
		
		items: {
			xtype: 'form',
			frame: true,
			border: false,
			layout: 'absolute',
			
			items: [{
				x: 5,
				y: 9,
				xtype: 'label',
				text: '상품 코드:'
			},{
				x: 60,
				y: 5,
				xtype: 'textfield',
				width: 150,
				id: 'fOnlyItemCD',
				allowBlank: false,
				emptyText: '상품코드를 입력하세요.',
				listeners: {
					specialKey: function(self, e){
						if (e.getKey() == e.ENTER) {
							Ext.getCmp('winSearchOnlyItem')._doSearch();
						}
					}
				}
			},{
				x: 215,
				y: 5,
				xtype: 'button',
				text: '검색',
				width: 70,
				handler: function(b, e){
					Ext.getCmp('winSearchOnlyItem')._doSearch();
				}
			},{
				x: 5,
				y: 35,
				xtype: 'grid',
				id: 'result_only_item_list',
				frame: true,
				border: true,
				height: 150,
				loadMask: true,
				store: new Ext.data.JsonStore({
					url: '/store/search_item.php',
					root: 'data',
					fields: [
						'item_cd',
						'item_nm'
					],
					listeners: {
						load: function(){
							Ext.getCmp('result_only_item_list').getSelectionModel().selectAll();
						},
						exception: function(self, type, action, options, response, arg){
							if (type == 'remote') {
								//console.log(response);
							} else {
								Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
							}
						}
					}
				}),
				cm: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true,
						menuDisabled: true
					},				
					columns: [
						new Ext.grid.CheckboxSelectionModel(),
						{header: '코드', dataIndex: 'item_cd', width: 50, align: 'center'},
						{header: '상품명', dataIndex: 'item_nm'}
					]
				}),
				sm: new Ext.grid.CheckboxSelectionModel(),
				viewConfig: {
					emptyText: '검색된 결과가 없습니다.',
					forceFit: true
				}		
			}]
		},

		buttons: [{
			text: '확인',
			handler: function(b, e){
				var rItem = Ext.getCmp('result_only_item_list').getSelectionModel().getSelections();

				Ext.each(rItem, function(r, i, a){
					if (Ext.getCmp('item_list').store.find('item_cd', r.get('item_cd')) < 0) {
						Ext.getCmp('item_list').store.add(r);
					}
				});

				b.ownerCt.ownerCt.close();
			}
		},{
			text: '취소',
			handler: function(b, e){
				b.ownerCt.ownerCt.close();
			}
		}]
	}).show();

})()
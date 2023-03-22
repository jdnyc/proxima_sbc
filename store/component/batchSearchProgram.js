(function(){

	new Ext.Window({
		id: 'window_manager_member',
		width: 650,
		height: 450,
		modal: true,
		border: false,
		resizeHandles: 'e',
		title: '프로그램 추가',
		layout: 'fit',
		
		items: {
			xtype: 'form',
			frame: true,
			border: false,
			layout: 'absolute',
			
			items: [{
				x: 5,
				y: 10,
				xtype: 'label',
				text: '방송일:'
			},{
				x: 50, 
				y: 5,
				xtype: 'datefield',
				id: 'bd_dt',
				width: 120,
				editable: true,
				format: 'Y-m-d',
				value: new Date()
			},{
				x: 175,
				y: 5,
				xtype: 'button',
				width: 60,
				text: '검색',
				handler: function(){
					Ext.getCmp('result_program_list').store.reload({
						params: {
							bd_dt: Ext.getCmp('bd_dt').getRawValue()
						}
					});
				}
			},{
				x: 5,
				y: 35,
				xtype: 'grid',
				id: 'result_program_list',
				frame: true,
				border: true,
				//anchor: '99% 100',
				height: 150,
				loadMask: true,
				store: new Ext.data.JsonStore({
					url: '/store/search_program.php',
					root: 'data',
					fields: [
						'PROG_CODE',
						'PROG_NAME',
						'BD_DATE',
						'BDSTART_DATE',
						'BDSTART_TIME',
						'BDSTART_DATETIME',
						'BDEND_DATETIME',
						'SHOWHOST_INFO',
						'VIDEO_INFO',
						'PD_INFO',
						'MD_INFO',
						'FD_INFO',
						'PLAY_NM',
						'MODEL_NM',
						'ERROR_TYPE',
						'ERROR_DESCRIPTION'
					]
				}),
				cm: new Ext.grid.ColumnModel({
					defaults: {
						sortable: true,
						menuDisabled: true
					},				
					columns: [
						new Ext.grid.RowNumberer(),
						{header: '코드',		dataIndex: 'PROG_CODE'},
						{header: '시작시간',	dataIndex: 'BDSTART_DATETIME', width: 120},
						{header: '프로그램명',	dataIndex: 'PROG_NAME', width: 150},
						{header: '쇼호스트',	dataIndex: 'SHOWHOST_INFO'},
						{header: 'PD 이름',		dataIndex: 'PD_INFO', width: 150},
						{header: 'Video 감독',	dataIndex: 'VIDEO_INFO', width: 150},
						{header: 'MD 이름',		dataIndex: 'MD_INFO', width: 150},
						{header: 'FD 이름',		dataIndex: 'FD_INFO', width: 150},
						{header: '출연자',		dataIndex: 'PLAY_NM', width: 150},
						{header: '모델',		dataIndex: 'MODEL_NM', width: 200},
						{header: '오류 유형',	dataIndex: 'ERROR_TYPE'},
						{header: '오류 설명',	dataIndex: 'ERROR_DESCRIPTION', width: 200}
					]
				}),
				viewConfig: {
					emptyText: '검색된 결과가 없습니다.'
				},
				listeners: {
					rowdblclick: function(self, ri, e){
						var r = self.getSelectionModel().getSelected();
						Ext.getCmp('result_item_list').store.reload({
							params: {
								pgm_cd: r.get('PROG_CODE'),
								bd_str_dtm: r.get('BDSTART_DATE')
							}
						});
					}
				}
			},{
				x: 5,
				y: 195,
				xtype: 'label',
				text: '상품 목록'
			},{
				x: 5,
				y: 215,
				xtype: 'grid',
				id: 'result_item_list',
				frame: true,
				border: true,
				height: 150,
				loadMask: true,
				store: new Ext.data.JsonStore({
					url: '/store/search_items.php',
					root: 'data',
					fields: [
						'item_cd',
						'item_nm'
					],
					listeners: {
						load: function(){
							Ext.getCmp('result_item_list').getSelectionModel().selectAll();
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
						{header: '코드', dataIndex: 'item_cd', width: 100},
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

				function doSubmit(own){
					var f = Ext.getCmp('batch_form').getForm().items.items;
					var rPgmInfo = Ext.getCmp('result_program_list').getSelectionModel().getSelected();

					Ext.each(f, function(v, i, a){
						if (v.fieldLabel) {
							/*
							'PROG_CODE',
							'PROG_NAME',
							'BSSCHED_DATE',
							'BDSTART_DATE',
							'BDSTART_TIME',
							'BDSTART_DATETIME',
							'BDEND_DATETIME',
							'SHOWHOST_INFO',
							'VIDEO_INFO',
							'PD_INFO',
							'MD_INFO',
							'FD_INFO',
							'PLAY_NM'
							*/
							switch (v.fieldLabel) {
								case '상품목록':
									v.items.get(0).setValue(true);
									Ext.getCmp('item_list').store.removeAll();
									Ext.getCmp('item_list').store.add(Ext.getCmp('result_item_list').getSelectionModel().getSelections());
								break;

								case '방송일시':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('BDSTART_DATE'));
									v.items.get(2).setValue(rPgmInfo.get('BDSTART_TIME'));
								break;

								case '프로그램코드':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('PROG_CODE'));
								break;

								case '프로그램명':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('PROG_NAME'));
								break;

								case '모델':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('MODEL_NM'));
								break;

								case 'PD':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('PD_INFO'));
								break;

								case 'MD':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('MD_INFO'));
								break;

								case 'FD':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('FD_INFO'));
								break;

								case 'Video감독':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('VIDEO_INFO'));
								break;

								case '쇼호스트':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('SHOWHOST_INFO'));
								break;

								case '출연자':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('PLAY_NM'));
								break;

								case '오류유형':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('ERROR_TYPE'));
								break;

								case '오류내용':
									v.items.get(0).setValue(true);
									v.items.get(1).setValue(rPgmInfo.get('ERROR_DESCRIPTION'));
								break;
							}
						}
					});
					own.close();

				}

				Ext.Msg.show({
					title: '확인',
					msg: '등록 하시겠습니까?',
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnID){
						if (btnID == 'ok') 
						{
							doSubmit(b.ownerCt.ownerCt);
						}
					}
				})
			}
		},{
			text: '취소',
			handler: function(b, e){
				b.ownerCt.ownerCt.close();
			}
		}]
	}).show();

})()
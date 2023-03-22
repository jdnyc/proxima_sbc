(function(target_id){
	var myPageSize = 10;
	var store = new Ext.data.JsonStore({
		url: '/store/searchmeta_store.php',
		totalProperty: 'total',
		autoLoad: true,
		root: 'data',
		fields: [
			'korname',
			'prognm',
			'brodymd',
			'subprognm',
			'mainnote',
			'medcd',
			'progcd',
			'formbaseymd',
			'subprogcd'
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				if(Ext.getCmp('search_f').getValue() == 'bd_plan_dt'){
					var search_v=Ext.getCmp('search_v1').getValue().format('Y-m-d');
				}else{
					var search_v=Ext.getCmp('search_v2').getValue();
				}

				Ext.apply(opts.params, {						
					category_id: Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode().attributes.id,
					search_field: Ext.getCmp('search_f').getValue(),
					search_value: search_v
				});
			
			},
			afterload: function(self, opts) {
			}
		}
	});
	var win = new Ext.Window({
		title: '사전제작물 검색',
		modal: true,
		width: 800,
		height: 400,
		layout: 'fit',
		
		tbar: [{
			hidden: true,
			xtype: 'combo',
			id: 'search_f',
			width: 70,
			triggerAction: 'all',
			editable: false,
			mode: 'local',
			store: [
				['pro_nm', '프로그램명']				
			],
			value: 'pro_nm',
			listeners: {
				select: function(self, r, i){
					
						self.ownerCt.get(3).setVisible(true);
						self.ownerCt.get(2).setVisible(false);
					
				}
			}
		},'->','방송일자',{			
			xtype: 'datefield',
			id: 'search_v1',
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					self.setValue(new Date().add(Date.MONTH, -1));
				}
			}
		},'~',{			
			xtype: 'datefield',
			id: 'search_v2',
			format: 'Y-m-d',
			listeners: {			
				render: function(self){
					self.setValue(new Date().add(Date.MONTH, 1));
				}							
			}
		},' ',{			
			xtype: 'button',
			text: '검색',
			icon: '/led-icons/find.png',
			handler: function(b, e){
				var w = b.ownerCt.ownerCt;
				w.doSearch(w.getTopToolbar(), w.get(0).store);
			}
		}],

		items: [{
			xtype: 'grid',
			loadMask: true,
			store: store,

			listeners: {
				rowdblclick: function(self){
					self.ownerCt.doSubmit(self.ownerCt);
				}/*,				
				viewready: function(self){					
					if(Ext.getCmp('search_f').getValue() == 'bd_plan_dt'){
						var search_v=Ext.getCmp('search_v1').getValue().format('Y-m-d');
					}else{
						var search_v=Ext.getCmp('search_v2').getValue();
					}
					self.store.load({
						params: {
							start: 0,
							limit: myPageSize,
							category_id: Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode().attributes.id,
							search_field: Ext.getCmp('search_f').getValue(),
							search_value: search_v
						}
					})
				}*/
			},
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true
				},
				columns: [
					{header: '담당PD',		dataIndex: 'korname'},
					{header: '프로그램 명',	dataIndex: 'prognm', width: 150},
					{header: '방송일',		dataIndex: 'brodymd'},
					{header: '부제',		dataIndex: 'subprognm',  width: 300 },
					{header: '내용',		dataIndex: 'mainnote', width: 280 , hidden: true}
					/*2010-12-15촬영일, 촬영장소 삭제 by CONOZ
					,{header: '촬영장소',	dataIndex: 'place'}
					,{header: '촬영일',		dataIndex: 'place2'}
					*/
				]
			}),
			view: new Ext.ux.grid.BufferView({
				emptyText: '검색결과가 없습니다.',
				forceFit: true
			}),
			bbar: new Ext.PagingToolbar({
				store: store,
				pageSize: myPageSize			
			}),
			buttons: [{
				text: '선택',
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.doSubmit(b.ownerCt.ownerCt.ownerCt);
				}
			},{
				text: '닫기',
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.close();
				}
			}]
		}],

		doSearch: function(tbar, store){

			
			var combo_value = tbar.get(0).getValue(),
				params = {};
				params.start = 0;
				params.limit = myPageSize;
			
			var brodstymd = tbar.get(3).getValue().format("Ymd");
			var brodendymd = tbar.get(5).getValue().format("Ymd");


			if (combo_value == 'bd_plan_dt')
			{
				params.search_field = combo_value;
				params.search_value = tbar.get(2).getValue().format('Y-m-d');
			}
			else
			{
				params.search_field = 'brodymd';
				params.category_id = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode().attributes.id;
				params.brodstymd = brodstymd;
				params.brodendymd = brodendymd;
			}

			store.load({
				params: params
			});
		},

		doSubmit: function(w){
			var sm = w.get(0).getSelectionModel();
			if (!sm.hasSelection())
			{
				Ext.Msg.alert('확인', '선택하여주세요.');
				return;
			}
			Ext.Msg.show({
				title: '확인',
				msg: '방송정보를 변경하시겠습니까?',
				icon: Ext.Msg.QUESTION,
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId){
					if (btnId == 'ok')
					{						
						//var applyTarget = Ext.decode(target_id);
						//var mainContainer='user_metadata_'+applyTarget;
						//meta_group_type: 1-(제목), 2-korname(담당PD), 3-brodymd(방송예정일), 4-Place(촬영장소), 5-Place2(촬영일), 6-prognm(프로그램), 7-subprognm(부제), 8-mainnote(내용)
						var metaCnt=8;
						for(var i=1; i <= metaCnt; i++){
							var nameVal='';
							var cId ='c-mapping-'+i;
							if(i == '1'){
								nameVal = '';
							}else if(i == '2'){
								nameVal = sm.getSelected().get('korname');											
							}else if(i == '3'){
								nameVal = sm.getSelected().get('brodymd');
							}else if(i == '6'){
								nameVal = sm.getSelected().get('prognm');
							}else if(i == '7'){
								nameVal = sm.getSelected().get('subprognm');
							}

							if( !Ext.isEmpty(nameVal) ){								
								Ext.getCmp(cId).setValue(nameVal);
							}
						}
						w.close();
					}
				}
			})
		}

	}).show();
})('<?=$_POST['target_id']?>')
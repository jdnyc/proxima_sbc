(function(target_id){
	var myPageSize = 10;
	var store = new Ext.data.JsonStore({
		url: '/store/searchCreatedDate.php',
		totalProperty: 'total',
		root: 'data',
		fields: [
			'korname',
			'prognm',
			'brodymd',
			'subprognm',
			'mainnote'
			/*2010-12-15촬영일, 촬영장소 삭제 by CONOZ
			,'place'
			,'place2'
			*/
		],
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				if(Ext.getCmp('search_f').getValue() == 'bd_plan_dt'){
					var search_v=Ext.getCmp('search_v1').getValue().format('Y-m-d');
				}else{
					var search_v=Ext.getCmp('search_v2').getValue();
				}
				// 2010-12-16 검색어 필수입력 by CONOZ
				if(Ext.isEmpty(search_v)){
					//>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
					Ext.Msg.alert('<?=_text('MN00023')?>', '<?=_text('MSG00007')?>');
					return false;
				}else{
					Ext.apply(opts.params, {
						search_field: Ext.getCmp('search_f').getValue(),
						search_value: search_v
					});
				}
			}
		}
	});
	var win = new Ext.Window({
		title: '사전제작물 검색',
		modal: true,
		width: 730,
		height: 350,
		layout: 'fit',

		tbar: [{
			xtype: 'combo',
			id: 'search_f',
			width: 70,
			triggerAction: 'all',
			editable: false,
			mode: 'local',
			store: [
				['bd_plan_dt', '방송일'],
				['pd_nm', 'PD'],
				['pro_nm', '프로그램명']
			],
			value: 'bd_plan_dt',
			listeners: {
				select: function(self, r, i){
					if (i == 0)
					{
						self.ownerCt.get(2).setVisible(true);
						self.ownerCt.get(3).setVisible(false);
					}
					else
					{
						self.ownerCt.get(3).setVisible(true);
						self.ownerCt.get(2).setVisible(false);
					}
				}
			}
		},' ',{
			xtype: 'datefield',
			id: 'search_v1',
			format: 'Y-m-d',
			listeners: {
				render: function(self){
					self.setValue(new Date());
				}
			}
		},{
			hidden: true,
			allowBlank: false,
			xtype: 'textfield',
			id: 'search_v2',
			listeners: {
				specialKey: function(self, e){
					var w = self.ownerCt.ownerCt;
					if (e.getKey() == e.ENTER && self.isValid())
					{
						e.stopEvent();
						w.doSearch(w.getTopToolbar(), w.get(0).store);
					}
				}
			}
		},' ',{
			xtype: 'button',
			text: '검색',
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
					{header: '부제',		dataIndex: 'subprognm'},
					{header: '내용',		dataIndex: 'mainnote', width: 280}
					/*2010-12-15촬영일, 촬영장소 삭제 by CONOZ
					,{header: '촬영장소',	dataIndex: 'place'}
					,{header: '촬영일',		dataIndex: 'place2'}
					*/
				]
			}),
			view: new Ext.ux.grid.BufferView({
				emptyText: '검색결과가 없습니다.'
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

			if (combo_value == 'bd_plan_dt')
			{
				params.search_field = combo_value;
				params.search_value = tbar.get(2).getValue().format('Y-m-d');
			}
			else
			{
				params.search_field = combo_value;
				params.search_value = tbar.get(3).getValue();
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
							var cId='c-'+i;
							if(i == '1'){
								nameVal = '';
							}else if(i == '2'){
								nameVal = sm.getSelected().get('korname');
								//var korName = sm.getSelected().get('korname');
								//Ext.getCmp(cId).setValue(korName);
							}else if(i == '3'){
								nameVal = sm.getSelected().get('brodymd');
							}/*2010-12-15촬영일, 촬영장소 삭제 by CONOZ
							else if(i == '4'){
								nameVal = sm.getSelected().get('Place');
							}else if(i == '5'){
								nameVal = sm.getSelected().get('Place2');
							}*/else if(i == '6'){
								nameVal = sm.getSelected().get('prognm');
							}else if(i == '7'){
								nameVal = sm.getSelected().get('subprognm');
							}else if(i == '8'){
								nameVal = sm.getSelected().get('mainnote');
							}
							if(nameVal && nameVal != undefined){
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
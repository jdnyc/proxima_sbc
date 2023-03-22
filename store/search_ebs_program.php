
(function(target_id, target, meta_table_id ){
	var grid_store = new Ext.data.JsonStore({
		url: '/store/nps_work/get_EBS_MSS.php',
		fields: [
			'brodsttm',
			'brodendtm',
			'progshapgu',
			'medcd',
			'progcd',
			{name : 'formbaseymd', type: 'date', dateFormat: 'Ymd' },
			'content_id',
			'prognm',
			'auditarget',
			'auditargetnm',
			'progshapgunm',
			'medcdnm'
		],
		root: 'data',
		totalProperty: 'total',
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {
					limit: 25,
					mode: 'product_prog'
				});
			}
		}
	});
	var grid_store_sub = new Ext.data.JsonStore({
		url: '/store/nps_work/get_EBS_MSS.php',
		fields: [
		'subprogcd',
		'subprognm',
		{name : 'formbaseymd', type: 'date', dateFormat: 'Ymd' },
		'progpdnm',
		'pdempno',
		{name : 'brodymd', type:'date', dateFormat : 'Ymd'},
		'medcd',
		'progcd',
		'frmatnm',
		'objnm',
		'progshapgu',
		'prodowergu',
		'prodowergunm'
		],
		root: 'data',
		totalProperty: 'total',
		listeners: {
			beforeload: function(self, opts){
				opts.params = opts.params || {};
				Ext.apply(opts.params, {
					limit: 25,
					mode: 'product_subprog'
				});
			}
		}
	});

	new Ext.Window({
		title: '프로그램 검색',
		modal: true,
		layout: 'fit',
		width: 800,
		height: 600,
		//maximized : true,
		//maximizable : true,
		loseAction: 'destroy',
		resizable: true,
		plian: true,
		items:[{
			xtype:'form',
			border: false,
			frame: true,
			labelPad: 20 ,
			labelAlign: 'top',
			labelSeparator: '',
			defaults: {
				anchor:'100%'
			},
			items:[{
				xtype: 'grid',
				height: 220,
				fieldLabel:'프로그램 목록',
				columnSort: true,
				reserveScrollOffset: true,
				autoScroll: true,
				loadMask: true,
				viewConfig: {
					forceFit: true,
					emptyText: '해당되는 프로그램이 없습니다.'
				},
				emptyText: '등록된 데이터가 없습니다.',
				tbar: [{
					xtype: 'displayfield',
					value: '매체'
				},{
					xtype: 'combo',
					width: 110,
					triggerAction: 'all',
					editable: false,
					mode: 'remote',
					typeAhead: true,
					store: new Ext.data.JsonStore({
						autoLoad: true,
						url: '/pages/menu/config/Program/php/medcd.php',
						root: 'data',
						fields: [
							'code','name'
						],
						listeners: {
							load: function( self ){
							}
						}
					}),
					displayField: 'name',
					valueField: 'code',
					value: 'TV',
					listeners: {
						select : function(self){
							self.ownerCt.get(4).getStore().setBaseParam('medcd', self.getValue());
							self.ownerCt.get(4).getStore().load({params: {
							'medcd': self.getValue()
							}});
						}
					}
				},'-',{
					xtype: 'displayfield',
					value: '편성일자'
				},{
					xtype: 'combo',
					width: 110,
					triggerAction: 'all',
					editable: false,
					mode: 'remote',
					typeAhead: true,
					store: new Ext.data.JsonStore({
						autoLoad: true,
						url: '/pages/menu/config/Program/php/formbaseymd.php',
						root: 'data',
						fields: [
							'formbaseymd','name'
						],
						listeners: {
							beforeload: function( self ){

								if( Ext.isEmpty(self.baseParams.medcd) ){
									self.setBaseParam('medcd', '001');
								}
							}
						}
					}),
					displayField: 'name',
					valueField: 'formbaseymd',
					value: '전체'
				},'-',{
					xtype: 'displayfield',
					value: '프로그램명'
				},{
					xtype: 'textfield',
					width: 150
				},'-',{
					xtype: 'button',
					menuAlign: 'center',
					text: '검색',
					icon: '/led-icons/magnifier.png',
					handler: function(b, e){
						b.ownerCt.ownerCt.getStore().reload({
							params: {
								'medcd' : b.ownerCt.get(1).getValue(),
								'formbaseymd' : b.ownerCt.get(4).getValue(),
								'prognm' : b.ownerCt.get(7).getValue()
							}
						});
					}
				}],
				selModel: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				store: grid_store,
				columns: [
					new Ext.grid.RowNumberer(),
					{ header : '매체구분', dataIndex: 'medcdnm', width : 40, sortable: true},
					{ header : '매체코드', dataIndex: 'medcd', width : 40, sortable: true, hidden:true },
					{ header : '프로그램코드', dataIndex: 'progcd' , sortable: true,  width : 40},

					{ header : '편성일자', dataIndex: 'formbaseymd',  sortable: true, width : 40 ,renderer:Ext.util.Format.dateRenderer('Y-m-d') },
					{ header : '프로그램명', dataIndex: 'prognm' , sortable: true}
				],
				listeners: {
					rowdblclick: function(self, rowIndex, e){
						var selected_medcd = self.getStore().data.items[rowIndex].data.medcd;
						var selected_progcd = self.getStore().data.items[rowIndex].data.progcd;
						var selected_formbaseymd = self.getStore().data.items[rowIndex].data.formbaseymd;


						//grid_store_sub.setBaseParam('medcd', selected_medcd);
						//grid_store_sub.setBaseParam('progcd', selected_progcd);
						//grid_store_sub.setBaseParam('formbaseymd', selected_formbaseymd.format('Ymd'));
						grid_store_sub.load({params:{
							'medcd' : selected_medcd,
							'progcd': selected_progcd,
							'formbaseymd': selected_formbaseymd.format('Ymd')
						}});
					}
				},
				bbar: new Ext.PagingToolbar({
					store: grid_store,
					pageSize: 15
				})
			},{
				xtype: 'grid',
				height: 250,
				fieldLabel:'부제 목록',
				columnSort: false,
				autoScroll: true,
				loadMask: true,
				viewConfig: {
					forceFit: true,
					emptyText: '해당되는 부제가 없습니다.'
				},
				tbar: [{
					xtype: 'displayfield',
					value: '부제명'
				},{
					xtype: 'textfield',
					width: 150
				},'-',{
					xtype: 'button',
					menuAlign: 'center',
					text: '검색',
					icon: '/led-icons/magnifier.png',
					handler: function(b, e){
						var subprognm = b.ownerCt.get(1).getValue();

						b.ownerCt.ownerCt.getStore().reload({
							params: {
								subprognm: subprognm
							}
						});
					}
				}],
				store: grid_store_sub,
				columns: [
					new Ext.grid.RowNumberer(),
					{ header : '부제코드', dataIndex: 'subprogcd', width : 80 },
					{ header : '부제명', dataIndex: 'subprognm' , width : 400},
					{ header : '편성기준일', dataIndex: 'formbaseymd', width : 80 ,renderer:Ext.util.Format.dateRenderer('Y-m-d')},
					{ header : '담당PD', dataIndex: 'progpdnm', width : 80 },
					{ header : '방송일자', dataIndex: 'brodymd', width : 80 ,renderer:Ext.util.Format.dateRenderer('Y-m-d')}
				],
				listeners: {
					rowdblclick: function(self, rowIndex, e){
						var selected_medcd = self.getStore().data.items[rowIndex].data.medcd;
						var selected_progcd = self.getStore().data.items[rowIndex].data.progcd;
						var selected_formbaseymd = self.getStore().data.items[rowIndex].data.formbaseymd;
						grid_store.load({params:{
							'medcd' : selected_medcd,
							'progcd': selected_progcd,
							'formbaseymd': selected_formbaseymd.format('Ymd')
						}});
					}
				},
				bbar: new Ext.PagingToolbar({
					store: grid_store_sub,
					pageSize: 15
				})
			}]
		}],
		buttonAlign: 'center',
		buttons:[{
			text: '확인',
			icon: '/led-icons/accept.png',
			scale: 'medium',
			handler: function(b, e){
				var sel_prog;
				var sel_subprog ;
				var sub_data;

				var sel = b.ownerCt.ownerCt.get(0).get(1).getSelectionModel().getSelected();
				var sel2 = b.ownerCt.ownerCt.get(0).get(0).getSelectionModel().getSelected();

				if( Ext.isEmpty(sel) ){
					Ext.Msg.alert( _text('MN00023'),'부제를 선택하세요');
					return;
				}

				if( Ext.isEmpty(sel2) ){
					Ext.Msg.alert( _text('MN00023'),'프로그램를 선택하세요');
					return;
				}

				if( !Ext.isEmpty(Ext.getCmp(target_id)) ){

					if(target == ''){

						var val = {
							k_progcd: sel.json.progcd,
							k_subprogcd: sel.json.subprogcd,
							k_brodymd: sel.json.brodymd,
							k_formbaseymd: sel.json.formbaseymd,
							k_medcd: sel.json.medcd,
							4000292 : sel.json.prognm.trim(),
							4000293 : sel.json.subprognm.trim(),
							4000289	: sel.json.brodymd,
							4000288 : sel.json.progpdnm.trim()
						};
					}else if( target == 'todas' && meta_table_id=='81722' ){

						var val = {
							k_progcd: sel.json.progcd,
							k_subprogcd: sel.json.subprogcd,
							k_brodymd: sel.json.brodymd,
							k_formbaseymd: sel.json.formbaseymd,
							k_medcd: sel.json.medcd,
							81787 : sel.json.prognm.trim(),
							81786 : sel.json.subprognm.trim(),
							4002618	: sel.json.brodymd,

							4002619 : sel.json.brodsttm,
							4002620 : sel.json.brodendtm,
							81776 : sel2.json.progshapgunm.trim(),
							81775 : sel.json.prodowergunm.trim(),
							81784 : sel.json.auditargetnm,
							81783 : sel2.json.medcdnm.trim(),
							4002622 : sel.json.progpdnm.trim()

						};
					}else if( target == 'todas'&& meta_table_id=='81767' ){

						var val = {
							k_progcd: sel.json.progcd,
							k_subprogcd: sel.json.subprogcd,
							k_brodymd: sel.json.brodymd,
							k_formbaseymd: sel.json.formbaseymd,
							k_medcd: sel.json.medcd,
							81851 : sel.json.prognm.trim(),
							81853 : sel.json.subprognm.trim(),
							81854	: sel.json.brodymd,
							4021168 : sel.json.progpdnm.trim()
						};

					}else if( target == 'todmc' ){
//기술담당자	4778212
//녹화일자	4778211
//방송이력	4778210
//프로그램 등급	4778209
//만료일자	4778208
//광고유무	4778207
//연출자	4778206
//방송일시	4778205
//부제명	4778204
//프로그램명	4778203
						var BrodYmd =  sel.json.brodymd.trim();
						var BrodStTm = sel.json.brodsttm.trim();
						var 	MedCdNm = sel2.json.medcdnm.trim();



						var val = {
							k_medcd: sel.json.medcd,
							k_progcd: sel.json.progcd,
							k_subprogcd: sel.json.subprogcd,
							k_formbaseymd: sel.json.formbaseymd,
							k_brodymd:  sel.json.brodymd,
							k_pdempno:  sel.json.pdempno,
							4778203 : sel.json.prognm.trim(),
							4778204 : sel.json.subprognm.trim(),
							4778206 : sel.json.progpdnm.trim(),

							'4778205-0'	: sel2.json.medcdnm.trim(),
							'4778205-1'	:sel.json.brodymd.trim(),
							'4778205-2'	:sel.json.brodsttm.trim(),
							'4778205-3'	: '본방',
							4778208 : sel.json.brodymd.trim()
						};

					}else{
						Ext.Msg.alert( _text('MN00023'),'입력대상을 찾을 수 없습니다.');
						return;
					}

					Ext.getCmp(target_id).getForm().setValues(val);

					b.ownerCt.ownerCt.close();

				}else{
					Ext.Msg.alert( _text('MN00023'),'입력대상을 찾을 수 없습니다.');
				}
			}
		},{
			text: '취소',
			icon: '/led-icons/cross.png',
			scale: 'medium',
			handler: function(b, e){
				b.ownerCt.ownerCt.close();
			}
		}]
	}).show();
})('<?=$_REQUEST['target_id']?>','<?=$_REQUEST['target']?>','<?=$_REQUEST['meta_table_id']?>' )

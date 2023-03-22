(function(args){
	new Ext.Window({
		title: '작업 정보',
		width: 800,
		height: 400,
		layout:	'border',
		border: false,
		collapsible: true,
		maximizable: true,
		items: [{
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			height: 300,
			region: 'center',
			intervalID: null,
			runAutoReload: function(thisRef){
				thisRef.stopAutoReload(thisRef);
				thisRef.intervalID = setInterval(function (e) {
					if (!Ext.isEmpty(thisRef.getStore())) {
						thisRef.getStore().reload();
					}else{
						thisRef.stopAutoReload(thisRef);
					}
				}, 10000);
			},
			stopAutoReload: function(thisRef){
				if (thisRef.intervalID) {
					clearInterval(thisRef.intervalID);
				}
			},
			store: new Ext.data.JsonStore({
				url: '/store/workFlowStore.php?'+Ext.urlEncode( args ),
				root: 'data',
				autoLoad: true,
				fields: [
					'task_id',
					't_name',
					'user_task_name',
					'type',
					'no_log',
					'status',
					'source',
					'target',
					'progress',
					'task_user_id',
					'assign_ip',
					'destination',
					{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
					{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
					{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'}
				]
			}),
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){
						if(self.getSelected().get('no_log') == 1){
							self.grid.ownerCt.get(1).getStore().removeAll();
						}else{
							if(self.grid.ownerCt.get(1).collapsed == false){
								self.grid.ownerCt.get(1).getStore().load({
									params: {
										task_id: self.getSelected().get('task_id')
									}
								});
							}
						}
					},
					rowdeselect: function(self){
						self.grid.ownerCt.get(1).getStore().removeAll();
					}
				}
			}),
			listeners: {
				afterrender: function(self){
					self.runAutoReload(self);
				}
			},
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false,
					align: 'center'
				},
				columns: [
					{header: _text('MN00235'), dataIndex: 'task_id', width: 60, hidden: true},
					{header: _text('MN00236'), dataIndex: 'user_task_name', width: 150},
					{header: '작업 유형', dataIndex: 't_name', width: 80},
					new Ext.ux.ProgressColumn({
							header: _text('MN00261'),
							width: 70,
							dataIndex: 'progress',
							//divisor: 'price',
							align: 'center',
							renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
								return Ext.util.Format.number(pct, "0%");
							}
					}),
					{header: _text('MN00138'), dataIndex: 'status', renderer: function(value){
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
								return '승인';
							break;
							case 'refuse':
								return '반려';
							break;
						}
						return value;
					}, width: 40},
					{header: _text('MN00102'), dataIndex: 'creation_datetime', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: _text('MN00233'), dataIndex: 'start_datetime', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: _text('MN00234'), dataIndex: 'complete_datetime', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
					{header: '소스', dataIndex: 'source', width: 250, hidden: true},
					{header: '타겟', dataIndex: 'target', width: 250, hidden: true},
					{header: '작업서버', dataIndex: 'assign_ip', width: 90, hidden: true}
				]
			}),
			tbar: [{
				//>>text: '새로고침',
				text: _text('MN00139'),
				icon: '/led-icons/arrow_refresh.png',
				handler: function(b, e){
					b.ownerCt.ownerCt.getStore().reload();
				},
				scope: this
			}
			,'-'
			,{
				text: '상세 로그보기',
				enableToggle: true,
				icon: '/led-icons/page_white_text.png',
				handler: function(b, e){
					if(b.pressed){
						b.ownerCt.ownerCt.ownerCt.get(1).expand();
					}else{
						b.ownerCt.ownerCt.ownerCt.get(1).collapse();
					}
				},
				scope: this
			},'->',{
				text: '자동 새로고침',
				enableToggle: true,
				pressed: true,
				icon: '/led-icons/arrow_refresh.png',
				handler: function(b, e){
					if(b.pressed){
						b.ownerCt.ownerCt.runAutoReload(b.ownerCt.ownerCt);
					}else{
						b.ownerCt.ownerCt.stopAutoReload(b.ownerCt.ownerCt);
					}
				}
			}
			///			,{
			//				xtype: 'combo',
			//				width: 100,
			//				triggerAction: 'all',
			//				editable: false,
			//				mode: 'local',
			//				store: [
			//					['content_id', _text('MN00287')]
			//				],
			//				value: 'content_id'
			//
			//			},{
			//				//hidden: true,
			//				allowBlank: false,
			//				xtype: 'textfield',
			//				listeners: {
			//					specialKey: function(self, e){
			//						var w = self.ownerCt.ownerCt;
			//						if (e.getKey() == e.ENTER && self.isValid())
			//						{
			//							e.stopEvent();
			//							w.doSearch(w.getTopToolbar(), this.store);
			//						}
			//					}
			//				}
			//			},' '
			//			,{
			//				xtype: 'button',
			//				//>>text: '조회',
			//				text: _text('MN00047'),
			//				handler: function(b, e){
			//					var w = b.ownerCt.ownerCt;
			//					w.doSearch(w.getTopToolbar(), this.store);
			//				}
			//			}
			],
			//			tools: [{
			//				id: 'refresh',
			//				qtip:_text('MN00139'),
			//				handler: function(e, t, p, c){
			//					p.getStore().reload();
			//				}
			//			}],
			viewConfig: {
				//	forceFit: true,
				//>>emptyText: '결과값이 없습니다.',
				emptyText: _text('MSG00148'),
				listeners: {
					refresh: function(self) {
						//self.grid.ownerCt.get(1).getStore().removeAll();
					}
				}
			},

			doSearch: function(tbar, store){
				var params = {};
				params.search_field = tbar.get(2).getValue();
				params.search_value = tbar.get(3).getValue();

				if(Ext.isEmpty(params.search_value)){
					//>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
					Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
				}else{
					tbar.ownerCt.getStore().load({
						params: params
					});
				}
			}
		},{
			title:	_text('MN00048'),
			tools: [{
				id: 'refresh',
				qtip:_text('MN00139'),
				handler: function(e, t, p, c){
					p.getStore().reload();
				}
			}],
			xtype:	'grid',
			region: 'south',
			split: true,
			collapsed: true,
			collapsible: true,
			height: 200,
			loadMask: true,
			autoExpandColumn: 'description',
			store:	new	Ext.data.JsonStore({
				url: '/store/get_task_log.php',
				totalProperty: 'total',
				idProperty:	'task_log_id',
				root: 'data',
				fields:	[
					{name: 'task_log_id'},
					{name: 'task_id'},
					{name: 'description'},
					{name: 'creation_date',	type: 'date', dateFormat: 'YmdHis'}
				],
				listeners: {
					beforeload:	function(self, opts){
					}
				}
			}),
			columns: [
				//{header: 'ID', dataIndex: 'task_log_id',	width: 45},
				//>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				//>>{header: '내용', dataIndex: 'description', id: 'description'}
				{header: _text('MN00107'), dataIndex: 'creation_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
				{header: _text('MN00156'), dataIndex:	'description', id: 'description'}
			],
			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true
			}),
			viewConfig: {
				emptyText: _text('MSG00166')
			},
			listeners: {
				collapse : function(p){
					//p.ownerCt.setHeight(400);
					//p.ownerCt.setHeight(410);
				},
				expand : function(p){
					p.ownerCt.setHeight(600);
					var position = p.ownerCt.getPosition();
					p.ownerCt.setPosition( position[0], 10 );
				}
			}
        }
		],
		buttonAlign:'center',
		buttons: [{
			text: _text('MN00031'),
			handler: function(b, e){
				b.ownerCt.ownerCt.close();
			}
		}],
		listeners: {
			collapse : function(self){
				self.get(0).stopAutoReload(self.get(0));
			}
		}
	}).show();

})('<?=$_REQUEST['args']?>')
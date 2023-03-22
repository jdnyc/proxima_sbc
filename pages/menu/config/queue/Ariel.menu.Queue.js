Ext.ns('Ariel.menu');
Ariel.menu.QueuePanel = Ext.extend(Ext.Panel, {
	layout: 'border',

	initComponent: function(config){
		Ext.apply(this, config || {});

		this.items = [
			this.buildList(this), this.buildForm(this)
		];

		Ariel.menu.QueuePanel.superclass.initComponent.call(this);
	},

	buildList: function(ownerCt){
		var store = new Ext.data.JsonStore({
			url: '/pages/menu/config/queue/php/get_queue.php',
			root: 'data',
			messageProperty: 'msg',
			fields: [
				'id',
				'content_id',
				'asset_type',
				'file',
				'title',
				'vlaue',
				'duration',
				'resolution',
				'vcodec',
				{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'}
			],
			listeners: {
				load: function(self, records, options){
					if (records.length == 0) {
						var v = Ext.getCmp('status-combo').getRawValue();
						var msg = '';
						switch(v){
							case '전체':
								msg = '현제까지 등록된 콘텐츠가 없습니다.';
							break;
							
							case '대기':
								msg = '대기중인 콘텐츠가 없습니다.';
							break;

							case '완료':
								msg = '완료된 콘텐츠가 없습니다.';
							break;
								
							default:
								msg = '콘텐츠가 없습니다.('+v+')';
							break;								
						}
						Ext.getCmp('queue_list').getView().mainBody.update('<div class="x-gird-empty" style="text-align: center; font-size: 18px; margin-top: 15px">'+msg+'</div>');

					}
				},
				exception: function(self, type, action, options, response, arg){
					if(type == 'response'){
						if(response.status == '200') {
							Ext.Msg.alert('오류', response.responseText);
						}else{
							Ext.Msg.alert('오류', response.status);
						}
					}else{
					}
				}
			}
		});


		function _action(btn, msg, status){
			var sm = Ext.getCmp('queue_list').getSelectionModel();
			if (!sm.hasSelection()) {
				Ext.Msg.alert('오류', '변경하실 콘텐츠를 선택해주세요.');
				return;
			}

			var ids = '';
			var selections = sm.getSelections();
			Ext.each(selections, function(item, index, allItems){				
				ids += item.get('id') + ',';
			});

			Ext.Msg.show({
				animEl: btn.getId(),
				title: '확인',
				icon: Ext.Msg.QUESTION,
				msg: '선택하기 ' + selections.length + '개의 콘텐츠를 ' + msg + '로 변경 하시겠습니까?',
				buttons: Ext.Msg.OKCANCEL,
				fn: function(btnId, text, opts){
					if (btnId == 'cancel' ) return;

					Ext.Ajax.request({
						url: '/pages/menu/config/queue/php/action.php',
						params: {
							ids: ids,
							status: status
						},
						callback: function(self, success, response){
							if (!success) {
								Ext.Msg.alert('오류', response.statusText + '(' + response.status + ')');
							}else{
								try {
									var r = Ext.decode(response.responseText, true);
									if (r.success) {
										Ext.getCmp('queue_list').getStore().reload();
									}else{
										Ext.Msg.alert('오류', r.msg);
									}
								}
								catch(e) {
									Ext.Msg.alert('오류(' +  e + ')', response.responseText);
								}
							}
						}
					})
				}
			})
		}

		return {
			id: 'queue_list',
			xtype: 'grid',
			region: 'center',
			store: store,
			loadMask: true,
			flex: 1,
			viewConfig: {
				emptyText: '등록 대기중인 콘텐츠가 없습니다.'
			},
			columns: [
				{header: '자산종류', dataIndex: 'asset_type'},
				{header: '제목', dataIndex: 'title', width: 200},
				{header: '파일명', dataIndex: 'file', width: 300},
				{header: '재생시간', dataIndex: 'duration'},
				{header: '해상도', dataIndex: 'resolution'},
				{header: '비디오코덱', dataIndex: 'vcodec'},
				{header: '등록일', dataIndex: 'creation_datetime', width: 140, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
			],
			selModel: new Ext.grid.RowSelectionModel({
				listeners: {
					rowselect: {
						fn: function(self, rowIndex, r){
							Ext.getCmp('meta_table_combo').getStore().setBaseParam('type', r.get('asset_type'));
							Ext.getCmp('main_category').getSelectionModel().clearSelections();
							Ext.getCmp('meta_table_combo').reset();
							Ext.getCmp('queue_metadata').removeAll();
						}
					}
				}
			}),
			tbar: [{
				id: 'status-combo',
				xtype: 'combo',
				store: new Ext.data.ArrayStore({
					idIndex: 0,
					fields: ['type', 'displayText'],
					data: [
						['all', '전체'], 
						['queue', '대기'], 
						['completed', '완료']
					]
				}),
				listeners: {
					select: function(self, rec, idx){
						if(rec.get('type') == 'queue'){
							Ext.getCmp('reg_form').expand();
						}else{
							Ext.getCmp('reg_form').collapse();
						}

						Ext.getCmp('queue_list').store.reload({
							params: {
								status: rec.get('type')
							}
						});
						Ext.getCmp('queue_metadata').removeAll();
					}
				},
				value: 'queue',
				displayField: 'displayText',
				valueField: 'type',
				hiddenName: 'type',
				mode: 'local',
				fieldLabel: '콘텐츠 종료',
				triggerAction: 'all',
				editable: false
			},{
				icon: 'led-icons/arrow_refresh.png',
				text: '새로고침',
				handler: function(btn, e){
					Ext.getCmp('queue_list').getStore().reload();
				}
			},{
				icon: 'led-icons/delete.png',
				text: '삭제',
				handler: function(btn, e){
					_action(btn, '삭제', 'deleted');
				}
			},{
				hidden: true,
				icon: 'led-icons/bomb.png',
				text: 'reset',
				handler: function(btn, e) {
					Ext.Ajax.request({
						url: '/pages/menu/config/queue/php/reset.php',
						callback: function(options, success, response) {
							Ext.getCmp('queue_list').getStore().reload();
						}
					})
				}
			}],
			listeners: {
				viewready: function(self){
					self.getStore().load({
						params: {
							status: 'queue'
						}
					});
				}
			}
		}
	},

	buildForm: function(_ownerCt) {

		function _add_form(meta_table_id) {
			Ext.Ajax.request({
				url: '/pages/menu/config/queue/php/get_form.php',
				params: {
					meta_table_id: meta_table_id
				},
				callback: function(options, success, response){
					if(success) {
						try {
							var r = Ext.decode(response.responseText);
							if(r.success) {
								var form = Ext.getCmp('queue_metadata');
								form.removeAll();

								form.add([{
									xtype: 'hidden',
									name: 'k_meta_table_id',
									value: r.data[0].meta_table_id
								},{
									xtype: 'hidden',
									name: 'k_meta_field_id',
									value: r.data[0].meta_field_id
								},{
									xtype: 'hidden',
									name: 'k_type',
									value: r.data[0].meta_field_type
								}]);

								Ext.each(r.data, function(item, index, allItems){
									var attr = {};

									attr.msgTarget = 'under';
									attr.xtype = item.meta_field_type;
									attr.name = item.meta_field_name;
									attr.fieldLabel = item.meta_field_name;
									if(item.is_required == '1') attr.allowBlank = false;

									switch (item.meta_field_type) {
										case 'combo':
											attr.store = item.store;
											attr.mode = 'local';
											attr.triggerAction = 'all';
											attr.editable = false;
										break;

										case 'datefield':
											attr.format = 'Y-m-d';
											attr.editable = false;
										break;
									}

									form.add(attr);
									form.doLayout();

									form.get(0).focus();
								});

								form.add([{
									xtype: 'checkbox',
									fieldLabel: '공개여부',
									name: 'k_is_hidden',
									checked: true,
									inputValue: 'Y'
								},{
									xtype: 'datefield',
									fieldLabel: '보존만료',
									name: 'k_expire_date',
									editable: false,
									format: 'Y-m-d',
									value: new Date().add(Date.YEAR, 2)
								}]);
								form.doLayout();

							}else{
								Ext.Msg.alert('오류', r.msg);
							}
						}catch(e) {
							Ext.Msg.alert('디코드 오류(' + e + ')', response.responseText);
						}
					}else{
						Ext.Msg.alert('페이지 요청 오류', response.statusText + '(' + response.status + ')');
					}
				}
			});		
		}

		function queue_next(){
			var sm = Ext.getCmp('queue_list').getSelectionModel();
			if(!sm.selectNext()) alert('더이상 없습니다.');
		}

		function queue_previous() {
			var sm = Ext.getCmp('queue_list').getSelectionModel();
			if(!sm.selectPrevious()) alert('더이상 없습니다.');
		}

		function _queue_registered(id) {
			Ext.Ajax.request({
				url: '/pages/menu/config/queue/php/delete.php',
				params: {
					id: id,
					status: 'completed'
				},
				callback: function(options, success, response){
					if(success) {
						try {
							var r = Ext.decode(response.responseText, true);
							if(r.success) {
								Ext.getCmp('queue_list').getStore().reload();
							}else{
								Ext.Msg.alert('등록 오류', r.msg);
							}
						}catch (e) {
							Ext.Msg.alert('디코드 오류', e + '(' + response.responseText + ')');
						}
					}else{
						Ext.Msg.alert('페이지 요청 오류', response.status + '(' + response.statusText + ')');
					}
				}
			});
		}

		function queue_submit(btn, e) {
			var sm = Ext.getCmp('queue_list').getSelectionModel();
			if(!sm.hasSelection()){
				Ext.Msg.alert('정보', '등록하실 콘텐츠을 선택해주세요.');
				return;
			}

			if(Ext.getCmp('main_category').getSelectionModel().isSelected()){
				Ext.Msg.alert('정보', '카테고리를 선택해주세요.');
				return;
			}

			if(Ext.getCmp('meta_table_combo').getValue() == ''){
				Ext.Msg.alert('정보', '메타데이터 유형을 선택해주세요.');
				return;
			}

			var f = Ext.getCmp('queue_metadata').getForm();
			if(!f.isDirty()){
				Ext.Msg.alert('정보', '메타데이터 변경 내용이 없습니다.');
				return;
			}

			var is_valid = f.isValid();
			if(is_valid) {
				btn.disable();
				var request_win = Ext.Msg.wait('등록 요청 중입니다.');
				
				var r = sm.getSelected();
				
				var p = Ext.getCmp('queue_metadata').getForm().getValues();
				Ext.apply(p, {
					k_content_id: r.get('content_id'),
					k_category: Ext.getCmp('main_category').getSelectionModel().getSelectedNode().attributes.id
				});

				Ext.Ajax.request({
					url: '/pages/menu/config/queue/php/add.php',
					params: p,
					callback: function(opts, success, response){
						request_win.hide();
						btn.enable();
						if(success){
							try {
								var r = Ext.decode(response.responseText);
								if(r.success){
									Ext.getCmp('main_category').getSelectionModel().clearSelections();
									var r = Ext.getCmp('queue_list').getSelectionModel().getSelected();
									_queue_registered(r.get('id'));
									Ext.getCmp('queue_metadata').removeAll();
								}else{
									Ext.Msg.alert('오류', r.msg);
								}
							}
							catch(e){
								Ext.Msg.alert('오류', e+'<br />'+response.responseText);
							}
						}else{
							Ext.Msg.alert('오류', response.statusText+'('+response.status+')');
						}
					} 
				});
			}else{
				Ext.Msg.alert('입력 오류', '필수 항목에 값을 넣어주세요');
			}
		}

		return {
			id: 'reg_form',
			title: '메타데이터 입력',
			region: 'east',
			xtype: 'panel',
			width: 500,
			split: true,
			layout: 'border',
			items: [{
				xtype: 'treepanel',
				id: 'main_category',
				region: 'west',
				split: true,
				collapsible: true,
				width: 180,
				autoScoroll: true,

				loader: new Ext.tree.TreeLoader({
					url: '/store/get_categories.php',
					listeners: {
						beforeload: {
							fn: function (treeLoader, node, callback){
								treeLoader.baseParams.action = "get-folders";
							},
							scope: this
						}
					}
				}),
				root: new Ext.tree.AsyncTreeNode({
					id: '0',
					text: "Ariel MAM",
					expanded: true
				}),
				listeners: {
					click: function(self, e){
						if(!Ext.getCmp('queue_list').getSelectionModel().hasSelection()){
							Ext.Msg.alert('정보', '등록 하실 항목 부터 선택해주세요.');
							return;
						}
					}
				}
			},{
				id: 'queue_metadata',
				region: 'center',
				flex: 1,
				url: '/pages/menu/config/queue/php/add.php',
				xtype: 'form',
				split: true,
				baseCls:'x-plain',
				trackResetOnLoad: true,
				padding: '5px',
				defaultType: 'textfield',
				defaults: {
					anchor: '100%'
				},
				tbar: ['종류: ',{
					id: 'meta_table_combo',
					flex: 1,
					xtype: 'combo',
					typeAhead: true,
					triggerAction: 'all',
					editable: false,
					mode: 'remote',
					displayField: 'name',
					valueField: 'meta_table_id',
					listeners: {
						select: function(self, r, idx){
							_add_form(r.get('meta_table_id'));
						},
						beforequery: function(qe){
							if(!Ext.getCmp('queue_list').getSelectionModel().hasSelection()){
								Ext.Msg.alert('정보', '등록 하실 항목 부터 선택해주세요.');
								return;
							}
							delete qe.combo.lastQuery;
						}
					},
					store: new Ext.data.JsonStore({
						url: '/pages/menu/config/queue/php/get_meta_type.php',
						root: 'data',
						fields: [
							'meta_table_id',
							'name'
						]
					})
				}],
				items: [{
					hidden: true
				}],
				keys: [{
					key: 34, // 34 : page_down, 40 : down
					fn: queue_next
				},{
					key: 33, // 34 : page_down, 40 : down
					fn: queue_previous
				},{
					key: 13,
					fn: queue_submit
				}],
				buttons: [{
					text: '등록',
					handler: queue_submit
				}]
			}]
		}
	},

	_submit: function(_form, _record){
		Ext.Msg.show({
			msg: '변경사항이 있습니다. 등록 하시겠습니까?',
			modal: true,
			icon: Ext.Msg.QUESTION,
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId, text, opt){
				if(btnId == 'ok'){
					_form.submit({
						url: 'ass.php',
						success: function(form, action){
						},
						failure: function(form, action){
							Ext.Msg.alert('오류', '등록에 실패하였습니다.');
						}
					});
				}
				_form.reset().loadRecord(_record);
				Ext.getCmp('queue_list').store.reload();
			}
		});
	}
});

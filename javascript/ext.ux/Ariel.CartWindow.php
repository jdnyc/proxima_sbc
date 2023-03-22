<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
?>
Ext.ns('Ariel');
Ariel.CartWindow = Ext.extend(Ext.Window, {
	id: 'work_window',
//	autoDestory: false,
	closeAction: 'hide',
	title: '작업 판넬',
	layout: 'fit',
	autoScroll: true,
	width: 600,
	height: 400,

	initComponent: function(config){
		Ext.apply(this, config || {});

		this.editorAction = new Ext.form.ComboBox({
			typeAhead: true,
			editable: false,
			triggerAction: 'all',
			mode: 'local',
			store: [['n', '없음'], ['t', '트랜스코더'], ['p', '파셜리스토어'], ['c', '카탈로그']]
		});

		this.tbar = [{
			icon: 'led-icons/bin_closed.png',
			text: '작업 삭제',
			handler:  this.sendTaskAction.createDelegate(this, ['delete'])
		},{
			icon: 'led-icons/arrow_refresh.png',
			text: '새로 고침',
			handler: function(btn, e){
				Ext.getCmp('work_list').getStore().reload();
			}
		}];

		this.buttons = [{
			text: '작업 실행',
			scale: 'medium',
			split: false,
			menuAlign: 'bl-tl',
			menu: {
				items: [{
					text: '전송',
					icon: '/led-icons/delivery.png',
					handler: function(btn,e ){
						this.chooseOptionWinTask('transfer');
					},
					scope: this
				},{
					text: '다운로드',
					icon: '/led-icons/disk.png',
					handler: function(btn, e){
						this.chooseOptionWinTask('download');
					},
					scope: this
				},{
					text: 'VCR리스트작성',
					icon: '/led-icons/page_white_paintbrush.png',
					handler: function(btn, e){
						var g = Ext.getCmp('work_list');
						var sm = g.getSelectionModel(); sm.selectAll();
						var records = sm.getSelections();

						var argContentID = [];;
						 Ext.each(records, function(r, i, a){
							 //console.log(r.get('meta_table_id'));
							 if (r.get('meta_table_id') == <?=PRE_PRODUCE?> || r.get('meta_table_id') == <?=CLEAN?>) {
								 argContentID.push(r.get('content_id'));
							 } else {
								 Ext.Msg.alert('정보', '사전제작 콘텐츠와 클린 콘텐츠만 VCR리스트 작성이 가능합니다.');
							 }
						});

						var args = '"write_content" "'+argContentID.join(';')+'" "<?=$_SESSION['user']['user_id']?>"';
						runArielAppVCRList('vcr', args);
					},
					scope: this
				},{
					<?php
					$hide_review = 'true';
					if (in_array(REVIEW_GROUP, $_SESSION['user']['groups'])
						|| in_array(CHANNEL_GROUP, $_SESSION['user']['groups'])) {
						$hide_review = 'false';
					}
					?>
					disabled: <?=$hide_review?>,
					text: '심의',
					icon: '/led-icons/page_white_star.png',
					handler: function(btn,e ){
						Ext.Ajax.request({
							url: '/store/component/reviewRequest.js',
							callback: function(opts, success, response){
								if (success) {
									try {
										var w = Ext.decode(response.responseText);
										w.show();
									} catch(e) {
										Ext.Msg.alert('<?=_text('MN00022')?>', e);
									}
								}else{
									Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
								}
							}
						})
					},
					scope: this
				},{
					hidden: true,
					text: '트랜스코딩',
					icon: '/img/Transcoding.png',
					handler: function(btn,e ){
						this.chooseOptionWinTask('transcoder');
					},
					scope: this
				},{
					hidden: true,
					text: '카탈로깅',
					icon: '/img/Cataloging.png',
					handler: function(btn,e ){
						this.chooseOptionWinTask('cataloging');
					},
					scope: this
				}]
			},
			listeners: {
				click: function(self, e){
					if(!Ext.getCmp('work_list').getStore().getCount()){
						self.hideMenu();
						Ext.Msg.alert('정보', '작업할 내용이 없습니다. 작업을 추가해주세요');
						return;
					}

					var s = Ext.getCmp('work_list').getStore();

					var t = [], e = true;
					for(var i=1; i < s.getCount(); i++){
						if(s.getAt(0).get('content_type') != s.getAt(i).get('content_type')){
							e = false;
							break;
						}
					}
					if(e && s.getAt(0).get('content_type') == '동영상'){
						this.menu.items.items[1].enable();
						this.menu.items.items[2].enable();
					}
					else if(e && s.getAt(0).get('content_type') == '사운드'){
						this.menu.items.items[1].enable();
						this.menu.items.items[2].disable();
					}
					else{
						this.menu.items.items[1].disable();
						this.menu.items.items[2].disable();
					}

				}
			}
		}];

		this.items = {
			id: 'work_list',
			xtype: 'grid',
			border: false,
			loadMask: true,
			tmp: [],

			keys: [{
				key: 46,
				fn: this.sendTaskAction.createDelegate(this, ['delete'])
			}],
			store: new Ext.data.JsonStore({
				url: '/store/get_work.php',
				root: 'data',
				fields: [
					'content_type',
					'content_type_id',
					'content_id',
					'meta_table_id',
					'meta_table_name',
					'task_id',
					'cart_id',
					'type',
					'title',
					{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}
				],
				listeners: {
					exception: function(self, type, action, options, response, args){
						Ext.Msg.alert('오류',  response.responseText);
					},
					add: function(self, record, index){
						Ext.each(record, function(item, index, allItem){
							Ext.Ajax.request({
								url: '/store/cart.php',
								params: {
									content_id: item.get('content_id')
								},
								callback: function(self, success, response){
									if(success){
										try {
											var r = Ext.decode(response.responseText);
											if(!r.success){
												throw r.msg;
											}

											item.set('cart_id', r.id);
										}
										catch(e){
											Ext.Msg.alert('오류', e+"<br />responseText: "+response.responseText);
											Ext.getCmp('work_list').getStore().remove(item);
										}
									}else{
										Ext.Msg.alert('오류', response.statusText+'( '+response.status+' )');
										Ext.getCmp('work_list').getStore().remove(item);
									}
								},
								scope: this
							});
						});
					},
					remove: function(self, record, index){
					}
				}
			}),
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					sortable: true,
					menuDisabled: true
				},
				columns: [
					{header: 'Content Type', dataIndex: 'content_type'},
					{header: '유형', dataIndex: 'meta_table_name'},
					{header: '제목', dataIndex: 'title', width: 200},
					{header: '상태', dataIndex: 'progress', renderer: this.renderProgress},
					{header: '등록일', dataIndex: 'created_time', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130, align: 'center'}
				]
			}),
			selModel: new Ext.grid.RowSelectionModel({

			}),
			listeners: {
				viewready: function(self){
					self.getStore().load();
				},
				afterrender: {
					fn: function(self){
						var dd = new Ext.dd.DropTarget(self.el, {
							ddGroup:'ContentDD',
							notifyDrop: function(dd, e, node){
								var Plant = self.getStore().recordType;
								for(var i=0; i<node.selections.length; i++){
									var r = node.selections[i];

//									self.store.each(function(_r){
//										console.log(_r.get('content_id'));
//										console.log(r.get('content_id'));
////										self.getStore().insert(0, new Plant({
////											content_type: r.get('content_type'),
////											content_type_id: r.get('content_type_id'),
////											content_id: r.get('content_id'),
////											title: r.get('title'),
////											created_time: new Date()
////										}));
//									});


									if(self.tmp.indexOf(r.get('content_id')) == undefined){
										App.setAlert(App.STATUS_NOTICE, "'"+r.get('title')+"' content_id 가 없습니다.");
										continue;
									}
									else if(self.tmp.indexOf(r.get('content_id')) > -1) {
										App.setAlert(App.STATUS_NOTICE, "'"+r.get('title')+"' 중복된 자료입니다.");
										continue;
									}
									self.tmp.push(r.get('content_id'));
									self.getStore().insert(0, new Plant({
										content_type: r.get('content_type'),
										content_type_id: r.get('content_type_id'),
										content_id: r.get('content_id'),
										title: r.get('title'),
										created_time: new Date()
									}));
								}

								return true;
							}
						})
					},
					scope: this
				},
				afteredit: function(e){
				},
				contextmenu: function(self, index, node, e){
					var m = self.parent.menu;

					var d = m.find('text', '삭제');
					if(d.length == 0){
						m.addItem({
							text: '삭제',
							handler: function(b, e){
								var s = self.getStore();
								var rs = self.getSelectedRecords();

								for(var i=0; i<rs.length; i++){
									s.remove(rs[i]);
								}
							}
						});
					}

					e.stopEvent();
					m.showAt(e.getXY());
				}
			}
		}

		Ariel.CartWindow.superclass.initComponent.call(this);

		<?php
		if ($_POST['action'] == 'selected') {
		?>
/*
		var work_list = Ext.getCmp('work_list');
		var records = Ext.getCmp('grid_list').getSelectionModel().getSelections();
		var Plant = work_list.getStore().recordType;

		Ext.each(records, function(item, idx, allItems){
			if(work_list.tmp.indexOf(item.get('content_id')) == undefined){
				App.setAlert(App.STATUS_NOTICE, "'"+item.get('title')+"' content_id 가 없습니다.");
				continue;
			}
			else if(work_list.tmp.indexOf(item.get('content_id')) > -1) {
				App.setAlert(App.STATUS_NOTICE, "'"+item.get('title')+"' 중복된 자료입니다.");
				continue;
			}

			work_list.tmp.push(item.get('content_id'));
			work_list.getStore().insert(0, new Plant({
				content_type: item.get('content_type'),
				content_type_id: item.get('content_type_id'),
				content_id: item.get('content_id'),
				title: item.get('title'),
				created_time: new Date()
			}));
		});

		this.chooseOptionWinTask('transfer');
*/
		<?php
		}
		?>
	},

	chooseOptionWinTask: function(task, content_id){
		Ext.getCmp('work_list').getSelectionModel().selectAll();

		var content_ids = '';
		var cart_ids = '';
		var rs = Ext.getCmp('work_list').getSelectionModel().getSelections();
		Ext.each(rs, function(item, itemIndex, itemAll){
			content_ids += item.get('content_id')+',';
			cart_ids += item.get('cart_id')+',';
		});

		var options_base = [{
			xtype: 'hidden',
			name: 'cart_ids',
			value: cart_ids
		},{
			xtype: 'hidden',
			name: 'content_ids',
			value: content_ids
		}];
		switch(task){
			case 'download':
				var rs = Ext.getCmp('work_list').getSelectionModel().getSelections();
				if (rs.length == 0) {
					Ext.Msg.alert('정보', '선택되어진 항목이 없습니다.');
					return;
				}

				new Ext.Window({
					modal: true,
					width: 300,
					height: 150,
					title: '다운로드 사유',
					border: false,
					layout: 'border',

					items: [{
						region: 'center',
						xtype: 'textarea',
						allowBlank: false,
						name: 'download_summary',
						emptyText: '다운로드 사유를 적어주세요.'
					}],

					listeners: {
						show: function(self){
							self.get(0).focus(false, 500);
						}
					},

					buttons: [{
						text: '다운로드',
						handler: function(b, e){
							var s = b.ownerCt.ownerCt.get(0);
							if (!s.isValid()) {
								return;
							}

							var ids = [];
							Ext.each(rs, function(i){
								ids.push(i.get('content_id'));
							})
							Ext.Ajax.request({
								url: '/store/get_download_list.php',
								params: {
									content_id: ids.join(','),
									summary: s.getValue()
								},
								callback: function(opts, success, resp){
									//console.log(resp.responseText);
									GeminiAxCtrl.MultiDownload(resp.responseText);
								}
							});
						}
					},{
						text: '취소',
						handler: function(b, e){
							b.ownerCt.ownerCt.close();
						}
					}]
				}).show();

			break;

			case 'transfer':
				var height = 110;
				var options_add = [{
					xtype: 'hidden',
					name: 'task',
					value: 'transfer'
				},{
					xtype: 'combo',
					allowBlank: false,
					typeAhead: true,
					triggerAction: 'all',
					editable: false,
					store: new Ext.data.JsonStore({
						url: '/store/get_taransfer.php',
						root: 'data',
						fields: [
							'name'
						]
					}),
					displayField: 'name',
					fieldLabel: '코덱',
					emptyText: '전송지를 선택하여 주세요.',
					name: 'destination',
					listeners: {
						invalid: function(self, msg){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() != 120){
								w.setHeight(120);
							}
						},
						valid: function(self){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() != 110 ){
								w.setHeight(110);
							}
						}
					}
				}];
			break;

			case 'transcoder':
				var height = 165;
				var options_add = [{
					xtype: 'hidden',
					name: 'task',
					value: 'transcoder'
				},{
					xtype: 'combo',
					allowBlank: false,
					typeAhead: true,
					triggerAction: 'all',
					editable: false,
					store: ['H.264', 'WMV'],
					fieldLabel: '코덱',
					emptyText: '동영상 유형을 선택해주세요',
					name: 'codec',
					listeners: {
						invalid: function(self, msg){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() < 210 ){
								w.setHeight(w.getHeight() + 15);
							}
						},
						valid: function(self){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() > 165 ){
								w.setHeight(w.getHeight() - 15);
							}
						}
					}
				},{
					xtype: 'combo',
					allowBlank: false,
					typeAhead: true,
					triggerAction: 'all',
					editable: false,
					store: ['320x240', '480x270'],
					fieldLabel: '해상도',
					emptyText: '해상도을 선택해주세요',
					name: 'resolution',
					listeners: {
						invalid: function(self, msg){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() < 210 ){
								w.setHeight(w.getHeight() + 15);
							}
						},
						valid: function(self){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() > 165 ){
								w.setHeight(w.getHeight() - 15);
							}
						}
					}
				},{
					xtype: 'combo',
					allowBlank: false,
					typeAhead: true,
					triggerAction: 'all',
					editable: false,
					store: ['300', '500', '1000'],
					fieldLabel: '비트레이트',
					emptyText: '비트레이트을 선택해주세요',
					name: 'vbitrate',
					listeners: {
						invalid: function(self, msg){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() < 210 ){
								w.setHeight(w.getHeight() + 15);
							}
						},
						valid: function(self){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() > 165 ){
								w.setHeight(w.getHeight() - 15);
							}
						}
					}
				}];
			break;

			case 'cataloging':
				var height = 160;
				var options_add= [{
					xtype: 'hidden',
					name: 'task',
					value: 'cataloging'
				},{
					xtype: 'combo',
					allowBlank: false,
					typeAhead: true,
					triggerAction: 'all',
					editable: false,
					mode: 'local',
					store: new Ext.data.ArrayStore({
						id: 0,
						fields: [
							'value', 'display'
						],
						data: [
							['auto', '자동 장면 전환']	,
							['custom', '지정 갯수 추출']
						]
					}),
					displayField: 'display',
					hiddenName: 'value',
					valueField: 'value',
					fieldLabel: '카탈로깅 유형',
					emptyText: '카탈로깅 유형을 선택해주세요',
					name: 'type',
					listeners: {
						select: function(self, record, index){
							if(record.get('value') == 'custom'){
								if(self.ownerCt.get('opts_sensitivity')) self.ownerCt.remove(self.ownerCt.get('opts_sensitivity'));
								self.ownerCt.add({
									xtype: 'combo',
									allowBlank: false,
									typeAhead: true,
									triggerAction: 'all',
									editable: false,
									store: [5, 10, 20, 30, 40, 50, '사용자 직접 입력'],
									fieldLabel: '장면 수',
									emptyText: '장면 수를 선택해주세요',
									group: 'extract_num',
									name: 'num',
									value: 10,
									listeners: {
										select: function(self, record, index){
											if(record.get('field1') == '사용자 직접 입력'){
												self.ownerCt.add({
													id: 'custom_num',
													group: 'extract_num',
													allowBlank: false,
													xtype: 'numberfield',
													name: 'num',
													maxValue: 100,
													emptyText: '장면 수를 입력해주세요'
												});
											}else{
												if(self.ownerCt.get('custom_num')){
													self.ownerCt.remove(self.ownerCt.get('custom_num'));
												}
											}
											self.ownerCt.doLayout();
										}
									}
								});
								self.ownerCt.doLayout();
							}else{
								var opt_form = Ext.getCmp('cart_option');
								var r = opt_form.findBy(function(item){
									if(item.group == 'extract_num') return true;
								});
								Ext.each(r, function(item, index, allItems){
									opt_form.remove(item);
								});

								if(!opt_form.get('opts_sensitivity')){
									opt_form.add({
										id: 'opts_sensitivity',
										xtype: 'numberfield',
										emptyText: '민감도를 입력해주세요.',
										name: 'sensitivity',
										value: 19,
										maxValue: 100
									})
								}
								opt_form.doLayout();
							}
						},
						valid: function(self){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() != 110){
								//w.setHeight(110);
							}
						},
						invalid: function(self){
							var w = self.ownerCt.ownerCt;
							if(w.getHeight() != 120){
								//w.setHeight(120);
							}
						}
					}
				}];
			break;
		}

		var task_options = options_base.concat(options_add);
		return new Ext.Window({
			title: '작업 등록',
			autoShow: true,
			width: 280,
			height: height,
			layout: 'fit',
			modal: true,
			border: false,

			items: [{
				xtype: 'form',
				id: 'cart_option',
				url: '/store/task_add_from_cart.php',
				border: false,
				frame: true,
				padding: 5,
				defaults: {
					hideLabel: true,
					msgTarget: 'under',
					anchor: '100%'
				},
				items: task_options
			}],

			buttonAlign: 'center',
			buttons: [{
				text: '등록',
				handler: function(btn, e){
					var f = btn.ownerCt.ownerCt.get(0).getForm();
					if(f.isValid()){
						Ext.Ajax.request({
							url: '/store/task_add_from_cart.php',
							params: f.getValues(),
							callback: function(self, success, response){
								if(!success) {
									Ext.Msg.alert('오류', response.statusText+'('+response.status+')');
								}else{
									try {
										var r = Ext.decode(response.responseText);
										if(r.success){
											Ext.getCmp('work_list').getStore().reload();
											btn.ownerCt.ownerCt.close();
										}else{
											Ext.Msg.alert('오류', r.msg);
										}
									}
									catch(e){
										Ext.Msg.alert('디코드 오류', e+"<br />"+response.responseText);
									}
								}
							}
						})
					}
				},
				scope: this
			},{
				text: '취소',
				handler: function(btn, e){
					this.ownerCt.ownerCt.close();
				}
			}]
		}).show();
	},

	renderAction: function(v, p, r){
		var t;
		switch(v){
			case 't':
				t = '트랜스코더';
				break;
			case 'p':
				t = '파셜리스토어';
				break;
			case 'c':
				t = '카탈로그';
				break;
			default:
				t = '없음';
				break;
		}

		return t;
	},

	renderProgress: function(v, p, r){
		if(v == 0 || !v)
			return '대기중';
		else
			return v;
	},

	sendTaskAction: function(action){
		var wp = Ext.getCmp('work_list');
		var sm = wp.getSelectionModel();
		if(!sm.hasSelection()){
			Ext.Msg.alert('정보', '삭제하실 항목을 선택하여주세요');
			return;
		}

		var rs = sm.getSelections();
		switch(action){
			case 'delete':
				Ext.each(rs, function(item, index, allItem){
					Ext.Ajax.request({
						url: '/store/cart_remove.php',
						params: {
							cart_id: item.get('cart_id')
						},
						callback: function(opts, success, response){
							if(success){
								try {
									var r = Ext.decode(response.responseText);
									if(r.success){
										wp.getStore().remove(item);
									}else{
										Ext.Msg.alert('오류', r.msg);
									}
								}
								catch(e){
									Ext.Msg.alert('오류', e+"<br />"+response.responseText);
								}
							}else{
								Ext.Msg.alert('오류', response.statusText+'('+response.status+')');
							}
						}
					})
				})
			break;
		}
	},

	getSelected: function(){
		return this.get(0).getSelectionModel().getSelected();
	},

	hasSelection: function(){
		return this.get(0).getSelectionModel().hasSelection();
	}

});

globalWorkPanel = new Ariel.CartWindow();
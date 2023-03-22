<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

$streamer_addr = STREAMER_ADDR.'/vod';
$switch = true;
?>

Ariel.Nps.BISEpisode = Ext.extend(Ext.Panel, {
	layout: 'fit',
	fieldLabel: _text('MN02462'),
	border: false,

	initComponent: function(config){
		Ext.apply(this, config || {});

		var store = new Ext.data.JsonStore({
			url: '/store/bis/get_episode_list.php',
			root: 'data',
			totalProperty: 'total',
			autoLoad: false,
			fields: [
				'award_info',				'brd_run',
				{name :'brd_ymd',type:'date',dateFormat:'Ymd'},
				'ca_right',					'cjenr_clf',
				'cjenr_clf_code',			'cstry_clf',
				'cstry_clf_code',			'delib_grd',
				'delib_grd_code',			'director',
				'dm_right',					'emerg_yn',
				'epsd_no',
				'epsd_id',					'epsd_nm',
				'epsd_onm',					'flash_yn',
				'frgn_clf',					'frgn_clf_code',
				'house_no',					'info_grd',
				'ip_right',					'jenr_clf',
				'jenr_clf1',				'jenr_clf_code',
				'main_role',				'mc_nm',
				'news_yn',					'pgm_clf',
				'pgm_clf1',					'pgm_clf1_code',
				'pgm_clf2',					'pgm_clf2_code',
				'pgm_clf_code',				'pgm_id',
				'pgm_info',					'pgm_nm',
				'pgm_onm',					'pgm_typ',
				'pgm_type_code',			'pilot_yn',
				'pp_right',					'prd_clf',
				'prd_clf_code',				'prd_cntry1',
				'prd_cntry1_code',			'prd_cntry2',
				'prd_cntry2_code',			'prd_co_cd',
				'prd_co_nm',
				{name :'prd_ym',type:'date',dateFormat:'Ymd'},
				'sa_right',					'scl_clf',
				'scl_clf_code',				'st_right',
				'stry_clf1',				'stry_clf1_code',
				'supp_role',				'synopsis',
				'target',					'target_code',
				'tot_cnt',					'trff_no',
				'trff_ymd',					'trff_seq',
				'use_yn',
				'view_hm',					'vo_right',
				{name: '4778261', mapping: 'pgm_id'},   // 프로그램ID
				{name: '4000292', mapping: 'pgm_nm'},   // 프로그램
//				{name: '4778262', mapping: 'house_no'},   // 소재ID   
				{name: '4778263', mapping: 'epsd_no'},   // 회차
				{name: '4778407', mapping: 'epsd_id'},   // 회차  
				{name: '4000293', mapping: 'epsd_nm'},   // 부제 (일단 회차명을 부제로)
				{name: '4000289', mapping: 'brd_ymd', type:'date',dateFormat:'Ymd'},    // 방송일자
				{name: '4778141', mapping: 'delib_grd_code'},   // 등급분류
				{name: '4000294', mapping: 'pgm_info'},   // 내용
				{name: '4000288', mapping: 'main_role'}   // 담당PD
			],
			listeners: {
				load: function(self, r, opt){
					//로드 후 첫 데이터 폼에 입력 2014-12-28
					var metaTab = Ext.getCmp('episode_list').ownerCt.metaTab;
					if( !Ext.isEmpty(metaTab) ){
						var p = Ext.getCmp(metaTab).activeTab.getForm();
						
						if(!Ext.isEmpty(r[0])){
							Ext.getCmp('episode_list').getSelectionModel().selectFirstRow();
							p.loadRecord(r[0]);
						}
					}
					
					Ext.getCmp('episode_list').fireEvent('onafterload', self, r, opt);
				},
				exception: function(self, type, action, opts, response, args){
				
					if (type == 'response'){
						var result = Ext.decode(response.responseText);
						if (!result.success){
							Ext.Msg.alert(_text('MN00024'), result.msg);
						}
					}
				}
			}
		});

		this.items = {
			xtype: 'grid',
			id: 'episode_list',
			region: 'south',
			cls: 'proxima_customize',
			height: 300,
			loadMask: true,
			store: store,
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					align: 'center'
				},
				columns: [
					new Ext.grid.RowNumberer(),
					{header: _text('MN02463'),    dataIndex: 'epsd_no', width: 40},
					{header: _text('MN02464'),    dataIndex: 'epsd_id', width: 60},
					{header: _text('MN02465'),    dataIndex: 'epsd_onm', align: 'center'},
					{header: _text('MN02466'),    dataIndex: 'epsd_nm', align: 'center'},
					{header: _text('MN02455'),		dataIndex: 'brd_ymd', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 80 },
					{header: _text('MN02467'),		dataIndex: 'brd_run', width : 40 }
				]
			}),
			viewConfig: {
				emptyText: _text('MSG02174'),
				forceFit: true
			},
			listeners: {
				viewready: function(self) {
				},
				rowdblclick: function(self, rowIndex, e) {
					var metaTab = self.ownerCt.metaTab;
					var p = Ext.getCmp(metaTab).activeTab.getForm();
	
					var episode_list = self.getSelectionModel();
	
					if(episode_list.hasSelection()) {
						var rec = episode_list.getSelected();
						p.loadRecord(rec);
					} else {
						Ext.Msg.alert(_text('MN00023'), _text('MSG02175'));
					}
				}
			},
			tbar: [{
				cls : 'proxima_btn_customize',
				text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				handler: function(btn, e){
					var grid = btn.ownerCt.ownerCt;
					grid.getStore().reload();
				}
			}],
	
			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: store
			}
		};
	
		Ariel.Nps.BISEpisode.superclass.initComponent.call(this);
	}
});

Ariel.Nps.Cuesheet = Ext.extend(Ext.Panel, {
	layout: {
		type: 'vbox',
		align: 'stretch'
	},
	border: false,
	defaults: {
		split: true
	},

	initComponent: function(config){
		Ext.apply(this, config || {});

		this.items = [
			new Ariel.Nps.Cuesheet.List({
				flex: 1
			}),
			new Ariel.Nps.Cuesheet.Detail({
				flex: 1
			})
		];

		Ariel.Nps.Cuesheet.superclass.initComponent.call(this);
	}
});


Ariel.Nps.Cuesheet.List = Ext.extend(Ext.Panel, {
	layout: 'fit',
	border: false,

	initComponent: function(config){
		Ext.apply(this, config || {});

		var controlroom_nm = function(v) {
			switch(v) {
				case 'large' :
					return 'Large';
				break;
				case 'middle' :
					return 'Medium';
				break;
				case 'small' :
					return 'Small';
				break;
			}
		};
		var store = new Ext.data.JsonStore({
			url: '/store/cuesheet/get_cuesheet_list.php',
			root: 'data',
			idPropery: 'cuesheet_id',
			fields: [
				'cuesheet_id',
				'cuesheet_title',
				{name :'broad_date',type:'date',dateFormat:'YmdHis'},
				{name :'created_date',type:'date',dateFormat:'YmdHis'},
				'subcontrol_room',
				'created_system',
				'prog_id',
				'prog_nm',
				'user_id',
				'type',
				'duration'
			],
			listeners: {
				exception: function(self, type, action, options, response, arg){
					if(type == 'response') {
						if(response.status == '200') {
							Ext.Msg.alert(_text('MN00022'), response.responseText);
						}else{
							Ext.Msg.alert(_text('MN00022'), response.status);
						}
					}else{
						Ext.Msg.alert(_text('MN00022'), type);
					}
				}
			}
		});

		this.items = {
			xtype: 'grid',
			id: 'cuesheet_list',
			title: _text('MN02450'),
			cls: 'proxima_customize',
			region: 'center',
			loadMask: true,
			store: store,
			enableColumnMove: false,
			viewConfig:{
				emptyText:_text('MN02451')
			},
			colModel: new Ext.grid.ColumnModel({
				columns: [
					new Ext.grid.RowNumberer(),
					{header: 'CueSheet ID', dataIndex: 'cuesheet_id', hidden:true},
					{header: _text('MN02452'), dataIndex: 'cuesheet_title', width: 150, align: 'center'},
					{header: _text('MN02453'), dataIndex: 'prog_nm', width: 150, align: 'center'},
					{header: _text('MN02454'), dataIndex: 'duration', width: 80, align: 'center'},
					{header: _text('MN02455'), dataIndex: 'broad_date', width: 80, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center'},
					{header: _text('MN02456'), dataIndex: 'subcontrol_room', renderer: controlroom_nm, align: 'center'},
					{header: _text('MN02457'), width: 80 , dataIndex: 'user_id', align: 'center'},
					{header: _text('MN02458'), dataIndex: 'created_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'},
					{header: _text('MN02459'), dataIndex: 'type', hidden: true}
				]
			}),
			tbar: [{
				cls : 'proxima_btn_customize',
				text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				handler: function(btn, e){
					var is_dirty = Ext.getCmp('cuesheet_items').is_dirty;
					
					if(!is_dirty) { 
						Ext.getCmp('cuesheet_list').getStore().reload();
						Ext.getCmp('cuesheet_items').is_dirty = false;
						Ext.getCmp('cuesheet_items').getStore().removeAll();
					} else {
						Ext.Msg.show({
							animEl: e.getTarget(),
							title: _text('MN00024'),
							icon: Ext.Msg.INFO,
							msg: _text('MSG02170'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnID, text, opt) {
								if(btnID == 'ok') {
									Ext.getCmp('cuesheet_list').getStore().reload();
									Ext.getCmp('cuesheet_items').is_dirty = false;
									Ext.getCmp('cuesheet_items').getStore().removeAll();	
								} else {
									var old_cuesheet_id = Ext.getCmp('cuesheet_items').getStore().getAt(0).get('cuesheet_id');
									var idx = Ext.getCmp('cuesheet_list').getStore().find('cuesheet_id', old_cuesheet_id);
									Ext.getCmp('cuesheet_list').getSelectionModel().selectRow(idx);
								}
							}
						})
					}
				}
			},{
				xtype: 'button',
				cls : 'proxima_btn_customize',
				text: '<span style="position:relative;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//search
				handler: function(self){
					var position_arr = self.ownerCt.items.items[0].getPosition();
					var x_pos = position_arr[0];
					var y_pos = position_arr[1]+22;

					if( !Ext.isEmpty(cuesheetSearchWin) ) {
						cuesheetSearchWin.setPosition(x_pos,y_pos);
						cuesheetSearchWin.show();
						return;
					}

					cuesheetSearchWin = new Ext.Window({
						width: 350,
						height: 170,
						title: _text('MN00037'),
						id: 'cuesheet_search_win',
						closeAction: 'hide',
						buttonAlign: 'center',
						layout: 'fit',
						x: x_pos,
						y: y_pos,
						items: [{
							xtype: 'form',
							id: 'cuesheet_search_win_form',
							layout: 'form',
							padding: 5,
							labelAlign: 'right',
							labelWidth: 100,
							buttonAlign: 'center',
							defaults: {
								anchor: '95%'
							},
							items: [{
								xtype: 'compositefield',
								fieldLabel: _text('MN00180'),
								width: 120,
								items: [{
									xtype: 'datefield',
									name: 'broad_sdate',
									fieldLabel: _text('MN00180'),
									width: 90,
									format: 'Y-m-d',
									listeners: {
										render: function(self) {
											// 기본 검색 기간은 7일
											var sdate = new Date().add(Date.DAY, -7);
											self.setValue(sdate);
										}
									}
								},{
									xtype: 'displayfield',
									value: '~',
									width: 12
								},{
									xtype: 'datefield',
									name: 'broad_edate',
									fieldLabel: _text('MN00180'),
									width: 90,
									format: 'Y-m-d',
									listeners: {
										render: function(self) {
											self.setValue(new Date());
										}
									}
								}]
							},{
								xtype : 'combo',
								id : 's_cue_prog',
								fieldLabel: _text('MN02453'),
								typeAhead: true,
								triggerAction: 'all',
								width : 120,
								editable : false,
								valueField: 'prog_id',
								displayField: 'prog_nm',
								hiddenName: 'prog_id',
								store: new Ext.data.JsonStore({
									url: '/store/cuesheet/get_program_list.php',
									autoLoad: true,
									root: 'data',
									baseParams: {
										action: 's_cuesheet'
									},
									fields: [
										'prog_id','prog_nm'
									],
									listeners: {
										load: function(self, records, opts) {
											Ext.getCmp('s_cue_prog').setValue('all');
										}
									}
								})
							},{
								xtype: 'combo',
								width: 70,
								name: 'subcontrol_room',
								store: new Ext.data.ArrayStore({
									fields: [ 'value', 'name' ],
									data: [
										['all',	'All'],
										['large',	'Large'],
										['middle',	'Middle'],
										['small',	'Small']
									]
								}),
								allowBlank: false,
								hiddenName: 'subcontrol_room',
								valueField: 'value',
								displayField: 'name',
								value: 'all',
								fieldLabel: _text('MN02456'),
								mode: 'local',
								typeAhead: true,
								triggerAction: 'all',
								forceSelection: true,
								editable: false
							}]
						}],
						buttons: [{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),//검색
							scale: 'medium',
							handler: function(btn, e) {
								var is_dirty = Ext.getCmp('cuesheet_items').is_dirty;
					
								if(!is_dirty) {
									var values = Ext.getCmp('cuesheet_search_win_form').getForm().getValues();
									var sdate = values['broad_sdate'].replace('-','').replace("-","");
									var edate = values['broad_edate'].replace('-','').replace("-","");
									
									Ext.getCmp('cuesheet_list').getStore().load({
										params: {
											broad_sdate: sdate,
											broad_edate: edate,
											cuesheet_type: 'M',
											prog_id: values['prog_id'],
											subcontrol_room : values['subcontrol_room']
										},
										callback: function(opt, success, response){
											if(success) {
												Ext.getCmp('cuesheet_items').getStore().removeAll();
												Ext.getCmp('cuesheet_items').is_dirty = false;
												cuesheetSearchWin.hide();
											}
										}
									});
								} else {
									Ext.Msg.show({
										animEl: e.getTarget(),
										title: _text('MN00024'),
										icon: Ext.Msg.INFO,
										msg: _text('MSG02170'),
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnID, text, opt) {
											if(btnID == 'ok') {
												var values = Ext.getCmp('cuesheet_search_win_form').getForm().getValues();
												var sdate = values['broad_sdate'].replace('-','').replace("-","");
												var edate = values['broad_edate'].replace('-','').replace("-","");
												
												Ext.getCmp('cuesheet_list').getStore().load({
													params: {
														broad_sdate: sdate,
														broad_edate: edate,
														cuesheet_type: 'M',
														prog_id: values['prog_id'],
														subcontrol_room : values['subcontrol_room']
													},
													callback: function(opt, success, response){
														if(success) {
															Ext.getCmp('cuesheet_items').getStore().removeAll();
															Ext.getCmp('cuesheet_items').is_dirty = false;
															cuesheetSearchWin.hide();
														}
													}
												});	
											} else {
												cuesheetSearchWin.hide();
											}
										}
									})
								}
							}
						},{
							text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00031'),//'닫기'
							scale: 'medium',
							handler: function(btn, e) {
								cuesheetSearchWin.hide();
							}
						}]
					}).show();
				}
			}],
			listeners: {
				viewready: function(self){

				},
				rowclick: function(self, rowIndex, e) {
					var records = self.getStore().getAt(rowIndex);
					var cuesheet_id = records.get('cuesheet_id');
					var cuesheet_type = 'M';
					var is_dirty = Ext.getCmp('cuesheet_items').is_dirty;

					if(!is_dirty) {
						Ext.getCmp('cuesheet_items').store.load({
							params: {
								cuesheet_type: cuesheet_type,
								cuesheet_id: cuesheet_id
							}
						});
					} else {
						Ext.Msg.show({
							animEl: e.getTarget(),
							title: _text('MN00024'),
							icon: Ext.Msg.INFO,
							msg: _text('MSG02170'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnID, text, opt) {
								if(btnID == 'ok') {
									Ext.getCmp('cuesheet_items').store.load({
										params: {
											cuesheet_type: cuesheet_type,
											cuesheet_id: cuesheet_id
										}
									});	
									
									Ext.getCmp('cuesheet_items').is_dirty = false;	
								} else {
									var old_cuesheet_id = Ext.getCmp('cuesheet_items').getStore().getAt(0).get('cuesheet_id');
									var idx = Ext.getCmp('cuesheet_list').getStore().find('cuesheet_id', old_cuesheet_id);
									Ext.getCmp('cuesheet_list').getSelectionModel().selectRow(idx);
								}
							}
						})
					}
				}
			},
			buttonAlign: 'center',
			fbar: [{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
				scale: 'medium',
				handler: function(btn, e) {
					var cuesheet_type = 'M';
					this.buildAddCueSheet(e, cuesheet_type);
				},
				scope: this
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
				scale: 'medium',
				handler: function(btn, e) {
					var cuesheet_type = 'M';
					var hasSelection = Ext.getCmp('cuesheet_list').getSelectionModel().hasSelection();
					if(hasSelection) {
						var sel = Ext.getCmp('cuesheet_list').getSelectionModel().getSelected();

						this.buildEditCueSheet(e,sel, cuesheet_type);
					}else{
						Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
					}
				},
				scope: this
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),//delete
				scale: 'medium',
				handler: function(btn, e) {
					var hasSelection = Ext.getCmp('cuesheet_list').getSelectionModel().hasSelection();
					if(hasSelection) {
						this.buildDeleteCuesSheet(e);
					} else {
						Ext.Msg.alert(_text('MN00022'), _text('MSG00170'));
					}
				},
				scope: this
			}],
		};

		Ariel.Nps.Cuesheet.List.superclass.initComponent.call(this);
	},

	buildAddCueSheet: function(e, cuesheet_type){
		var win = new Ext.Window({
			id: 'add_cuesheet_win',
			border: false,
			layout: 'fit',
			split: true,
			title: _text('MN02460'),
			width: 600,
			height: 250,
			modal: true,
			items: [{
				id: 'add_cuesheet_form',
				xtype: 'form',
				padding: 10,
				defaults: {
					anchor: '100%'
				},
				items: [{
					xtype: 'textfield',
					name: 'cuesheet_title',
					fieldLabel: _text('MN02461'),
					allowBlank: false
				},{
					xtype: 'datefield',
					format: 'Y-m-d',
					name: 'broad_date',
					fieldLabel: _text('MN02455'),
					allowBlank: false,
					listeners: {
						render: function(self) {
							self.setValue(new Date());
						}
					}
				},{
					xtype: 'combo',
					store: new Ext.data.ArrayStore({
						fields: [ 'value', 'name' ],
						data: [
							['large', 'Large'],
							['middle',  'Middle'],
							['small', 'Small']
						]
					}),
					allowBlank: false,
					hiddenName: 'subcontrol_room',
					valueField: 'value',
					displayField: 'name',
					fieldLabel: _text('MN02456'),
					mode: 'local',
					typeAhead: true,
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					emptyText: _text('MSG02171')
				},{
					xtype: 'textfield',
					name: 'prog_nm',
					fieldLabel: _text('MN02453'),
					allowBlank: false
				}
				/*,{
					xtype : 'combo',
					hiddenName: 'prog_id',
					typeAhead: true,
					triggerAction: 'all',
					width : 100,
					editable : false,
					hidden : false,
					allowBlank: false,
					fieldLabel: _text('MN00322'),
					valueField: 'prog_id',
					displayField: 'prog_nm',
					store: new Ext.data.JsonStore({
						url: '/store/cuesheet/get_program_list.php',
						autoLoad: true,
						baseParams: {
									action: 's_cuesheet'
								},
						root: 'data',
						fields: [ 'prog_nm', 'prog_id' ]
					}),
					emptyText: _text('MSG02172'),
					listeners: {
						select: function(self, record, index) {
							var episode_list = Ext.getCmp('add_cuesheet_episode_list').items.items[0];

							episode_list.getStore().load({
							params: {
								pgm_id: record.data.prog_id
							}
							});
						}
					}
				},
				new Ariel.Nps.BISEpisode({
					id: 'add_cuesheet_episode_list',
					autoScroll: true
				}) */
			]
		}],
		buttonAlign: 'center',
		buttons: [{
			text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
			scale: 'medium',
			handler: function(btn, e) {
					if (Ext.getCmp('add_cuesheet_form').getForm().isValid()){
						Ext.getCmp('add_cuesheet_form').getForm().submit({
							url: '/store/cuesheet/cuesheet_action.php',
							params: {
								action: 'add',
								cuesheet_type: cuesheet_type,
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('add_cuesheet_win').close();
										Ext.getCmp('cuesheet_list').getStore().reload();
									} else {
										Ext.Msg.show({
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								} catch(e) {
									Ext.Msg.show({
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(form, action) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									title: _text('MN00022'),
									msg: action.result.msg,
									buttons: Ext.Msg.OK
								});
							}
						});
					}
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function() {
					Ext.getCmp('add_cuesheet_win').close();
				}
			}]
		}).show(e.getTarget());
	},

	buildEditCueSheet: function(e, rec, cuesheet_type){
		var win = new Ext.Window({
			id: 'edit_cuesheet_win',
			border: false,
			layout: 'fit',
			split: true,
			title: _text('MN02468'),
			width: 600,
			height: 250,
			modal: true,
			items: [{
				id: 'edit_cuesheet_form',
				xtype: 'form',
				padding: 10,
				defaults: {
					anchor: '100%'
				},
				items: [{
					xtype: 'hidden',
					name: 'cuesheet_id',
				},{
					xtype: 'textfield',
					name: 'cuesheet_title',
					fieldLabel: _text('MN02461'),
					allowBlank: false
				},{
					xtype: 'datefield',
					format: 'Y-m-d',
					name: 'broad_date',
					fieldLabel: _text('MN02455'),
					allowBlank: false,
					listeners: {
						render: function(self) {
							self.setValue(new Date());
						}
					}
				},{
					xtype: 'combo',
					store: new Ext.data.ArrayStore({
						fields: [ 'value', 'name' ],
						data: [
							['large', 'Large'],
							['middle',  'Middle'],
							['small', 'Small']
						]
					}),
					allowBlank: false,
					hiddenName: 'subcontrol_room',
					valueField: 'value',
					displayField: 'name',
					fieldLabel: _text('MN02456'),
					mode: 'local',
					typeAhead: true,
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					emptyText: _text('MSG02171')
				},{
					xtype: 'textfield',
					name: 'prog_nm',
					fieldLabel: _text('MN02453'),
					allowBlank: false
				}
				/*,{
					xtype : 'combo',
					id: 'edit_cuesheet_prog',
					typeAhead: true,
					triggerAction: 'all',
					width : 100,
					editable : false,
					hidden : false,
					allowBlank: false,
					fieldLabel: _text('MN00322'),
					valueField: 'prog_id',
					displayField: 'prog_nm',
					hiddenName: 'prog_id',
					forceSelection: true,
					store: new Ext.data.JsonStore({
						url: '/store/cuesheet/get_program_list.php',
						root: 'data',
						baseParams: {
										action: 's_cuesheet'
									},
						fields: [ 'prog_nm', 'prog_id' ]
					}),
					emptyText: _text('MSG02172'),
					listeners: {
						afterrender: function(self) {
							Ext.getCmp('edit_cuesheet_prog').getStore().load({
								callback: function(r, opt, success) {
									if(success) {
										Ext.getCmp('edit_cuesheet_form').getForm().loadRecord(rec);

										var episode_list = Ext.getCmp('edit_cuesheet_episode_list').items.items[0];

										episode_list.getStore().load({
											params: {
												pgm_id: rec.data.prog_id
											}
										});

									}
								}
							});
						},
						select: function(self, record, index) {
							var episode_list = Ext.getCmp('edit_cuesheet_episode_list').items.items[0];

							episode_list.getStore().load({
								params: {
									pgm_id: record.data.prog_id
								}
							});
						}
					}
				},
				new Ariel.Nps.BISEpisode({
					id: 'edit_cuesheet_episode_list'
				})*/
				]
			}],
			buttonAlign: 'center',
			buttons: [{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
				scale: 'medium',
				handler: function(btn, e) {
					if (Ext.getCmp('edit_cuesheet_form').getForm().isValid()){
						Ext.getCmp('edit_cuesheet_form').getForm().submit({
							url: '/store/cuesheet/cuesheet_action.php',
							params: {
								action: 'edit',
								cuesheet_type: cuesheet_type
							},
							success: function(form, action) {
								try {
									var result = Ext.decode(action.response.responseText, true);
									if(result.success) {
										Ext.getCmp('edit_cuesheet_win').close();
										Ext.getCmp('cuesheet_list').getStore().reload();
									} else {
										Ext.Msg.show({
											title: _text('MN00022'),
											icon: Ext.Msg.ERROR,
											msg: result.msg,
											buttons: Ext.Msg.OK
										})
									}
								} catch(e) {
									Ext.Msg.show({
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: e.message,
										buttons: Ext.Msg.OK
									})
								}
							},
							failure: function(form, action) {
								Ext.Msg.show({
									icon: Ext.Msg.ERROR,
									title: _text('MN00022'),
									msg: action.result.msg,
									buttons: Ext.Msg.OK
								});
							}
						});
					}
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function() {
					Ext.getCmp('edit_cuesheet_win').close();
				}
			}],
			listeners: {
				afterrender: function(self) {
					var rec = Ext.getCmp('cuesheet_list').getSelectionModel().getSelected();
					Ext.getCmp('edit_cuesheet_form').getForm().loadRecord(rec);
				}
			}
		});

		win.show(e.getTarget());
	},

	buildDeleteCuesSheet: function(e, cuesheet_id) {
		var rec = Ext.getCmp('cuesheet_list').getSelectionModel().getSelected();
		Ext.Msg.show({
			animEl: e.getTarget(),
			title: _text('MN00024'),
			icon: Ext.Msg.INFO,
			msg: _text('MSG02176')+' "' + rec.get('cuesheet_title') + '" ' +_text('MSG02177'),
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnID, text, opt) {
				if(btnID == 'ok') {
					Ext.Ajax.request({
						url: '/store/cuesheet/cuesheet_action.php',
						params: {
							action: 'del',
							cuesheet_id: rec.get('cuesheet_id')
						},
						callback: function(opts, success, response) {
							try {
								var r = Ext.decode(response.responseText, true);
								if(r.success) {
									Ext.getCmp('cuesheet_list').store.reload();
								} else {
									//>>Ext.Msg.alert('오류', r.msg);
									Ext.Msg.alert(_text('MN00022') , r.msg);
								}
							} catch(e) {
								alert(e.message + '(responseText: ' + response.responseText + ')');
							}
						}
					})
				}
			}
		})
	}
});

Ariel.Nps.Cuesheet.Detail = Ext.extend(Ext.Panel, {
	layout: 'fit',
	border: false,

	initComponent: function(config){
		Ext.apply(this, config || {});

		var store = new Ext.data.JsonStore({
			url: '/store/cuesheet/get_cuesheet_content.php',
			root: 'data',
			autoLoad: false,
			fields: [
				'cuesheet_id',
				'show_order',
				'title',
				'content_id',
				'cuesheet_content_id',
				'duration',
				'status',
				'progress'
			]
		});

		this.workStatusMapping = function(value){
			switch(value) {
				case 'queue':
					return '대기';
				break;
				case 'processing':
					return '진행중';
				break;
				case 'error':
					return '실패';
				break;
				case 'complete':
					return '완료';
				break;
				case 'empty':
					return '미전송';
				break;
			}
		};

		this.items = {
			xtype: 'grid',
			title: _text('MN02469'),
			id: 'cuesheet_items',
			cls: 'proxima_customize',
			region: 'south',
			height: 400,
			loadMask: true,
			enableDragDrop: true,
			ddGroup: 'cuesheetGridDD',
			enableColumnMove: false,
			is_dirty: false,
			dellist: [],
			store: store,
			colModel: new Ext.grid.ColumnModel({
				defaults: {
					align: 'center'
				},
				// 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
				columns: [
					new Ext.grid.RowNumberer(),
					{header: _text('MN02470'),		dataIndex: 'title', align: 'center' },
					{header: _text('MN02454'),		dataIndex: 'duration', },
					{header: _text('MN02471'),		dataIndex: 'cuesheet_id', hidden: true},
					{header: _text('MN02119'),	dataIndex: 'show_order', hidden: true},
					{header: _text('MN00287'),		dataIndex: 'content_id', hidden: true },
					{header: _text('MN02472'),		dataIndex: 'cuesheet_content_id', hidden: true },
					{header: _text('MN02473'),		dataIndex: 'status', renderer: this.workStatusMapping},
					new Ext.ux.ProgressColumn({
						header: _text('MN00261'),
						width: 90,
						dataIndex: 'progress',
						align: 'center',
						renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
							return Ext.util.Format.number(pct, "0%");
						}
					})
				]
			}),
			viewConfig: {
				emptyText: _text('MSG02178'),
				forceFit: true,
			},
			listeners: {
				viewready: function(self) {
					var downGridDroptgtCfg = Ext.apply({}, CueSheetDropZoneOverrides, {
						table: 'bc_cuesheet_content',
						id_field: 'cuesheet_content_id',
						ddGroup: 'cuesheetGridDD',
						grid : Ext.getCmp('cuesheet_items')
					});
					new Ext.dd.DropZone(Ext.getCmp('cuesheet_items').getEl(), downGridDroptgtCfg);
				},
				keypress: function(e) {
					var hasSelection = Ext.getCmp('cuesheet_items').getSelectionModel().hasSelection();
					if(hasSelection && e.getKey() == 32) {
						var selection = Ext.getCmp('cuesheet_items').getSelectionModel().getSelected();
						Ext.Ajax.request({
							url:'/store/cuesheet/player_window.php',
							params:{
								content_id: selection.get('content_id')
							},
							callback:function(option,success,response) {
								var r = Ext.decode(response.responseText);

								if(success) {
									r.show();

									$f("player3", {src: "/flash/flowplayer/flowplayer.swf", wmode: 'opaque'}, {
										clip: {
											autoPlay: false,
											autoBuffering: <?=$switch?>,
											scaling: 'fit',
											provider: 'rtmp'
										},
										plugins: {
											rtmp: {
												url: '/flash/flowplayer/flowplayer.rtmp.swf',
												netConnectionUrl: '<?=$streamer_addr?>'
											}
										},
										onKeypress: function(clip){
										}
									});
								} else {
									Ext.Msg.alert(_text('MN00022'), _text('MSG02179'));
									return;
								}
							}
						});
					}
				}
			},
			tbar: [{
				cls : 'proxima_btn_customize',
				text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				handler: function(btn, e){
					var is_dirty = Ext.getCmp('cuesheet_items').is_dirty;
					
					if(!is_dirty) {
						Ext.getCmp('cuesheet_items').store.reload();
					} else {
						Ext.Msg.show({
							animEl: e.getTarget(),
							title: _text('MN00024'),
							icon: Ext.Msg.INFO,
							msg: _text('MSG02170'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnID, text, opt) {
								if(btnID == 'ok') {
									Ext.getCmp('cuesheet_items').store.reload();
									Ext.getCmp('cuesheet_items').is_dirty = false;	
								}
							}
						})
					}
				}
			},{
				//text: _text('MN00243'),
				//icon: '/led-icons/transmit.png',
				cls : 'proxima_btn_customize',
				id: 'transfer_cuesheet_btn',
				text: '<span style="position:relative;" title="'+_text('MN00243')+'"><i class="fa fa-external-link" style="font-size:13px;color:white;"></i></span>',
				handler: function(btn, e) {
					var cuesheet_list = Ext.getCmp('cuesheet_list').getSelectionModel();

					if(cuesheet_list.hasSelection()) {
						//플레이리스트 항목은 선택되었으나 해당 리스트에 아이템이 하나도 없을 경우에 대한 에러 처리
						var items_count = Ext.getCmp('cuesheet_items').getStore().getCount();

						if( items_count == 0 ) {
							Ext.Msg.alert(_text('MN00023'), _text('MSG02180'));
						} else {
							var rec = cuesheet_list.getSelected();
							// 전송시에도 수정된 항목이 있는지 체크할것
							var is_dirty = Ext.getCmp('cuesheet_items').is_dirty;
							if(!is_dirty) {
								Ext.Ajax.request({
									url: '/store/cuesheet/transfer_cuesheet.php',
									params: {
										cuesheet_id: rec.get('cuesheet_id'),
										cuesheet_type: rec.get('type'),
										cuesheet_nm: rec.get('cuesheet_title'),
										subcontrol_room: rec.get('subcontrol_room')
									},
									callback: function (self, success, response) {
										if ( success ) {
											try {
												var result = Ext.decode(response.responseText);
												Ext.Msg.alert(_text('MN00023'), result.msg);
											} catch ( e ) {
												Ext.Msg.alert(e['name'], e['message']);
											}
										} else {
											Ext.Msg.alert(_text('MN02008'), response.statusText + '(' + response.status + ')');
										}
									}
								});
							} else {
								Ext.Msg.show({
									animEl: e.getTarget(),
									title: _text('MN00024'),
									icon: Ext.Msg.INFO,
									msg: _text('MSG02170'),
									buttons: Ext.Msg.OKCANCEL,
									fn: function(btnID, text, opt) {
										if(btnID == 'ok') {
											Ext.Ajax.request({
												url: '/store/cuesheet/transfer_cuesheet.php',
												params: {
													cuesheet_id: rec.get('cuesheet_id'),
													cuesheet_type: rec.get('type'),
													subcontrol_room: rec.get('subcontrol_room')
												},
												callback: function (self, success, response) {
													if ( success ) {
														try {
															var result = Ext.decode(response.responseText);
															Ext.Msg.alert(_text('MN00023'), result.msg);
														} catch ( e ) {
															Ext.Msg.alert(e['name'], e['message']);
														}
													} else {
														Ext.Msg.alert(_text('MN02008'), response.statusText + '(' + response.status + ')');
													}
												}
											});

											Ext.getCmp('cuesheet_items').is_dirty = false;
										}
									}
								});
							}
						}
					} else {
						Ext.Msg.alert(_text('MN00023'), _text('MSG02181'));
					}
				}
			}],
			buttonAlign: 'center',
			fbar: [{
				text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00046'),
				scale: 'medium',
				handler: function(btn, e) {
					var grid = Ext.getCmp('cuesheet_items');
					grid.getStore().commitChanges();
					var datas = [];
					var datalist = grid.getStore().getRange();
					Ext.each(datalist,function(r){
						datas.push(r.data);
					});

					var dellist = grid.dellist;

					Ext.Ajax.request({
						url : '/store/cuesheet/cuesheet_action.php',
						params : {
							action: 'save-items',
							datas: Ext.encode(datas),
							dellist: Ext.encode(dellist)
						},
						callback : function(opts, success, response){
							if (success){
								try{
									var r = Ext.decode(response.responseText);
									if(r.success){
										// Ext.Msg.alert(_text('MN00023'), r.msg);
										grid.getStore().reload();
										grid.is_dirty = false;
									} else {
										Ext.Msg.alert(_text('MN00022'), r.msg);
									}
								} catch(e) {
									Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
								}
							} else {
								Ext.Msg.alert(_text('MN00022'), response.statusText);
							}
						}
					});
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),//delete
				scale: 'medium',
				handler: function(btn, e) {
					var cuesheet_items = Ext.getCmp('cuesheet_items').getSelectionModel();

					if(cuesheet_items.hasSelection()) {
						var records = cuesheet_items.getSelections();

						Ext.each(records, function(r){
							Ext.getCmp('cuesheet_items').getStore().remove(r);
							var cuesheet_content_id = r.get('cuesheet_content_id');
							if(!Ext.isEmpty(cuesheet_content_id)) {
								Ext.getCmp('cuesheet_items').dellist.push(cuesheet_content_id);
							}
						});

						Ext.getCmp('cuesheet_items').is_dirty = true;
					} else {
						Ext.Msg.alert(_text('MN00023'), _text('MSG02182'));
					}
				}
			}]
		};

		Ariel.Nps.Cuesheet.Detail.superclass.initComponent.call(this);
	}
});


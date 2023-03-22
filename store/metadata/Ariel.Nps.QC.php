<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

?>
Ext.ns('Ariel.Nps');
Ariel.Nps.QC = Ext.extend(Ext.Panel, {
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

		var sel_model = new Ext.grid.CheckboxSelectionModel();

				this.items = [{
						xtype: 'grid',
						flex: 3,
			id: 'qc_grid',
						loadMask: true,
						layout: 'fit',
						split: true,
						store: new Ext.data.JsonStore({
								autoLoad: true,
								url: '/store/media_quality_store.php',
								root: 'data',
								fields: [
										{name: 'media_id'},
										{name: 'media_type'},
										{name: 'quality_type'},
										{name: 'start_tc'},
										{name: 'end_tc' },
										{name: 'show_order' },
										{name: 'no_error' },
										{name: 'quality_id'},
										{name: 'sound_channel'}
								],
								listeners: {
										exception: function(self, type, action, opts, response, args){
												Ext.Msg.alert(_text('MN00022'), response.responseText);
										}
								}
						}),
						sm: sel_model,
			cm: new Ext.grid.ColumnModel({
								defaults: {
										sortable: false
								},
								columns: [
					sel_model,
										{header: '파일 용도', dataIndex: 'media_type' , hidden: true},
										{header: 'Quality 유형', dataIndex: 'quality_type' },
										{header: 'Start TC', dataIndex: 'start_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
												var h = parseInt( value / 3600 );
												var i = parseInt(  (value % 3600) / 60 );
												var s = (value % 3600) % 60;

												h = String.leftPad(h, 2, '0');
												i = String.leftPad(i, 2, '0');
												s = String.leftPad(s, 2, '0');
												var time = h+':'+i+':'+s;
												return time;
										}},
										{header: 'End TC', dataIndex: 'end_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
												var h = parseInt( value / 3600 );
												var i = parseInt(  (value % 3600) / 60 );
												var s = (value % 3600) % 60;

												if(h==0 && i==0 && s==0) return;

												h = String.leftPad(h, 2, '0');
												i = String.leftPad(i, 2, '0');
												s = String.leftPad(s, 2, '0');
												var time = h+':'+i+':'+s;
												return time;
										}},
										{header: '이상유무', sortable:true, dataIndex: 'no_error', editor: new Ext.form.Checkbox({

										}), renderer: function(value, metaData, record, rowIndex, colIndex, store){
												if(value=='1'){
							Ext.getCmp('qc_grid').getSelectionModel().selectRow(rowIndex, true);
														return '이상없음';
												}else{
														return '이상';
												}
										}
										},
										{header: 'quality_id', dataIndex: 'quality_id', hidden:true},
										{header: '채널', dataIndex: 'sound_channel'}
								]
						}),
						listeners: {
								rowclick: function(self, idx, e){
										var select = self.getSelectionModel().getSelected();
										var tc = select.get('start_tc');

					if(!Ext.isEmpty(Ext.getCmp('player_warp'))) {
						if(parseInt(tc-1) < 0) {
							Ext.getCmp('player_warp').seek(0);
						} else {
												Ext.getCmp('player_warp').seek(tc-1);
						}
										}
								},
								viewready: function(self){
					var content_id = self.ownerCt.content_id;
										Ext.Ajax.request({
						url: '/store/media_quality_store.php',
						params: {
							action: 'get_cmt',
							content_id: content_id
						},
						callback: function(se, success, response){
							if (success) {
								try {
									var r = Ext.decode(response.responseText);
									if(r.success) {
										var cmt = r.comment;
										Ext.getCmp('qc_review_cmt').setValue(cmt);
										self.getStore().load({
											params: {
												content_id: content_id
											}
										});

									} else {
										Ext.Msg.alert(_text('MN00012'), r.msg );
									}
								} catch (e) {
									//alert(response.responseText)
									Ext.Msg.alert(e['name'], e['message'] );
								}
							} else {
								//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
								Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
							}
						}
					});
								}
						}
				},{
						xtype: 'panel',
						title: '검토의견',
						flex: 1,
						layout: 'fit',
						width: '100%',
						items: [{
								xtype: 'textarea',
				id: 'qc_review_cmt',
								layout: 'fit'
						}]
				}];

		this.buttons = [{
			text: '확인',
			scale: 'medium',
			icon: '/led-icons/accept.png',
			handler: function(b, e){
				var parent = b.ownerCt.ownerCt;
				Ext.Msg.show({
					title: '확인',
					msg: '선택된 QC가 문제되지 않는 항목이라고 확인합니다.',
					icon: Ext.Msg.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if(btnId=='ok')
						{
							var selections = Ext.getCmp('qc_grid').getSelectionModel().getSelections();
							var comment = Ext.getCmp('qc_review_cmt').getValue();
							var arr_data = [];
							Ext.each(selections, function(item){
								arr_data.push(Ext.encode(item.data));
							});

							Ext.Ajax.request({
								url: '/store/media_quality_store.php',
								params: {
									action:'check',
									content_id: b.ownerCt.ownerCt.content_id,
									comment: comment,
									'grid_data[]': arr_data
								},
								callback: function(opts, success, response){
									if(success) {
										try
										{
											var r  = Ext.decode(response.responseText);
											if(!r.success)
											{
												Ext.Msg.alert('오류', r.msg);
												return;
											}
											Ext.Msg.alert('성공','수정되었습니다.');
											Ext.getCmp('qc_review_cmt').setValue(r.comment);
											Ext.getCmp('qc_grid').getStore().reload();
											//b.ownerCt.ownerCt.close();
										}
										catch(e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert('오류', response.statusText);
									}
								}
							});
						}
					}
				});
			},
			listeners: {
				beforerender: function(self) {
				if(self.ownerCt.ownerCt.is_checked == 'Y') {
					self.hide();
					Ext.getCmp('edit_qc_button').show();
				}
				}
			}
		},{
			text:'수정',
			id: 'edit_qc_button',
			hidden: true,
			scale: 'medium',
			icon: '/led-icons/application_edit.png',
			handler: function(b, e){
				Ext.Msg.show({
					title: '확인',
					msg: '수정사항을 저장하시겠습니까?',
					icon: Ext.Msg.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if(btnId=='ok') {
							var comment = Ext.getCmp('qc_review_cmt').getValue();
							var arr_data = [];
							var selections = Ext.getCmp('qc_grid').getStore().data.items;
							Ext.each(selections, function(item){
								var isSelected = Ext.getCmp('qc_grid').getSelectionModel().isSelected(item);
								if(isSelected) {
									item.data.no_error = 1;
								} else {
									item.data.no_error = 0;
								}
								arr_data.push(Ext.encode(item.data));
							});

							Ext.Ajax.request({
								url: '/store/media_quality_store.php',
								params: {
									action:'edit_check',
									content_id: b.ownerCt.ownerCt.content_id,
									comment: comment,
									'grid_data[]': arr_data
								},
								callback: function(opts, success, response){
									if(success) {
										try
										{
											var r  = Ext.decode(response.responseText);
											if(!r.success)
											{
												Ext.Msg.alert('오류', r.msg);
												return;
											}
											Ext.Msg.alert('성공','수정되었습니다.');
											Ext.getCmp('qc_review_cmt').setValue(r.comment);
											Ext.getCmp('qc_grid').getStore().reload();
											//b.ownerCt.ownerCt.close();
										}
										catch(e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert('오류', response.statusText);
									}
								}
							});
						}
					}
				});
			}
		}];

				Ariel.Nps.QC.superclass.initComponent.call(this);
		}
});


Ariel.Nps.MasterQC = Ext.extend(Ext.Panel, {
	layout: 'fit',
	border: false,
	defaults: {
		split: true
	},

	initComponent: function(config){
		Ext.apply(this, config || {});
		
		this.items = [{
			xtype: 'grid',
			loadMask: true,
			layout: 'fit',
			split: true,
			store: new Ext.data.JsonStore({
				url: '/store/master_media_quality_store.php',
				root: 'data',
				fields: [
					{name: 'media_id'},
					{name: 'media_type'},
					{name: 'quality_type'},
					{name: 'start_tc'},
					{name: 'end_tc' },
					{name: 'show_order' },
					{name: 'no_error' },
					{name: 'quality_id'},
					{name: 'sound_channel'}
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						Ext.Msg.alert(_text('MN00022'), response.responseText);
					}
				}
			}),
			sm: new Ext.grid.RowSelectionModel(),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false
				},
				columns: [
					new Ext.grid.RowNumberer(),
					{header: 'Quality 유형', dataIndex: 'quality_type' },					
					{header: 'Start TC', dataIndex: 'start_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
							var h = parseInt( value / 3600 );
							var i = parseInt(  (value % 3600) / 60 );
							var s = (value % 3600) % 60;

							h = String.leftPad(h, 2, '0');
							i = String.leftPad(i, 2, '0');
							s = String.leftPad(s, 2, '0');
							var time = h+':'+i+':'+s;
							return time;
					}},
					{header: 'End TC', dataIndex: 'end_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
							var h = parseInt( value / 3600 );
							var i = parseInt(  (value % 3600) / 60 );
							var s = (value % 3600) % 60;

							if(h==0 && i==0 && s==0) return;

							h = String.leftPad(h, 2, '0');
							i = String.leftPad(i, 2, '0');
							s = String.leftPad(s, 2, '0');
							var time = h+':'+i+':'+s;
							return time;
					}},
					{header: 'quality_id', dataIndex: 'quality_id', hidden:true},
					{header: '채널', dataIndex: 'sound_channel'}
				]
			}),
			listeners: {
				rowclick: function(self, idx, e){
					var select = self.getSelectionModel().getSelected();
					var tc = select.get('start_tc');

					if(!Ext.isEmpty(Ext.getCmp('player_warp'))) {
						if(parseInt(tc-1) < 0) {
							Ext.getCmp('player_warp').seek(0);
						} else {
							Ext.getCmp('player_warp').seek(tc-1);
						}
					}
				},
				viewready: function(self){
					var request_id = self.ownerCt.request_id;
					self.getStore().load({
						params: {
							request_id: request_id
						}
					});	
				}
			}
		}];

		Ariel.Nps.MasterQC.superclass.initComponent.call(this);
	}
});


Ariel.Nps.QualityCheck = Ext.extend(Ext.Panel, {
	layout: 'fit',
	border: false,
	defaults: {
		split: true
	},

	initComponent: function(config){
		Ext.apply(this, config || {});
		var that = this;
		this.items = [{
			xtype: 'grid',
			cls: 'proxima_customize',
			border: false,
			stripeRows: true,
			loadMask: true,
			layout: 'fit',
			split: true,
			store: new Ext.data.JsonStore({
				url: '/store/media_quality_store.php',
				root: 'data',
				fields: [
					{name: 'media_id'},
					{name: 'media_type'},
					{name: 'quality_type'},
					{name: 'start_tc'},
					{name: 'end_tc' },
					{name: 'show_order' },
					{name: 'no_error' },
					{name: 'quality_id'},
					{name: 'sound_channel'}
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						Ext.Msg.alert(_text('MN00022'), response.responseText);
					},
                    load: function(self, record, option) {
                        //오류메세지가 있을시 메세지창 띄움
                        if(!Ext.isEmpty(self.reader)) {
                            if(!Ext.isEmpty(self.reader.jsonData)) {
                                if(!Ext.isEmpty(self.reader.jsonData.msg)) {
                                    Ext.Msg.alert(_text('MN00022'), self.reader.jsonData.msg);
                                }
                            }
                        }
                    }
				}
			}),
			tbar: [{
				xtype: 'button',
				cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
				handler: function(){
					that.items.get(0).getStore().reload();
				}
			},{
				xtype: 'button',
				//text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02368'),
				cls: 'proxima_button_customize',
				width: 30,
				viewType: 'check',
				text: '<span style="position:relative;top:1px;" title="'+_text('MN02368')+'"><i class="fa fa-check" style="font-size:13px;color:white;"></i></span>',
				handler: function(b, e){
					Ext.Msg.show({
						title: _text('MN00024'),//Confirmination
						msg: _text('MSG02124'),//Mark QC list as not error.
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.OKCANCEL,
						fn: function(btnId){
							if(btnId=='ok')
							{
								Ext.Ajax.request({
									url: '/store/media_quality_action.php',
									params: {
										action:'edit',
										content_id: that.content_id
									},
									callback: function(opts, success, response){
										that.items.get(0).getStore().reload();
									}
								});
							}
						}
					});
				}
			}],
			sm: new Ext.grid.RowSelectionModel(),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false
				},
				columns: [
					new Ext.grid.RowNumberer(),
					{header: _text('MN02295'), dataIndex: 'quality_type' },					
					{header: _text('MN02296'), dataIndex: 'start_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
							var h = parseInt( value / 3600 );
							var i = parseInt(  (value % 3600) / 60 );
							var s = (value % 3600) % 60;

							h = String.leftPad(h, 2, '0');
							i = String.leftPad(i, 2, '0');
							s = String.leftPad(s, 2, '0');
							var time = h+':'+i+':'+s;
							return time;
					}},
					{header: _text('MN02297'), dataIndex: 'end_tc', renderer: function(value, metaData, record, rowIndex, colIndex, store){
							var h = parseInt( value / 3600 );
							var i = parseInt(  (value % 3600) / 60 );
							var s = (value % 3600) % 60;

							if(h==0 && i==0 && s==0) return;

							h = String.leftPad(h, 2, '0');
							i = String.leftPad(i, 2, '0');
							s = String.leftPad(s, 2, '0');
							var time = h+':'+i+':'+s;
							return time;
					}},
					{header: 'quality_id', dataIndex: 'quality_id', hidden:true},
					{header: _text('MN02299'), dataIndex: 'sound_channel'},
					//user confirm
					{header: _text('MN02369'), dataIndex: 'no_error', renderer: function(value, metaData, record, rowIndex, colIndex, store){
						if(value == '1') {
							return 'O';
						} else {
							return 'X';
						}
					}}
				]
			}),
			listeners: {
				rowclick: function(self, idx, e){
					var player3 = videojs(document.getElementById('player3'), {}, function(){
					});
					var select = self.getSelectionModel().getSelected();
					var tc = select.get('start_tc');
					
					if(!Ext.isEmpty(Ext.getCmp('player_warp'))) {
						if(parseInt(tc-1) < 0) {
							player3.currentTime(0);
						} else {
							player3.currentTime(tc);
						}
					}
				},
				viewready: function(self){
					var content_id = self.ownerCt.content_id;
					var viewType = self.ownerCt.viewType;

					/*QC에 대해서 확인용도일 경우에는 사용자확인 버튼과 사용자 확인 컬럼이 필요없으므로 숨김처리 - 2018.03.20 Alex*/
					if(viewType == 'read') {
						var buttonCheck = self.getTopToolbar().find('viewType', 'check')[0];
						buttonCheck.setVisible(false);

						var colModel = self.getColumnModel();
						var hiddenColumnIdx = colModel.findColumnIndex('no_error');
						colModel.setHidden(hiddenColumnIdx, true);
					}
					
					self.getStore().load({
						params: {
							content_id: content_id
						}
					});	
				}
			},
			viewConfig: {
				forceFit: true,
				emptyText: _text('MSG00148')
				//!!emptyText: '검색 결과가 없습니다.'
			}
		}];

		Ariel.Nps.QualityCheck.superclass.initComponent.call(this);
	}
});
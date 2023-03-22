<?php
session_start();

function buildMediaQualityMetaTab($content_id, $args)
{
	global $db;


	return "
	{
                xtype: 'panel',
                title: '영상 QC(Quality Check)',
                layout: 'vbox',
                items : [{
                        xtype: 'grid',
//                        $args,
                        flex: 3,
                        loadMask: true,
                        layout: 'fit',
        //		clicksToEdit: 1,
                        split: true,
        /*		store: new Ext.data.GroupingStore({
                                reader: new Ext.data.ArrayReader({}, [
                                        {name: 'media_id'},
                                        {name: 'media_type'},
                                        {name: 'quality_type'},
                                        {name: 'start_tc'},
                                        {name: 'end_tc' },
                                        {name: 'show_order' },
                                        {name: 'no_error' },
                                        {name: 'quality_id'}
                                ]),
                                autoLoad: true,
                                url: '/store/media_quality_store.php',
                                groupField:'quality_type',
                                listeners: {
                                        exception: function(self, type, action, opts, response, args){
                                                Ext.Msg.alert(_text('MN00022'), response.responseText);
                                        }
                                }
                        }),
        */
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
                        sm: new Ext.grid.RowSelectionModel({
                                singleSelect: true
                        }),
        /*		view: new Ext.grid.GroupingView({
                                forceFit: true,
                                emptyText: '오류정보가 없습니다.',
                                        startCollapsed : true,
                                groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? \"Position\" : \"Position\"]})'
                        }),
        */		cm: new Ext.grid.ColumnModel({
                                defaults: {
                                        sortable: false
                                },
                                columns: [
                                        {header: '파일 용도', dataIndex: 'media_type' , hidden: true},
                                        {header: 'Quality 유형', dataIndex: 'quality_type' },
                                        /* 예전에 쓰던 부분. DB를 NUMBER에서 VARCHAR(200)으로 바꿔서 코드화 안하고 바로 보여줌(2012. 06. 04)
                                        renderer: function(value, metaData, record, rowIndex, colIndex, store){
                                                switch(value){
                                                        case '1':
                                                                return 'Bad Video';
                                                        break;

                                                        case '2':
                                                                return 'No Video';
                                                        break;

                                                        case '3':
                                                                return 'No Audio';
                                                        break;
                                                        case '4':
                                                                return 'Silent Audio';
                                                        break;
                                                }
                                        }
                                        */

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

                                        }), renderer: function(value){
                                                if(value=='1'){
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

                                        if(!Ext.isEmpty(Ext.getCmp('player_warp')))
                                        {
                                                Ext.getCmp('player_warp').seek(tc-1);
                                        }
                                },
                                viewready: function(self){
                                        self.getStore().load({
                                                params: {
                                                        content_id: $content_id
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
                                layout: 'fit'
                        }]
                }],
		buttonAlign: 'left',
		buttons:[".buildButtonsNEW($content_id)."{xtype: 'tbfill'},{
			text: '확인완료',
			scale: 'medium',
			icon: '/led-icons/accept.png',
			handler: function(b, e){
				var parent = b.ownerCt.ownerCt;
				Ext.Msg.show({
					title: '확인',
					msg: '검출된 QC가 문제되지 않는 항목이라고 확인합니다.',
					icon: Ext.Msg.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if(btnId=='ok')
						{
							var grid_data = parent.store.getRange();
							//console.log(grid_data);

							var arr_data = [];
							Ext.each(grid_data, function(item){
								item.data.no_error = 1;
								arr_data.push(Ext.encode(item.data));
							});

							//console.log(arr_data);

							Ext.Ajax.request({
								url: '/store/media_quality_store.php',
								params: {
									action:'edit',
									'grid_data[]': arr_data
								},
								callback: function(opts, success, response){
									if(success)
									{
										try
										{
											var r  = Ext.decode(response.responseText);
											if(!r.success)
											{
												Ext.Msg.alert('오류', r.msg);
												return;
											}
											Ext.Msg.alert('성공','수정되었습니다.');
											//b.ownerCt.ownerCt.close();
										}
										catch(e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
										parent.store.reload();
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
		},{
			text:'수정',
			scale: 'medium',
			icon: '/led-icons/application_edit.png',
			handler: function(b, e){
				var parent=b.ownerCt.ownerCt;
				Ext.Msg.show({
					title: '확인',
					msg: '수정하신 내용을 저장하시겠습니까?',
					icon: Ext.Msg.QUESTION,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if(btnId=='ok')
						{
							var grid_data = parent.store.getRange();
							//console.log(grid_data);

							var arr_data = [];
							Ext.each(grid_data, function(item){
								arr_data.push(Ext.encode(item.data));
							});

							//console.log(arr_data);

							Ext.Ajax.request({
								url: '/store/media_quality_store.php',
								params: {
									action:'edit',
									'grid_data[]': arr_data
								},
								callback: function(opts, success, response){
									if(success)
									{
										try
										{
											var r  = Ext.decode(response.responseText);
											if(!r.success)
											{
												Ext.Msg.alert('오류', r.msg);
												return;
											}
											Ext.Msg.alert('성공','수정되었습니다.');
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
		}]
	}";
}





function buildButtonsNEW($content_id)
{
	global $db, $DMC_online_confirm, $qc_end_flag;

	if($DMC_online_confirm == 'Y' && $qc_end_flag)
	{
		$sche_info = $db->queryRow("select * from dmc_online_delivery
				where content_id='".$content_id."'");
		if( (in_array(PD_GROUP, $_SESSION['user']['groups']) && $sche_info['source_from'] == 'NPS')
			|| $_SESSION['user']['is_admin'] == 'Y' )
		{
			$buttons[] = buttonPDconfirmNEW($content_id);
			$buttons[] = buttonPDdeclineNEW($content_id);
		}
		if( (in_array(AD_EDIT_GROUP, $_SESSION['user']['groups']) && $sche_info['source_from'] != 'NPS')
		 || $_SESSION['user']['is_admin'] == 'Y' )
		{
			//$buttons[] = buttonADconfirmNEW($content_id);
		}
	}

	if ( isset($buttons) )
	{
		$buttons = join(',', $buttons);
	}

	if(empty($buttons))
	{
		return '';
	}
	else
	{
		return $buttons.',';
	}
}

function buttonPDconfirmNEW($content_id)
{
	$result = "{
		icon:'/led-icons/accept.png',
		text: 'PD 최종확인',
		scale: 'medium',
		submit: function(callback){
			var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));

			// 메타데이터 업데이트
			Ext.Ajax.request({
				url: '/pages/tape_info/online_conf_regist.php',
				params: {
					content_id: ".$content_id.",
					mode: 'PDconfirm'
				},
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{
								if(r.msg == 'request')
								{
									Ext.Msg.show({
										title: '확인',
										msg: r.data,
										icon: Ext.Msg.QUESTION,
										buttons: Ext.Msg.OKCANCEL,
										fn: function(btnId, t, opt){
											if (btnId == 'ok')
											{
												//확인되면 변경.
												var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));

												Ext.Ajax.request({
													url: '/pages/tape_info/online_conf_regist.php',
													params: {
														content_id: ".$content_id.",
														mode: 'PDconfirm_checked'
													},
													callback: function(opts, success, response){
														w.hide();
														if (success)
														{
															try
															{
																var r = Ext.decode(response.responseText);
																if (!r.success)
																{
																	Ext.Msg.alert(_text('MN00022'), r.msg);
																}
																else
																{
																	Ext.Msg.alert( _text('MN00023'), '반영되었습니다.');
																}
															}
															catch (e)
															{
																Ext.Msg.alert(e['name'], e['message']);
															}
														}
														else
														{
															Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
														}

													}
												});
											}
										}
									});
								}
								else
								{
									Ext.Msg.alert( _text('MN00023'), '반영되었습니다.');
								}
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}

				}
			});
		},
		listeners: {
			click: function(self, pass_conform){

				if ( pass_conform == true )
				{
					self.submit(function(){
						Ext.Msg.alert(_text('MN00003'), _text('MSG00087'));
					});
				}
				else
				{
					Ext.Msg.show({
						title: _text('MN00003'),
						msg: '해당영상을 최종 확인 처리 합니다.',
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.YESNO,
						fn: function(btnId){
							if (btnId == 'yes')
							{
								self.submit(function(){
									Ext.getCmp('online_grid').getStore().reload();
//									Ext.Msg.show({
//										title: _text('MN00003'),
//										msg: _text('MSG00087')+'<br />'+_text('MSG00190'),
//										icon: Ext.Msg.QUESTION,
//										buttons: Ext.Msg.OKCANCEL,
//										fn: function(btnId){
//											if (btnId == 'ok')
//											{
//												Ext.getCmp('winDetail').close();
//												//Ext.getCmp('tab_warp').getActiveTab().getStore().reload();
//											}
//										}
//									});/
								});
							}
						}
					});
				}
			}
		},
		handler: function(b, e) {
			// 2011-01-20 박정근
			// listeners 에 click 으로 변경
		}
	}";

	return $result;
}

function buttonPDdeclineNEW($content_id)
{
	$result = "{
		icon:'/led-icons/accept.png',
		text: 'PD 확인취소',
		scale: 'medium',
		submit: function(callback){
			var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));

			// 메타데이터 업데이트
			Ext.Ajax.request({
				url: '/pages/tape_info/online_conf_regist.php',
				params: {
					content_id: ".$content_id.",
					mode: 'PDdecline'
				},
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{
								if ( Ext.isFunction(callback) )
								{
									callback();
								}
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}

				}
			});
		},
		listeners: {
			click: function(self, pass_conform){

				if ( pass_conform == true )
				{
					self.submit(function(){
						Ext.Msg.alert(_text('MN00003'), _text('MSG00087'));
					});
				}
				else
				{
					Ext.Msg.show({
						title: _text('MN00003'),
						msg: '해당영상을 확인취소 처리 합니다.',
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.YESNO,
						fn: function(btnId){
							if (btnId == 'yes')
							{
								self.submit(function(){
									Ext.getCmp('online_grid').getStore().reload();
								});
							}
						}
					});
				}
			}
		},
		handler: function(b, e) {
			// 2011-01-20 박정근
			// listeners 에 click 으로 변경
		}
	}";

	return $result;
}

function buttonADconfirmNEW($content_id)
{
	$result = "{
		icon:'/led-icons/accept.png',
		text: '광고편집실 최종확인',
		scale: 'medium',
		submit: function(callback){
			var w = Ext.Msg.wait(_text('MN00065'), _text('MN00023'));



			// 메타데이터 업데이트
			Ext.Ajax.request({
				url: '/pages/tape_info/online_conf_regist.php',
				params: {
					content_id: ".$content_id.",
					mode: 'ADconfirm'
				},
				callback: function(opts, success, response){
					w.hide();
					if (success)
					{
						try
						{
							var r = Ext.decode(response.responseText);
							if (!r.success)
							{
								Ext.Msg.alert(_text('MN00022'), r.msg);
							}
							else
							{


								if ( Ext.isFunction(callback) )
								{
									callback();
								}
							}
						}
						catch (e)
						{
							Ext.Msg.alert(e['name'], e['message']);
						}
					}
					else
					{
						Ext.Msg.alert(_text('MN00022'), response.ststusText+'( '+response.status+' )');
					}

				}
			});
		},
		listeners: {
			click: function(self, pass_conform){

				if ( pass_conform == true )
				{
					self.submit(function(){
						Ext.Msg.alert(_text('MN00003'), _text('MSG00087'));
					});
				}
				else
				{
					Ext.Msg.show({
						title: _text('MN00003'),
						msg: '해당영상을 인수 확인 처리 합니다.',
						icon: Ext.Msg.QUESTION,
						buttons: Ext.Msg.YESNO,
						fn: function(btnId){
							if (btnId == 'yes')
							{
								self.submit(function(){
									Ext.getCmp('online_grid').getStore().reload();
//									Ext.Msg.show({
//										title: _text('MN00003'),
//										msg: _text('MSG00087')+'<br />'+_text('MSG00190'),
//										icon: Ext.Msg.QUESTION,
//										buttons: Ext.Msg.OKCANCEL,
//										fn: function(btnId){
//											if (btnId == 'ok')
//											{
//												Ext.getCmp('winDetail').close();
//												//Ext.getCmp('tab_warp').getActiveTab().getStore().reload();
//											}
//										}
//									});/
								});
							}
						}
					});
				}
			}
		},
		handler: function(b, e) {
			// 2011-01-20 박정근
			// listeners 에 click 으로 변경
		}
	}";

	return $result;
}
?>
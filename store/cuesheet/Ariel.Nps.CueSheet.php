<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

$streamer_addr_ip = convertIP( $_SERVER['REMOTE_ADDR'] ,'dmc_stream' );
$streamer_addr = 'rtmp://'.$streamer_addr_ip.'/vod';
$switch = true;
?>

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
                            return '대형부조';
                            break;
                        case 'middle' :
                            return '중형부조';
                            break;
                        case 'small' :
                            return '소형부조';
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
                            {name :'broad_date',type:'date',dateFormat:'Ymd'},
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
                title: '큐시트 목록',
                region: 'center',
                loadMask: true,
                store: store,
		        viewConfig:{
                        emptyText:'등록된 큐시트가 없습니다'
                },
                colModel: new Ext.grid.ColumnModel({
                        columns: [
                                new Ext.grid.RowNumberer(),
                                {header: 'CueSheet ID', dataIndex: 'cuesheet_id', hidden:true},
                                {header: '<center>큐시트명</center>', dataIndex: 'cuesheet_title', width: 150},
                                {header: '<center>프로그램</center>', dataIndex: 'prog_nm', width: 150},
				{header: '편성길이', dataIndex: 'duration', width: 80, align: 'center'},
                                {header: '방송일시', dataIndex: 'broad_date', width: 80, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center'},
				{header: '부조정실', dataIndex: 'subcontrol_room', renderer: controlroom_nm, align: 'center'},
                                {header: '등록자' , width: 80 , dataIndex: 'user_id', align: 'center'},
				{header: '등록일시', dataIndex: 'created_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'},
                                {header: '등록타입', dataIndex: 'type', hidden: true}
                        ]
                }),
                tbar: [{
                    text: '새로고침',
                    icon: '/led-icons/arrow_refresh.png',
                    handler: function(btn, e){
                        Ext.getCmp('cuesheet_list').getStore().reload();
                    }
                },'-',{
                    text: '검색',
                    icon: '/led-icons/find.png',
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
				width: 270,
				height: 170,
				title: '검색',
				id: 'cuesheet_search_win',
				closeAction: 'hide',
				layout: 'fit',
				x: x_pos,
				y: y_pos,
				items: [{
				    xtype: 'form',
				    id: 'cuesheet_search_win_form',
				    layout: 'form',
				    padding: 5,
				    labelAlign: 'right',
				    labelWidth: 50,
				    defaults: {
					anchor: '95%'
				    },
				    items: [{
					    xtype: 'datefield',
					    id: 'cuesheet_broad_date',
					    name: 'broad_date',
					    fieldLabel: '방송일시',
					    width: 75,
					    format: 'Y-m-d',
					    listeners: {
						render: function(self) {
						    self.setValue(new Date());
						}
					    }
				    },{
					    xtype : 'combo',
					    id : 's_cue_prog',
					    fieldLabel: '프로그램',
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
						    fields: [
							    'value',
							    'name'
						    ],
						    data: [
							['all',	'전체'],
							['large',	'대형 부조'],
							['middle',	'중형 부조'],
							['small',	'소형 부조']
						    ]
					    }),
					    allowBlank: false,
					    hiddenName: 'subcontrol_room',
					    valueField: 'value',
					    displayField: 'name',
					    value: 'all',
					    fieldLabel: '부조정실',
					    mode: 'local',
					    typeAhead: true,
					    triggerAction: 'all',
					    forceSelection: true,
					    editable: false
				    }]
				}],
				buttons: [{
				    text: '검색',
				    scale: 'medium',
				    handler: function(btn, e) {
					var values = Ext.getCmp('cuesheet_search_win_form').getForm().getValues();
					values['broad_date'] = values['broad_date'].replace('-','').replace("-","");

					Ext.getCmp('cuesheet_list').getStore().load({
					    params: {
						broad_date: values['broad_date'],
						cuesheet_type: 'M',
						prog_id: values['prog_id'],
						subcontrol_room : values['subcontrol_room']
					    },
					    callback: function(opt, success, response){
						if(success) {
						    cuesheetSearchWin.hide();
						}
					    }
				       });
				    }
				},{
				    text: '닫기',
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
                                var cuesheet_type = 'M';
                                var broad_date = new Date().format('Ymd');

                                self.store.load({
                                        params: {
                                                broad_date: broad_date,
                                                cuesheet_type: cuesheet_type
                                        }
                                });
                        },
                        rowclick: function(self, rowIndex, e) {
                            var records = self.getStore().getAt(rowIndex);
                            var cuesheet_id = records.get('cuesheet_id');
                            var cuesheet_type = 'M';

                            Ext.getCmp('cuesheet_items').store.load({
                                params: {
                                        cuesheet_type: cuesheet_type,
                                        cuesheet_id: cuesheet_id
                                }
                            });
                        }
                },
                buttonAlign: 'center',
                fbar: [{
                        text: _text('MN00033'),
                        scale: 'medium',
                        handler: function(btn, e) {
                                var cuesheet_type = 'M';
                                this.buildAddCueSheet(e, cuesheet_type);
                        },
                        scope: this
                },{
                        text: _text('MN00043'),
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
                        text: _text('MN00034'),
                        scale: 'medium',
                        handler: function(btn, e) {
                                var hasSelection = Ext.getCmp('cuesheet_list').getSelectionModel().hasSelection();
                                if(hasSelection) {
                                        this.buildDeleteCuesSheet(e);
                                }else{
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
                    title: '큐시트 추가',
                    width: 650,
                    height: 600,
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
                                    fieldLabel: '큐시트 명',
                                    allowBlank: false
                            },{
                                    xtype: 'datefield',
                                    format: 'Y-m-d',
                                    name: 'broad_date',
                                    fieldLabel: '방송일자',
                                    allowBlank: false,
                                    listeners: {
                                        render: function(self) {
                                            self.setValue(new Date());
                                        }
                                    }
                            },{
                                    xtype: 'combo',
                                    store: new Ext.data.ArrayStore({
                                            fields: [
                                                    'value',
                                                    'name'
                                            ],
                                            data: [
                                                ['large', '대형 부조'],
                                                ['middle',  '중형 부조'],
                                                ['small', '소형 부조']
                                            ]
                                    }),
                                    allowBlank: false,
                                    hiddenName: 'subcontrol_room',
                                    valueField: 'value',
                                    displayField: 'name',
                                    fieldLabel: '부조정실',
                                    mode: 'local',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    forceSelection: true,
                                    editable: false,
                                    emptyText: '부조정실을 선택해 주세요'
                            },{
                                    xtype : 'combo',
                                    hiddenName: 'prog_id',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    width : 100,
                                    editable : false,
                                    hidden : false,
                                    allowBlank: false,
                                    fieldLabel: '프로그램',
                                    valueField: 'prog_id',
                                    displayField: 'prog_nm',
                                    store: new Ext.data.JsonStore({
                                            url: '/store/cuesheet/get_program_list.php',
                                            autoLoad: true,
                                            root: 'data',
                                            fields: [
                                                    'prog_nm', 'prog_id'
                                            ]
                                    }),
                                    emptyText: '프로그램을 선택해 주세요',
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
			    })]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                            text: _text('MN00033'),
                            scale: 'medium',
                            handler: function(btn, e) {
				    var episode_list = Ext.getCmp('add_cuesheet_episode_list').items.items[0];
				    var hasSelection = episode_list.getSelectionModel().hasSelection();

				    if(hasSelection) {
					var episode_info = episode_list.getSelectionModel().getSelected().data;

					Ext.getCmp('add_cuesheet_form').getForm().submit({
						url: '/store/cuesheet/cuesheet_action.php',
						params: {
							action: 'add',
							cuesheet_type: cuesheet_type,
							episode_num: episode_info.epsd_id,
							trff_ymd: episode_info.trff_ymd,
							trff_seq: episode_info.trff_seq,
							trff_no: episode_info.trff_no
						},
						success: function(form, action) {
							try {
								var result = Ext.decode(action.response.responseText, true);
								if(result.success) {
									Ext.getCmp('add_cuesheet_win').close();
									Ext.getCmp('cuesheet_list').getStore().reload();
								}else{
									Ext.Msg.show({
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: result.msg,
										buttons: Ext.Msg.OK
									})
								}
							}catch(e){
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
				    } else {
					Ext.Msg.alert( _text('MN00023'), '회차정보를 선택해 주세요');
				    }
                            }
                    },{
                            text: _text('MN00004'),
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
                    title: '큐시트 수정',
                    width: 600,
                    height: 600,
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
                                    fieldLabel: '큐시트 명',
                                    allowBlank: false
                            },{
                                    xtype: 'datefield',
                                    format: 'Y-m-d',
                                    name: 'broad_date',
                                    fieldLabel: '방송일자',
                                    allowBlank: false,
                                    listeners: {
                                        render: function(self) {
                                            self.setValue(new Date());
                                        }
                                    }
                            },{
                                    xtype: 'combo',
                                    store: new Ext.data.ArrayStore({
                                            fields: [
                                                    'value',
                                                    'name'
                                            ],
                                            data: [
                                                ['large', '대형 부조'],
                                                ['middle',  '중형 부조'],
                                                ['small', '소형 부조']
                                            ]
                                    }),
                                    allowBlank: false,
                                    hiddenName: 'subcontrol_room',
                                    valueField: 'value',
                                    displayField: 'name',
                                    fieldLabel: '부조정실',
                                    mode: 'local',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    forceSelection: true,
                                    editable: false,
                                    emptyText: '부조정실을 선택해 주세요'
                            },{
                                    xtype : 'combo',
                                    id: 'edit_cuesheet_prog',
                                    typeAhead: true,
                                    triggerAction: 'all',
                                    width : 100,
                                    editable : false,
                                    hidden : false,
                                    allowBlank: false,
                                    fieldLabel: '프로그램',
                                    valueField: 'prog_id',
                                    displayField: 'prog_nm',
                                    hiddenName: 'prog_id',
                                    forceSelection: true,
                                    store: new Ext.data.JsonStore({
                                            url: '/store/cuesheet/get_program_list.php',
                                            root: 'data',
                                            fields: [
                                                    'prog_nm', 'prog_id'
                                            ]
                                    }),
                                    emptyText: '프로그램을 선택해 주세요',
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
			    })]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                            text: '수정',
                            scale: 'medium',
                            handler: function(btn, e) {
				    var episode_list = Ext.getCmp('edit_cuesheet_episode_list').items.items[0];
				    var hasSelection = episode_list.getSelectionModel().hasSelection();

				    if(hasSelection) {
					var episode_info = episode_list.getSelectionModel().getSelected().data;
					Ext.getCmp('edit_cuesheet_form').getForm().submit({
						url: '/store/cuesheet/cuesheet_action.php',
						params: {
							action: 'edit',
							cuesheet_type: cuesheet_type,
							episode_num: episode_info.epsd_id,
							trff_ymd: episode_info.trff_ymd,
							trff_seq: episode_info.trff_seq,
							trff_no: episode_info.trff_no
						},
						success: function(form, action) {
							try {
								var result = Ext.decode(action.response.responseText, true);
								if(result.success) {
									Ext.getCmp('edit_cuesheet_win').close();
									Ext.getCmp('cuesheet_list').getStore().reload();
								}else{
									Ext.Msg.show({
										title: _text('MN00022'),
										icon: Ext.Msg.ERROR,
										msg: result.msg,
										buttons: Ext.Msg.OK
									})
								}
							}catch(e){
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
				    } else {
					Ext.Msg.alert( _text('MN00023'), '회차정보를 선택해 주세요');
				    }
                            }
                    },{
                            text: _text('MN00004'),
                            scale: 'medium',
                            handler: function() {
                                    Ext.getCmp('edit_cuesheet_win').close();
                            }
                    }]
            });
            win.show(e.getTarget());
    },

    buildDeleteCuesSheet: function(e, cuesheet_id) {
            var rec = Ext.getCmp('cuesheet_list').getSelectionModel().getSelected();
            // 삭제 확인 창
            Ext.Msg.show({
                    animEl: e.getTarget(),
                    //>>title: '삭제 확인',
                    title: _text('MN00024'),

                    icon: Ext.Msg.INFO,
                    msg: '선택하신 "' + rec.get('cuesheet_title') + '" 큐시트를 삭제하시겠습니까?',

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
                                                     }else{
                                                                    //>>Ext.Msg.alert('오류', r.msg);
                                                                    Ext.Msg.alert(_text('MN00022') , r.msg);
                                                            }
                                                    }catch(e) {
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
                                'status'
                        ]
                });

        this.workStatusMapping = function(value){
			switch (value) {
			case 'queue':
				return '대기';
			    break;

			case 'progressing':
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
                    title: '큐시트 상세 목록',
                    id: 'cuesheet_items',
                    region: 'south',
                    height: 400,
                    loadMask: true,
                    enableDragDrop: true,
                    ddGroup: 'cuesheetGridDD',
                    store: store,
                    colModel: new Ext.grid.ColumnModel({
                            defaults: {
                                    align: 'center'
                            },
                            // 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
                            columns: [
                                    new Ext.grid.RowNumberer(),
                                    {header: '콘텐츠명',		dataIndex: 'title', },
                                    {header: 'Duration',		dataIndex: 'duration', },
                                    {header: '큐시트 ID',		dataIndex: 'cuesheet_id', hidden: true},
                                    {header: '순서',	dataIndex: 'show_order', hidden: true},
                                    {header: '콘텐츠ID',		dataIndex: 'content_id', hidden: true },
                                    {header: '큐시트 콘텐츠ID',		dataIndex: 'cuesheet_content_id', hidden: true },
                                    {header: '전송상태',		dataIndex: 'status'}
                            ]
                    }),
                    viewConfig: {
                            emptyText: '플래이리스트 내 콘텐츠가 없습니다',
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
					    callback:function(option,success,response)
					    {
						    var r = Ext.decode(response.responseText);

						    if(success)
						    {
							    r.show();

							    //self.ownerCt.ownerCt.get(0).getStore().remove( records );

						    }
						    else
						    {

							    Ext.Msg.alert('오류', '서버오류 : 다시 시도 해 주시기 바랍니다.');
							    return;
						    }
					    }
				    });

				}
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
						//							    netConnectionUrl: '<?=$streamer_addr?>'
											netConnectionUrl: 'rtmp://192.168.0.8/vod/vod'
										}
									},
									onKeypress: function(clip){

									}
								});
			    }
                    },
                    tbar: [{
                        text: '새로고침',
                        icon: '/led-icons/arrow_refresh.png',
                        handler: function(btn, e){
                            Ext.getCmp('cuesheet_items').store.reload();
                        }
                    },'-',{
			text: '전송',
			icon: '/led-icons/transmit.png',
			handler: function(btn, e) {
			    var cuesheet_list = Ext.getCmp('cuesheet_list').getSelectionModel();

			    if(cuesheet_list.hasSelection()) {
				var rec = cuesheet_list.getSelected();

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
						Ext.Msg.alert( _text('MN00023'), result.msg);
					    }
					    catch ( e ) {
						Ext.Msg.alert(e['name'], e['message']);
					    }
					} else {
					    Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
					}
				    }
				});
			    } else {
				Ext.Msg.alert( _text('MN00023'), '전송 할 큐시트를 선택해 주세요');
			    }
			}
                    }],

                    buttonAlign: 'center',
                    fbar: [{
                            text: '저장',
                            scale: 'medium',
                            handler: function(btn, e) {
                                var grid = Ext.getCmp('cuesheet_items');
                                grid.getStore().commitChanges();
                                var datas = [];
                                var datalist = grid.getStore().getRange();
                                Ext.each(datalist,function(r){
                                    datas.push(r.data);
                                });

                                Ext.Ajax.request({
                                        url : '/store/cuesheet/cuesheet_action.php',
                                        params : {
//                                                cuesheet_id : cuesheet_id,
                                                action: 'save-comment',
                                                datas: Ext.encode(datas)
                                        },
                                        callback : function(opts, success, response){
                                                if (success){
                                                        try{
                                                                var r = Ext.decode(response.responseText);
                                                                if(r.success){
                                                                       // Ext.Msg.alert( _text('MN00023'), r.msg);
                                                                       grid.getStore().load();
                                                                }else{
                                                                        Ext.Msg.alert(_text('MN00022'), r.msg);
                                                                }
                                                        }catch(e){
                                                                Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
                                                        }
                                                }else{
                                                        Ext.Msg.alert(_text('MN00022'), response.statusText);
                                                }
                                        }
                                });
                            }
                    },{
                            text: '삭제',
                            scale: 'medium',
                            handler: function(btn, e) {
                                var cuesheet_items = Ext.getCmp('cuesheet_items').getSelectionModel();

                                if(cuesheet_items.hasSelection()) {
                                    var records = cuesheet_items.getSelections();
                                    var rs=[];

                                    Ext.each(records, function(r){
                                        rs.push(r.get('cuesheet_content_id'));
                                    });
                                    var cuesheet_id = records[0].get('cuesheet_id');
                                    Ext.Ajax.request({
                                        url: '/store/cuesheet/cuesheet_action.php',
                                        params: {
                                            action: 'del-items',
                                            cuesheet_id: cuesheet_id,
                                            contents: Ext.encode(rs)
                                        },
                                        callback: function (self, success, response) {
                                            if ( success ) {
                                                try {
                                                    var result = Ext.decode(response.responseText);
                                                    Ext.getCmp('cuesheet_items').getStore().reload();
                                                }
                                                catch ( e ) {
                                                    Ext.Msg.alert(e['name'], e['message']);
                                                }
                                            } else {
                                                Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
                                            }
                                        }
                                    });
                                } else {
                                    Ext.Msg.alert( _text('MN00023'), '삭제할 콘텐츠를 선택해 주세요');
                                }
                            }
                    }]
                };

            Ariel.Nps.Cuesheet.Detail.superclass.initComponent.call(this);
        }
});


Ariel.Nps.Cuesheet.AudioList = Ext.extend(Ext.Panel, {
            layout: 'fit',
            border: false,

            initComponent: function(config){
                Ext.apply(this, config || {});

                var store = new Ext.data.JsonStore({
                    url: '/store/cuesheet/get_cuesheet_list.php',
                    root: 'data',
                    idPropery: 'cuesheet_id',
                    fields: [
                            'cuesheet_id',
                            'cuesheet_title',
                            {name :'broad_date',type:'date',dateFormat:'Ymd'},
                            {name :'created_date',type:'date',dateFormat:'YmdHis'},
                            'subcontrol_room',
                            'user_id',
                            'type'
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
                id: 'audio_cuesheet_list',
                title: '오디오 큐시트 목록',
                region: 'center',
                loadMask: true,
                store: store,
		        viewConfig:{
                        emptyText:'등록된 오디오 큐시트 정보가 없습니다'
                },
                colModel: new Ext.grid.ColumnModel({
                        columns: [
                                new Ext.grid.RowNumberer(),
                                {header: 'CueSheet ID', dataIndex: 'cuesheet_id', hidden:true},
                                {header: '<center>타이틀</center>', dataIndex: 'cuesheet_title', width: 150},
                                {header: '방송일시', dataIndex: 'broad_date', width: 80, renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center'},
				{header: '등록자' , width: 80 , dataIndex: 'user_id', align: 'center'},
                                {header: '등록일시', dataIndex: 'created_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'},
                                {header: '등록타입', dataIndex: 'type', hidden: true}
                        ]
                }),
                tbar: [{
                    text: '새로고침',
                    icon: '/led-icons/arrow_refresh.png',
                    handler: function(btn, e){
                        Ext.getCmp('audio_cuesheet_list').getStore().reload();
                    }
                },'-',{
                    xtype: 'displayfield',
                    value: '방송일시'
                },' ',{
                    xtype: 'datefield',
                    id: 'audio_cuesheet_broad_date',
                    width: 90,
                    format: 'Y-m-d',
                    listeners: {
                        render: function(self) {
                            self.setValue(new Date());
                        },
                        select: function(self, date) {
                            var cuesheet_type = 'A';
                            Ext.getCmp('audio_cuesheet_list').getStore().load({
                            params: {
                                broad_date: date.format('Ymd'),
                                cuesheet_type: cuesheet_type
                            }
                        });
                        }
                    }
                },{
                    text: '검색',
                    hidden: true,
                    icon: '/led-icons/find.png',
                    handler: function(self){
                        var cuesheet_type = 'A';
                        var broad_date = Ext.getCmp('audio_cuesheet_broad_date').getValue().format('Ymd');
                        Ext.getCmp('cuesheet_list').getStore().load({
                            params: {
                                broad_date: broad_date,
                                cuesheet_type: cuesheet_type
                            }
                        });
                    }
                }],
                listeners: {
                        viewready: function(self){
                                var cuesheet_type = 'A';
                                var broad_date = Ext.getCmp('audio_cuesheet_broad_date').getValue().format('Ymd');

                                self.store.load({
                                        params: {
                                                broad_date: broad_date,
                                                cuesheet_type: cuesheet_type
                                        }
                                });
                        },
                        rowclick: function(self, rowIndex, e) {
                            var records = self.getStore().getAt(rowIndex);
                            var cuesheet_id = records.get('cuesheet_id');
                            var cuesheet_type = 'A';

                            Ext.getCmp('audio_cuesheet_items').store.load({
                                params: {
                                        cuesheet_type: cuesheet_type,
                                        cuesheet_id: cuesheet_id
                                }
                            });
                        }
                },
                buttonAlign: 'center',
                fbar: [{
                        text: _text('MN00033'),
                        scale: 'medium',
                        handler: function(btn, e) {
                                var cuesheet_type = 'A';
                                this.buildAddCueSheet(e, cuesheet_type);
                        },
                        scope: this
                },{
                        text: _text('MN00043'),
                        scale: 'medium',
                        handler: function(btn, e) {
                                var cuesheet_type = 'A';
                                var hasSelection = Ext.getCmp('audio_cuesheet_list').getSelectionModel().hasSelection();
                                if(hasSelection) {
                                        var sel = Ext.getCmp('audio_cuesheet_list').getSelectionModel().getSelected();

                                        this.buildEditCueSheet(e,sel, cuesheet_type);
                                }else{
                                        Ext.Msg.alert(_text('MN00022'), _text('MSG00169'));
                                }
                        },
                        scope: this
                },{
                        text: _text('MN00034'),
                        scale: 'medium',
                        handler: function(btn, e) {
                                var hasSelection = Ext.getCmp('audio_cuesheet_list').getSelectionModel().hasSelection();
                                if(hasSelection) {
                                        this.buildDeleteCuesSheet(e);
                                }else{
                                        Ext.Msg.alert(_text('MN00022'), _text('MSG00170'));
                                }

                        },
                        scope: this
                }],
            };

            Ariel.Nps.Cuesheet.AudioList.superclass.initComponent.call(this);
    },

    buildAddCueSheet: function(e, cuesheet_type){
            var win = new Ext.Window({
                    id: 'add_audio_cuesheet_win',
                    border: false,
                    layout: 'fit',
                    split: true,
                    title: '오디오 큐시트 추가',
                    width: 400,
                    height: 150,
                    modal: true,
                    items: [{
                            id: 'add_audio_cuesheet_form',
                            xtype: 'form',
                            padding: 10,
                            defaults: {
                                    anchor: '100%'
                            },
                            items: [{
                                    xtype: 'textfield',
                                    name: 'cuesheet_title',
                                    fieldLabel: '큐시트 명',
                                    allowBlank: false
                            },{
                                    xtype: 'datefield',
                                    format: 'Y-m-d',
                                    name: 'broad_date',
                                    fieldLabel: '방송일자',
                                    allowBlank: false,
                                    listeners: {
                                        render: function(self) {
                                            self.setValue(new Date());
                                        }
                                    }
                            }]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                            text: _text('MN00033'),
                            scale: 'medium',
                            handler: function(btn, e) {

                                    Ext.getCmp('add_audio_cuesheet_form').getForm().submit({
                                            url: '/store/cuesheet/cuesheet_action.php',
                                            params: {
                                                    action: 'add',
                                                    cuesheet_type: cuesheet_type
                                            },
                                            success: function(form, action) {
                                                    try {
                                                            var result = Ext.decode(action.response.responseText, true);
                                                            if(result.success) {
                                                                    Ext.getCmp('add_audio_cuesheet_win').close();
                                                                    Ext.getCmp('audio_cuesheet_list').getStore().reload();
                                                            }else{
                                                                    Ext.Msg.show({
                                                                            title: _text('MN00022'),
                                                                            icon: Ext.Msg.ERROR,
                                                                            msg: result.msg,
                                                                            buttons: Ext.Msg.OK
                                                                    })
                                                            }
                                                    }catch(e){
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
                    },{
                            text: _text('MN00004'),
                            scale: 'medium',
                            handler: function() {
                                    Ext.getCmp('add_audio_cuesheet_win').close();
                            }
                    }]
            }).show(e.getTarget());
    },

    buildEditCueSheet: function(e, rec, cuesheet_type){
            var win = new Ext.Window({
                    id: 'edit_audio_cuesheet_win',
                    border: false,
                    layout: 'fit',
                    split: true,
                    title: '오디오 큐시트 수정',
                    width: 400,
                    height: 150,
                    modal: true,
                    items: [{
                            id: 'edit_audio_cuesheet_form',
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
                                    fieldLabel: '큐시트 명',
                                    allowBlank: false
                            },{
                                    xtype: 'datefield',
                                    format: 'Y-m-d',
                                    name: 'broad_date',
                                    fieldLabel: '방송일자',
                                    allowBlank: false,
                                    listeners: {
                                        render: function(self) {
                                            self.setValue(new Date());
                                        }
                                    }
                            }]
                    }],
                    buttonAlign: 'center',
                    buttons: [{
                            text: '수정',
                            scale: 'medium',
                            handler: function(btn, e) {
                                    Ext.getCmp('edit_audio_cuesheet_form').getForm().submit({
                                            url: '/store/cuesheet/cuesheet_action.php',
                                            params: {
                                                    action: 'edit'
                                            },
                                            success: function(form, action) {
                                                    try {
                                                            var result = Ext.decode(action.response.responseText, true);
                                                            if(result.success) {
                                                                    Ext.getCmp('edit_audio_cuesheet_win').close();
                                                                    Ext.getCmp('cuesheet_list').getStore().reload();
                                                            }else{
                                                                    Ext.Msg.show({
                                                                            title: _text('MN00022'),
                                                                            icon: Ext.Msg.ERROR,
                                                                            msg: result.msg,
                                                                            buttons: Ext.Msg.OK
                                                                    })
                                                            }
                                                    }catch(e){
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
                    },{
                            text: _text('MN00004'),
                            scale: 'medium',
                            handler: function() {
                                    Ext.getCmp('edit_audio_cuesheet_win').close();
                            }
                    }]
            });
            Ext.getCmp('edit_audio_cuesheet_form').getForm().loadRecord(rec);
            win.show(e.getTarget());
    },

    buildDeleteCuesSheet: function(e, cuesheet_id) {
            var rec = Ext.getCmp('audio_cuesheet_list').getSelectionModel().getSelected();
            // 삭제 확인 창
            Ext.Msg.show({
                    animEl: e.getTarget(),
                    //>>title: '삭제 확인',
                    title: _text('MN00024'),

                    icon: Ext.Msg.INFO,
                    msg: '선택하신 "' + rec.get('cuesheet_title') + '" 큐시트를 삭제하시겠습니까?',

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
                                                     }else{
                                                                    //>>Ext.Msg.alert('오류', r.msg);
                                                                    Ext.Msg.alert(_text('MN00022') , r.msg);
                                                            }
                                                    }catch(e) {
                                                            alert(e.message + '(responseText: ' + response.responseText + ')');
                                                    }
                                            }
                                    })
                            }
                    }
            })
    }
});

Ariel.Nps.Cuesheet.AudioDetail = Ext.extend(Ext.Panel, {
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
				'control'
                        ]
                });

                this.controlMapping = function(value){
			switch(value)
			{
				case 'goto':
					return '처음으로';
				break;
				case 'pause':
					return '일시정지';
				break;
				case 'next':
					return '다음으로';
				break;
			}
		};

                this.items = {
                    xtype: 'editorgrid',
                    title: '오디오 큐시트 상세 목록',
                    id: 'audio_cuesheet_items',
                    region: 'south',
                    height: 400,
                    loadMask: true,
		            enableDragDrop: true,
		    draggable: true,
                    ddGroup: 'cuesheetGridDD',
                    store: store,
                    colModel: new Ext.grid.ColumnModel({
                            defaults: {
                                    align: 'center'
                            },
                            // 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
                            columns: [
                                    new Ext.grid.RowNumberer(),
                                    {header: '콘텐츠명',		dataIndex: 'title', },
                                    {header: 'Duration',		dataIndex: 'duration', },
                                    {header: '큐시트 ID',		dataIndex: 'cuesheet_id', hidden: true},
                                    {header: '순서',	dataIndex: 'show_order', hidden: true},
                                    {header: '콘텐츠ID',		dataIndex: 'content_id', hidden: true },
                                    {header: '큐시트 콘텐츠ID',		dataIndex: 'cuesheet_content_id', hidden: true },
				    {header: '제어',	dataIndex: 'control', width: 90,
					editor: {
					    xtype: 'combo',
					    store: new Ext.data.ArrayStore({
						    fields: [
							    'value',
							    'name'
						    ],
						    data: [
							['goto',	'처음으로'],
							['pause',	'일시정지'],
							['next',	'다음으로']
						    ]
					    }),
					    valueField: 'value',
					    displayField: 'name',
					    fieldLabel: '부조정실',
					    mode: 'local',
					    typeAhead: true,
					    triggerAction: 'all',
					    forceSelection: true,
					    editable: false
				    }, renderer: this.controlMapping}
                            ]
                    }),
		    selModel: new Ext.grid.RowSelectionModel({
			    singleSelect: true
		    }),
                    viewConfig: {
                            emptyText: '오디오 큐시트 내 콘텐츠가 없습니다',
                            forceFit: true,
                    },
                    listeners: {
                            viewready: function(self) {
                                    var downGridDroptgtCfg = Ext.apply({}, CueSheetDropZoneOverrides, {
                                            table: 'bc_cuesheet_content',
                                            id_field: 'cuesheet_content_id',
                                            ddGroup: 'cuesheetGridDD',
                                            grid : Ext.getCmp('audio_cuesheet_items')
                                    });
                                    new Ext.dd.DropZone(Ext.getCmp('audio_cuesheet_items').getEl(), downGridDroptgtCfg);
                            }
                    },
                    tbar: [{
                        text: '새로고침',
                        icon: '/led-icons/arrow_refresh.png',
                        handler: function(btn, e){
                            Ext.getCmp('audio_cuesheet_items').store.reload();
                        }
                    }],

                    buttonAlign: 'center',
                    fbar: [{
                            text: '저장',
                            scale: 'medium',
                            handler: function(btn, e) {
                                var grid = Ext.getCmp('audio_cuesheet_items');
                                grid.getStore().commitChanges();
                                var datas = [];
                                var datalist = grid.getStore().getRange();
                                Ext.each(datalist,function(r){
                                    datas.push(r.data);
                                });

                                Ext.Ajax.request({
                                        url : '/store/cuesheet/cuesheet_action.php',
                                        params : {
//                                                cuesheet_id : cuesheet_id,
                                                action: 'save-control',
                                                datas: Ext.encode(datas)
                                        },
                                        callback : function(opts, success, response){
                                                if (success){
                                                        try{
                                                                var r = Ext.decode(response.responseText);
                                                                if(r.success){
                                                                       // Ext.Msg.alert( _text('MN00023'), r.msg);
                                                                       grid.getStore().reload();
                                                                }else{
                                                                        Ext.Msg.alert(_text('MN00022'), r.msg);
                                                                }
                                                        }catch(e){
                                                                Ext.Msg.alert(_text('MN00022'), e+'<br />'+response.responseText);
                                                        }
                                                }else{
                                                        Ext.Msg.alert(_text('MN00022'), response.statusText);
                                                }
                                        }
                                });
                            }
                    },{
                            text: '삭제',
                            scale: 'medium',
                            handler: function(btn, e) {
                                var cuesheet_items = Ext.getCmp('audio_cuesheet_items').getSelectionModel();

                                if(cuesheet_items.hasSelection()) {
                                    var records = cuesheet_items.getSelections();
                                    var rs=[];

                                    Ext.each(records, function(r){
                                        rs.push(r.get('cuesheet_content_id'));
                                    });
                                    var cuesheet_id = records[0].get('cuesheet_id');
                                    Ext.Ajax.request({
                                        url: '/store/cuesheet/cuesheet_action.php',
                                        params: {
                                            action: 'del-items',
                                            cuesheet_id: cuesheet_id,
                                            contents: Ext.encode(rs)
                                        },
                                        callback: function (self, success, response) {
                                            if ( success ) {
                                                try {
                                                    var result = Ext.decode(response.responseText);
                                                    Ext.getCmp('audio_cuesheet_items').getStore().reload();
                                                }
                                                catch ( e ) {
                                                    Ext.Msg.alert(e['name'], e['message']);
                                                }
                                            } else {
                                                Ext.Msg.alert('서버 오류', response.statusText + '(' + response.status + ')');
                                            }
                                        }
                                    });
                                } else {
                                    Ext.Msg.alert( _text('MN00023'), '삭제할 콘텐츠를 선택해 주세요');
                                }
                            }
                    }]
                };

            Ariel.Nps.Cuesheet.AudioDetail.superclass.initComponent.call(this);
        }
});

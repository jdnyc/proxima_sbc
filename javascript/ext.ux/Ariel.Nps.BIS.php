<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

?>

Ariel.Nps.BIS = Ext.extend(Ext.Panel, {
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

                var metaTab = this.metaTab;

                this.items = [
                        new Ariel.Nps.BIS.ProgramList({
                            flex: 1,
                            id: metaTab + '-program'
                        }),
                        new Ariel.Nps.BIS.EpisodeList({
                            flex: 1,
                            id: metaTab + '-episode'
                        })
                ];

                Ariel.Nps.BIS.superclass.initComponent.call(this);
        }
});


Ariel.Nps.BIS.ProgramList = Ext.extend(Ext.Panel, {
            layout: 'fit',
            border: false,

            initComponent: function(config){
                Ext.apply(this, config || {});

                var store = new Ext.data.JsonStore({
                    url: '/store/bis/get_program_list.php',
                    root: 'data',
                    totalProperty: 'total',
                    idPropery: 'pgm_id',
                    fields: [
                            'pgm_id',
                            'pgm_nm',
                            'info_grd',
                            'main_role',
                            'pgm_info',
                            'status'
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
                id: 'bis_program_list',
                title: '프로그램 목록',
                region: 'center',
                loadMask: true,
                store: store,
                viewConfig:{
                        emptyText:'조회된 프로그램 정보가 없습니다',
                        forceFit: true
                },
                colModel: new Ext.grid.ColumnModel({
                        columns: [
                                new Ext.grid.RowNumberer(),
                                {header: '프로그램 ID', dataIndex: 'pgm_id', hidden:true},
                                {header: '<center>프로그램명</center>', dataIndex: 'pgm_nm', width: 200},
                                {header: '내용', dataIndex: 'pgm_info'},
                                {header: '담당 PD', dataIndex: 'main_role'},
                                {header: '등급분류' , dataIndex: 'info_grd'},
                                {header: '상태' , dataIndex: 'status', hidden: true}
                        ]
                }),
                tbar: [{
                    text: '새로고침',
                    icon: '/led-icons/arrow_refresh.png',
                    handler: function(btn, e){
                        var grid = btn.ownerCt.ownerCt;

                        grid.getStore().reload();
                    }
                },'-',{
                    xtype: 'displayfield',
                    value: '프로그램명',
                    style: {
                        'text-align':'center'
                    },
                    width: 70
                },{
                    xtype: 'textfield',
                    width: 120,
                    enableKeyEvents: true,
                    listeners: {
                        keydown: function(self, e) {
                            if (e.getKey() == e.ENTER) {
                                e.stopEvent();

                                var search_pgm_nm = self.getValue();
                                var grid = self.ownerCt.ownerCt;

                                grid.getStore().load({
                                    params: {
                                        pgm_nm: search_pgm_nm
                                    }
                                });
                            }
                        }
                    }
                },{
                    xtype: 'button',
                    text:'검색',
                    icon: '/led-icons/find.png',
                    style: {
                            marginLeft: '5px'
                    },
                    listeners: {
                        click: function(self, e) {
                            var search_pgm_nm = self.ownerCt.items.items[3].getValue();
                            var grid = self.ownerCt.ownerCt;

                            grid.getStore().load({
                                params: {
                                    pgm_nm: search_pgm_nm
                                }
                            });
                        }
                    }
                }],
                bbar: {
                        xtype: 'paging',
                        pageSize: 20,
                        displayInfo: true,
                        store: store
                },
                listeners: {
                        viewready: function(self){

                        },
                        rowclick: function(self, rowIndex, e) {
                            var records = self.getStore().getAt(rowIndex);
                            var pgm_id = records.get('pgm_id');
                            var episode_list = self.ownerCt.ownerCt.metaTab + '-episode';

                            Ext.getCmp(episode_list).items.items[0].getStore().load({
                                params: {
                                        pgm_id: pgm_id
                                }
                            });
                        }
                }
            };

            Ariel.Nps.BIS.ProgramList.superclass.initComponent.call(this);
    }
});

Ariel.Nps.BIS.EpisodeList = Ext.extend(Ext.Panel, {
            layout: 'fit',
            border: false,

            initComponent: function(config){
                Ext.apply(this, config || {});

                var store = new Ext.data.JsonStore({
                        url: '/store/bis/get_episode_list.php',
                        root: 'data',
                        totalProperty: 'total',
                        autoLoad: false,
                        fields: [
                                'award_info',           'brd_run',
                                {name :'brd_ymd',type:'date',dateFormat:'Ymd'},
                                'ca_right',             'cjenr_clf',
                                'cjenr_clf_code',       'cstry_clf',
                                'cstry_clf_code',       'delib_grd',
                                'delib_grd_code',       'director',
                                'dm_right',             'emerg_yn',
                                'epsd_id',              'epsd_nm',
                                'epsd_onm',             'flash_yn',
                                'frgn_clf',             'frgn_clf_code',
                                'house_no',             'info_grd',
                                'ip_right',             'jenr_clf',
                                'jenr_clf1',            'jenr_clf_code',
                                'main_role',            'mc_nm',
                                'news_yn',              'pgm_clf',
                                'pgm_clf1',             'pgm_clf1_code',
                                'pgm_clf2',             'pgm_clf2_code',
                                'pgm_clf_code',         'pgm_id',
                                'pgm_info',             'pgm_nm',
                                'pgm_onm',              'pgm_typ',
                                'pgm_type_code',        'pilot_yn',
                                'pp_right',             'prd_clf',
                                'prd_clf_code',         'prd_cntry1',
                                'prd_cntry1_code',      'prd_cntry2',
                                'prd_cntry2_code',      'prd_co_cd',
                                'prd_co_nm',
                                {name :'prd_ym',type:'date',dateFormat:'Ymd'},
                                'sa_right',             'scl_clf',
                                'scl_clf_code',         'st_right',
                                'stry_clf1',            'stry_clf1_code',
                                'supp_role',            'synopsis',
                                'target',               'target_code',
                                'tot_cnt',              'use_yn',
                                'view_hm',              'vo_right',
                                {name: '4778261', mapping: 'pgm_id'},   // 프로그램ID
                                {name: '4000292', mapping: 'pgm_nm'},   // 프로그램
                                {name: '4778262', mapping: 'house_no'},   // 소재ID
                                {name: '4778263', mapping: 'epsd_id'},   // 회차
                                {name: '4000293', mapping: 'epsd_nm'},   // 부제 (일단 회차명을 부제로)
                                {name: '4000289', mapping: 'brd_ymd', type:'date',dateFormat:'Ymd'},    // 방송일자
                                {name: '4778141', mapping: 'delib_grd_code'},   // 등급분류
                                {name: '4000294', mapping: 'pgm_info'},   // 내용
                                {name: '4000288', mapping: 'main_role'}   // 담당PD
                        ]
                });

                this.items = {
                    xtype: 'grid',
                    title: '상세 목록',
                    id: 'episode_list',
                    region: 'south',
                    height: 400,
                    loadMask: true,
                    store: store,
                    colModel: new Ext.grid.ColumnModel({
                            defaults: {
                                    align: 'center'
                            },
                            // 2010-11-08 container_name 추가 (컨테이너 추가 by CONOZ)
                            columns: [
                                    new Ext.grid.RowNumberer(),
                                    {header: '방송일시',		dataIndex: 'brd_ymd', renderer: Ext.util.Format.dateRenderer('Y-m-d') },
                                    {header: 'Running Time',		dataIndex: 'brd_run', },
                                    {header: '내용',		dataIndex: 'pgm_info'},
                                    {header: '담당PD',	dataIndex: 'main_role'},
                                    {header: '등급분류',		dataIndex: 'delib_grd_code'},
                                    {header: '프로그램형태',		dataIndex: 'pgm_clf2_code'}
                            ]
                    }),
                    viewConfig: {
                            emptyText: '해당 프로그램에 대한 BIS정보가 없습니다',
                            forceFit: true,
                    },
                    listeners: {
                            viewready: function(self) {
                            },
                            rowdblclick: function(self, rowIndex, e) {
                                var win = self.ownerCt.ownerCt.ownerCt;
                                var metaTab = self.ownerCt.ownerCt.ownerCt.items.items[0].metaTab;
                                var p = Ext.getCmp(metaTab).activeTab.getForm();

                                var episode_list = self.getSelectionModel();

                                if(episode_list.hasSelection()) {
                                    var rec = episode_list.getSelected();
                                    p.loadRecord(rec);

                                    win.close();
                                } else {
                                    Ext.Msg.alert( _text('MN00023'), 'BIS항목을 선택해 주세요');
                                }
                            }
                    },
                    tbar: [{
                        text: '새로고침',
                        icon: '/led-icons/arrow_refresh.png',
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

            Ariel.Nps.BIS.EpisodeList.superclass.initComponent.call(this);
        }
});

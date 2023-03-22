<?php
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
//require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");

?>

Ariel.Nps.BISEpisode = Ext.extend(Ext.Panel, {
            layout: 'fit',
            fieldLabel: '부제 목록',
            border: false,

            initComponent: function(config){
                Ext.apply(this, config || {});

                var store = new Ext.data.JsonStore({
                        url: '/store/bis/get_episode_list.php',
                        root: 'data',
                        totalProperty: 'total',
                        autoLoad: false,
                        fields: [
								// 프로그램ID
								{name: '4778366', mapping: 'pgm_id'},
								{name: '4778383', mapping: 'pgm_id'},
								{name: '4778387', mapping: 'pgm_id'},
								// 프로그램명
								{name: '4778365', mapping: 'pgm_nm'},
								{name: '4778381', mapping: 'pgm_nm'},
								{name: '4778386', mapping: 'pgm_nm'},
								//회차
								{name: '4778389', mapping: 'epsd_no'},
								{name: '4778390', mapping: 'epsd_no'},
								{name: '4778391', mapping: 'epsd_no'},
								//회차부제
//								{name: '4778377', mapping: 'epsd_nm'},
								//방송길이
//								{name: '4778378', mapping: 'brd_run'},
								//부제구분
//								{name: '4778379', mapping: 'epsd_clf'},
								//부제구분명
//								{name: '4778380', mapping: 'epsd_clf_nm'},
								//심의등급
//								{name: '4778381', mapping: 'delib_grd'},
								//심의등급명
//								{name: '4778382', mapping: 'delib_grd_nm'},
								//정보등급
//								{name: '4778383', mapping: 'info_grd'},
								//편성구분
//								{name: '4778384', mapping: 'scl_clf'},
								//편성구분명
//								{name: '4778385', mapping: 'scl_clf_nm'},
								//최종회여부
//								{name: '4778386', mapping: 'epsd_end_yn'},
								//제작장소
//								{name: '4778387', mapping: 'rec_place'},
								//사용여부
//								{name: '4778388', mapping: 'epsd_use_yn'},

                                'pgm_id',   // 프로그램ID
                                'pgm_nm',   // 프로그램
                                'epsd_no',   // 회차
                                'epsd_nm',   // 부제 (일단 회차명을 부제로)
								'brd_run',
								'epsd_clf',
								'epsd_clf_nm',
								'delib_grd',
								'delib_grd_nm',
								'info_grd',
								'scl_clf',
								'scl_clf_nm',
								'epsd_end_yn',
								'rec_place',
								'epsd_use_yn'
                        ]
                });

                this.items = {
                    xtype: 'grid',
                    id: 'episode_list',
                    region: 'south',
                    height: 400,
                    loadMask: true,
                    store: store,
                    colModel: new Ext.grid.ColumnModel({
                            defaults: {
                                    align: 'center'
                            },
                            columns: [
                                    new Ext.grid.RowNumberer(),
                                    {header: '회차번호',    dataIndex: 'epsd_no', width: 40},
                                    {header: '회차부제',    dataIndex: 'epsd_nm', align: 'undefined'},
                                    {header: 'Running Time',		dataIndex: 'brd_run', width : 40 },
                                    {header: '부제구분명',    dataIndex: 'epsd_clf_nm', align: 'undefined'},
                                    {header: '심의등급명',    dataIndex: 'delib_grd_nm', align: 'undefined'},
                                    {header: '편성구분명',    dataIndex: 'scl_clf_nm', align: 'undefined'},
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
                                var metaTab = self.ownerCt.metaTab;
                                var p = Ext.getCmp(metaTab).activeTab.getForm();

                                var episode_list = self.getSelectionModel();

                                if(episode_list.hasSelection()) {
                                    var rec = episode_list.getSelected();
                                    p.loadRecord(rec);

                                } else {
                                    Ext.Msg.alert('알림', 'BIS항목을 선택해 주세요');
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

            Ariel.Nps.BISEpisode.superclass.initComponent.call(this);
        }
});

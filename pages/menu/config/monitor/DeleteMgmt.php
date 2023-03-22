<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
session_start();
fn_checkAuthPermission($_SESSION);

?>

(function(){

	var deleteMonitorPageSize = 100;

	Ext.ns('Ariel.monitor');
	Ext.override(Ext.PagingToolbar, {
		doLoad : function(start){
			var o = {}, pn = this.getParams();
			o[pn.start] = start;
			o[pn.limit] = this.pageSize;
			if(this.fireEvent('beforechange', this, o) !== false){
				var options = Ext.apply({}, this.store.lastOptions);
				options.params = Ext.applyIf(o, options.params);
				this.store.load(options);
			}
		}
	});

	Ariel.monitor.DeletePanel = Ext.extend(Ext.grid.GridPanel, {
		stripeRows: true,
        loadMask: true,
		border: false,
		listeners: {
		},
		initComponent: function(){
			var _this = this;

			this.store = new Ext.data.JsonStore({
				url: '/custom/cjos/store/get_delete_mgmt.php',
				totalProperty: 'total',
                remoteSort: true,
				root: 'data',
				fields: [
					'content_id','category_id','category_title','title',
                    {name: 'created_date', type: 'date', dateFormat: 'YmdHis'}
				],
				listeners: {
					beforeload: function(self, opts){
                        var g = Ext.getCmp('delete_tab').getActiveTab().get(0);
						var from = _this.getTopToolbar().getComponent('delete_date_from').getValue().format('Ymd000000');
						var to = _this.getTopToolbar().getComponent('delete_date_to').getValue().format('Ymd999999');

						self.baseParams = {
							data_type: _this.data_type,
							start_date: from,
							end_date: to,
							limit: deleteMonitorPageSize,
							start: 0
						};
					}
				}
			});
			this.bbar = new Ext.PagingToolbar({
				store: this.store,
				pageSize: deleteMonitorPageSize,
                buttonAlign: 'center'
			});

			Ext.apply(this, {
                selModel: new Ext.grid.RowSelectionModel({
					singleSelect: false
				}),
				cm: new Ext.grid.ColumnModel({
                    defaults: {
                        sortable: true
                    },
                    columns: [
                        {header: 'Content ID', dataIndex: 'content_id', width: 120},
                        {header: '등록일자', dataIndex: 'created_date', width: 130, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
                        {header: 'Category ID', dataIndex: 'category_id', width: 120, hidden: true},
                        {header: '카테고리', dataIndex: 'category_title', width: 250},
                        {header: '제목', dataIndex: 'title', width: 550}
                    ]
                }),
				tbar: [{
					itemId: 'delete_date_from',
					xtype: 'datefield',
					format: 'Y-m-d',
					width : 90,
					value: new Date().add(Date.DAY, -365*10).format('Y-m-d')
				}, _text('MN00183'), {//' 부터 '
					itemId: 'delete_date_to',
					xtype: 'datefield',
					format: 'Y-m-d',
					width : 90,
					value: new Date().add(Date.DAY, -this.default_date).format('Y-m-d')
				}, '-', {
					//>>text: '새로고침',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
					//icon: '/led-icons/arrow_refresh.png',
					handler: function(){
						this.getStore().reload();
					},
					scope: this
				}, '-', {
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;" title="원본삭제"><i class="fa fa-times" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						var activeTab = Ext.getCmp('delete_tab').getActiveTab();
                        var sm = activeTab.get(0).getSelectionModel();
                        var selections = sm.getSelections();
                        if(sm.getCount() < 1) {
                            Ext.Msg.alert(_text('MN00023'), _text('MSG01005'));
			                return;
                        }

                        var win = new Ext.Window({
                            layout:'fit',
                            title:'<?=_text('MN00128')?>',
                            modal: true,
                            width:500,
                            height:150,
                            buttonAlign: 'center',
                            items:[{
                                xtype:'form',
                                border: false,
                                frame: true,
                                padding: 5,
                                labelWidth: 70,
                                cls: 'change_background_panel',
                                defaults: {
                                    anchor: '100%'
                                },
                                items: [{
                                    itemId:'delete_reason',
                                    xtype: 'textarea',
                                    height: 50,
                                    fieldLabel:'<?=_text('MN00128')?>',
                                    allowBlank: false,
                                    blankText: _text('MSG01062'),//'삭제 사유를 적어주세요',
                                    msgTarget: 'under'
                                }]
                            }],
                            buttons:[{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
                                scale: 'medium',
                                handler: function(btn,e){
                                    var form = win.items.get(0);
                                    var isValid = form.getComponent('delete_reason').isValid();
                                    if (!isValid)
                                    {
                                        Ext.Msg.show({
                                            icon: Ext.Msg.INFO,
                                            title: '<?=_text('MN00024')?>',
                                            msg: _text('MSG01062'),//'삭제사유를 적어주세요.',
                                            buttons: Ext.Msg.OK
                                        });
                                        return;
                                    }

                                    var active_grid = Ext.getCmp('delete_tab').getActiveTab().get(0);
                                    var sm = active_grid.getSelectionModel();
                                    var tm = form.getComponent('delete_reason').getValue();

                                    var rs = [];
                                    var _rs = sm.getSelections();
                                    Ext.each(_rs, function(r, i, a){
                                        rs.push({
                                            content_id: r.get('content_id'),
                                            delete_his: tm
                                        });
                                    });

                                    Ext.Msg.show({
                                        icon: Ext.Msg.QUESTION,
                                        title: '<?=_text('MN00024')?>',
                                        msg: '<?=_text('MSG00145')?>',

                                        buttons: Ext.Msg.OKCANCEL,
                                        fn: function(btnId, text, opts){
                                            if(btnId == 'cancel') return;

                                            var ownerCt = Ext.getCmp('tab_warp').getActiveTab().get(0);
                                            ownerCt.sendAction('delete_hr', rs, active_grid);
                                            win.destroy();
                                        }
                                    });
                                }
                            },{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
                                scale: 'medium',
                                handler: function(btn,e){
                                    win.destroy();
                                }
                            }]
                        });
                        win.show();
					},
					scope: this
				}, '-', {
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;" title="삭제"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						var activeTab = Ext.getCmp('delete_tab').getActiveTab();
                        var sm = activeTab.get(0).getSelectionModel();
                        var selections = sm.getSelections();
                        if(sm.getCount() < 1) {
                            Ext.Msg.alert(_text('MN00023'), _text('MSG01005'));
			                return;
                        }
                        
                        var win = new Ext.Window({
                            layout:'fit',
                            title: _text('MN00128'),
                            modal: true,
                            width:500,
                            height:150,
                            buttonAlign: 'center',
                            items:[{
                                xtype:'form',
                                border: false,
                                frame: true,
                                padding: 5,
                                labelWidth: 70,
                                cls: 'change_background_panel',
                                defaults: {
                                    anchor: '95%'
                                },
                                items: [{
                                    itemId:'delete_reason',
                                    xtype: 'textarea',
                                    height: 50,
                                    fieldLabel:_text('MN00128'),
                                    allowBlank: false,
                                    blankText: '<?=_text('MSG02015')?>',
                                    msgTarget: 'under'
                                }]
                            }],
                            buttons:[{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-trash" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00034'),
                                scale: 'medium',
                                handler: function(btn,e){
                                    var form = win.items.get(0);
                                    var isValid = form.getComponent('delete_reason').isValid();
                                    if ( ! isValid) {
                                        Ext.Msg.show({
                                            icon: Ext.Msg.INFO,
                                            title: _text('MN00024'),//확인
                                            msg: '<?=_text('MSG02015')?>',
                                            buttons: Ext.Msg.OK
                                        });
                                        return;
                                    }

                                    var active_grid = Ext.getCmp('delete_tab').getActiveTab().get(0);
                                    var sm = active_grid.getSelectionModel();
                                    var tm = form.getComponent('delete_reason').getValue();

                                    var rs = [];
                                    var _rs = sm.getSelections();
                                    Ext.each(_rs, function(r, i, a){
                                        rs.push({
                                            content_id: r.get('content_id'),
                                            delete_his: tm
                                        });
                                    });

                                    Ext.Msg.show({
                                        icon: Ext.Msg.QUESTION,
                                        title: _text('MN00024'),
                                        msg: _text('MSG00145'),
                                        buttons: Ext.Msg.OKCANCEL,
                                        fn: function(btnId, text, opts){
                                            if(btnId == 'cancel') return;

                                            var ownerCt = Ext.getCmp('tab_warp').getActiveTab().get(0);
                                            ownerCt.sendAction('delete', rs, active_grid);
                                            win.destroy();
                                        }
                                    });
                                }
                            },{
                                text : '<span style="position:relative;top:1px;"><i class="fa fa-close style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
                                scale: 'medium',
                                handler: function(btn,e){
                                    win.destroy();
                                }
                            }]
                        });
                        win.show();
					},
					scope: this
				}
                ,'->','※ 카테고리나 제목에 "공통"이라는 단어가 들어간 항목은 검색되지 않습니다.'
				],
                view: new Ext.ux.grid.BufferView({
                    emptyText: '검색 결과가 없습니다.',
                    scrollDelay: false,
                    templates: {
                        cell: new Ext.Template(
                            '<td class="x-grid3-col x-grid3-cell x-grid3-td-{id} x-selectable {css}" style="{style}" tabIndex="0" {cellAttr}>',
                            '<div class="x-grid3-cell-inner x-grid3-col-{id}" {attr}>{value}</div>',
                            '</td>'
                        )
                    }
                })
            });

			Ariel.monitor.DeletePanel.superclass.initComponent.call(this);
		}
	});

	return {
		layout:	'border',
		border: false,
		items: [{
			id: 'delete_tab',
			cls: 'proxima_tabpanel_customize proxima_customize proxima_customize_progress',
			xtype: 'tabpanel',
			region: 'center',
			border: false,
			activeTab: 0,
			plain: true,
			intervalID: null,
			defaults: {
				autoScroll: true
			},
			listeners: {
				tabchange: function(self, p){
					p.get(0).getStore().reload();
				},
				beforetabchange: function(self, newTab, currentTab){
				}
			},
			items: [{
				title: '동영상',
				layout: 'fit',
				items: new Ariel.monitor.DeletePanel({
                    data_type: 'video',
                    default_date: 365
                })
			},{
				title: '이미지',
				layout: 'fit',
                items: new Ariel.monitor.DeletePanel({
                    data_type: 'image',
                    default_date: 365
                })
			},{
				title: '클린',
				layout: 'fit',
                items: new Ariel.monitor.DeletePanel({
                    data_type: 'clean',
                    default_date: 365*2
                })
			},{
				title: '사전제작',
				layout: 'fit',
                items: new Ariel.monitor.DeletePanel({
                    data_type: 'preprod',
                    default_date: 365*5
                })
			}]
		}]
	}
})()
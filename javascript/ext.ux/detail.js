Ext.ns('Ariel.DetailPanel');

Ariel.DetailPanel = Ext.extend(Ext.Panel, {
	initComponent: function(config){
		Ext.apply(this, config || {});
		
        this.items = [
            {
                xtype: 'container',
				align: 'stretchmax',
                layout: 'hbox',
                items: [
                    {
                        xtype: 'container',
                        items: [
                            {
                                xtype: 'panel',
                                title: '미리보기',
                                height: 310,
                                width: 480
                            },
                            {
                                xtype: 'container',
                                layout: 'hbox',
                                height: 30,
                                layoutConfig: {
                                    pack: 'center',
                                    align: 'middle'
                                },
								defaultType: 'button',
								defaults: {
									margins: '0 5 0 0'
								},
                                items: [
                                    {
                                        text: '다운로드'
                                    },
                                    {
                                        text: '전송'
                                    },
                                    {
                                        text: 'MyButton'
                                    }
                                ]
                            },
                            {
                                xtype: 'grid',
                                title: '파일 정보',
                                height: 150,
								store: new Ext.data.ArrayStore(),
                                columns: [
                                    {
                                        xtype: 'gridcolumn',
                                        header: 'Column',
                                        sortable: true,
                                        resizable: true,
                                        width: 100,
                                        dataIndex: 'string'
                                    },
                                    {
                                        xtype: 'gridcolumn',
                                        header: 'Column',
                                        sortable: true,
                                        resizable: true,
                                        width: 100,
                                        dataIndex: 'string'
                                    },
                                    {
                                        xtype: 'gridcolumn',
                                        header: 'Column',
                                        sortable: true,
                                        resizable: true,
                                        width: 100,
                                        dataIndex: 'string'
                                    }
                                ]
                            }
                        ]
                    },
                    {
                        xtype: 'form',
                        title: '메타데이터',
                        labelWidth: 100,
                        labelAlign: 'left',
                        layout: 'form',
                        flex: 1,
						defaults: {
							anchor: '100%'
						},
						defaultType: 'textfield',
						items: [{
							fieldLabel: '제목'
						},{
							fieldLabel: '내용'
						},{
							xtype: 'datefield',
							fieldLabel: '방송일정'
						},{
							fieldLabel: '심의여부'
						}]
                    }
                ]
            },
            {
                xtype: 'tabpanel',
                activeTab: 0,
                items: [
                    {
                        xtype: 'panel',
                        title: '카탈로깅'
                    },
                    {
                        xtype: 'panel',
                        title: '의견'
                    }
                ]
            }
        ];
		
		Ariel.DetailPanel.superclass.initComponent.call(this);
	}
})

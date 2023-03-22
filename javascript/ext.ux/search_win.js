var search_win = new Ext.Window({
	title: '상세 검색',
    width:  380,
    height: 345,
    modal: true,
    closeAction: 'hide',
	resizable: false,
    layout: 'fit',
    items: [{
        id: 'search_form',
        xtype: 'form',
        bodyStyle: 'padding: 5px',
        baseCls: 'x-plain',
        defaultType: 'textfield',
		labelWidth: 70,
        defaults: {
            anchor: '100%'
        },
        items: [{
			xtype: 'hidden',
			name: 're[]',
			value: '1'
		},{
			xtype: 'hidden',
			name: 're[]',
			value: '2'
		},{
			xtype: 'hidden',
			name: 're[]',
			value: '3'
		},{
            fieldLabel: '제목',
            name: 'title'
        },{
            fieldLabel: '요약',
            name: 'summary'
        },{
            fieldLabel: '출연자',
            name: 'actor'
        },{
            fieldLabel: '취재자',
            name: 'shooter'
        },{
            fieldLabel: 'Tape No.',
            name: 'scriptor'
        },{
            fieldLabel: '촬영 장소',
            name: 'shooting_place'
        },{
			xtype: 'container',
			layout: 'column',
			defaults: {
				xtype: 'container',
				layout: 'form',
				columnWidth: 0.5
			},
			items: [{
				items: {
					xtype: 'datefield',
					id: 'startShootingDate',
					name: 'startShootingDate',
					fieldLabel: '촬영일',					
					format: 'Y-m-d',
					anchor: '100%',
					editable: false,
					listeners: {
						change: function(self, n, o){
							Ext.getCmp('endShootingDate').setMinValue(n);
						}
					}
				}
			},{
				items: {
					xtype: 'datefield',
					id: 'endShootingDate',
					name: 'endShootingDate',
					fieldLabel: '부터',
					format: 'Y-m-d',
					anchor: '100%',
					editable: false,
					listeners: {
						change: function(self, n, o){
							Ext.getCmp('startShootingDate').setMaxValue(n);
						}
					}
				}
			}]
		},{
			xtype: 'container',
			layout: 'column',
			defaults: {
				xtype: 'container',
				layout: 'form',
				columnWidth: 0.5
			},
			items: [{
				items: {
					xtype: 'datefield',
					id: 'startBroadcastingDate',
					name: 'startBroadcastingDate',
					fieldLabel: '방송 예정일',
					format: 'Y-m-d',
					anchor: '100%',
					editable: false,
					listeners: {
						change: function(self, n, o){
							Ext.getCmp('endBroadcastingDate').setMinValue(n);
						}
					}
				}
			},{
				items: {
					xtype: 'datefield',
					id: 'endBroadcastingDate',
					name: 'endBroadcastingDate',
					fieldLabel: '부터',
					format: 'Y-m-d',
					anchor: '100%',
					editable: false,
					listeners: {
						change: function(self, n, o){
							Ext.getCmp('startBroadcastingDate').setMaxValue(n);
						}
					}
				}
			}]
		},{
			xtype: 'container',
			layout: 'column',
			defaults: {
				xtype: 'container',
				layout: 'form',
				columnWidth: 0.5
			},
			items: [{
				items: {
					xtype: 'datefield',
					id: 'startRegisteredDate',
					name: 'startRegisteredDate',
					fieldLabel: '생성일',
					format: 'Y-m-d',
					anchor: '100%',
					editable: false,
					listeners: {
						change: function(self, n, o){
							Ext.getCmp('endRegisteredDate').setMinValue(n);
						}
					}
				}
			},{
				items: {
					xtype: 'datefield',
					id: 'endRegisteredDate',
					name: 'endRegisteredDate',
					fieldLabel: '부터',
					format: 'Y-m-d',
					anchor: '100%',
					editable: false,
					listeners: {
						change: function(self, n, o){
							Ext.getCmp('startRegisteredDate').setMaxValue(n);
						}
					}
				}
			}]
		}]
    }],
    keys: [{
    	key: 13,
    	fn: searching
    }],
    buttons: [{
		text: '초기화',
		handler: function(){
			Ext.getCmp('search_form').getForm().reset();
		}
	},{
        text: '검색',
        handler: searching
    },{
        text: '취소',
        handler: function(){
            search_win.hide();
        }
    }]             
});

function searching(){
    var params;

	Ext.getCmp('simple_search').setValue('');
    var v = Ext.getCmp('search_form').getForm().getValues();
    v.type = Ext.getCmp('view-type').getActiveItem().type;

    
    Ext.getCmp('grid_list').getStore().reload({    	
        params: v
    });
    
    search_win.hide();	
}

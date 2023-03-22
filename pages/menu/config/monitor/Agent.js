(function() {
    var store_resource = new Ext.data.JsonStore({
        autoLoad: true,
        url: '/pages/menu/config/monitor/resource.php',
        fields: [
            'name',
			'ip',
			'used_cpu',
			'used_memory',
            {name: 'timestamp', type: 'date', dateFormat: 'YmdHis'}
        ],
        root: 'data'
    });

    var store_storage = new Ext.data.JsonStore({
        autoLoad: true,
        url: '/pages/menu/config/monitor/storage.php',
        fields: [
            'name',
			'ip',
			'drive_name',
			'used',
			'available',
			'total',
            {name: 'timestamp', type: 'date', dateFormat: 'YmdHis'},
        ],
        root: 'data'
    });

    /**
     * CJ오쇼핑의 경우 Agent 모니터링은 안쓰기때문에 숨김처리 및 타이틀 변경 - 2018.01.15 Alex
     */
    return {
        xtype: 'panel',
        //cls: 'proxima_customize',
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00381')+'</span></span>',
        cls: 'grid_title_customize proxima_customize',
        border: false,
        layout: {
			type: 'vbox',
			align:'stretch'
		},

        items: [{
            region: 'center',
			flex : 1,
            xtype: 'grid',
            stripeRows: true,
            border: false,
            hidden: true,
            columns: [
                {header:  _text('MN02355'), dataIndex: 'name'},
                {header:  _text('MN02356'), dataIndex: 'ip'},
                {header: _text('MN02358')+'(%)', dataIndex: 'used_cpu'},
                {header: _text('MN02357')+'(%)', dataIndex: 'used_memory'},
                {header: _text('MN02354'), dataIndex: 'timestamp',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120}
            ],
            loadMask: true,
            store: store_resource,
            viewConfig: {
                forceFit: true,
				emptyText: _text('MSG00148')
            },
            tbar: [{
                //text: '새로고침',
                //icon: '/led-icons/arrow_refresh.png',
                cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                handler: function(){
                    store_resource.reload();
                }
            }]
        }, {
            //title: '스토리지',
        	//title: _text('MN00381'),
        	//title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00381')+'</span></span>',
        	cls: 'grid_title_customize proxima_customize',
        	border: false,
        	flex : 2,
            region: 'south',
            //height: '500',
            xtype: 'grid',
            stripeRows: true,
            columns: [
                {header: _text('MN02350'), dataIndex: 'drive_name'},
                {header: _text('MN02351'), dataIndex: 'used'},
                {header: _text('MN02352'), dataIndex: 'available'},
                {header: _text('MN02353'), dataIndex: 'total'},
                {header: _text('MN02354'), dataIndex: 'timestamp',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120}
            ],
            loadMask: true,
            store: store_storage,
            viewConfig: {
                forceFit: true,
				emptyText: _text('MSG00148')
            },
            tbar: [{
                //text: '새로고침',
                //icon: '/led-icons/arrow_refresh.png',
            	cls: 'proxima_button_customize',
				width: 30,
				text: '<span style="position:relative;top:1px;" title="'+_text('MN00139')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                handler: function(){
                    store_storage.reload();
                }
            }]
        }]
    };
})()
/*
(function() {
	var store = new Ext.data.JsonStore({
		autoLoad: true,
		url: '/pages/menu/config/monitor/agent.php',
		fields: [
			'name', 'main_ip',
			{name: 'last_access', type: 'date', dateFormat: 'YmdHis'},
		],
		root: 'data'
	});

	return {
		xtype: 'grid',
		columns: [
			//{header: '이름', dataIndex: 'name'},
			//{header: 'IP', dataIndex: 'main_ip'},
			//{header: '마지막 접속일자', dataIndex: 'last_access',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120}
			{header: _text('MN00223'), dataIndex: 'name'},
			{header: _text('MN02163'), dataIndex: 'main_ip'},
			{header: _text('MN00104'), dataIndex: 'last_access',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120}
		],
		loadMask: true,
		store: store,
		viewConfig: {
			forceFit: true
		},
		tbar: [{
			//text: '새로고침',
			text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00139'),
			//icon: '/led-icons/arrow_refresh.png',
			handler: function(){
				store.reload();
			}
		}]
	};
})()
*/
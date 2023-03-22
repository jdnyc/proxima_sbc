(function(){
	var myPageSize = 30;//페이지 제한

	Ext.override(Ext.PagingToolbar, {
		doload : function(start){
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

	var archive_request_store = new Ext.data.JsonStore({//소산된 항목 스토어
			url: '/store/archive/getArchiveDissipation.php',
			root: 'data',
			totalProperty: 'total',
			fields: [
				{name: 'content_id'},
                                {name: 'unique_id'},
				{name: 'title'},
                                {name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
				{name: 'tape_number'},
                                {name: 'reg_user_id'},
                                {name: 'reg_user_nm'}
			],
			listeners: {
				beforeload: function(){
					baseParams = {
						limit: myPageSize,
						start: 0
					}
				}
			}
		});
	archive_request_store.load({params:{start:0, limit:myPageSize}});

	var archive_request_bar = new Ext.PagingToolbar({
		store: archive_request_store,
		pageSize: myPageSize,
                plugins: [
                                    new Ext.ux.grid.PageSizer()
                         ]
	});
        
        var sm = new Ext.grid.CheckboxSelectionModel();
        
	var archive_request_list_grid = new Ext.grid.GridPanel({
		id:'archive_request_list_grid',
		store: archive_request_store,
		
		tbar: [{
			icon: '/led-icons/arrow_undo.png',
			text: '소산 리스토어',
			handler: function(btn, e){
                            if(sm.hasSelection)
                            {
                                var contents = [];

                                Ext.each(sm.getSelections(), function(rec){
                                        contents.push(rec.data.content_id);
                                });
                                var post_contents = [];
                                post_contents.push(contents);                               

                                Ext.Ajax.request({
                                    url : '/store/archive/archive_dissipation_action.php',
                                    params : {
                                        contents : post_contents,
                                        action : 'complete_requst_restore'
                                    },
                                    callback: function(options, success, response) {
                                            if (success) {
                                                    try {
                                                            var r = Ext.decode(response.responseText);

                                                            if(!r.success) {
                                                                    //>>Ext.Msg.alert('오류', r.msg);
                                                                    Ext.Msg.alert(_text('MN00022'), r.msg);
                                                            } else {
                                                                    Ext.Msg.alert('완료',r.msg);
                                                                    Ext.getCmp('archive_request_list_grid').getStore().reload();
                                                            }
                                                    }
                                                    catch (e)
                                                    {
                                                            btn.isShowing = false;
                                                            Ext.Msg.alert(e['name'], e['message']);
                                                    }
                                            }
                                            else
                                            {
                                                    //>>Ext.Msg.alert('서버 오류', response.statusText);
                                                    Ext.Msg.alert(_text('MN00022'), response.statusText);
                                            }
                                            btn.isShowing = false;
                                    }
                                });
                            }
			}
		},{
			xtype: 'tbseparator',
			width: 20
                },{
			icon: '/led-icons/arrow_refresh.png',
			text: '새로고침',
			handler: function(btn, e){
				Ext.getCmp('archive_request_list_grid').getStore().reload();
			}
		}],
                sm : sm,
		columns: [
			new Ext.grid.RowNumberer(),
                        sm,
			{header: '컨텐츠 ID', dataIndex: 'content_id',hidden: true},
                        {header: 'Volume',align:'center', dataIndex: 'tape_number', width: 100},
                        {header: 'UNIQUE ID',align:'center', dataIndex: 'unique_id', width: 150},
			{header: '<center>제목</center>', dataIndex: 'title', width: 350},
                        {header: '<center>요청자</center>', dataIndex: 'reg_user_nm', width: 150},
                        {header: '요청일자', width: 150, dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align:'center'}
		],
		viewConfig: {
//			forceFit: true,
                        emptyText : '소산된 자료에 대한 리스토어 요청이 없습니다' 
		},
		listeners: {
			
		},
		bbar : [archive_request_bar]
	});
	return{
		border: false,
		loadMask: true,
		layout: 'fit',
		items: [
			archive_request_list_grid
		]
	};
})()
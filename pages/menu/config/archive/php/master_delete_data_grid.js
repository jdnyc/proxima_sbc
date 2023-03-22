(function(){
	var myPageSizeArchiveDelete = 25;//페이지 제한

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

	var archive_delete_store = new Ext.data.JsonStore({//공지사항 스토어
			url: '/store/getMasterArchiveDelete.php',
			root: 'data',
			totalProperty: 'total',
			fields: [
				{name: 'content_id'},
				{name: 'title'},
                                {name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
				{name: 'expired_date', type: 'date', dateFormat: 'YmdHis'},
				{name: 'reg_user_id'}
			],
			listeners: {
				beforeload: function(){
					baseParams = {
						limit: myPageSizeArchiveDelete,
						start: 0
					}
				}
			}
		});
	archive_delete_store.load({params:{start:0, limit:myPageSizeArchiveDelete}});

	var archive_delete_bar = new Ext.PagingToolbar({
		store: archive_delete_store,
		pageSize: myPageSizeArchiveDelete,
                plugins: [
                                    new Ext.ux.grid.PageSizer()
                         ]
	});
        
        var sm = new Ext.grid.CheckboxSelectionModel();
        
	var archive_delete_list_grid = new Ext.grid.GridPanel({
		id:'archive_delete_list_grid',
		store: archive_delete_store,
		
		tbar: [{
			icon: '/led-icons/arrow_undo.png',
			text: '폐기 취소',
			handler: function(btn, e){
                            if(sm.hasSelection)
                            {
                                var contents = [];

                                Ext.each(sm.getSelections(), function(rec){
                                        contents.push(rec.data.content_id);
                                });
                                /*
                                var post_contents = [];
                                var post_counts = [];
                                post_contents.push(contents);
                                post_counts.push(counts);
                                */

                                Ext.Ajax.request({
                                    url : '/store/archive/master_delete_action.php',
                                    params : {
                                        contents : contents,
                                        action : 'undo'
                                    },
                                    callback: function(options, success, response)
                                    {
                                            if (success)
                                            {
                                                    try
                                                    {
                                                            var r = Ext.decode(response.responseText);

                                                            if(!r.success)
                                                            {
                                                                    //>>Ext.Msg.alert('오류', r.msg);
                                                                    Ext.Msg.alert(_text('MN00022'), r.msg);
                                                            }
                                                            else
                                                            {
                                                                    Ext.Msg.alert('폐기 취소 완료',r.msg);

                                                                    Ext.getCmp('archive_delete_list_grid').getStore().reload();
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
				Ext.getCmp('archive_delete_list_grid').getStore().reload();
			}
		}],
                sm : sm,
		columns: [
			new Ext.grid.RowNumberer(),
                        sm,
			{header: '컨텐츠 ID', dataIndex: 'content_id',hidden: true},
			{header: '<center>제목</center>', dataIndex: 'title', width: 180},
                        {header: '<center>등록자</center>', dataIndex: 'reg_user_id', width: 60},
                        {header: '등록일자', width: 60, dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align:'center'}
		],
		viewConfig: {
			forceFit: true,
                        emptyText : '자료 요청이 없습니다' 
		},
		listeners: {
			
		},
		bbar : [archive_delete_bar]
	});
	return{
		border: false,
		loadMask: true,
		layout: 'fit',
		items: [
			archive_delete_list_grid
		]
	};
})()
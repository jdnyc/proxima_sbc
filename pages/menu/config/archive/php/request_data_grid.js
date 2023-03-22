(function(){
	var myPageSize_request = 25;//페이지 제한

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

	var request_store = new Ext.data.JsonStore({//공지사항 스토어
			url: '/store/request_data_store.php',
			root: 'data',
			totalProperty: 'total',
			fields: [
				{name: 'old_content_id'},
				{name: 'title'},
				{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
				{name: 'reg_user_id'},
                                {name: 'transfer_count'}
			],
			listeners: {
				beforeload: function(){
					baseParams = {
						limit: myPageSize_request,
						start: 0
					}
				}
			}
		});
	request_store.load({params:{start:0, limit:myPageSize_request}});

	var request_bbar = new Ext.PagingToolbar({
		store: request_store,
		pageSize: myPageSize_request,
                plugins: [
                                    new Ext.ux.grid.PageSizer()
                         ]
	});
        
        var sm = new Ext.grid.CheckboxSelectionModel();
        
	var request_data_grid = new Ext.grid.GridPanel({
		id:'request_data_grid',
		store: request_store,
		
		tbar: [{
			icon: '/led-icons/accept.png',
			text: '승인',
			handler: function(btn, e){
                            if(sm.hasSelection)
                            {
                            var contents = [];
                            var counts = [];
                            Ext.each(sm.getSelections(), function(rec){
                                    contents.push(rec.data.old_content_id);
                                    counts.push(rec.data.transfer_count);
                            });
                            var post_contents = [];
                            var post_counts = [];
                            post_contents.push(contents);
                            post_counts.push(counts);
                            
                            
                                Ext.Ajax.request({
                                    url : '/pages/menu/config/archive/php/request_data_approve.php',
                                    params : {
                                        contents : post_contents,
                                        counts : post_counts
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
                                                                    Ext.Msg.alert('승인완료',r.msg);

                                                                  Ext.getCmp('request_data_grid').getStore().reload();
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
			icon: '/led-icons/cross.png',
			text: '반려',
			handler: function(btn, e){
                            if(sm.hasSelection)
                            {
                            var contents = [];
                            var counts = [];
                            Ext.each(sm.getSelections(), function(rec){
                                    contents.push(rec.data.old_content_id);
                                    counts.push(rec.data.transfer_count);
                            });
                            var post_contents = [];
                            var post_counts = [];
                            post_contents.push(contents);
                            post_counts.push(counts);
                            
                            
                                Ext.Ajax.request({
                                    url : '/pages/menu/config/archive/php/request_data_reject.php',
                                    params : {
                                        contents : post_contents,
                                        counts : post_counts
                                    },
                                    callback: function(options, success, response){
                                        if(success)
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
                                                                Ext.Msg.alert('반려',r.msg);

                                                                Ext.getCmp('request_data_grid').getStore().reload();
                                                        }
                                                }
                                                catch (e)
                                                {
                                                        Ext.Msg.alert(e['name'], e['message']);
                                                }
                                        }
                                        else
                                        {
                                                Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'( '+response.status+' )');
                                        }
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
				Ext.getCmp('request_data_grid').getStore().reload();
			}
		}],
                sm : sm,
		columns: [
			new Ext.grid.RowNumberer(),
                        sm,
			{header: '컨텐츠 ID', dataIndex: 'old_content_id',hidden: true},
			{header: '<center>제목</center>', dataIndex: 'title'},
                        {header: '<center>요청자</center>', dataIndex: 'reg_user_id'},
			{header: '요청일자', width: 60, dataIndex: 'creation_datetime',  renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center'}
		],
		viewConfig: {
			forceFit: true,
                        emptyText : '자료 요청이 없습니다' 
		},
		listeners: {
			
		},
		bbar : [request_bbar]
	});
	return{
		border: false,
		loadMask: true,
		layout: 'fit',
		items: [
			request_data_grid
		]
	};
})()
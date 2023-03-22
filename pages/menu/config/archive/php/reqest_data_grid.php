(function(){


	var myPageSize_notice = 25;//페이지 제한

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

	var notice_store = new Ext.data.JsonStore({//공지사항 스토어
			url: '/store/mypage/noticestore.php',
			root: 'data',
			totalProperty: 'total',
			fields: [
				{name: 'notice_id'},
				{name: 'notice_title'},
				{name: 'created_date', type: 'date', dateFormat: 'YmdHis'},
				{name: 'notice_content'}
			],
			listeners: {
				beforeload: function(){
					baseParams = {
						limit: myPageSize_notice,
						start: 0
					}
				}
			}
		});
	notice_store.load({params:{start:0, limit:myPageSize_notice}});

	var notice_bbar = new Ext.PagingToolbar({//공지사항 페이징
		store: notice_store,
		pageSize: myPageSize_notice
	});

	var msg = function(title, msg){//메세지 함수
		Ext.Msg.show({
			title: title,
			msg: msg,
			minWidth: 200,
			modal: true,
			icon: Ext.Msg.INFO,
			buttons: Ext.Msg.OK
		});
	};

	var notice_grid = new Ext.grid.GridPanel({//////공지사항 그리드///////
		id:'notice_grid',
		store: notice_store,
		sm: new Ext.grid.RowSelectionModel({
			singleSelect: true
		}),
		tbar: [{
			icon: '/led-icons/pencil.png',
			//style:'border-style:solid;border-width:thin;border-color:#bebebe',

			//>>text: '등록',
			text: _text('MN00038'),
			handler: function(btn, e){
				var save_win = new Ext.Window({//공지사항 새로작성하기 창
					layout:'fit'
					//>>,title: '공지사항'
					,title: _text('MN00144')
					,width:500
					,height:300
					,modal: true
					,resizable: false
					,plain: true
					,items: [{
						id: 'new_notice',
						xtype: 'form',
						border: false,
						frame: true,
						padding: 10,
						labelWidth: 50,
						defaults: {
							anchor: '100%'
						},
						items: [{
							id: 'newtitle',
							xtype: 'textfield',
							//>>fieldLabel: '제목'
							fieldLabel: _text('MN00249')
						},{
							id:'newcontent',
							xtype: 'textarea',
							height: 180,
							//>>fieldLabel: '내용'
							fieldLabel: _text('MN00067')
						}]
					}]
					,buttons: [{
						//text:'저 장',
						text:_text('MN00046'),
						handler: function(){
							if(Ext.getCmp('newtitle').getValue())
							{
								Ext.getCmp('new_notice').getForm().submit({
									url:'/pages/menu/config/notice/notice_save.php',
									method: 'POST',
									//>>waitMsg: '저장중..',
									waitMsg: _text('MN00065'),
									success: function(){
										msg('Success', _text('MSG00162'));
										notice_store.reload();
									},
									failure: function(){
										//>>msg('Failure', '저장 실패');
										msg('Failure', _text('MSG00167'));
									},
									params: {
										title: Ext.getCmp('newtitle').getValue(),
										content: Ext.getCmp('newcontent').getValue()
									}
								});
								save_win.destroy();
							}
							else
								//>>msg('알림', '제목을 입력하세요.');
								msg(_text('MN00023'), _text('MSG00090'));
						}
					},{
						//text:'닫기',
						text:_text('MN00031'),
						handler: function(){
							save_win.destroy();
						}
					}]
				});
				save_win.show();
			}
		},{
			xtype: 'tbseparator',
			width: 20
        },{
			icon: '/led-icons/bandaid.png',
			//style:'border-style:solid;border-width:thin;border-color:#bebebe',
			//>>text: '수 정',
			text: _text('MN00043'),
			handler: function(btn, e){
				var edit_sel = notice_grid.getSelectionModel().getSelected();
				if(!edit_sel)
					//>>msg('알림', '수정하실 글을 선택해주세요');
					msg(_text('MN00023'), _text('MSG00160'));
				else{
					var edit_win = new Ext.Window({//공지사항 수정하기 창
						layout:'fit'
						//>>,title: '공지사항'
						,title: _text('MN00144')
						,width:500
						,height:300
						,modal: true
						,resizable: false
						,plain: true
						,items: [{
							id: 'edit_notice',
							xtype: 'form',
							border: false,
							frame: true,
							padding: 10,
							labelWidth: 50,
							defaults: {
								anchor: '100%'
							},
							items: [{
								id: 'edittitle',
								xtype: 'textfield',
								//>>fieldLabel: '제목',
								fieldLabel: _text('MN00249'),
								value: edit_sel.get('notice_title')

							},{
								id:'editcontent',
								xtype: 'textarea',
								height: 180,
								//>>fieldLabel: '내용',
								fieldLabel: _text('MN00067'),
								value: edit_sel.get('notice_content')
							}]
						}]
						,buttons: [{
							text:'수 정',
							text:_text('MN00043'),
							handler: function(){
								if(Ext.getCmp('edittitle').getValue())
								{
									Ext.getCmp('edit_notice').getForm().submit({
										url:'/pages/menu/config/notice/notice_edit.php',
										method: 'POST',
										//waitMsg: '저장중..',
										waitMsg: _text('MN00065'),
										success: function(){
											//>>msg('Success', '수정 완료');
											msg('Success', _text('MSG00087'));
											notice_store.reload();
										},
										failure: function(){
											//>>msg('failure', '수정 실패');
											msg('failure', _text('MSG00168'));
										},
										params: {
											id:  edit_sel.get('notice_id'),
											title: Ext.getCmp('edittitle').getValue(),
											content: Ext.getCmp('editcontent').getValue()
										}
									});
									edit_win.destroy();
								}
								else
									//msg('알림', '제목을 입력하세요.');
									msg(_text('MN00023'), _text('MSG00090'));
							}
						},{
							//>>text:'닫기',
							text:_text('MN00031'),
							handler: function(){
								edit_win.destroy();
							}
						}]
					});
					edit_win.show();
				}
			}
		},{
			xtype: 'tbseparator',
			width: 20
        },{
			icon: '/led-icons/bin_closed.png',
			//style:'border-style:solid;border-width:thin;border-color:#bebebe',
			//>>text: '삭 제',
			text: _text('MN00034'),
			handler: function(){
				var del_sel = notice_grid.getSelectionModel().getSelected();
				if(!del_sel)
					//>>msg('알림', '삭제하실 글을 선택해주세요');
					msg(_text('MN00023'), _text('MSG00082'));
				else{
					Ext.Msg.show({
						icon: Ext.Msg.QUESTION,
						//>>title: '확인',
						//>>msg: '삭제 하시겠습니까?',
						title: _text('MN00024'),
						msg: _text('MSG00161'),
						buttons: Ext.Msg.OKCANCEL,
						fn: function(btnId, text, opts){
							if(btnId == 'cancel') return;

							var del_id = del_sel.get('notice_id');
							Ext.Ajax.request({
								url: '/pages/menu/config/notice/notice_del.php',
								params: {
									id: del_id
								},
								callback: function(options, success, response){
									if (success)
									{
										try
										{
											var r = Ext.decode(response.responseText);
											if (r.success)
											{
												notice_store.reload();
												//>>msg('알림', '삭제 완료');
												msg(_text('MN00023'), _text('MSG00040'));
											}
											else
											{
												//>>Ext.Msg.alert('확인', r.msg);
												Ext.Msg.alert(_text('MN00024'), r.msg);
											}
										}
										catch (e)
										{
											Ext.Msg.alert(e['name'], e['message']);
										}
									}
									else
									{
										Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
										Ext.Msg.alert(_text('MSG00024'), response.statusText);
									}
								}
							});
						}
					});
				}
			}
		}],
		columns: [
			//{header: 'No.', width: 20, dataIndex: 'no',align:'center'},
			new Ext.grid.RowNumberer(),
			{header: 'id', dataIndex: 'notice_id',hidden: true},
			//>>{header: '제 목', dataIndex: 'notice_title', align:'center'},
			//>>{header: '등록일자', width: 60, dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center'},
			//>>{header: '내용', dataIndex: 'notice_content',hidden: true}
			{header: _text('MN00249'), dataIndex: 'notice_title', align:'center'},
			{header: _text('MN00102'), width: 60, dataIndex: 'created_date',  renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center'},
			{header: _text('MN00067'), dataIndex: 'notice_content',hidden: true}
		],
		viewConfig: {
			forceFit: true
		},
		listeners: {
			rowdblclick: function(self, idx, e){
				var record = self.getSelectionModel().getSelected();

				var content_win = new Ext.Window({//공지사항 내용 창
					layout:'fit'
					//>>,title: '공지사항'
					,title: _text('MN00144')
					,width:500
					,height:300
					,modal: true
					,resizable: false
					,plain: true
					,items: [{
						id: 'notice_form',
						xtype: 'form',
						border: false,
						frame: true,
						padding: 10,
						labelWidth: 50,
						defaults: {
							anchor: '100%'
						},
						items: [{
							id: 'title',
							xtype: 'textfield',
							//>>fieldLabel: '제목',
							fieldLabel: _text('MN00249'),
							value: record.get('notice_title')
						},{
							id:'content',
							xtype: 'textarea',
							height: 180,
							//>>fieldLabel: '내용',
							fieldLabel: _text('MN00067'),
							value: record.get('notice_content')
						}]
					}]
					,buttons: [{
						//>>text:'수정',
						text:_text('MN00043'),
						handler: function(){
							if(Ext.getCmp('title').getValue())
							{
								Ext.getCmp('notice_form').getForm().submit({
									url:'/pages/menu/config/notice/notice_edit.php',
									method: 'POST',
									//>>waitMsg: '수정중..',
									waitMsg: _text('MN00068'),
									success: function(){
										//>>msg('Success', '수정 완료');
										msg('Success', _text('MSG00087'));
										notice_store.reload();
									},
									failure: function(){
										//>>msg('failure', '수정 실패');
										msg('failure', _text('MSG00168'));
									},
									params: {
										id:  record.get('notice_id'),
										title: Ext.getCmp('title').getValue(),
										content: Ext.getCmp('content').getValue()
									}
								});
								content_win.destroy();
							}
							else
								//>>msg('알림', '제목을 입력하세요.');
								msg(_text('MN00023'), _text('MSG00090'));
						}
					},{
						//>>text: '삭제',
						text: _text('MN00034'),
						handler: function(){
							Ext.Msg.show({
								icon: Ext.Msg.QUESTION,
								//>>title: '확인',
								//>>msg: '삭제 하시겠습니까?',
								title: _text('MN00024'),
								msg: _text('MSG00161'),
								buttons: Ext.Msg.OKCANCEL,
								fn: function(btnId, text, opts){
									if(btnId == 'cancel') return;

									Ext.getCmp('notice_form').getForm().submit({
										url:'/pages/menu/config/notice/notice_del.php',
										method: 'POST',
										waitMsg: '삭제중..',
										success: function(){
											//>>msg('Success','삭제 완료');
											msg('Success', _text('MSG00040'));
											notice_store.reload();
										},
										failure: function(){
											//>>msg('Failure','삭제 실패');
											msg('Failure', _text('MN00130'));
										},
										params: {
											id: record.get('notice_id')
										}
									});
									content_win.destroy();
								}
							});
						}
					},{
						//>>text:'닫기',
						text: _text('MN00031'),
						handler: function(){
							content_win.destroy();
						}
					}]
				});
				content_win.show();
			}
		},
		bbar : [notice_bbar]
	});
	return{
		border: false,
		loadMask: true,
		layout: 'fit',
		items: [
			notice_grid
		]
	};
})()
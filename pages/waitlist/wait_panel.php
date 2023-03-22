<?php

session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

?>
Ext.ns('Ariel.Wait');

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

Ariel.Wait.Panel = Ext.extend(Ext.grid.GridPanel, {
	loadMask: true,
	border: false,
	listeners: {
		viewready: function(self){
			//self.getStore().reload();
		},
		rowcontextmenu: function(self, rowIndex, e){
			e.stopEvent();
			var sm = self.getSelectionModel();
			sm.selectRow(rowIndex);
			var r = sm.getSelected();

			var menu = new Ext.menu.Menu({
				items: [{
					icon:'/led-icons/accept.png',
					text: '승인하기',
					handler: function(btn, e){
						var records = self.getSelectionModel().getSelections();	
						var content_ids = [];

						var that = self;
						var ud_check = false;

						Ext.each(records, function(i){
							content_ids.push(i.get('content_id'));

							if(i.get('ud_content_id') == '-1')
							{
								ud_check = true;
							}
						});	
						
						if(ud_check)
						{
							Ext.Msg.alert(_text('MN00003'), '콘텐츠 유형의 정의가 필요합니다.' );
							return;
						}
						
						Ext.Ajax.request({
							url: '/pages/waitlist/doAccept.php',
							params: {
								content_ids: Ext.encode(content_ids)
							},
							callback: function(self, success, response){
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if(r.success)
										{
											Ext.Msg.alert( _text('MN00003'), _text('MN00011') );
											that.getStore().reload();
										}
										else
										{
											Ext.Msg.alert(_text('MN00012'), r.msg );
										}
										
									}
									catch (e)
									{
										//alert(response.responseText)
										Ext.Msg.alert(e['name'], e['message'] );
									}
								}
								else
								{
									//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
									Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
								}
							}
						});
					}
				}]
			})

			menu.showAt(e.getXY());
		},
		rowdblclick: function(self, rowIndex, e){
			var record = self.getSelectionModel().getSelected();
			self.doviewContent(self, record.get('content_id'), record.get('ud_content_id') );
		}			
	},

	initComponent: function(){		

		this.bbar = new Ext.PagingToolbar({
			store: this.store,
			pageSize: 20
		});

		Ext.apply(this, {

			selModel: new Ext.grid.RowSelectionModel({
				singleSelect: true,
				listeners: {
					rowselect: function(self){						
					},
					rowdeselect: function(self){					
					}
				}
			}),
			store: this.store,			
			tbar: [{
				//text: '새로고침',
				text: _text('MN00139'),
				icon: '/led-icons/arrow_refresh.png',
				handler: function(){
					this.getStore().reload();
				},
				scope: this
			}],
			viewConfig: {
				//emptyText: '등록된 자료가 없습니다.',
				emptyText: _text('MSG00142'),
				forceFit: true,
				listeners: {
					refresh: function(self) {					
					}
				}
			}
		});
		Ariel.Wait.Panel.superclass.initComponent.call(this);
	},
	detailview: function( self, content_id, ud_content_id ){
		
		if ( !Ext.Ajax.isLoading(self.isOpen) )
		{			
			self.isOpen = Ext.Ajax.request({
				url: '/javascript/ext.ux/Ariel.DetailWindow.php',
				params: {
					content_id: content_id,
					ud_content_id: ud_content_id
				},
				callback: function(self, success, response){
					if (success)
					{
						try
						{
							Ext.decode(response.responseText);
						//	that.load.hide();
						}
						catch (e)
						{
							//alert(response.responseText)
							Ext.Msg.alert(e['name'], e['message'] );
						}
					}
					else
					{
						//>>Ext.Msg.alert('서버 오류', response.statusText+'('+response.status+')');
						Ext.Msg.alert(_text('MN00022'), response.statusText+'('+response.status+')');
					}
				}
			});
		}
	},
	doviewContent: function(self, content_id, ud_content_id ){	
		
		if( ud_content_id != '-1' )//유저콘텐츠타입이 정의되지 않았을때..
		{	
			self.detailview( self, content_id, ud_content_id );
		}
		else
		{
			var that = self;
			var win = new Ext.Window({
				title: '콘텐츠 유형을 선택해 주세요',
				modal: true,
				width: 350,
				height: 200,
				layout: 'fit',
				items: [{
					xtype: 'grid',
					loadMask: true,
					enableDD: false,
					store: new Ext.data.JsonStore({
						url: '/store/ud_content_list.php',
						autoLoad: true,
						root: 'data',
						fields: [
							'bc_content_id',
							'ud_content_id',
							'ud_content_title'
						]
					}),
					listeners: {
						rowdblclick: function(self){
							var sm = self.getSelectionModel();
							
							if (!sm.hasSelection())
							{
								Ext.Msg.alert('확인', '선택해주세요.');
								return;
							}							
							that.detailview( that, content_id, sm.getSelected().get('ud_content_id') );	
							win.close();
						}
					},				
					columns: [
						{header: '콘텐츠 유형',	dataIndex: 'ud_content_title',align: 'center', sortable: true }
					],				
					buttons: [{
						text: '선택',
						handler: function(b, e){
							var sm = self.getSelectionModel();
							
							if (!sm.hasSelection())
							{
								Ext.Msg.alert('확인', '선택해주세요.');
								return;
							}							
							that.detailview( that, content_id, sm.getSelected().get('ud_content_id') );	
							win.close();												
						}
					},{
						text: '닫기',
						handler: function(b, e){
							b.ownerCt.ownerCt.ownerCt.close();
						}
					}],
					viewConfig: {
						forceFit: true
					}
				}]
			}).show();
		}
	}
});
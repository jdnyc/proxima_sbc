<?php
function buildEditedListTab($content_id, $args)
{
// FTP관련 주석처리 일단 보류 (2011년 1월 12일 조훈휘)
	global $db;
//	
	//$down_ip = '192.168.0.102';
	$query = "select ud_content_id from bc_content where content_id = {$content_id}";
	$ud_content_id = $db->queryOne($query);

	return "
	{
	
			//fieldLabel: ' 메타 수정 내역 ',	
			//title : '" . _text ( 'MN02196' ) . "',		
			padding:0,	
			//frame: true,		
			xtype: 'grid',
			cls: 'proxima_customize',
			border: false,
			buttonAlign: 'center',
			anchor: '95%',
			layout:'fit',		
			id: 'edited_list',
			autoScroll: true,			
			//height: 450,
			//maxHeight : 500,						
			//width : 500,
            disableSelection: true,
			columnWidth: 1,
			loadMask: true,
			$args,		
			name: 'grid1',
			/*		
			tools: [{
				id: 'refresh',
				qtip: '" . _text ( 'MN00139' ) . "',
				handler: function(e, toolEl, p, tc){
					p.store.reload();
				}
			}],*/		   		
			loadMask: true,
			//!!title: '미디어파일 리스트',
			//title: '메타 수정 내역 리스트',
			split: true,			
			store: new Ext.data.JsonStore({
				id: 'detail_media_grid',
				url: '/store/get_edited_list.php',
				totalProperty: 'total',
				baseParams: {
					start: 0,
					limit: 20
				},
				root: 'data',
				fields: [
					'user_nm',
					{name: 'date', type: 'date', dateFormat: 'YmdHis'},				
					'description',
					'log_id'
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						Ext.Msg.alert(_text('MN00022'), response.responseText);
					}
				}
			}),
			viewConfig: {				
				emptyText: '"._text('MSG00124')."',
				forceFit: true,
				stripeRows: true,
				getRowClass: function(record, rowIndex, rowParams, store) {
					return 'multiline-row';
				}
			},
			sm: new Ext.grid.CheckboxSelectionModel({
				singleSelect: true
			}),
			bbar: new Ext.PagingToolbar({
							store: 'detail_media_grid',
							pageSize: 20
						}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false,
					menuDisabled: true
				},
				columns: [	
					new Ext.grid.RowNumberer(),												
					{header: '" . _text ( 'MN00189' ) . "', dataIndex: 'user_nm', width: 100,align: 'left'},
					{header: '" . _text ( 'MN00354' ) . "', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 180, align: 'left'},
					{header: '" . _text ( 'MN00067' ) . "', dataIndex: 'description', width: 200, align: 'left'}							
				]
			}),
			listeners: 
			{				
				viewready: function(self)
				{
					self.getStore().load({
						params: {
							content_id: $content_id
						}
					});
				},
				rowdblclick: function(self, rowIndex, e)
				{	
					
					 var sel = self.getSelectionModel().getSelected();
					 var log_id = sel.get('log_id');
					 var date_info = sel.get('date').format('Y/m/d H:i:s');
					 new Ext.Window({
						 title : date_info+' " . _text ( 'MN02199' ) . "',
						 id : 'detail_info',
						 width : 700,
						 height : 600,		
                         modal : true,
                         layout: 'border',
						 buttonAlign: 'center',
						 items:
						 [{
                            width: 450,
                            region: 'east',
                            xtype:'form',
                            layout: 'form',
                            labelWidth: 50,
                            defaults: {
                                anchor: '95%',
                            },
                            items:[{
                                fieldLabel:'변경전',
                                name:'before',
                                xtype:'textarea',
                                height: 250,
                                readOnly: true
                            },{
                                fieldLabel:'변경후',
                                name:'after',
                                xtype:'textarea',
                                height: 250,
                                readOnly: true
                            }]
                         },{
                             width: 250,
                             region: 'center',
							xtype :'grid',
							cls: 'proxima_customize',
							border: false,
							loadMask: true,
							layout:'fit',		
							id: 'edited_list',
							autoScroll: true,						
							buttonAlign: 'center',
							sm: new Ext.grid.RowSelectionModel({
								singleSelect:true
                            }),
                            height: 550,
							colModel: new Ext.grid.ColumnModel({
								defaults: {	            
									sortable: true,
									menuDisabled: true
								},
								columns: [
									new Ext.grid.RowNumberer(),
									{header: '" . _text ( 'MN02200' ) . "', dataIndex: 'field_id', width: 110},
									{header: '" . _text ( 'MN02201' ) . "', dataIndex: 'old_contents', width:100},
									{header: '" . _text ( 'MN02202' ) . "', dataIndex: 'new_contents', width:100}
								]
							}),
							store : new Ext.data.JsonStore({
								url: '/store/get_log_info.php',
								params : {
									log_id : log_id
								},
								root: 'details',
								fields: ['field_id','old_contents','new_contents']
							}),	
							viewConfig: {				
								//emptyText: 'No history information has been modified 수정된 정보내역이 없습니다.',
								forceFit: true
							},										
							listeners: 
							{				
								viewready: function(self)
								{
									self.getStore().load({
										params: {
											log_id : log_id,
											ud_content_id : $ud_content_id
										}
									});
                                },
                                rowclick: function(self, rowIndex, e)
                                {	
                                    var sel = self.getSelectionModel().getSelected();
                                    self.ownerCt.get(0).getForm().setValues(
                                        {
                                            after: sel.get('new_contents'),
                                            before: sel.get('old_contents')
                                        }

                                    );
                                }
							}
						 }],buttons:[
                            {
								xtype:'button',
								scale:'medium',
								text : '<span style=\"position:relative;top:1px;\"><i class=\"fa fa-close\" style=\"font-size:13px;\"></i></span>&nbsp;'+_text('MN00031'),
								handler : function(e)
								{
									Ext.getCmp('detail_info').close();
								}
							 }
                         ]

					 }).show();
					
				}		
			}
		
	}
";
	
	/*return <<<EOD
	{
		title : ' 메타 수정 내역 ',
		xtype: 'form',		
		id: 'edited_list_form',		
		padding: 0,
		border: false,
		frame: true,
		labelAlign: 'right',
		labelWidth: 100,	
		loadMask: true,
		$args,
		items: [{	
			//fieldLabel: ' 메타 수정 내역 ',			
			padding:0,	
			frame: true,		
			xtype: 'grid',
			anchor: '95%',
			layout:'fit',		
			id: 'edited_list',
			autoScroll: true,			
			height: 510,
			//maxHeight : 500,						
			width : 500,		
			name: 'grid1',		
			
			tools: [{
				id: 'refresh',
				handler: function(e, toolEl, p, tc){
					p.store.reload();
				}
			}],
		
			loadMask: true,
			//!!title: '미디어파일 리스트',
			//title: '메타 수정 내역 리스트',
			split: true,			
			store: new Ext.data.JsonStore({
				id: 'detail_media_grid',
				url: '/store/get_edited_list.php',
				totalProperty: 'total',
				baseParams: {
					start: 0,
					limit: 20
				},
				root: 'data',
				fields: [
					'user_nm',
					{name: 'date', type: 'date', dateFormat: 'YmdHis'},				
					'description'
				],
				listeners: {
					exception: function(self, type, action, opts, response, args){
						Ext.Msg.alert(_text('MN00022'), response.responseText);
					}
				}
			}),
			viewConfig: {				
				emptyText: '수정된 내역이 없습니다.',
				forceFit: true
			},
			sm: new Ext.grid.CheckboxSelectionModel({
				singleSelect: true
			}),
			bbar: new Ext.PagingToolbar({
							store: 'detail_media_grid',
							pageSize: 20
						}),
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false,
					menuDisabled: true
				},
				columns: [	
					new Ext.grid.RowNumberer(),												
					{header: '수정자', dataIndex: 'user_nm', width: 100},
					{header: '수정일', dataIndex: 'date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
					{header: '내용', dataIndex: 'description', width: 100, align: 'center'}
									
				]
			}),
			listeners: {				
				viewready: function(self){
					self.getStore().load({
						params: {
							content_id: $content_id
						}
					});
				}
			}
		}]
	}
EOD;*/
}

?>
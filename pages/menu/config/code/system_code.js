(function(){
/****************************************************************
 * 16-02-22, 송민정
 * 1. 시스템관리 -> 시스템코드관리 페이지
 * 2. 구성
 * 		코드 추가: system_code_add.php,
 * 		코드 삭제: system_code_del.php,
 * 		코드 수정: system_code_edit.php,
 * 		코드 목록 그리드에 출력: system_code_get.php
 * 		
 ****************************************************************/
	var main_store = new Ext.data.JsonStore({
        url: '/pages/menu/config/code/system_code_get.php',
        root: 'data',
        totalProperty: 'total',
        fields: ['id', 'code', 'code_nm','code_nm_english', 'use_yn', 'memo','ref1','ref2','ref3','ref4','ref5']
    });
    main_store.load();    
	
	function editCode(action, url){	
		var disable_code;
		var btn_text = '';
		if(action == _text('MN02031')){
			disable_code = false;
			btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033');
		}else{
			disable_code = true;
			btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043');
		}
		new Ext.Window({
			id: 'system_code_edit_win',
			//title: '코드 수정',
			title: action,
			width: 500,
			height: 400,
			modal: true,
			layout: 'fit',
			buttonAlign: 'center',
			items: {
				id: 'system_code_edit_form',
				cls: 'change_background_panel',
				xtype: 'form',
				defaults : {
					anchor : '100%'
				}, 
				url: url,
				frame: true,
				items:[{
					xtype: 'hidden',
					value: id,
					name: 'id'
				},{
					xtype: 'textfield',
					name: 'code',
					//fieldLabel: '코드'
					fieldLabel: _text('MN02030'),
					disabled: disable_code
				},{
					xtype: 'textfield',
					name: 'code_nm',
					//fieldLabel: '코드 명'
					fieldLabel: _text('MN02032')
				},{
					xtype: 'textfield',
					name: 'code_nm_english',
					fieldLabel: _text('MN02032')+' (english)'
				},{
					xtype: 'checkbox',
					name: 'use_yn',
					status: 'use_yn',
					//>>fieldLabel: '활성화'
					fieldLabel: _text('MN02205')
				},{
					xtype: 'textfield',
					name: 'memo',
					//fieldLabel: '설명'
					fieldLabel: _text('MN00049')
				},{
					xtype: 'textfield',
					name: 'ref1',
					fieldLabel: _text('MN02043')+' 1'
				},{
					xtype: 'textfield',
					name: 'ref2',
					fieldLabel: _text('MN02043')+' 2'
				},{
					xtype: 'textfield',
					name: 'ref3',
					fieldLabel: _text('MN02043')+' 3'
				},{
					xtype: 'textfield',
					name: 'ref4',
					fieldLabel: _text('MN02043')+' 4'
				},{
					xtype: 'textfield',
					name: 'ref5',
					fieldLabel: _text('MN02043')+' 5'
				}],
				listeners: {
					afterrender: function(self) {
						if( url == '/pages/menu/config/code/system_code_edit.php' ){
							var sm = Ext.getCmp('main_grid').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);
							var use_yn = rec.json['use_yn'];
							use_yn = (use_yn == 'Y')? true: false;
							Ext.getCmp('system_code_edit_form').getForm().findField('use_yn').setValue(use_yn);
						}
					}
				}
			},
			buttons: [{
				text: btn_text,
				scale: 'medium',
				handler: function(){
					Ext.getCmp('system_code_edit_form').getForm().submit({
						success: function(form, action){
							if(action.result.success)
							{
								//Ext.Msg.alert( _text('MN02033'), action.result.msg);//'코드 수정'
							}
							else
							{
								Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
							}
							main_store.reload();
							Ext.getCmp('system_code_edit_win').close();
						}
						,failure: function(form, action) {				
							Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
							Ext.getCmp('system_code_edit_win').close();										
						}
					});								
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function(){
					Ext.getCmp('system_code_edit_win').close();
				}
			}]
		}).show();
	}
    
    return {
    	xtype: 'panel',
    	title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN02204')+'</span></span>',
    	cls: 'grid_title_customize',
    	border: false,
    	layout: 'fit',    	
    	items: [ {
		    	xtype: 'grid',
				id: 'main_grid',
				cls: 'proxima_customize',
				stripeRows: true,
		        store: main_store,		        
		        autoWidth: true,
		        autoScroll: true,
		        border: false,
		        viewConfig: {
					forceFit: true
				},
				sm: new Ext.grid.RowSelectionModel({
					singleSelect: true
				}),
				tbar:[{
					//text: '추가',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00033')+'"><i class="fa fa-plus-circle" style="font-size:13px;color:white;"></i></span>',
					handler: function(b, e){
						editCode(_text('MN02031'), '/pages/menu/config/code/system_code_add.php');
					}
				},{
					//text: '수정',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00043')+'"><i class="fa fa-edit" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
						if(check)
						{
							editCode(_text('MN00043'), '/pages/menu/config/code/system_code_edit.php');
						}
						else
						{
							Ext.Msg.alert( _text('MN00043'), _text('MSG01005'));//'수정', 먼저 대상을 선택 해 주시기 바랍니다.
						}
					}
				},{
					//text: '삭제',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-minus-circle" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
						if(check)
						{
							var id = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['id'];
							Ext.Msg.show({
								//title: '삭제',
								text: _text('MN00034'),
								//msg: '정말로 삭제하시겠습니까?',
								msg: _text('MSG02039'),
								buttons: {
									yes: true,
									no: true
								},
								fn: function(btn){
									if(btn == 'yes')
									{							
										Ext.Ajax.request({
											url: '/pages/menu/config/code/system_code_del.php',
											params: {
												id: id
											},
											callback: function(opts, success, response){
												
												if (success)
												{
													main_store.reload();
													try
													{
														var result = Ext.decode(response.responseText);
														if (!result.success)
														{
															Ext.Msg.show({
																//title: '오류',
																title:  _text('MN01039'),//'오류'
																msg: result.msg,
																icon: Ext.Msg.ERROR,
																buttons: Ext.Msg.OK
															});
														}
													}
													catch (e)
													{
															Ext.Msg.show({
																//title: '디코드 오류',
																title:  _text('MN01039'),//'오류'
																msg: e['message'],
																icon: Ext.Msg.ERROR,
																buttons: Ext.Msg.OK
															});
													}
												}
												else
												{
													
												}									
											}
										});
										
									}						
								}
							});
						}
						else
						{			
							Ext.Msg.alert( _text('MN00034'), _text('MSG01005'));//'삭제', 먼저 대상을 선택 해 주시기 바랍니다.
						}
						
					}
				},{
					//text: '엑셀로 저장',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00212')+'"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
					handler: function(){
						var search_word = Ext.getCmp('search_word').getValue();
						window.location = '/pages/menu/config/code/system_code_get.php?mode=excel&search_word='+search_word;
					}
				},{
					//text: '새로고침',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
					handler: function(b, e){
						main_store.reload();
					}
				},'->',{
					xtype: 'textfield',
					id: 'search_word',
					listeners:{
						specialkey: function(field, e){
							var search_word = Ext.getCmp('search_word').getValue();
							main_store.reload({
								params:{
									search_word: search_word
								}
							});
						}
					}
				},{
					//text: '검색',
					cls: 'proxima_button_customize',
					width: 30,
					text: '<span style="position:relative;top:1px;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
					handler: function(b, e){
						var search_word = Ext.getCmp('search_word').getValue();
						main_store.reload({
							params:{
								search_word: search_word
							}
						});
					}
				}],
				listeners: {
					rowdblclick: function(self, index, e){
						var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
						if(check)
						{
							editCode(_text('MN00043'), '/pages/menu/config/code/system_code_edit.php');
						}
						else
						{
							Ext.Msg.alert( _text('MN00043'), _text('MSG01005'));//'수정', 먼저 대상을 선택 해 주시기 바랍니다.
						}
					}
				},
		        colModel: new Ext.grid.ColumnModel({
			        defaultSortable: true,
					defaults: {
				        sortable: true,
				        menuDisabled: true
				        //width: 100
				    },
			        columns: [
			        	new Ext.grid.RowNumberer(),
			            {
			                //header   : '코드', 
							header   : _text('MN02030'),
			                sortable : true, 
			                dataIndex: 'code'
			            },{
			                //header   : '코드 명', 
							header   : _text('MN02032'),
			                sortable : true, 
			                dataIndex: 'code_nm'
			            },
			            {
							header   : 'Code Name (English)',
			                sortable : true, 
			                dataIndex: 'code_nm_english'
			            }
			            ,{
			                //header   : '사용 여부', 
			                //header   : '활성화', 
							header   : _text('MN02205'),
			                sortable : true, 
			                dataIndex: 'use_yn',
							renderer: function(value){
								//if(value == 'Y') return '사용';
								//return '미사용';
								return value;
							}
			            },{
							header   : _text('MN00049'),
			                sortable : true, 
			                dataIndex: 'memo'
			            },
			            {
							header   : _text('MN02043')+' 1',
			                sortable : true, 
			                dataIndex: 'ref1'
			            },
			            {
							header   : _text('MN02043')+' 2',
			                sortable : true, 
			                dataIndex: 'ref2'
			            },
			            {
							header   : _text('MN02043')+' 3',
			                sortable : true, 
			                dataIndex: 'ref3'
			            },
			            {
							header   : _text('MN02043')+' 4',
			                sortable : true, 
			                dataIndex: 'ref4'
			            },
			            {
							header   : _text('MN02043')+' 5',
			                sortable : true, 
			                dataIndex: 'ref5'
			            }
			            ]
		        })
		    }
    	]
    };
})()
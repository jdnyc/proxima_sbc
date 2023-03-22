(function(){
/****************************************************************
 * 11-11-15, 이승수 
 * 1. 시스템관리 -> 코드관리 페이지
 * 2. 구성
 * 		코드 추가: add.php,
 * 		코드 삭제: del.php,
 * 		코드 수정: edit.php,
 * 		코드추가시 콤보박스: get_comgbo.php
 * 		코드 목록 그리드에 출력: get.php
 * 		
 ****************************************************************/
	var store_type = new Ext.data.JsonStore({
        url: '/pages/menu/config/code/get.php',
        root: 'data',
        totalProperty: 'total',
		baseParams : {
			type : 'code_type'
		},
        fields:  ['code', 'name', 'id', 'ref1']
    });
    store_type.load();

	var main_store = new Ext.data.JsonStore({
        url: '/pages/menu/config/code/get.php',
        root: 'data',
        totalProperty: 'total',
        fields: ['code_type', 'code_type_name', 'id', 'code', 'name', 'ename','ref1','ref2','ref3','ref4','ref5','use_yn']
    });
	main_store.load();


	function showWin(action, type, title, button){
		Ext.Ajax.request({
			url: '/pages/menu/config/code/win_code.php',
			params: {
				action: action,
				type : type,
				title : title,
				button : button
			},
			callback: function(self, success, response){
				try {
					var r = Ext.decode(response.responseText);
					r.show();
				}
				catch(e){
					//>>Ext.Msg.alert('오류', e);
					Ext.Msg.alert(_text('MN00022'), e);
				}
			}
		});
	}

	function deleteCode(type, code_id, title){
		var msg = _text('MN00034')+' : '+_text('MSG02039');
		if(type == 'code_type'){
			if( panel_code.getStore().totalLength > 0 ){
				msg = panel_code.getStore().totalLength+_text('MSG02004');
			}
		}
		Ext.Msg.show({
			title : title,
			msg : msg,
			buttons: Ext.Msg.OKCANCEL,
			fn: function(btnId, text, opts){
				if(btnId == 'ok'){
					Ext.Ajax.request({
						url: '/pages/menu/config/code/del.php',
						params: {
							code_id : code_id,
							type : type
						},
						callback: function(opts, success, response){
							if (success){
								try{
									var result = Ext.decode(response.responseText);
									if (result.success){
										store_type.reload();
										main_store.reload();
									}else{
										Ext.Msg.show({
											title:  _text('MN01039'),//'오류'
											msg: result.msg,
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
									}
								}catch (e){
										Ext.Msg.show({
											title:  _text('MN01039'),//'오류'
											msg: e['message'],
											icon: Ext.Msg.ERROR,
											buttons: Ext.Msg.OK
										});
								}
							}else{
								
							}									
						}
					});
					
				}
			}
		});
	}
	
	function editCode(action, url){
		var edit_window = new Ext.Window({
			id: 'code_edit_win',
			//title: '코드 수정',
			title: _text('MN02033'),
			width: 500,
			height: 350,
			modal: true,
			layout: 'fit',
			items: {
				id: 'code_edit_form',
				xtype: 'form',
				url: '/pages/menu/config/code/edit.php',
				frame: true,
				items:[{
					xtype: 'hidden',
					name: 'code_id'
				},{
					xtype: 'combo',
					width: 350,
					name: 'code_type_combo',
					//fieldLabel: '코드유형 명',
					fieldLabel: _text('MN02026'),
					mode: 'local',
					triggerAction: 'all',
					disabled: true,
					editable: false,			
					displayField: 'code_type_name',
					store: new Ext.data.JsonStore({
						url: '/pages/menu/config/code/get_combo.php',
						root: 'data',
						totalProperty: 'total',
						fields: ['code_type_id', 'code_type_name']
					}),
					listeners: {
						render: function(){
							this.store.reload();
						}
					}
				},{
					xtype: 'textfield',
					width: 350,
					name: 'code',
					disabled: true,
					//fieldLabel: '코드'
					fieldLabel: _text('MN02030')
				},{
					xtype: 'textfield',
					width: 350,
					name: 'code_name',
					//fieldLabel: '코드 명'
					fieldLabel: _text('MN02032')
				},{
					xtype: 'textfield',
					width: 350,
					name: 'code_ename',
					//fieldLabel: '코드 영문명'
					fieldLabel: _text('MN02191')
				},{
					xtype: 'checkbox',
					width: 350,
					name: 'use_yn',
					status: 'hidden',
					//>>fieldLabel: '활성화'
					fieldLabel: _text('MN02205')
				},{
					xtype: 'textfield',
					width: 350,
					name: 'ref1',
					fieldLabel: _text('MN02043')+' 1'
				},{
					xtype: 'textfield',
					width: 350,
					name: 'ref2',
					fieldLabel: _text('MN02043')+' 2'
				},{
					xtype: 'textfield',
					width: 350,
					name: 'ref3',
					fieldLabel: _text('MN02043')+' 3'
				},{
					xtype: 'textfield',
					width: 350,
					name: 'ref4',
					fieldLabel: _text('MN02043')+' 4'
				},{
					xtype: 'textfield',
					width: 350,
					name: 'ref5',
					fieldLabel: _text('MN02043')+' 5'
				}],
				listeners: {
					afterrender: function(self) {
						if( url == '/pages/menu/config/code/edit.php' ){
							var sm = Ext.getCmp('main_grid').getSelectionModel();
							var rec = sm.getSelected();
							self.getForm().loadRecord(rec);
							var use_yn = rec.json['use_yn'];
							var code_form = Ext.getCmp('code_edit_form').getForm();
							use_yn = (use_yn == 'Y')? true: false;
							code_form.findField('use_yn').setValue(use_yn);
						}
					}
				},
				buttons: [{
					//text: '수정',
					text: _text('MN00043'),
					handler: function(){
						Ext.getCmp('code_edit_form').getForm().submit({
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
								edit_window.close();
							}
							,failure: function(form, action) {				
								Ext.Msg.alert( _text('MN01039'), action.result.errormsg);//오류
								edit_window.close();										
							}
						});								
					}
				},{
					//text: '취소',
					text: _text('MN00004'),
					handler: function(){
						edit_window.close();
					}
				}]
			}
		}).show();
	}

	var panel_type = new Ext.grid.GridPanel({
		id: 'grid_type',
		stripeRows: true,
		border: false,
		flex : 1,
		store: store_type,
		autoScroll: true,
		viewConfig: {
			forceFit: true,
			emptyText : _text('MSG00148')
		},
		tbar:[{
			//text: '추가',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00033')+'"><i class="fa fa-plus-circle" style="font-size:13px;color:white;"></i></span>',
			handler: function(b, e){
				showWin('add', 'type', 'MN02025', 'MN00033');
			}
		},{
			//text: '수정',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00043')+'"><i class="fa fa-edit" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				var selected = Ext.getCmp('grid_type').getSelectionModel().getSelected();
				if(selected){
					showWin('edit', 'type', 'MN02027', 'MN00043');
				}else{
					Ext.Msg.alert(_text('MN00024'), _text('MSG01005'));
				}
			}
		},{
			//text: '삭제',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-minus-circle" style="font-size:13px;color:white;"></i></span>',
			handler: function(){
				var selected = Ext.getCmp('grid_type').getSelectionModel().getSelected();
				if(selected){
					var code_id = selected.data.id;
					deleteCode('code_type', code_id, _text('MN02029'));
				}else{
					Ext.Msg.alert(_text('MN00024'), _text('MSG01005'));
				}
			}
		},{
			//text: '새로고침',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
			handler: function(b, e){
				store_type.reload();
			}
		},'->',{
			xtype: 'textfield',
			id: 'search_word_type',
			listeners:{
				specialkey: function(field, e){
					var search_word = Ext.getCmp('search_word_type').getValue();
					store_type.reload({
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
				var search_word = Ext.getCmp('search_word_type').getValue();
				store_type.reload({
					params:{
						search_word: search_word,
						type : 'code_type'
					}
				});
			}
		}],
		sm: new Ext.grid.RowSelectionModel({
			singleSelect: true
		}),
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
					//header   : '코드유형', 
					header   : _text('MN02024'),
					sortable : true, 
					dataIndex: 'code'
				},{
					//header   : '코드유형 명', 
					header   : _text('MN02026'),
					sortable : true, 
					dataIndex: 'name'
				}]
		}),
		listeners : {
			rowclick : function (self, row_index, e) {
				var selected = self.getSelectionModel().getSelected();
				panel_code.getStore().load({
					params : {
						type : 'code',
						code_type_id : selected.get('id')
					}
				});
			}	
		}
	});

	var panel_code = new Ext.grid.GridPanel({
		id: 'main_grid',
		stripeRows: true,
		flex : 2,
		border:false,
		store: main_store,
		autoScroll: true,
		viewConfig: {
			forceFit: true,
			emptyText : _text('MSG00148')
		},
		sm: new Ext.grid.RowSelectionModel({
			singleSelect: true
		}),
		tbar:[{
			//text: '추가',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00033')+'"><i class="fa fa-plus-circle" style="font-size:13px;color:white"></i></span>',
			handler: function(b, e){
				showWin('add', 'code', 'MN02031', 'MN00033');
			}
		},{
			//text: '수정',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00043')+'"><i class="fa fa-edit" style="font-size:13px;color:white"></i></span>',
			handler: function(){
				var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
				if(check)
				{
					//showWin(action, type, title, button)
					showWin('edit', 'code', 'MN02031', 'MN00043');
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
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00034')+'"><i class="fa fa-minus-circle" style="font-size:13px;color:white"></i></span>',
			handler: function(){
				var selected = Ext.getCmp('main_grid').getSelectionModel().getSelected();
				if(selected){
					var code_id = selected.data.id;
					//deleteCode(type, code_id, title)
					deleteCode('code', code_id, _text('MN02034'));
				}else{
					Ext.Msg.alert(_text('MN00024'), _text('MSG01005'));
				}
			}
		},{
			//text: '새로고침',
			cls: 'proxima_button_customize',
			width: 30,
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00390')+'"><i class="fa fa-refresh" style="font-size:13px;color:white"></i></span>',
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
			text: '<span style="position:relative;top:1px;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white"></i></span>',
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
			/*
			rowdblclick: function(self, index, e){
				var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
				if(check)
				{
					editCode(_text('MN00043'), '/pages/menu/config/code/edit.php');
				}
				else
				{
					Ext.Msg.alert( _text('MN00043'), _text('MSG01005'));//'수정', 먼저 대상을 선택 해 주시기 바랍니다.
				}
			}
			*/
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
					//header   : '코드유형', 
					header   : _text('MN02024'),
					sortable : true, 
					dataIndex: 'code_type'
				},{
					//header   : '코드유형 명', 
					header   : _text('MN02026'),
					sortable : true, 
					dataIndex: 'code_type_name'
				},{
					//header   : '코드', 
					header   : _text('MN02030'),
					sortable : true, 
					dataIndex: 'code'
				},{
					//header   : '코드 명', 한국어
					header   : _text('MN02032'),
					sortable : true, 
					dataIndex: 'name'
				},{
					//header   : '코드 명', 영어
					header   : _text('MN02032')+' (english)',
					sortable : true, 
					dataIndex: 'ename'
				},{
					// usable
					header   : _text('MN02205'),
					sortable : true, 
					dataIndex: 'use_yn'
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
	});

	var panel_line = new Ext.Panel({
		flex : 0.003,
		border : false,
		items : []
	});
    
    return {
    	xtype : 'panel',
    	title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN01009')+'</span></span>',
    	cls: 'grid_title_customize proxima_customize',
		layout : 'hbox',
		border : false,
		layoutConfig : {
			align : 'stretch'
		},
		items : [panel_type, panel_line, panel_code],
		listeners : {
			afterrender : function(self){
				
			}
		}
    };
})()
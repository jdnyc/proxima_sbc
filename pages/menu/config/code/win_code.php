<?php
$action = $_POST['action'];
$type = $_POST['type'];
$title = $_POST['title'];
$button = $_POST['button'];

?>

(function(){
	var selected = Ext.getCmp('grid_type').getSelectionModel().getSelected();
	var type_id = Ext.isEmpty(selected) ? '' : selected.data.id;
	var selected_code = Ext.getCmp('main_grid').getSelectionModel().getSelected();
	var code_id = Ext.isEmpty(selected_code) ? '' : selected_code.data.id;
	var win_type = new Ext.Window({
		id: 'code_type_add_win',
		//title: '코드유형 추가',
		title: _text('<?=$title?>'),
		width: 500,
		height: 150,
		modal: true,
		layout: 'fit',
		buttonAlign: 'center',
		items: [{
			id: 'form_type',
			cls: 'change_background_panel',
			xtype: 'form',
			url: '/pages/menu/config/code/<?=$action?>.php',
			frame: true,
			items:[{
				xtype: 'textfield',
				width: 350,
				name: 'code',
				allowBlank: false,
				//fieldLabel: '코드유형'
				fieldLabel: _text('MN02024')
			},{
				xtype: 'textfield',
				width: 350,
				allowBlank: false,
				name: 'name',
				//fieldLabel: '코드유형 명'
				fieldLabel: _text('MN02026')
			}]
		}],
		buttons: [{
				<?php if($button == 'MN00033'){ ?>
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
				<?php }else{ ?>
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
				<?php } ?>
				scale: 'medium',
				handler: function(){
					var form_type = Ext.getCmp('form_type').getForm();
					var infos = form_type.getValues();
					if( Ext.isEmpty(infos.code) || Ext.isEmpty(infos.name)){
						Ext.Msg.alert(_text('MN00024'), _text('MSG00125'));
						return;
					}else{
						Ext.Msg.show({
							title : _text('MN00024'),
							msg : _text('<?=$button?>')+' : '+_text('MSG02039'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'ok'){
									Ext.Ajax.request({
										url: '/pages/menu/config/code/<?=$action?>.php',
										params: {
											type : 'code_type',
											code_type_id : type_id,
											values : Ext.encode(infos)
										},
										callback: function(opt, success, response){
											try{
												var r = Ext.decode(response.responseText);
												if(r.add){
													Ext.getCmp('grid_type').getStore().reload();
													Ext.getCmp('main_grid').getStore().reload();
												}else{
													Ext.Msg.alert( _text('MN01039'), _text('MSG02002'));//코드 또는 코드명이 이미 등록되어 있습니다.
												}
											}catch (e){
												Ext.Msg.alert( _text('MN01039'), _text('MSG00024'));//오류
											}
										}
									});
									win_type.close();
								}
							}
						});
					}
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function(){
					win_type.close();
				}
			}],
		listeners : {
			afterrender : function(self){
				if( '<?=$action?>'  == 'edit' ){
					var selected_data = new Ext.data.Record(selected.data);
					self.get(0).getForm().loadRecord(selected_data);
				}
			}
		}
	});

	var win_code = new Ext.Window({
		id: 'code_add_win',
		//title: '코드 추가',
		title: _text('<?=$title?>'),
		width: 500,
		height: 400,
		modal: true,
		layout: 'fit',
		buttonAlign: 'center',
		items: [{
			id: 'code_add_form',
			cls: 'change_background_panel',
			xtype: 'form',
			url: '/pages/menu/config/code/<?=$action?>.php',
			frame: true,
			defaults : {
				anchor : '100%'
			},
			items:[ {
				xtype: 'combo',
				//fieldLabel: '코드유형 명',
				fieldLabel: _text('MN02026'),
				name: 'code_type_id',
				store: new Ext.data.JsonStore({
					url: '/pages/menu/config/code/get_combo.php',
					root: 'data',
					totalProperty: 'total',
					fields: ['code_type_id', 'code_type_name']
				}),
				hiddenName: 'code_type_id',
				allowBlank: false,
				valueField: 'code_type_id',
				displayField: 'code_type_name',
				typeAhead: true,
				triggerAction: 'all',
				editable: false
			},{
				xtype: 'textfield',
				allowBlank: false,
				name: 'code',
				//fieldLabel: '코드'
				fieldLabel: _text('MN02030')
			},{
				xtype: 'textfield',
				allowBlank: false,
				name: 'name',
				//fieldLabel: '코드 명'
				fieldLabel: _text('MN02032')
			},{
				xtype: 'textfield',
				name: 'ename',
				//fieldLabel: '코드 명'
				fieldLabel: _text('MN02032')+' (english)'
			},{
				xtype: 'checkbox',
				name: 'use_yn',
				fieldLabel: _text('MN02205')
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
			listeners : {
				afterrender : function(self){
					if(selected){
						self.getForm().findField('code_type_id').getStore().load({
							callback: function(s, r) {
								self.getForm().findField('code_type_id').setValue(type_id);
							}
						});
					}
				}
			}
		}],
		buttons: [{
				<?php if($button == 'MN00033'){ ?>
					text : '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033'),
				<?php }else{ ?>
					text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
				<?php } ?>
				scale: 'medium',
				handler: function(){
					var form_type = Ext.getCmp('code_add_form').getForm();
					var infos = form_type.getFieldValues();
					if( Ext.isEmpty(infos.code) || Ext.isEmpty(infos.name)){
						Ext.Msg.alert(_text('MN00024'), _text('MSG00125'));
						return;
					}else{
						Ext.Msg.show({
							title : _text('MN00024'),
							msg : _text('<?=$button?>')+' : '+_text('MSG02039'),
							buttons: Ext.Msg.OKCANCEL,
							fn: function(btnId, text, opts){
								if(btnId == 'ok'){
									Ext.Ajax.request({
										url: '/pages/menu/config/code/<?=$action?>.php',
										params: {
											type : 'code',
											code_type_id : type_id,
											code_id : code_id,
											values : Ext.encode(infos)
										},
										callback: function(opt, success, response){
											try{
												var r = Ext.decode(response.responseText);
												if(r.add){
													Ext.getCmp('main_grid').getStore().reload();
												}else{
													Ext.Msg.alert( _text('MN01039'), _text('MSG02002'));//코드 또는 코드명이 이미 등록되어 있습니다.
												}
											}catch (e){
												Ext.Msg.alert( _text('MN01039'), _text('MSG00024'));//오류
											}
										}
									});
									win_code.close();
								}
							}
						});
					}
				}
			},{
				text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
				scale: 'medium',
				handler: function(){
					Ext.getCmp('code_add_win').close();
				}
			}],
		listeners : {
			afterrender : function(self){
				if( '<?=$action?>'  == 'edit' ){
					var selected_data = new Ext.data.Record(selected_code.data);
					var use_yn = selected_data.data.use_yn == 'Y' ? 'on' : 'off';
					self.get(0).getForm().loadRecord(selected_data);
					self.get(0).getForm().findField('use_yn').setValue(use_yn);
				}
			}
		}
	});

	if('<?=$type?>' == 'type'){
		return win_type;
	}else{
		return win_code;
	}
})()
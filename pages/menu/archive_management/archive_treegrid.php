<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/lib/DB.class.php");
$user_id = $_SESSION['user']['user_id'];
?>
(function(){

	function setup_win(){  
		var win = new Ext.Window({
					width : 400,
					height : 160,
					layout : 'anchor',
					modal : true,
					title : '설정',

					items : [{
						itemId : 'setup_form',
						xtype : 'form',
						monitorValid : true,
						border : false,
						padding : '5',
						freame : true,

						items : [{
								xtype: 'hidden',
								itemId:'c_type',
								name:'type'
						},{
								xtype: 'hidden',
								itemId:'c_category_id',
								name: 'category_id'
						},{
								xtype: 'textfield',
								itemId: 'c_category_title',
								name : 'category_title',
								anchor: '95%',
								fieldLabel: '카테고리',
								labelWidth : 70,
								disabled : true
						},{
								xtype:'combo',
								itemId: 'c_method',
								name : 'archive_method',
								anchor : '95%',
								mode: 'local',
								value: 'A',
								forceSelection: true,
								editable: false,
								allowBlank: false,
								fieldLabel: '방법',
								labelWidth : 70,
								displayField: 'method_nm',
								valueField: 'method_val',
								hiddenName: 'archive_method',
								triggerAction : 'all',					 
								store: new Ext.data.ArrayStore({
									fields : ['method_nm', 'method_val'],
									data:[
										['자동', 'A'],['수동', 'M']
									]
								}),
								listeners : {
									select : function(combo, record, index) {
										var combo_val = combo.getValue();
										var form		= win.items.items[0].getForm();
										if(combo_val == 'M' || combo_val == 'N') {
											form.findField("s_period").setDisabled(true);
											form.findField("e_period").setDisabled(true);
										} else if(combo_val == 'A') {
											form.findField("s_period").setDisabled(false);
											form.findField("e_period").setDisabled(false);
										}
									}
								}
						},{
							xtype: 'compositefield',
							flex: 1,
							fieldLabel: '요청승인시간',
							items: [{
								xtype : 'timefield',
								name : 's_period',
								editable: false,
								width: 100,
								format:'H:i',
								minValue: '00:00',
								maxValue: '23:00',
								increment: 60,
								allowBlank : false
							},{
								xtype : 'timefield',
								name : 'e_period',
								editable: false,
								width: 100,
								format:'H:i',
								minValue: '00:00',
								maxValue: '23:00',
								increment: 60,
								allowBlank : false,
								listeners: {
									select : function( combo, record, index ) {
										var form	   = win.items.items[0].getForm();
										var s_period_v = form.findField("s_period").getValue();
									}
								}
							}]
						}]
					}],
					buttons : [{
						scale : 'medium',
						text : '설정',
						handler : function(){
							var form = win.items.items[0].getForm();
							var archive_method = form.findField("archive_method").getValue();
							
							if(archive_method =='A') {
								var s_period_v = form.findField("s_period").getValue();	
								var e_period_v = form.findField("e_period").getValue();
		
								if(Ext.isEmpty(s_period_v) || Ext.isEmpty(e_period_v)) {
									Ext.Msg.alert('알림','승인 시작시간 또는 승인 종료시간이 비어있습니다.');
									form.isValid();
									return;
								}
							}	
							
							win.getComponent('setup_form').getForm().submit({
								url : '/pages/menu/archive_management/set_up_archive_config.php',
								params : {
									user_id : '<?=$user_id?>',
									mode : 'archive'
								},
								success : function(form, action) {
									Ext.Msg.alert('수정완료', action.result.msg);
									win.destroy();
									var parent_node = treegrid.getSelectionModel().getSelectedNode().parentNode;
									var tree_loader = treegrid.getLoader();
									tree_loader.load(parent_node);
								},
								failure: function(form, action) {
									switch (action.failureType) {
										case Ext.form.Action.CLIENT_INVALID:
											Ext.Msg.alert('Failure', 'Form fields may not be submitted with invalid values');
										break;
										case Ext.form.Action.CONNECT_FAILURE:
											Ext.Msg.alert('Failure', 'Ajax communication failed');
										break;
										case Ext.form.Action.SERVER_INVALID:
											Ext.Msg.alert('Failure', action.result.msg);
										break;
									}
								}
							})
						}
					},{
						itemId: 'cancel',
						scale: 'medium',
						text: '취소',
						handler : function(btn, e){
							win.destroy();
						}
					}]
			});

			return win;
	}
	
	function showEmpty(value){
		
		if(Ext.isEmpty(value)) value = '-';
		return value;
	}
	
	var treegrid = new Ext.ux.tree.TreeGrid({
		itemId : 'setup_grid',
		layout : 'fit',
		enableSort : false,
		enableHdMenu: false,
		
		columns : [
			{header : '장르', dataIndex : 'category_title', width:200},
			{header : '요청승인 방법', dataIndex : 'archive_method', width : 90, align: 'center', hidden: true},
			{header : '요청승인 방법', dataIndex : 'archive_method_nm', width : 90, align: 'center'},
			{header : '요청승인 시작시간', dataIndex : 'archive_auth_start_time', width: 110, align: 'center',renderer: showEmpty},
			{header : '요청승인 종료시간', dataIndex : 'archive_auth_end_time', width: 110, align: 'center', renderer: showEmpty}
		],
		sm: new Ext.grid.RowSelectionModel({
			singleSelect : true
		}),
		tbar : [{
			xtype : 'button',
			text : '요청승인 설정',
			icon : '/led-icons/accept.png',
			handler : function(){
				var sel = treegrid.getSelectionModel().getSelectedNode();
				if(sel) {
					var win = setup_win();
					win.show();

					var win_form = win.items.get(0);

					var c_id = sel.attributes.id;
					var c_title = sel.attributes.category_title;
					var archive_method = sel.attributes.archive_method;
					var acpt_period = sel.attributes.acpt_period;
					
					var archive_auth_start_time = sel.attributes.archive_auth_start_time;
					var archive_auth_end_time   = sel.attributes.archive_auth_end_time;

					win_form.getComponent('c_type').setRawValue('accept');
					win_form.getComponent('c_category_id').setRawValue(c_id);
					win_form.getComponent('c_category_title').setValue(c_title);
					win_form.getComponent('c_method').setValue(acpt_method);
					
					var form = win.items.items[0].getForm();
					
					if(archive_method == 'M') {
						form.findField("s_period").setDisabled(true);
						form.findField("e_period").setDisabled(true);
					} else {
						form.findField("s_period").setDisabled(false);
						form.findField("e_period").setDisabled(false);
						form.findField("s_period").setValue(archvie_auth_start_time);
						form.findField("e_period").setValue(archive_auth_end_time);
					}
				} else {
					Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
				}
			}
		},'-',{
			xtype : 'button',
			text : 'Tape삭제 설정',
			hidden: true,
			icon : '/led-icons/drive_burn.png',
			handler : function(){
				var sel = treegrid.getSelectionModel().getSelectedNode();
				if(sel) {
					var win = setup_win();
					win.show();

					var win_form = win.items.get(0);

					var c_id = sel.attributes.id;
					var c_title = sel.attributes.category_title;
					var del_method = sel.attributes.del_method;
					var del_period = sel.attributes.del_period;

					win_form.getComponent('c_type').setRawValue('delete');
					win_form.getComponent('c_category_id').setRawValue(c_id);
					win_form.getComponent('c_category_title').setValue(c_title);
					win_form.getComponent('c_method').setValue(del_method);
					win_form.getComponent('c_period').setValue(del_period);
				} else {
					Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
				}
			}
		}],
		dataUrl : '/pages/menu/archive_management/get_tree_grid_archive_data.php'
	});

	
	return treegrid;
})()
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');

$arr_info_msg = getStoragePolicyInfo();
$info_msg = $arr_info_msg['info1_1'];
$info2_msg = $arr_info_msg['info2_1'];

?>		  

(function(){
	var storage_win = new Ext.Window({
		title: 'Archive스토리지 정보',
		width: 300,
		height: 370,
		modal: true,
		layout: 'fit',
		items: [{
			xtype: 'form',
			frame: true,
			padding: 10,
			items: [{
				xtype: 'displayfield',
				hideLabel: true,
				value: '<?=$info_msg?>'
			},{
				xtype: 'textfield',
				fieldLabel: '허용치 수정'
			},{
				xtype: 'displayfield',
				hideLabel: true,
				value: '(수정가능 범위 : 70% ~ 90%)'
			},{
				xtype: 'displayfield',
				hideLabel: true,
				value: '<?='<br /><br />'.$info2_msg?>'
			},{
				xtype: 'textfield',
				fieldLabel: '허용치 수정'
			},{
				xtype: 'displayfield',
				hideLabel: true,
				value: '(수정가능 범위 : 70% ~ 90%)'
			},{
				xtype: 'textfield',
				fieldLabel: '최대용량 수정'
			},{
				xtype: 'displayfield',
				hideLabel: true,
				value: '(ex: 5, 7.5, 20, ...)'
			}]
		}],
		buttons:[{
			text: '수정',
			icon: '/led-icons/application_edit.png',
			handler: function(b, e){
				var limit = storage_win.get(0).items.get(1).getValue();
				var limit2 = storage_win.get(0).items.get(4).getValue();
				var cache_max = storage_win.get(0).items.get(6).getValue();
				Ext.Msg.show({
					title: '확인',
					msg: '허용치를 수정합니다.',
					icon: Ext.Msg.INFO,
					buttons: Ext.Msg.OKCANCEL,
					fn: function(btnId){
						if (btnId == 'ok')
						{
							Ext.Ajax.request({
								url: '/pages/menu/archive_management/archive_storage_edit.php',
								params: {
									limit: limit,
									limit2: limit2,
									cache_max: cache_max
								},
								callback: function(opt, success, response){
									var res = Ext.decode(response.responseText);
									if(res.success)
									{
										Ext.Msg.alert('알림', '반영되었습니다.');
										if(!Ext.isEmpty(Ext.getCmp('delete_inform_id'))) {
											Ext.getCmp('delete_inform_id').getStore().reload();
										}
										if(!Ext.isEmpty(Ext.getCmp('archive_request_grid_id'))) {
											Ext.getCmp('archive_request_grid_id').getStore().reload();
										}
										storage_win.close();
									}
									else
									{
										Ext.Msg.alert('알림', res.msg);
									}
								}
							});
						}
					}
				});
			}
		},{
			text: '닫기',
			icon: '/led-icons/cross.png',
			handler: function(b, e){
				storage_win.close();
			}
		}]
	});
	storage_win.show();
})()
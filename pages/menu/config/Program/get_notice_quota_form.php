<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');

$quota = $db->queryOne("select c.code from bc_code c, bc_code_type ct where c.code_type_id = ct.id and ct.code = 'QUOTA_LIMIT'");
?>
(function(){
	var f = new Ext.Window({
		id: 'set_notice_quota_win',
		width: 280,
		height: 120,
		modal: true,
		layout: 'fit',
		title: '쿼터 알림값 설정',

		items: [{
			xtype: 'form',
			id: 'set_notice_quota_form',
			border: false,
			frame: true,
			padding: '5px',
			layout: {
			    type: 'hbox',
			    align: 'middle'
			},
			labelSeparator: '',

			items: [{
				xtype: 'displayfield',
				value: '쿼터 사용가능량이 ',
				margins: '-3 0 0 0',
				flex: 1
			},{
				xtype: 'textfield',
				name: 'quota',
				width: 40,
				value: '<?=$quota?>'
			},{
				xtype: 'displayfield',
				margins: '-3 0 0 5',
				value: ' % 미만일 경우 알림',
				flex: 1
			}]
		}],
		buttonAlign: 'center',
		buttons: [{
			text: '설정',
			handler: function(self, e){

				Ext.getCmp('set_notice_quota_form').getForm().submit({
                                            url: '/pages/menu/config/Program/php/set_notice_quota.php',
                                            success: function(form, action) {
                                                    try {
                                                            var result = Ext.decode(action.response.responseText, true);
                                                            if(result.success) {
                                                                    Ext.getCmp('set_notice_quota_win').close();
                                                                    Ext.Msg.alert( _text('MN00023'), result.msg);
                                                            }else{
                                                                    Ext.Msg.show({
                                                                            title: '에러',
                                                                            icon: Ext.Msg.ERROR,
                                                                            msg: result.msg,
                                                                            buttons: Ext.Msg.OK
                                                                    })
                                                            }
                                                    }catch(e){
                                                            Ext.Msg.show({
                                                                    title: '에러',
                                                                    icon: Ext.Msg.ERROR,
                                                                    msg: e.message,
                                                                    buttons: Ext.Msg.OK
                                                            })
                                                    }
                                            },
                                            failure: function(form, action) {
                                                    Ext.Msg.show({
                                                            icon: Ext.Msg.ERROR,
                                                            title: '에러',
                                                            msg: action.result.msg,
                                                            buttons: Ext.Msg.OK
                                                    });
                                            }
                                    });
			}
		},{
			text: '취소',
			handler: function(btn, e){
				Ext.getCmp('set_notice_quota_win').close();
			}
		}]
	});

	return f;
})()
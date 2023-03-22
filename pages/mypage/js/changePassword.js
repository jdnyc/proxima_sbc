function buildFormChangePassword(user_id){
	var change_password = new Ext.Window({
		labelWidth: 120,
		title: '비밀번호 변경',
		modal: true,
		bodyStyle:'padding:5px 5px 0',
		width: 350,
		defaults: {
			width: 330,
			anchor: '100%'
		},
		defaultType: 'textfield',
		items: [{
			id: 'change_password_form',
			xtype: 'form',
			border: false,
			layout: 'form',
			frame: true,
			padding: '5',
			labelWidth: 100,
			items: [{
				id: 'user_password',
				name: 'user_password',
				xtype: 'textfield',
				inputType : 'password',
				fieldLabel: '새로운 비밀번호'
			},{
				id: 'user_password2',
				name: 'user_password2',
				xtype: 'textfield',
				inputType : 'password',
				fieldLabel: '비밀번호 확인'
			}],
			buttons: [{
				id:'ok',
				scale: 'medium',
				text: '변경',
				handler: function() {
					var user_password=Ext.getCmp('user_password').getValue();
					var user_password2=Ext.getCmp('user_password2').getValue();
					if(user_password == ''){
						Ext.Msg.alert('확인','비밀번호를 입력해 주세요.');
					}else if(user_password2 == ''){
						Ext.Msg.alert('확인','비밀번호 확인을 입력해 주세요.');
					}else if(user_password != user_password2){
						Ext.Msg.alert('확인','비밀번호 확인을 다시 입력해 주세요.');
					}else{
						Ext.Ajax.request({
							url: '/php/change_password.php',
							params: {
								user_id: user_id,
								user_password: user_password
							},
							callback: function(options, success, response){
								if (success)
								{
									try
									{
										var r = Ext.decode(response.responseText);
										if (r.success)
										{
											Ext.Msg.show({
												title: '확인',
												msg: r.msg,
												buttons: Ext.Msg.OK
											});
											change_password.destroy();
										}
										else
										{
										}
									}
									catch (e)
									{
										Ext.Msg.alert(e['name'], e['message']);
									}
								}
								else
								{
									Ext.Msg.alert('확인', response.statusText);
								}
							}
						});
					}
				}

			},{
				id:'cancel',
				scale: 'medium',
				text: '취소',
				handler: function(){
					change_password.destroy();
				}
			}]
		}]
	});
	change_password.show();
}

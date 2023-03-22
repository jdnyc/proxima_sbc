function requestAction(type, msg, data, callback) {
	Ext.Msg.show({
		msg: msg,
		buttons: Ext.Msg.OKCANCEL,
		fn: function (btnId, text) {
			var wait = new Ext.LoadMask(Ext.getBody(), {msg: 'Loading...'});
			wait.show();
			if (btnId === 'ok') {

				Ext.Ajax.request({
					url: '/store/request.php',
					timeout: 300000,
					params: {
						type: type
					},
					jsonData: data,
					callback: function (opts, success, response) {
						wait.hide();
						if (success) {
							try {
								var result = Ext.decode(response.responseText);
							}
							catch (e) {
								Ext.Msg.alert('Information', '서버 응답 오류');
								return;
							}

							Ext.Msg.alert('Information', result.msg, callback);				
						}
						else {
							Ext.Msg.alert('Information', '통신 오류', callback);
						}
					}
				});
			}
                        else
                        {
                            wait.hide();
                        }
		}
	});
}
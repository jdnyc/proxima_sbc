(function(){

	return new Ext.Panel({
		layout: 'fit',
		html: '',
		padding: 5,
		autoScroll: true,
		listeners: {
			render: function(self){
				self.refresh_data(self);
			}
		},
		refresh_data: function(self) {
			self.el.mask();
			Ext.Ajax.request({
				url: '/store/get_sgl_log_data.php',
				params: {
					content_id: '<?=$_POST['content_id']?>'
				},
				callback: function(opt, success, response){
					self.el.unmask();
					var res = Ext.decode(response.responseText);
					if(res.success) {
						self.update(res.msg);
					}
				}
			});
		}
	});

})()
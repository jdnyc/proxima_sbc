(function(){

	var win_form = new Ext.Window({
		layout: 'fit',

		items: [
		]
	});

	return {
		xtype: 'tabpanel',
		activeTab: 0,

		items: [{
			title: 'High Transcoder',
			xtype: 'editorgrid',
			store: new Ext.data.ArrayStore({
				fields: [
					'video_codec', 'carbon_guid', 'extension'
				],
				data: [
					['dvcpro', '{jiemdkl}', 'mxf'],
					['mpeg2video', '{jiemdkl}', 'mxf']
				]
			}),
			columns: [
				{header: '비디오 코덱', dataIndex: 'video_codec', editor: new Ext.form.TextField({})},
				{header: 'GUID', dataIndex: 'carbon_guid'},
				{header: '확장자', dataIndex: 'extension'}
			],
			listeners: {
				afteredit: function(e){
				}
			}
		}]
	};
})()
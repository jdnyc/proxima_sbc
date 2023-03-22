Ext.ns('Ext.ux');

Ext.ux.ContentListWin = Ext.extend(Ext.Window, {
	title: '콘텐츠 유형을 선택해 주세요',
	modal: true,
	width: 350,
	height: 200,
	layout: 'fit',

	constructor: function(config) {
		Ext.apply(this, config || {});

		Ext.ux.ContentListWin.superclass.constructor.call(this);
	},

	initComponent: function (config) {
		var thisRef = this;

		this.items = this.buildContentList(thisRef);

		Ext.ux.ContentListWin.superclass.initComponent.call(this);
	},

	buildContentList: function (thisRef) {
		return {
			xtype: 'grid',
			loadMask: true,
			enableDD: false,
			store: new Ext.data.JsonStore({
				url: 'php/getContentTypeList.php',
				autoLoad: true,
				root: 'data',
				fields: [
					'content_type_id',
					'content_type_name'
				]
			}),
			listeners: {
				rowdblclick: function(self){
					var sm = self.getSelectionModel();
					
					if (!sm.hasSelection())
					{
						Ext.Msg.alert('확인', '선택해주세요.');
						return;
					}							
					that.detailview( that, content_id, sm.getSelected().get('content_type_id') );
					thisRef.close();
				}
			},

			columns: [
				{header: '콘텐츠 유형',	dataIndex: 'content_type_name',align: 'center', sortable: true }
			],

			buttons: [{
				text: '선택',
				handler: function(b, e){
					var sm = b.ownerCt.ownerCt.getSelectionModel();
					
					if (!sm.hasSelection()) {
						Ext.Msg.alert('확인', '선택해주세요.');
						return;
					}
					var filename = thisRef.selectFile.substr(thisRef.selectFile.lastIndexOf('/')+1);

					var form_win = new Ext.Window({
						title: '콘텐츠 등록',
						width: 400,
						height: 300,
						modal: true,
						layout: 'fit',

						items: new Ext.ux.MetadataLoader({
							url: 'php/add_content.php',
							storage_id: thisRef.storage_id,
							selectFile: thisRef.selectFile,
							contentTypeID: sm.getSelected().get('content_type_id'),
							filename: filename
						}),
								
						buttons: [{
							text: '등록',
							handler: function (self, e) {
								form_win.get(0).get(0).getForm().submit({
									success: function (form, action) {
										form_win.close();
									},
									failure: function (form, action) {
									}
								});
							}
						},{
							text: '닫기',
							handler: function (self, e) {
								form_win.close();
							}
						}]
					}).show();

					thisRef.close();												
				}
			},{
				text: '닫기',
				handler: function(b, e){
					b.ownerCt.ownerCt.ownerCt.close();
				}
			}],
			viewConfig: {
				forceFit: true
			}
		};
	}
});

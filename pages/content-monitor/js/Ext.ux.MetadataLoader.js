Ext.ns('Ext.ux');

Ext.ux.MetadataLoader = Ext.extend(Ext.TabPanel, {
	border: false,
	padding: 5,
	contentTypeID: false,

	constructor: function (config) {
		Ext.apply(this, config || {});

		Ext.ux.MetadataLoader.superclass.constructor.call(this);
	},

	initComponent: function () {
		Ext.ux.MetadataLoader.superclass.initComponent.call(this);

		var loader = new Ext.data.JsonStore({
			url: 'php/getContentFieldList.php',
			root: 'data',
			fields: [
				'id',
				'type',
				'name',
				'required',
				'default_value'
			]
		});
		loader.on('load', this.buildTabs, this);

		loader.load({
			params: {
				content_type_id: this.contentTypeID
			}
		});

		this.on('tabchange', this.loadForm, this);
	},

	buildTabs: function (self, records) {
		var form = new Ext.form.FormPanel({
			title: '기본정보',
			autoScroll: true,
			url: this.url,
			defaults: {
				anchor: '100%',
				msgTarget: 'under'
			},

			items: [{
				xtype: 'hidden',
				name: 'fields'
			},{
				xtype: 'hidden',
				name: 'content_type_id',
				value: this.contentTypeID
			},{
				xtype: 'hidden',
				name: 'storage_id',
				value: this.storage_id
			},{
				xtype: 'hidden',
				name: 'file',
				value: this.selectFile
			},{
				xtype: 'treecombo',
				name: 'category_id',
				fieldLabel: '카테고리',
				allowBlank: false,
				rootVisible: false,
				loader: new Ext.tree.TreeLoader({
					url: 'php/getCategory.php',
					listeners: {
						beforeload: {
							fn: function(self, node){
								self.baseParams = {
									content_type_id: this.contentTypeID
								}
							},
							scope: this
						}
					}
				}),
				root: new Ext.tree.AsyncTreeNode({
					id: 'root',
					text: 'root'					
				})
			},{
				xtype: 'textfield',
				name: 'title',
				fieldLabel: '제목',
				allowBlank: false,
				value: this.filename
			}],

			listeners: {
				render: function (form) {
					form.getForm().on('beforeaction', function (baseform, action) {
						if (baseform.isValid()) {
							var fields = [];
							baseform.items.each(function (field) {
								// 동적 필드 체크
								if (/^f\_/.test(field.name)) {
									// 체크박스 체크
									if (field.xtype == 'checkbox') {
										fields.push({
											id: field.id,
											type: field.xtype,
											value: field.checked
										});
									}
									else {
										fields.push({
											id: field.id,
											type: field.xtype,
											value: field.el.dom.value
										});
									}
								}
								else if (field.name == 'category_id') {
									field.el.dom.value = field.value;
								}
							});
							baseform.items.get(0).el.dom.value = Ext.encode(fields);
						}
					});
				}
			}
		});

		// form field 생성
		Ext.each(records, function (record) {
			//console.log(record.get('type'));
			if (record.get('type') == 'container' 
					|| record.get('type') == 'listview'
					|| /^gs/.test(record.get('type'))) {
				return;
			}

			form.add({
				xtype: record.get('type'),
				id: record.get('id'),
				name: 'f_' + record.get('id'),
				fieldLabel: record.get('name'),
				msgTarget: 'under'
			});
		}, this);

		form.doLayout();

		this.add(form);
		this.doLayout();
		this.setActiveTab(0);
	},

	loadForm: function (self, tab) {
	}
});
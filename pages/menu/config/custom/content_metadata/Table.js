Ext.namespace('Ariel.menu.config.custom.metadata');

Ariel.menu.config.custom.metadata.Table = Ext.extend(Ext.grid.GridPanel, {
	id: 'media_type',
	title: '미디어 종류',
	region: 'center',
	loadMask: true,
	store: new Ext.data.JsonStore({
		url: '/pages/menu/config/custom/metadata/php/get.php',
		root: 'data',
		idPropery: 'srl',
		fields: [
			'srl',
			'idx',
			'bs_content_title',
			'allowed_extension',
			'description'
		],
		listeners: {
			exception: function(self, type, action, options, response, arg){
				if(type == 'response') {
					if(response.status == '200') {
						Ext.Msg.alert('오류', response.responseText);
					}else{
						Ext.Msg.alert('오류', response.status);
					}
				}else{
					Ext.Msg.alert('오류', type);
				}
			}
		}
	}),
	colModel: new Ext.grid.ColumnModel({
		defaults: {
			align: 'center'
		},
		columns: [
			{header: '미디어 종류', dataIndex: 'bs_content_title'},
			{header: '허용 확장자', dataIndex: 'allowed_extension'},
			{header: '설명', dataIndex: 'description'}
		]
	}),

	listeners: {
		viewready: function(self){
			self.store.load({
				params: {
					action: 'custom_table'
				}
			});
		},
		rowdbclick: function(self, rIdx, e) {
			
		}
	},

	viewConfig: {
		forceFit: true,
		emptyText: 'no data'
	},

	tbar: [{
		text: 'up'
	},{
		text: 'down'
	}],

	buttonAlign: 'center',
	fbar: [{
		text: '추가',
		scale: 'medium',
		handler: function() {
			this.buildAddTable();
		},
		scope: this
	},{
		text: '수정',
		scale: 'medium',
		handler: function() {
			var hasSelection = Ext.getCmp('media_type').getSelectionModel().hasSelection();
			if(hasSelection) {
				this.buildEditTableWin();
			}else{
				Ext.Msg.alert('오류', '수정하실 행을 선택해주세요');
			}
		},
		scope: this
	},{
		text: '삭제',
		scale: 'medium',
		handler: function() {
			var hasSelection = Ext.getCmp('media_type').getSelectionModel().hasSelection();
			if(hasSelection) {
				this.buildDeleteTable();
			}else{
				Ext.Msg.alert('오류', '삭제하실 행을 선택해주세요');
			}
		},
		scope: this

	}],

	initComponent: function(config){
		Ext.apply(this, config | {});

		Ariel.menu.config.custom.metadata.Table.superclass.initComponent.call(this);
	}
});

(function(){
	Ext.ns('Ariel.WorkFlowPanel');

	Ariel.WorkFlowPanel = Ext.extend(Ext.Panel, {
		layout: 'border',
		border: false,

		initComponent: function(config){
			Ext.apply(this, config || {});

			Ext.apply(this, {
				items: [{
					region: 'center',
					xtype: 'grid',

					listeners: {
						viewready: function(self){
							self.getSelectionModel().selectRow(0);
						}
					},
					store: new Ext.data.ArrayStore({
						fields: [
							'title', 'method'
						],
						data: [
							['사용자 등록', 'filer'],
							['자동 등록', 'watchfolder']
						]
					}),
					columns: [
						{header: '작업명', dataIndex: 'title'},
						{header: '등록 방법', dataIndex: 'method'}
					],
					selModel: new Ext.grid.RowSelectionModel({
						singleSelect: true,
						listeners: {
							rowselect: function(self, rowIndex, record){
								Ext.getCmp('workflow_chart').update('<img src="/img/'+record.get('method')+'.jpg" />');								
							}
						}
					})
				},{
					id: 'workflow_chart',
					xtype: 'box',
					region: 'east',
					width: 300,
					split: true
				}]
			});

			Ariel.WorkFlowPanel.superclass.initComponent.call(this);
		}		
	});

	return new Ariel.WorkFlowPanel();
})()
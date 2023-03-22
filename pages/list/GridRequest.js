Ext.ns('Ariel');

Ariel.GridRequest = Ext.extend(Ext.grid.GridPanel, {
	id: 'request',
	title: '등록대기',
	loadMask: true,
	viewConfig: {
		//emptyText: '등록된 작업흐름이 없습니다.'
	},

	initComponent: function(){
		this.store = new Ext.data.JsonStore({
			url: '/store/get-workflow-list.php',
			baseParams: {
				start: 0,
				limit: 20,
				task_type: '10'
			},							
			fields: [
				'id',
				'title',
				'status',
				'type',
				'progress',
				{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
				{name: 'complate_datetime', type: 'date', dateFormat: 'YmdHis'},
				{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'}							
			]
		});

		this.cm = new Ext.grid.ColumnModel({
			columns: [
				{header: '제목', dataIndex: 'title'},
				{header: '상태', dataIndex: 'status'},
				{header: '작업 시작 일시', dataIndex: 'start_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
				{header: '작업 종료 일시', dataIndex: 'complate_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
				{header: '등록 일시', dataIndex: 'creation_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
			]
		});

		this.bbar = new Ext.PagingToolbar({
			store: this.store,
			displayInfo: true,
			pageSize: 20							
		});

		Ariel.GridRequest.superclass.initComponent.call(this);
	}
});
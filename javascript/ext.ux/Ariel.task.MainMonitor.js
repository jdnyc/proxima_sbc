Ext.ns('Ariel.task');

Ariel.task.Monitor = Ext.extend(Ext.grid.GridPanel, {

	columns: [
		{header: 'id', dataIndex: 'task_id', width: 40, hidden: true},
		{header: '작업명', dataIndex: 'rule_name', width: 150, hidden: true},
		{header: '상태', dataIndex: 'status', width: 70},
		new Ext.ux.ProgressColumn({
			header: '진행률',
			width: 105,
			dataIndex: 'progress',
			align: 'center',
			renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
				return Ext.util.Format.number(pct, "0%");
			}
		}),		
		{header: '제목', dataIndex: 'title', width: 200},
		{header: 'source', dataIndex: 'source', width: 350, hidden: true},
		{header: 'target', dataIndex: 'target', width: 230},
		{header: '등록일시', dataIndex: 'creation_datetime', xtype: 'datecolumn', format: 'Y-m-d H:i:s', align: 'center', width: 130},
		{header: '시작일시', dataIndex: 'start_datetime', xtype: 'datecolumn', format: 'Y-m-d H:i:s', align: 'center', width: 130},
		{header: '종료일시', dataIndex: 'complete_datetime', xtype: 'datecolumn', format: 'Y-m-d H:i:s', align: 'center', width: 130},
	],

	initComponent: function(config) {
		var _this = this;

		_this.store = new Ext.data.JsonStore({
			url: '/store/task.php',
			fields: [
				'source', 'target', 'status', 'title', 'rule_name',
				{name: 'task_id', type: 'int'},
				{name: 'progress', type: 'int'},
				{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHid'},
				{name: 'start_datetime', type: 'date', dateFormat: 'YmdHid'},
				{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHid'}
			],
			root: 'data',
			totalProperty: 'total'
		});

		Ext.apply(this, config || {}, {
			tbar: [{
				icon: '/led-icons/arrow_refresh.png',
				text: '새로고침',
				handler: _this._reload,
				scope: _this
			}],

			bbar: {
				xtype: 'paging',
				pageSize: 20,
				displayInfo: true,
				store: _this.store
			}
		});

		Ariel.task.Monitor.superclass.initComponent.call(this);

		_this.on('show', _this._show);
		_this.on('hide', _this._hide);
	},

	_show: function() {
		var _this = this;

		if ( ! _this.task) {
			_this.task = {
			    run: function(){
			        _this.getStore().reload();
			    },
			    interval: 5000
			};
		}
		Ext.TaskMgr.start(this.task);
	},

	_hide: function() {
		var _this = this;

		if (_this.task) {
			Ext.TaskMgr.stop(this.task);
		}
	},

	_reload: function() {
		this.getStore().reload();
	}
});

Ext.reg('taskmonitor', Ariel.task.Monitor);

Ariel.task.MonitorTab = Ext.extend(Ext.TabPanel, {
	activeTab: 0,
	items: [{
		title: 'A부조',
		// xtype: 'taskmonitor'
	}, {
		title: 'B부조',
		// xtype: 'taskmonitor'
	}]
});
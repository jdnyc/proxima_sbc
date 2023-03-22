Ext.ns('Ariel.panel.archive');
(function() {

    Ariel.panel.archive.Result = Ext.extend(Ext.grid.GridPanel, {

        initComponent: function(config) {
            var _this = this;

            var store = new Ext.data.JsonStore({
                url: '/store/archive_list.php',
                fields: [
                    'title', 'comments', 'reject_comments', 'category_full_path',
                    'request_type', 'status', 'task_status',
                    {name:  'created', type: 'date', dateFormat: 'YmdHis'}
                ],
                root: 'data'
            });

            Ext.apply(this, {
                columns: [
                    {header: '의뢰구분', dataIndex: 'request_type', width: 70, align: 'center'},
                    {header: '의뢰상태', dataIndex: 'task_status', width: 50, align: 'center'},
                    {header: '카테고리', dataIndex: 'category_full_path', width: 200},
                    {header: '의뢰일시', dataIndex: 'created', width: 150, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'},
                    {header: '제목', dataIndex: 'title', width: 200},
                    {header: '의뢰내용', dataIndex: 'comments', width: 200},
                    {header: '반려사유', dataIndex: 'reject_comments', width: 200}
                ],
                loadMask: true,
                store: store,
                bbar: {
                    xtype: 'paging',
                    pageSize: 10,
                    store: store
                }
            }, config || {});

            Ariel.panel.archive.Result.superclass.initComponent.call(this);

            _this.on('rowclick', _this.onDrag, _this);

            store.load();
        },

        onDrag: function() {
        }
    });

    Ext.reg('panelarchiveresult', Ariel.panel.archive.Result);
})();

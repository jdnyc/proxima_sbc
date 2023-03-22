(function() {
    Ext.ns('Ariel.Panel');

    Ariel.Panel.Archive = Ext.extend(Ext.grid.GridPanel, {

        initComponent: function(config) {
            var _this = this;

            var store = new Ext.data.JsonStore({
                url: '/store/archive_list.php',
                fields: [
                    'title', 'status', 'comments', 'reject_comments', 'category_full_path',
                    {name:  'created', type: 'date', dateFormat: 'YmdHis'}
                ],
                root: 'data'
            });

            Ext.apply(this, {
                columns: [
                    {header: '제목', dataIndex: 'title', width: 200},
                    {header: '상태', dataIndex: 'status', width: 50},
                    {header: '의뢰내용', dataIndex: 'comments', width: 200},
                    {header: '반려사유', dataIndex: 'reject_comments', width: 200},
                    {header: '카테고리', dataIndex: 'category_full_path', width: 200},
                    {header: '등록일', dataIndex: 'created', width: 150, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
                ],
                loadMask: true,
                store: store,
                bbar: {
                    xtype: 'paging',
                    pageSize: 15,
                    store: store
                }
            }, config || {});

            Ariel.Panel.Archive.superclass.initComponent.call(this);

            store.load();
        }
    });

    Ext.reg('panelarchive', Ariel.Panel.Archive);
})();

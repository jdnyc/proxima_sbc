Ext.ns('Ariel.panel.review');
(function() {

    Ariel.panel.review.Result = Ext.extend(Ext.grid.GridPanel, {

        initComponent: function(config) {
            var _this = this;

            var store = new Ext.data.JsonStore({
                url: '/store/review/list.php',
                fields: [
                    'ID', 'CONTENT_ID', 'TITLE', 'STATE', 'COMMENTS',
                    {name:  'CREATED', type: 'date', dateFormat: 'YmdHis'},
                    {name: 'REQUESTER', convert: function(v, record) {
                        if (record.REQUESTER) {
                            return record.REQUESTER + '(' + record.REQUESTER_NAME + ')';
                        } else {
                            return '';
                        }
                    }},
                    {name: 'ACCEPTER', convert: function(v, record) {
                        if (record.ACCEPTER) {
                            return record.ACCEPTER + '(' + record.ACCEPTER_NAME + ')';
                        } else {
                            return '';
                        }
                    }}
                ],
                root: 'data',
                baseParams: {
                    state: 'result'
                }
            });

            Ext.apply(this, {
                columns: [
                    {header: '제목', dataIndex: 'TITLE', width: 200},
                    {header: '의뢰상태', dataIndex: 'STATE', width: 80, align: 'center'},
                    {header: '사유', dataIndex: 'COMMENTS', width: 200},
                    {header: '의뢰자', dataIndex: 'REQUESTER', width: 150},
                    {header: '승인자', dataIndex: 'ACCEPTER', width: 150, hidden: true},
                    {header: '의뢰일시', dataIndex: 'CREATED', width: 150, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
                ],
                loadMask: true,
                store: store,
                bbar: {
                    xtype: 'paging',
                    pageSize: 10,
                    store: store
                }
            }, config || {});

            Ariel.panel.review.Result.superclass.initComponent.call(this);

            _this.on('rowdblclick', _this.showDetail, _this);
            //_this.on('contextmenu', _this.showMenu, _this);

            store.load();
        },

        showDetail: function(self, rowIndex, e) {
            var record = this.getSelectionModel().getSelected();

            var win = new Ariel.window.review.Detail();

            win.loadRecord(record);
            win.show();

            //var record = this.getSelectionModel().getSelected();
            //var content_id = record.get('CONTENT_ID');
            //
            //openDetailWindow(self, content_id, record);
        },

        showMenu: function(e) {
            e.stopEvent();

            var menu = new Ext.menu.Menu({
                items: {
                    xtype: 'menureview',
                    grant: this.grant
                }
            });

            menu.showAt(e.getXY());
        }
    });

    Ext.reg('panelreviewresult', Ariel.panel.review.Result);
})();

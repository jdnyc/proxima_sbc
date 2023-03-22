Ext.ns('Ariel.panel.review');
(function() {

    Ariel.panel.review.Request = Ext.extend(Ext.grid.GridPanel, {

        initComponent: function(config) {
            var _this = this;

            var store = new Ext.data.JsonStore({
                url: '/store/review/list.php',
                fields: [
                    'ID', 'CONTENT_ID', 'TITLE', 'STATE', 'COMMENTS', 'USER_ID',
                    {name:  'CREATED', type: 'date', dateFormat: 'YmdHis'},
                    {name: 'REQUESTER', convert: function(v, record) {
                        return record.REQUESTER + '(' + record.REQUESTER_NAME + ')';
                    }},
                    {name: 'ACCEPTER', convert: function(v, record) {
                        return record.ACCEPTER + '(' + record.ACCEPTER_NAME + ')';
                    }}
                ],
                root: 'data',
                baseParams: {
                    state: 'request'
                }
            });
            var selModel = new Ext.grid.CheckboxSelectionModel();

            Ext.apply(this, {
                sm: selModel,
                columns: [
                    new Ext.grid.RowNumberer(),
                    selModel,
                    {header: '콘텐츠 제목', dataIndex: 'TITLE', width: 200},
                    {header: '의뢰상태', dataIndex: 'STATE', width: 80, align: 'center'},
                    {header: '사유', dataIndex: 'COMMENTS', width: 300},
                    {header: '의뢰자', dataIndex: 'REQUESTER', width: 150},
                    {header: '의뢰일시', dataIndex: 'CREATED', width: 150, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')}
                ],
                loadMask: true,
                store: store,
                tbar: [{
                    hidden: true,
                    grant: Ariel.grant.REVIEW_ACCEPT,
                    text: '승인',
                    icon: '/led-icons/review_sicon4.jpg',
                    handler: _this.onAccept,
                    scope: _this
                }, {
                    hidden: true,
                    grant: Ariel.grant.REVIEW_REJECT,
                    text: '반려',
                    icon: '/led-icons/review_sicon2.jpg',
                    handler: _this.onReject,
                    scope: _this
                }],
                bbar: {
                    xtype: 'paging',
                    pageSize: 10,
                    store: store
                },
                viewConfig: {
                    emptyText: 'aaa'
                }
            }, config || {});

            Ariel.panel.review.Request.superclass.initComponent.call(this);

            _this.on('rowdblclick', _this.showDetail, _this);
            //_this.on('contextmenu', _this.showMenu, _this);

            _this.getTopToolbar().on('render', _this._initButton, _this.getTopToolbar());

            store.load();
        },

        _initButton: function() {
            var _this = this;

            _this.find('hidden', true).forEach(function(item) {
                if (grant_check(item.grant)) {
                    item.hidden = false;
                }
            })
        },

        onAccept: function() {
            var items = this.getSelections();
            if (items.length > 0) {
                new Ariel.window.review.Accept({contents: items}).show();
            }
        },

        onReject: function() {
            new Ariel.window.review.Reject({contents: this.getSelections()}).show();
        },

        showDetail: function(self, rowIndex, e) {
            //var record = this.getSelectionModel().getSelected();
            //
            //var win = new Ariel.window.review.Detail();
            //
            //win.loadRecord(record);
            //win.show();

            var record = this.getSelectionModel().getSelected();
            var content_id = record.get('CONTENT_ID');

            openDetailWindow(self, content_id, record);
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
        },

        getSelections: function () {
            return this.getSelectionModel().getSelections();
        }
    });

    Ext.reg('panelreviewrequest', Ariel.panel.review.Request);
})();

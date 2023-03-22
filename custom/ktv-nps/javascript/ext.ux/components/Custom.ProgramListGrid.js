(function () {
    Ext.ns('Custom');
    Custom.ProgramListGrid = Ext.extend(Ext.grid.GridPanel, {
        _pgm_id: null,
        border: false,
        frame: false,
        //height : 120,
        listeners: {
            rowdblclick: function (self, idx, e) {
                var record = self.getStore().getAt(idx);
                self.fireEvent('pgmblclick', self, record, idx);
            }
        },
        constructor: function (config) {
            Ext.apply(this, {}, config || {});
            Custom.ProgramListGrid.superclass.constructor.call(this);
        },
        initComponent: function (config) {

            this.addEvents('pgmselect');
            this.addEvents('pgmblclick');
            this._initItems(config);
            Ext.apply(this, {}, config || {});
            Custom.ProgramListGrid.superclass.initComponent.call(this);
        },

        _updateEmptyText: function (text) {
            this.view.mainBody.update('<div class="x-grid-empty">' + text + '</div>');
        },
        _onSearch: function () {
            var _this = this;
            var pgm_nm = _this.searchField.getValue();
            _this.store.load({
                params: {
                    pgm_nm: pgm_nm
                }
            });
        },
        _initItems: function () {
            var _this = this;

            // _this.url = '/api/v1/bis-programs';
            _this.url = '/api/v1/open/bis-programs';
            //_this.url = _this.url.replace('{pgm_id}','PG2150036D');

            _this.cls = 'header-center-grid';
            _this.loadMask = true;

            _this.store = new Ext.data.JsonStore({
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: _this.url,
                    type: 'rest'
                    // ,
                    // headers: {
                    //     'Content-Type': 'application/json',
                    //     'X-API-KEY': 'B+Hqhy*3GEuJJmk%',
                    //     'api-user': 'admin'
                    // }
                }),
                root: 'data',
                totalProperty: 'total',
                idPropery: 'pgm_id',
                autoLoad: true,
                fields: [
                    'pgm_id',
                    'pgm_nm',
                    'director',
                    'main_role',
                    'pgm_info',
                    'status',
                    { name: 'category', mapping: 'pgm_nm' },
                    { name: 'folder_name', mapping: 'pgm_id' }
                ],
                sortInfo: {
                    field: 'pgm_nm',
                    direction: 'ASC'
                },
                listeners: {
                    // exception: function(self, type, action, options, response, arg){
                    //         if(type == 'response') {
                    //                 if(response.status == '200') {
                    //                         Ext.Msg.alert(_text('MN00022'), response.responseText);
                    //                 }else{
                    //                         Ext.Msg.alert(_text('MN00022'), response.status);
                    //                 }
                    //         }else{
                    //                 Ext.Msg.alert(_text('MN00022'), type);
                    //         }
                    // }
                }
            });

            _this.viewConfig = {
                emptyText: '검색된 결과가 없습니다.'
            };
            _this.colModel = new Ext.grid.ColumnModel({
                defaults: {
                    width: 120,
                    sortable: false,
                    menuDisabled: true
                },
                columns: [
                    new Ext.grid.RowNumberer(),
                    { header: '프로그램 ID', dataIndex: 'pgm_id', sortable: false, menuDisabled: true },
                    { header: '프로그램명', dataIndex: 'pgm_nm', width: 200, sortable: false, menuDisabled: true },
                    { header: '내용', dataIndex: 'pgm_info', width: 200, sortable: false, menuDisabled: true },
                    { header: '담당PD', dataIndex: 'director', sortable: false, menuDisabled: true }
                    //{header: '등급분류' , dataIndex: 'info_grd', sortable: false, menuDisabled: true},
                    //{header: '상태' , dataIndex: 'status', hidden: true, sortable: false, menuDisabled: true}
                ]
            });

            _this.sm = new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (self, idx, record) {
                        var pgmId = record.get('pgm_id');
                        Ext.Ajax.request({
                            method: 'GET',
                            url: '/api/v1/open/folder-mngs/'+pgmId,
                            callback:function(opts, success, response){
                                var r = Ext.decode(response.responseText);
                                if(r.success){
                                    var categoryId = null;
                                    if(!Ext.isEmpty(r.data)){
                                        if(!Ext.isEmpty(r.data.category_id)){
                                            categoryId = r.data.category_id;
                                        }
                                    }
         
                                    record.set('c_category_id', categoryId);
                                    record.commit();
                                }
                                _this.fireEvent('pgmselect', _this, record, idx);
                            }
                        });                        
                    }
                }
            });


            _this.searchField = new Ext.form.TextField({
                width: 120,
                submitValue: false,
                enableKeyEvents: true,
                listeners: {
                    keydown: function (self, e) {
                        if (e.getKey() == e.ENTER) {
                            e.stopEvent();
                            _this._onSearch();
                        }
                    }
                }
            });

            _this.tbar = ['조회', _this.searchField, {
                xtype: 'aw-button',
                text: '검색',
                iCls: 'fa fa-search',
                listeners: {
                    click: function (self, e) {
                        _this._onSearch();
                    }
                }
            }, '-', {
                    xtype: 'aw-button',
                    iCls: 'fa fa-refresh',
                    text: '새로고침',
                    handler: function (btn, e) {
                        _this.getStore().reload();
                    }
                }];
            _this.bbar = {
                xtype: 'paging',
                pageSize: 20,
                displayInfo: true,
                store: _this.store
            };

        }
    });

    Ext.reg('c-program-list-grid', Custom.ProgramListGrid);
})();
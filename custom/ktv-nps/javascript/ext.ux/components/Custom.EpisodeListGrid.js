(function () {
    Ext.ns('Custom');
    Custom.EpisodeListGrid = Ext.extend(Ext.grid.GridPanel, {
        _pgm_id: null,
        border: false,
        frame: false,
        //height : 120,
        listeners: {
            rowdblclick: function (self, idx, e) {
                var record = self.getStore().getAt(idx);
                self.fireEvent('epsddblclick', self, record, idx);
            }
        },
        constructor: function (config) {
            Ext.apply(this, {}, config || {});
            Custom.EpisodeListGrid.superclass.constructor.call(this);
        },

        initComponent: function (config) {

            this.addEvents('epsdselect');
            this.addEvents('epsddblclick');
            this._initItems(config);
            Ext.apply(this, {}, config || {});
            Custom.EpisodeListGrid.superclass.initComponent.call(this);
        },

        _updateEmptyText: function (text) {
            this.view.mainBody.update('<div class="x-grid-empty">' + text + '</div>');
        },
        _setUrlProgram: function (pgm_id) {
            this._pgm_id = pgm_id;
            this.store.url = this.url.replace('{pgm_id}', pgm_id);
            this.store.proxy.setUrl(this.store.url, true);
            return this.store.url;
        },
        _onSearch: function () {
            var search = this.searchField.getValue();
            this._setUrlProgram(this._pgm_id);
            this.store.load({
                params: {
                    keyword: search
                }
            });
        },
        _onReload: function () {
            var search = this.searchField.getValue();
            this._setUrlProgram(this._pgm_id);
            this.store.reload({
                params: {
                    keyword: search
                }
            });
        },
        _initItems: function () {
            var _this = this;

            // _this.url = '/api/v1/bis-programs/{pgm_id}/episodes';
            _this.url = '/api/v1/open/bis-programs/{pgm_id}/episodes';
            //_this.url = _this.url.replace('{pgm_id}','PG2150036D');

            _this.loadMask = true;
            _this.cls = 'header-center-grid';

            _this.store = new Ext.data.JsonStore({
                restful: true,
                remoteSort: true,
                proxy: new Ext.data.HttpProxy({
                    method: 'GET',
                    url: _this.url,
                    type: 'rest'
                }),
                root: 'data',
                totalProperty: 'total',
                //idPropery: 'pgm_id', 
                fields: [
                    "pgm_id",//: "PG2150036D",
                    "epsd_id",//: "A00012",
                    "epsd_no",//: "12",
                    "epsd_nm",//: "수원화성",
                    "epsd_onm",//: null,
                    "brd_run",//: "30",
                    "delib_grd",//: null,
                    "info_grd",//: null,
                    "scl_clf",//: null,
                    "epsd_clf",//: "0",
                    "epsd_use_yn",//: "Y",
                    "epsd_end_yn",//: "N",
                    "game_clf",//: null,
                    "main_role",//: "없음",
                    "supp_role",//: null,
                    "director",//: "김승훈",
                    "mc_nm",//: null,
                    "commentator",//: null,
                    "game_rslt",//: null,
                    "rec_ymd",//: "20161209",
                    "rec_bgn_hm",//: null,
                    "rec_end_hm",//: null,
                    "rec_run",//: null,
                    "rec_place",//: "없음",
                    "remark",//: null,
                    "info1",//: null,
                    "info2",//: "(매혹코리아)박시백의 세계유산 순례 12회 - 수원화성",
                    "info3",//: null,
                    "info4",//: null,
                    "info5",//: null,
                    "reg_dt",//: "20150721175453",
                    "regr",//: "paranoidrock84",
                    "mod_dt",//: "20150721175602",
                    "modr",//: "paranoidrock84",
                    "delib_ymd",//: null,
                    "delib_info",//: null,
                    "web_onair_yn",//: "Y",
                    "unsalable_yn",//: "Y",
                    "free_yn",//: null,
                    "makepd",//: "없음",
                    "keyword"//: "(매혹코리아)박시백의 세계유산 순례 12회 - 수원화성"
                ],
                sortInfo: {
                    field: 'epsd_no',
                    direction: 'ASC'
                }
            });
            _this.cm = new Ext.grid.ColumnModel({
                defaults: {
                    width: 120,
                    sortable: false,
                    menuDisabled: true
                },
                columns: [
                    new Ext.grid.RowNumberer(),
                    { header: '회차ID', dataIndex: 'epsd_id', hidden: true, width: 40 },
                    { header: '회차', dataIndex: 'epsd_no', width: 40 },
                    { header: '부제목', dataIndex: 'epsd_nm' },
                    { header: '방송길이', dataIndex: 'brd_run', width: 70 },
                    { header: '감독', dataIndex: 'supp_role' },
                    //{header: '정보1',    dataIndex: 'info1'},
                    { header: '정보', dataIndex: 'info2' },
                    //{header: '정보2',    dataIndex: 'info3'},
                    //{header: '정보2',    dataIndex: 'info4'},
                    //{header: '정보2',    dataIndex: 'info5'},
                    { header: '비고', dataIndex: 'remark' },
                    { header: '주연', dataIndex: 'main_role' },
                    { header: '조연', dataIndex: 'supp_role' },
                    { header: 'MC', dataIndex: 'supp_role' },
                    { header: '키워드', dataIndex: 'keyword' },

                    { header: '-', dataIndex: '', width: 10 }
                ]
            });

            _this.searchField = new Ext.form.TextField({
                xtype: 'textfield',
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
                        _this._onReload();
                    }
                }];

            _this.bbar = {
                xtype: 'paging',
                pageSize: 20,
                displayInfo: true,
                store: _this.store
            };

            _this.viewConfig = {
                emptyText: '검색된 결과가 없습니다.'
            };

            _this.sm = new Ext.grid.RowSelectionModel({
                singleSelect: true,
                listeners: {
                    rowselect: function (self, idx, record) {
                        _this.fireEvent('epsdselect', _this, record, idx);
                    }
                }
            });
        }
    });

    Ext.reg('c-episode-list-grid', Custom.EpisodeListGrid);
})();
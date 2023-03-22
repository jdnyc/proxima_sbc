(function () {
    Ext.ns("Custom");
    Custom.ParentContentGrid = Ext.extend(Ext.form.FormPanel, {
        contentId: null,
        // 콘텐츠 이동 버튼 true 숨김 false 보임
        relatedButton: false,
        // /////////////
        cls: 'change_background_panel background_panel_detail_content',
        layout: 'fit',
        buttonsAlign: 'right',
        defaults: {
            anchor: '95%'
        },
        padding: 5,
        border: false,

        initComponent: function (config) {

            this._initialize();
            Custom.ParentContentGrid.superclass.initComponent.call(this);
        },
        _initialize: function () {
            var _this = this;
            this.items = this._parentContentGrid();
            this.buttons =
                [{
                    // xtype: 'a-iconbutton',
                    hidden: true,
                    xtype: 'button',
                    scale: 'medium',
                    text: '소스 콘텐츠 조회',
                    handler: function (self) {
                        Ext.Ajax.request({
                            url: '/api/v1/contents/' + _this.contentId,
                            callback: function (options, success, response) {
                                var res = Ext.decode(response.responseText);
                                var parentContentId = res.data.parent_content_id;

                                if (!(parentContentId == null)) {
                                    new Custom.ContentDetailWindow({
                                        content_id: parentContentId,
                                        isPlayer: true,
                                        isMetaForm: true,
                                        playerMode: 'read',
                                        listeners: {
                                            afterrender: function (self) {
                                                self.buttons[0].hide();
                                            }
                                        }
                                    }).show();
                                } else {
                                    Ext.Msg.alert('알림', '소스 콘텐츠가 없습니다.');
                                }
                            }
                        });

                    }
                }]

        },
        _parentContentGrid: function () {
            var _this = this;
            var grid = new Ext.grid.GridPanel({
                // id: 'parent_content',
                border: false,
                cls: 'proxima_customize dark_grid',
                stripeRows: true,
                layout: 'fit',
                loadMask: true,
                split: true,
                store: new Ext.data.JsonStore({
                    remoteSort: true,
                    restful: true,
                    proxy: new Ext.data.HttpProxy({
                        method: "GET",
                        url: Ariel.archiveManagement.UrlSet.listParentContent(_this.contentId),
                        type: "rest"
                    }),
                    remoteSort: true,
                    totalProperty: "total",
                    root: "data",
                    fields: [
                        "category_id",
                        "category_full_path",
                        "bs_content_id",
                        "ud_content_id",
                        "content_id",
                        "title",
                        "is_deleted",
                        "is_hidden",
                        "reg_user_id",
                        "expired_date",
                        "last_modified_date",
                        { name: "created_date", type: 'date' },
                        "status",
                        "readed",
                        "last_accessed_date",
                        "parent_content_id",
                        "manager_status",
                        "is_group",
                        "group_count",
                        "state",
                        "archive_date",
                        "del_status",
                        "del_yn",
                        "restore_date",
                        "uan",
                        "thumbnail_content_id",
                        "sequence_id",
                        "is_archive",
                        "approval_yn",
                        "updated_at",
                        "updated_user_id",
                        "usr_meta"
                    ]
                }),
                cm: new Ext.grid.ColumnModel({
                    defaults: {
                        align: "center",
                        menuDisabled: true,
                        sortable: false
                    },
                    columns: [
                        {
                            header: "미디어ID", dataIndex: "usr_meta", renderer: function (v) {
                                if (Ext.isEmpty(v)) {
                                    return;
                                };
                                return v.media_id;
                            },
                            width: 200
                        },
                        { header: "제목", dataIndex: "title", width: 250, align: 'left' },
                        {
                            header: "콘텐츠 구분", renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                                var parentContentId = record.get('parent_content_id');
                                if (Ext.isEmpty(parentContentId)) {
                                    return '메인';
                                }
                                return '클립';

                            },
                            width: 100
                        },
                        { header: "등록일자", dataIndex: "created_date", renderer: Ext.util.Format.dateRenderer("Y-m-d") }

                    ]
                }),
                tbar: [{
                    text: '<span style="position:relative;top:1px;" title="' + _text('MN00390') + '"><i class="fa fa-refresh" style="font-size:13px;"></i></span>',
                    cls: 'proxima_btn_customize proxima_btn_customize_new',
                    handler: function (self) {
                        grid.getStore().reload();
                    }
                }, {
                    text: '<span>콘텐츠이동</span>',
                    cls: 'proxima_btn_customize proxima_btn_customize_new',
                    hidden: _this.relatedButton,
                    width: 65,
                    handler: function (self) {
                        grid._showRelatedContent();
                    }
                }],
                listeners: {
                    afterrender: function (self) {
                        self.getStore().load({

                        });
                    },
                    rowdblclick: function (self) {
                        var sm = self.getSelectionModel();
                        var getRecord = sm.getSelected();
                        var contentId = getRecord.get('content_id');
                        if (!(contentId == null)) {
                            new Custom.ContentDetailWindow({
                                content_id: contentId,
                                isPlayer: true,
                                isMetaForm: true,
                                hideMetaFormButton: true,
                                playerMode: 'read',
                                id: Ext.id(),
                                listeners: {
                                    afterrender: function (self) {
                                        self.buttons[0].hide();
                                    }
                                }
                            }).show();
                        } else {
                            Ext.Msg.alert('알림', '소스 콘텐츠가 없습니다.');
                        }
                    }
                },
                /**
                 * 기존 콘텐츠 창을 닫고 관련 콘텐츠창을 새로 띄운다
                 */
                _showRelatedContent: function () {
                    var sm = grid.getSelectionModel();
                    if (!sm.hasSelection()) {
                        return Ext.Msg.alert('알림', '이동할 컨텐츠를 선택해주세요.');
                    }

                    if (player_windown_flag) {
                        return;
                    };
                    var record = sm.getSelected();

                    var contentId = record.get('content_id');
                    var udContentId = record.get('ud_content_id');
                    var bsContentId = record.get('bs_content_id');
                    if (!Ext.isEmpty(Ext.getCmp('winDetail'))) {
                        Ext.getCmp('winDetail').close();

                    }

                    var tabPanel = Ext.getCmp('tab_warp');

                    var contentGrid = tabPanel.getActiveTab().get(0);
                    var win_type;

                    if (contentGrid.id == 'contentgrid_westtab_video' || contentGrid.id == 'contentgrid_westtab_graphic') {
                        win_type = 'zodiac';
                    }

                    var params = {
                        content_id: contentId,
                        record: Ext.encode(record.json),
                        win_type: win_type,
                        targetGrid: 'tab_warp'
                    };

                    openDetailWindowWithParams(contentGrid, params);
                }
            });
            return grid;
        }
    })
})()
Ext.ns('Ariel.popup');

Ariel.popup.SearchMaterial = Ext.extend(Ext.Window, {
    id: 'popup-search-material',
    title: '운행소재 검색',
    width: 350,
    height: 400,
    modal: true,
    layout: {
        type: 'vbox',
        align: 'stretch',
        pack: 'start'
    },

    initComponent: function(config) {
        var _this = this;

        Ext.apply(this, config || {}, {
            items: [{
                height: 40,
                xtype: 'form',
                frame: true,

                items: [{
                    xtype: 'compositefield',
                    hideLabel: true,

                    items: [{
                        width: 80,
                        xtype: 'combo',
                        name: 'mtrl_clf',
                        triggerAction: 'all',
                        editable: false,
                        store: [
                            ['NX', '이어서'],
                            ['ZZ', '기타'],
                            ['SP', 'SPOT'],
                            ['CP', '캠페인'],
                            ['PR', '예고'],
                            ['FL', 'FILLER'],
                            ['CA', '공익광고'],
                            ['ID', '채널ID'],
                            ['NX', '이어서'],
                            ['CM', '광고'],
                            ['PT', '전 타이틀'],
                            ['ZT', '후 타이틀']
                        ],
                        value: 'NX'
                    }, {
                        flex: 1,
                        xtype: 'textfield',
                        name: 'mtrl_nm'
                    }, {
                        width: 35,
                        xtype: 'button',
                        text: '검색',
                        handler: _this._search,
                        scope: _this
                    }]
                }]
            }, {
                flex: 1,
                xtype: 'grid',
                formId: _this.formId,
                loadMask: true,
                viewConfig: {
                    emptyText: '데이터가 없습니다.',
                    forceFit: true
                },
                columns: [{
                    header: '소재명', dataIndex: 'mtrl_nm'
                }],
                store: new Ext.data.JsonStore({
                    url: '/interface/ajax.php/bis/CIS_IF_502',
                    fields: [
                        'mtrl_id', 'mtrl_nm', 'mtrl_info', 'mtrl_clf1', 'mtrl_clf1_nm', 'mtrl_clf2', 'mtrl_clf2_nm'
                    ],
                    root: 'data',
                    sortInfo: {
                        field: 'mtrl_nm',
                        direction: 'asc'
                    }
                }),
                listeners: {
                    dblclick: _this._select
                }
            }]
        });

        Ariel.popup.SearchMaterial.superclass.initComponent.call(this);
    },

    afterShow: function() {
        Ariel.popup.SearchMaterial.superclass.afterShow.call(this);

        this._search();
    },

    _search: function() {
        var combo = this.find('xtype', 'form')[0].getForm().findField('mtrl_clf');
        var text =  this.find('xtype', 'form')[0].getForm().findField('mtrl_nm');
        this.find('xtype', 'grid')[0].getStore().load({
            params: {
                mtrl_clf: combo.getValue(),
                mtrl_nm: text.getValue()
            }
        });
    },

    _select: function() {
        var selModel = this.getSelectionModel();
        var record = selModel.getSelected();
        var form = Ext.getCmp(this.formId);

        form.getForm().setValues({
            k_title: record.get('mtrl_nm'),
            4778407: record.get('mtrl_id'),
            4778408: record.get('mtrl_nm'),
            4778409: record.get('mtrl_info'),
            4778410: record.get('mtrl_clf1'),
            4778411: record.get('mtrl_clf1_nm'),
            4778412: record.get('mtrl_clf2'),
            4778413: record.get('mtrl_clf2_nm')
        });

        this.ownerCt.close();
    }
});

function searchMaterial(btn) {

    var win = new Ariel.popup.SearchMaterial({
        formId: btn.formId
    });

    win.show();
}
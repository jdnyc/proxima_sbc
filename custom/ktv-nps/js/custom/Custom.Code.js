(function () {
    Ext.ns('Custom');
    // Custom.Code = Ext.extend(Ext.data.JsonStore, {
    //     //restful: true,
    //     _test: true,
    //     root: 'data',
    //     fields: [
    //         { name: 'items' },
    //         { name: 'type' },
    //         { name: 'id', mapping: 'id' }
    //     ],
    //     constructor: function (config) {
    //         Ext.apply(this, {}, config || {});
    //         console.log('constructor af', config);

    //         this.addEvents('load');
    //         this.custom();
    //         Custom.Code.superclass.constructor.call(this);
    //     },
    //     custom: function () {
    //         console.log('custom', this);
    //         this.listeners = {
    //             beforeload: function (self, record, option) {
    //                 console.log('beforeload', self);
    //             }, load: function (self, record, option) {
    //                 console.log('load', self);
    //             }
    //         };

    //         this.url = '/api/v1/data-dic-domains/104/code-items';

    //         // this.proxy = new Ext.data.HttpProxy({
    //         //     restful: true,
    //         //     type: 'rest',
    //         //     url: '/api/v1/data-dic-code-sets/*OR002,*OR003,*OR001,DD_TABLE_SE/codes',
    //         //     method: 'POST',
    //         //     api: {
    //         //         read: {
    //         //             url: '/api/v1/data-dic-code-sets/*OR002,*OR003,*OR001,DD_TABLE_SE/codes',
    //         //             method: 'POST'
    //         //         }
    //         //     },
    //         //     listeners: {
    //         //         load: function (self, record, option) {
    //         //             console.log('load', self);
    //         //         }
    //         //     }
    //         // });
    //     }
    // });
    Custom.Code = Ext.extend(Object, {
        makeStore: function (code) {
            return new Ext.data.JsonStore({
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    restful: true,
                    type: 'rest',
                    url: '/api/v1/data-dic-code-sets/' + code + '/codes',
                    method: 'POST',
                    api: {
                        read: {
                            url: '/api/v1/data-dic-code-sets/' + code + '/codes',
                            method: 'POST'
                        }
                    }
                    // *OR002,*OR003,*OR001,DD_TABLE_SE
                }),
                root: 'data',
                fields: [
                    { name: 'items' },
                    { name: 'type' },
                    { name: 'id', mapping: 'id' }
                ],
                listeners: {
                    load: function (self, record, option) {
                        console.log(self);
                        console.log(record);
                        console.log(option);
                    }
                }
            })
        },
        setStore: function (code) {
            this.store = this.makeStore(code);
        },
        getStore: function () {
            return this.store;
        },
        loadStore: function (code) {
            this.setStore(code);
            this.store.load();
        },
        getCode: function (code) {
            this.loadStore(code);
            return this.store;
        }
    });
    // Custom.Code = new Custom.Code();
})();
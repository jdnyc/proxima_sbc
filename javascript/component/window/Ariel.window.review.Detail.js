/**
 * Created by cerori on 2015-04-08.
 */

Ext.ns('Ariel.window.review');
(function() {

    Ariel.window.review.Detail = Ext.extend(Ext.Window, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                layout: 'fit',
                modal: true,
                resizable: false,
                width: 600,
                height: 315,
                border: false,
                title: '심의 상세',
                items: [{
                    xtype: 'formreviewdetail'
                }],

                buttons: [{
                    text: '닫기',
                    handler: _this.onCancel,
                    scope: _this
                }]
            }, config || {});

            Ariel.window.review.Detail.superclass.initComponent.call(this);
        },

        onCancel: function() {
            this.close();
        },

        loadRecord: function(record) {
            var form = this.get(0).getForm();

            form.loadRecord(record);
            form.findField('CREATED').setValue(record.get('CREATED').format('Y-m-d H:i:s'));
        }
    });

    Ext.reg('windowreviewdetail', Ariel.window.review.Detail);
})();

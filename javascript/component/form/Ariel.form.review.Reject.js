/**
 * Created by cerori on 2015-04-08.
 */

(function() {
    Ext.ns('Ariel.form.review');

    Ariel.form.review.Reject = Ext.extend(Ext.form.FormPanel, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                frame: true,
                padding: 5,
                labelAlign: 'top',
                defaults: {
                    anchor: '100%'
                },
                items: [{
                    xtype: 'hidden',
                    name: 'action',
                    value: 'reject'
                },{
                    xtype: 'textarea',
                    name: 'comments',
                    height: '190',
                    fieldLabel: '반려 사유',
                    labelSeparator: '',
                    allowBlank: false
                }]
            }, config || {});

            Ariel.form.review.Reject.superclass.initComponent.call(this);
        }
    });

    Ext.reg('formreviewreject', Ariel.form.review.Reject);
})();

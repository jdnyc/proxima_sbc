/**
 * Created by cerori on 2015-04-08.
 */

(function() {
    Ext.ns('Ariel.form.review');

    Ariel.form.review.Request = Ext.extend(Ext.form.FormPanel, {

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
                    value: 'request'
                },{
                    xtype: 'textarea',
                    name: 'comments',
                    height: '190',
                    fieldLabel: '의뢰 사유',
                    labelSeparator: '',
                    allowBlank: false
                }]
            }, config || {});

            Ariel.form.review.Request.superclass.initComponent.call(this);
        }
    });

    Ext.reg('formreviewrequest', Ariel.form.review.Request);
})();

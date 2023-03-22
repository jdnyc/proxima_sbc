/**
 * Created by cerori on 2015-04-08.
 */

(function() {
    Ext.ns('Ariel.form.review');

    Ariel.form.review.Detail = Ext.extend(Ext.form.FormPanel, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                frame: true,
                padding: 5,
                hideLabels: true,
                defaults: {
                    anchor: '100%'
                },
                items: [{
                    xtype: 'textarea',
                    name: 'COMMENTS',
                    height: '190',
                    fieldLabel: '사유',
                    labelSeparator: '',
                    readOnly: true
                }, {
                    xtype: 'textfield',
                    name: 'CREATED',
                    readOnly: true
                }]
            }, config || {});

            Ariel.form.review.Detail.superclass.initComponent.call(this);
        }
    });

    Ext.reg('formreviewdetail', Ariel.form.review.Detail);
})();

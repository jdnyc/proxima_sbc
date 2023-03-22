/**
 * Created by cerori on 2015-04-08.
 */

(function() {
    Ext.ns('Ariel.window.review');

    Ariel.window.review.Reject = Ext.extend(Ext.Window, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                layout: 'fit',
                modal: true,
                resizable: false,
                width: 600,
                height: 300,
                border: false,
                title: '심의 반려',
                items: [{
                    xtype: 'formreviewreject'
                }],

                buttons: [{
                    text: '반려',
                    handler: _this.onAccept,
                    scope: _this
                }, {
                    text: '취소',
                    handler: _this.onCancel,
                    scope: _this
                }]
            }, config || {});

            Ariel.window.review.Accept.superclass.initComponent.call(this);

            _this.on('render', _this._onRendered);
        },

        _onRendered: function() {
            this.get(0).get(1).focus(false, 400);
        },

        onAccept: function(btn) {
            var _this = this,
                form = this.get(0).getForm(),
                jsonData = [];

            btn.disable();

            jsonData = extractJsonFromModel(this.contents);
            form.submit({
                url: '/store/review/put.php',
                params: {
                    content_list: Ext.encode(jsonData)
                },
                success: function(form, action) {
                    if (action.result.success === true) {
                        Ext.getCmp('reviewrequest').getStore().reload();
                        _this.close();
                    } else if (action.result.success === false) {
                        Ext.Msg.alert('확인', action.result.msg);
                    } else {
                        Ext.Msg.alert('확인', action.response.responseText);
                    }
                }
            });
        },

        onCancel: function() {
            this.close();
        }
    });

    Ext.reg('windowreviewaccept', Ariel.window.review.Accept);
})();

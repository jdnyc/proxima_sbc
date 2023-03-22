/**
 * Created by cerori on 2015-04-08.
 */

(function() {
    Ext.ns('Ariel.window.review');

    Ariel.window.review.Request = Ext.extend(Ext.Window, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                layout: 'fit',
                modal: true,
                resizable: false,
                width: 600,
                height: 300,
                border: false,
                title: '심의 의뢰',
                items: [{
                    xtype: 'formreviewrequest'
                }],

                buttons: [{
                    text: '의뢰',
                    handler: _this.onAccept,
                    scope: _this
                }, {
                    text: '취소',
                    handler: _this.onCancel,
                    scope: _this
                }]
            }, config || {});

            Ariel.window.review.Request.superclass.initComponent.call(this);

            _this.on('render', _this._onRendered);
        },

        _onRendered: function() {
            this.get(0).get(1).focus(false, 400);
        },

        onAccept: function(btn) {
            var _this = this,
                form = this.get(0).getForm(),
                sm = Ext.getCmp('tab_warp').getActiveTab().get(0).getSelectionModel(),
                records = sm.getSelections(),
                jsonData = [];

            btn.disable();

            records.forEach(function(record) {
                jsonData.push({CONTENT_ID: record.get('content_id')});
            });


            form.submit({
                url: '/store/review/put.php',
                params: {
                    content_list: Ext.encode(jsonData)
                },
                success: function(form, action) {
                    if (action.result.success === true) {
                        Ext.getCmp('tab_warp').getActiveTab().get(0).getStore().reload();
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

    Ext.reg('windowreviewrequest', Ariel.window.review.Request);
})();

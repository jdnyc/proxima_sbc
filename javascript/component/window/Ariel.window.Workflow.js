/**
 * Created by cerori on 2015-04-08.
 */

Ext.ns('Ariel.window');
(function() {

    Ariel.window.Workflow = Ext.extend(Ext.Window, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                id: 'add_workflow_win',
                layout: 'fit',
                title: '작업흐름 추가',
                width: 400,
                height: 290,
                padding: 10,
                modal: true,
                items: {
                    xtype: 'formworkflow'
                },
                buttons: [{
                    text: '추가',
                    handler: _this.onAccept,
                    scope: _this
                },{
                    text: '취소',
                    handler: _this.onCancel,
                    scope: _this
                }]
            }, config || {});

            Ariel.window.Workflow.superclass.initComponent.call(this);

            _this.on('render', _this._onRendered);
        },

        _onRendered: function() {
            this.get(0).get(1).focus(false, 400);
        },

        onAccept: function() {
            var _this = this;

            this.getForm().submit({
                url: '/pages/menu/config/workflow/edit_workflow.php',
                params: {
                    action: 'add_workflow'
                },
                success: function(form, action) {
                    try {
                        var result = action.result;
                        if (result.success) {
                            _this.close();
                            Ext.getCmp('task_workflow').store.reload();
                        } else{
                            Ext.Msg.alert(_text('MN00022'),result.msg);
                        }
                    }catch(e){
                        Ext.Msg.alert(_text('MN00022'),e.message);
                    }
                },

                failure: function(form, action) {
                    Ext.Msg.alert(_text('MN00022'),action.result.msg);
                }
            });
        },

        onCancel: function() {
            this.close();
        },

        loadRecord: function(data) {
            this.getForm().loadRecord(data);
        },

        getForm: function() {
            return this.get(0).getForm();
        }
    });

    Ext.reg('windowworkflow', Ariel.window.Workflow);
})();

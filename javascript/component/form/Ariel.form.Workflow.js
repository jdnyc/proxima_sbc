/**
 * Created by cerori on 2015-04-09.
 */
/**
 * Created by cerori on 2015-04-08.
 */

Ext.ns('Ariel.form');

(function() {
    Ariel.form.Workflow = Ext.extend(Ext.form.FormPanel, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                frame: true,
                //padding: 5,
                defaults: {
                    anchor: '100%'
                },
                id: 'add_table_form',
                baseCls: 'x-plain',
                defaultType: 'textfield',
                items: [{
                    xtype: 'hidden',
                    name: 'task_workflow_id'
                }, {
                    name: 'user_task_name',
                    fieldLabel: '작업흐름 명',
                    msgTarget: 'under',
                    allowBlank: false
                },{
                    xtype: 'checkbox',
                    name: 'activity',
                    status: 'active',
                    fieldLabel: '활성화'
                },{
                    name: 'register',
                    fieldLabel: '등록 채널',
                    allowBlank: false
                },{
                    xtype: 'textarea',
                    name: 'description',
                    fieldLabel: '설  명'
                },{
                    xtype: 'formfieldcombo',
                    fieldLabel: '콘텐츠 상태',
                    name: 'content_status',
                    allowBlank: true,
                    hiddenName: 'content_status',
                    valueField: 'code',
                    displayField: 'name',
                    value: 2
                },{
                    xtype: 'numberfield',
                    allowBlank: false,
                    fieldLabel: '최대 동시 작업 수',
                    name: 'max',
                    value: 2
                }]
            }, config || {});

            Ariel.form.Workflow.superclass.initComponent.call(this);
        }
    });

    Ext.reg('formworkflow', Ariel.form.Workflow);
})();

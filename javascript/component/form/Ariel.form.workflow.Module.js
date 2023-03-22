/**
 * Created by cerori on 2015-04-09.
 */
/**
 * Created by cerori on 2015-04-08.
 */

Ext.ns('Ariel.form');

(function() {
    Ariel.form.workflow.Module = Ext.extend(Ext.form.FormPanel, {

        initComponent: function(config) {
            var _this = this;

            Ext.apply(this, {
                id: 'edit_table_form',
                baseCls: 'x-plain',
                defaultType: 'textfield',
                defaults: {
                    anchor: '100%'
                },

                items: [{
                    xtype: 'hidden',
                    name: 'task_rule_id'
                },{
                    name: 'job_name',
                    fieldLabel: '작업 명칭',
                    msgTarget: 'under',
                    allowBlank: false
                }, {
                    id: 'm_task_type_list',
                    xtype: 'combo',
                    fieldLabel: '작업 유형',
                    store: 	new Ext.data.JsonStore({
                        url: '/pages/menu/config/workflow/workflow_list.php',
                        autoDestroy: true,
                        baseParams: {
                            action: 'get_available_task_list'
                        },
                        root: 'data',
                        idProperty: 'type',
                        fields: [
                            {name: 'name'},
                            {name: 'type'},
                            {name: 'task_type_id'},
                            {name: 'type_and_name'}//타입과 모듈 동시에 나오는 필드 추가
                        ]
                    }),
                    hiddenName: 'type_and_name',
                    hiddenValue: sel.get('task_type_id'),
                    valueField: 'task_type_id',
                    tpl: '<tpl for="."><div class="x-combo-list-item" ><font color=red><b>[{type}]</b></font> {name}</div></tpl>',
                    displayField: 'type_and_name',
                    typeAhead: true,
                    triggerAction: 'all',
                    forceSelection: true,
                    editable: false,
                    //>>emptyText: '작업 타입을 선택하세요.'
                    emptyText: _text('MSG00197'),
                    listeners: {
                        beforequery: function(qe){
                            delete qe.combo.lastQuery;
                        }
                    }
                },{
                    name: 'parameter',
                    //>>fieldLabel: '파라미터',
                    fieldLabel: _text('MN00299'),
                    msgTarget: 'under',
                    allowBlank: false
                },{
                    xtype: 'combo',
                    //>>fieldLabel: '소스 경로',
                    fieldLabel: _text('MN00343'),
                    store: new Ext.data.JsonStore({
                        url: '/pages/menu/config/workflow/workflow_list.php',
                        baseParams: {
                            action: 'storage_list'
                        },
                        root: 'data',
                        idProperty: 'storage_id',
                        fields: [
                            {name: 'name', type: 'string'},
                            {name: 'storage_id', type: 'int'},
                            {name: 'path', type: 'string'}
                        ]
                    }),
                    hiddenName: 's_name',
                    hiddenValue: sel.get('source_path'),
                    valueField: 'storage_id',
                    displayField: 'name',
                    //수정일 : 2011.12.09
                    //작성자 : 김형기
                    //내용 : tpl구문 추가 하여 스토리지 이름과 경로가 동시에 나오도록 변경
                    tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
                    typeAhead: true,
                    triggerAction: 'all',
                    forceSelection: true,
                    editable: false,
                    //>>emptyText: '소스 스토리지를 선택하세요'
                    emptyText: _text('MSG00198')
                },{
                    xtype: 'combo',
                    //>>fieldLabel: '타겟 경로',
                    fieldLabel: _text('MN00344'),
                    store: new Ext.data.JsonStore({
                        url: '/pages/menu/config/workflow/workflow_list.php',
                        baseParams: {
                            action: 'storage_list'
                        },
                        root: 'data',
                        idProperty: 'storage_id',
                        fields: [
                            {name: 'name', type: 'string'},
                            {name: 'storage_id', type: 'int'},
                            {name: 'path', type: 'string'}
                        ]
                    }),
                    hiddenName: 't_name',
                    hiddenValue: sel.get('target_path'),
                    valueField: 'storage_id',
                    displayField: 'name',
                    //수정일 : 2011.12.09
                    //작성자 : 김형기
                    //내용 : tpl구문 추가 하여 스토리지 이름과 경로가 동시에 나오도록 변경
                    tpl: '<tpl for="."><div class="x-combo-list-item" >{name} [{path}]</div></tpl>',
                    typeAhead: true,
                    triggerAction: 'all',
                    forceSelection: true,
                    editable: false,
                    //>>emptyText: '타겟 스토리지를 선택하세요'
                    emptyText: _text('MSG00199')
                },{
                    xtype: 'textfield',
                    name: 'source_opt',
                    fieldLabel: '소스 옵션',
                    msgTarget: 'under'
                    //,allowBlank: false
                },{
                    xtype: 'textfield',
                    name: 'target_opt',
                    fieldLabel: '타겟 옵션',
                    msgTarget: 'under'
                    //,allowBlank: false
                }],
                keys: [{
                    key: 13,
                    handler: function(){
                        _submit();
                    }
                }],
                listeners: {
                    afterrender: function(self) {
                        var sm = Ext.getCmp('task_rule_list').getSelectionModel();
                        var rec = sm.getSelected();
                        self.getForm().loadRecord(rec);
                    }
                }
            }, config || {});

            Ariel.form.Workflow.superclass.initComponent.call(this);
        }
    });

    Ext.reg('formworkflow', Ariel.form.Workflow);
})();

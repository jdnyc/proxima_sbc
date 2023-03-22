<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
$module_info_id = $_REQUEST['module_info_id'];
?>

// 모듈 수정버튼 기능 실행
(function() {

    var _submit = function() {

        Ext.getCmp('edit_module_form').items.each(function(f) {
            if (f.el.getValue() == f.emptyText) {
                f.el.dom.value = '';
            }
        });
        var dbParams = Ext.encode(Ext.getCmp('edit_module_form').getForm().getValues());
        var dbParams2 = Ext.encode(Ext.getCmp('edit_available_task_form').getForm().getValues());
        //var dbParams3 = Ext.encode(Ext.getCmp('edit_storage_form').getForm().getValues());
        Ext.Ajax.request({
            method: 'post',
            url: '/pages/menu/config/workflow/edit_workflow.php',
            params: {
                action: 'edit_module',
                module_info_id : <?=$module_info_id?>,
                data : dbParams,
                task_rule :  dbParams2
                //storage :  dbParams3
            },

            success: function ( result, request ) {
                try {
                    var result = Ext.decode(result.responseText, true);
                    if(result.success) {
                        Ext.getCmp('edit_module_win').close();
                        Ext.getCmp('modueInfo').store.reload();
                        Ext.getCmp('task_type_list').store.removeAll();
                    }else{
                        Ext.Msg.show({

                            title: _text('MN00022'),
                            icon: Ext.Msg.ERROR,
                            msg: result.msg,
                            buttons: Ext.Msg.OK
                        })
                    }
                }catch(e){
                    Ext.Msg.show({
                        //>>title: lang.errorLabel,
                        title: _text('MN00022'),
                        icon: Ext.Msg.ERROR,
                        msg: e.message,
                        buttons: Ext.Msg.OK
                    })
                }
            },
            failure: function(result,request) {
                Ext.Msg.show({
                    icon: Ext.Msg.ERROR,
                    //>>title: lang.errorLabel,
                    title: _text('MN00022'),
                    msg: result.msg,
                    buttons: Ext.Msg.OK
                });
            }
        });
    }

            var south_form = new Ext.form.FormPanel({
                    title: _text('MN01090'),//MN01090' 스토리지 선택 ',
                    id: 'edit_storage_form',
                    autoScroll: true,
                    border: false,
                    frame: true,
                    items: {
                            xtype: 'checkboxgroup',
                            id: 'allow_storage',
                            hideLabel: true,
                            fieldLabel: ' ',
                            name: 'group',
                            columns: 2,

                            items: [
                                <?php
                                $check_module = $db->queryAll("select available_storage from bc_path_available where module_info_id = '$module_info_id' group by available_storage order by available_storage asc");

                                $module_group = array();
                                foreach($check_module as $cm)
                                {
                                    array_push($module_group, $cm['available_storage']);
                                }

                                $checkboxs = array();
                                $groups = $db->queryAll("select storage_id, name from bc_storage");
                                foreach($groups as $group){
                                    if(in_array($group['storage_id'], $module_group))
                                    {
                                        $checked = 'true';
                                    }
                                    else
                                    {
                                        $checked = 'false';
                                    }
                                    array_push($checkboxs, "{boxLabel: '{$group['name']}', name: 's_".$group['storage_id']."', inputValue: '{$group['storage_id']}' , checked: ".$checked."}");
                                }
                                echo implode(", \n", $checkboxs);
                                ?>
                                ]
                        }

            });


            var center_form = new Ext.form.FormPanel({
                    id: 'edit_available_task_form',
                    cls: 'change_background_panel',
                    title: _text('MN02062'),//MN01088'사용할 작업',
                    width: 450,
                    autoScroll: true,
                    border: false,
                    frame: true,
                    items: [{
                            xtype: 'checkboxgroup',
                            hideLabel: true,
                            id: 'available_task',
                            name: 'available_task_group',
                            columns: 1,
                            items: [
                            <?php
                                /* //2011.12.14 김형기 수정
                                $task_type_id = $db->queryAll("select task_type_id from bc_task_available where module_info_id = '$module_info_id' order by task_type_id ASC");

                                $module_group = array();

                                foreach($task_type_id as $tt)
                                {
                                    array_push($module_group, $tt['task_type_id']);
                                }

                                $checkboxs = array();
                                $groups = $db->queryAll("select type, name,task_type_id from bc_task_type");
                                foreach($groups as $group){
                                    if(in_array($group['task_type_id'], $module_group))
                                    {
                                        $checked = 'true';
                                    }
                                    else
                                    {
                                        $checked = 'false';
                                    }

                                    array_push($checkboxs, "{boxLabel: '[{$group['type']}] {$group['name']}', name: 's_".$group['task_type_id']."', inputValue: '{$group['task_type_id']}', checked: ".$checked."}");
                                }
                                echo implode(", \n", $checkboxs);
                                */
                                $checkboxs = array();
								$query = "
											SELECT	A.JOB_NAME, A.TYPE_NAME, A.TYPE, A.TASK_RULE_ID,
													(SELECT COUNT(*) FROM BC_TASK_AVAILABLE WHERE TASK_RULE_ID = A.TASK_RULE_ID AND MODULE_INFO_ID = $module_info_id) COUNT
											FROM	(
														SELECT	TR.TASK_RULE_ID, TR.JOB_NAME, TT.NAME TYPE_NAME, TT.TYPE
														FROM	BC_TASK_RULE TR	
																LEFT OUTER JOIN BC_TASK_TYPE TT ON TR.TASK_TYPE_ID = TT.TASK_TYPE_ID
											) A
											ORDER BY A.JOB_NAME ASC
								";
                                $groups = $db->queryAll($query);

                                foreach($groups as $group){
                                    $checked = $group['count'] > 0 ? 'true' : 'false';
                                    array_push($checkboxs, "{boxLabel: '{$group['job_name']} [{$group['type_name']}({$group['type']})]', name: 's_".$group['task_rule_id']."', inputValue: '{$group['task_rule_id']}', checked: $checked}");
                                }
                                echo implode(", \n", $checkboxs);

                                ?>
                            ]
                    }]
            });

            var left_form = new Ext.form.FormPanel({
                    title:  _text('MN02069'),//MN00384'모듈 정보',
                    id: 'edit_module_form',
                    cls: 'change_background_panel',
                    border: false,
                    frame: true,
                    defaultType: 'textfield',
                    defaults: {
                        anchor: '100%'
                    },
                    items: [{
                        name: 'module_id',
                        hidden: true
                    },
                    {
                        name: 'name',
                        //>>fieldLabel: '모듈 명칭',
                        fieldLabel: _text('MN00346'),
                        allowBlank: false,
                        //>>emptyText: '모듈 명을 입력해 주세요'
                        emptyText: _text('MSG00203'),
                        defaultValue :''
                    },{
                        xtype: 'checkbox',
                        name: 'activity',
                        checked: true,
                        //>>fieldLabel: '활성화'
                        fieldLabel: _text('MN00347')
                    },{
                        name: 'main_ip',
                        //fieldLabel: '메인 IP',
                        fieldLabel: _text('MN00348'),
                        allowBlank: false,
                        //>>emptyText: '메인 IP주소를 입력해 주세요.'
                        emptyText: _text('MSG00205')
                    },{
                        name: 'sub_ip',
                        //>>fieldLabel: '보조 IP',
                        fieldLabel: _text('MN00349'),
                        //>>emptyText: '보조 IP주소를 입력해 주세요.'
                        emptyText:  _text('MSG00206')
                    },{
                        xtype: 'textarea',
                        name: 'description',
                        //>>fieldLabel: '설 명',
                        fieldLabel: _text('MN00049'),
                        //>>emptyText: '모듈에 대한 설명을 입력해 주세요.'
                        emptyText: _text('MSG00204')
                    }]
                    ,listeners: {
                        afterrender: function(self) {
                            var sm = Ext.getCmp('modueInfo').getSelectionModel();
                            var rec = sm.getSelected();
                            self.getForm().loadRecord(rec);
                        }
                    }
            });

    var win = new Ext.Window({
        id: 'edit_module_win',
        layout: 'fit',
        title: _text('MN00388'),//MN00388'모듈 수정',
        width: 750,
        height: 500,
        padding: 0,
        modal: true,
        buttonAlign: 'center',
        items:[
            new Ext.Panel({
                layout: 'border',
                border: false,
                id : 'add_module_panel',
                items: [{
                    region: 'west',
                    border: false,
                    width: 350,
                    layout: 'border',
                    margins: '0 0 0 0',
                    items: [{
                        region: 'center',
                        border: false,
                        width: 350,
                        height:200,
                        layout: 'fit',
                        items: [
                            left_form
                            ]
                        }/*, {
                        region: 'south',
                        flex: 0,
                        autoScroll: true,
                        width: 350,
                        height:200,
                        layout: 'fit',
                        items: [
                                south_form
                            ]

                    }*/
                    ]
                }, {
                region: 'center',
                id: 'edit_module_tasks_for_use',
                flex: 2,
                autoScroll: true,
                width: 400,
                layout: 'fit',
                items:[
                    center_form
                ]

            }],

            keys: [{
                key: 13,
                handler: function(e){
                    _submit(e);
                }
            }]
        })],

        buttons: [{
            text : '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043'),
            scale: 'medium',
            handler: function(btn, e) {
				Ext.Msg.show({
					title : _text('MN00024'),
					msg : _text('MN00043')+' : '+_text('MSG02039'),
					buttons : Ext.Msg.OKCANCEL,
					fn : function(self){
						if( self == 'ok' ){
							_submit(e);
						}
					}
				});
            }
        },{
            text : '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
            scale: 'medium',
            handler: function(btn, e) {
                this.ownerCt.ownerCt.close();
            }
        }]
    });

    return win;
})()
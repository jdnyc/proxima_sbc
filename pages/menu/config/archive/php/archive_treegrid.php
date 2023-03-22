<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT']."/lib/config.php");
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT']."/lib/DBOracle.class.php");
$user_id = $_SESSION['user']['user_id'];
?>
(function(){

    function setup_win(){  
        var win = new Ext.Window({
                    width : 300,
                    height : 160,
                    layout : 'anchor',
                    modal : true,
                    title : '설정',

                    items : [{
                                id : 'setup_form',
                                xtype : 'form',
                                monitorValid : true,
                                border : false,
                                padding : '5',
                                freame : true,

                                items : [{
                                        xtype: 'hidden',
                                        id:'c_type',
                                        name:'type'
                                },{
                                        xtype: 'hidden',
                                        id:'c_category_id',
                                        name: 'category_id'
                                },{
                                        xtype: 'textfield',
                                        id: 'c_category_title',
                                        name : 'category_title',
                                        anchor: '95%',
                                        fieldLabel: '카테고리',
                                        labelWidth : 70,
                                        disabled : true
                                },{
                                        xtype:'combo',
                                        id: 'c_method',
                                        name : 'method_nm',
                                        anchor : '95%',
                                        mode: 'local',
                                        value: 'A',
                                        forceSelection: true,
                                        editable: false,
                                        allowBlank: false,
                                        fieldLabel: '방법',
                                        labelWidth : 70,
                                        displayField: 'method_nm',
                                        valueField: 'method_val',
                                        triggerAction : 'all',                     
                                        store: new Ext.data.ArrayStore({
                                            fields : ['method_nm', 'method_val'],
                                            data:[
                                                ['자동', 'A'],['수동', 'M'],['미지정', 'N']
                                            ]
                                        }),
                                        listeners : {
                                            select : function(combo, record, index) {
                                                var combo_val = combo.getValue();
                                                if(combo_val == 'M' || combo_val == 'N')
                                                {
                                                    Ext.getCmp('c_period').setDisabled(true);
                                                }
                                                else if(combo_val == 'A')
                                                {
                                                    Ext.getCmp('c_period').setDisabled(false);
                                                }
                                            }
                                        }                                        
                                },{
                                    xtype : 'textfield',
                                    id : 'c_period',
                                    name : 'period',
                                    anchor: '95%',
                                    fieldLabel: '시점',
                                    labelWidth : 70
                                }]
                    }],
                    buttons : [{
                        id : 'ok',
                        scale : 'medium',
                        text : '설정',
                        handler : function(){
                            Ext.getCmp('setup_form').getForm().submit({
                                url : '/pages/menu/config/archive/php/set_up_config.php',
                                params : {
                                    user_id : '<?=$user_id?>'
                                },
                                success : function(form, action) {
                                    Ext.Msg.alert('수정완료', action.result.msg);
                                    win.destroy();
                                    var parent_node = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode().parentNode;
                                    var tree_loader = Ext.getCmp('setup_grid').getLoader();
                                    tree_loader.load(parent_node);
                                },
                                failure: function(form, action) {
                                    switch (action.failureType) {
                                        case Ext.form.Action.CLIENT_INVALID:
                                            Ext.Msg.alert('Failure', 'Form fields may not be submitted with invalid values');
                                            break;
                                        case Ext.form.Action.CONNECT_FAILURE:
                                            Ext.Msg.alert('Failure', 'Ajax communication failed');
                                            break;
                                        case Ext.form.Action.SERVER_INVALID:
                                           Ext.Msg.alert('Failure', action.result.msg);
                                           break;
                                   }
                                }
                            })
                         }   
                    },{
                        id: 'cancel',
                        scale: 'medium',
                        text: '취소',
                        handler : function(btn, e){
                            win.destroy();
                       }                       
                    }]
            }).show();

            return win;
    }
    
    function is_master_win(){
        var sel = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode();
        var catPathTitle = sel.attributes.catPathTitle;
        var category_path = sel.attributes.category_path;
        var win = new Ext.Window({
                    width : 450,
                    height : 160,
                    layout : 'anchor',
                    modal : true,
                    title : '마스터 이관설정',

                    items : [{
                                id : 'master_form',
                                xtype : 'form',
                                monitorValid : true,
                                border : false,
                                padding : '5',
                                freame : true,

                                items : [{
                                        xtype: 'hidden',
                                        id:'c_type',
                                        name:'type'
                                },{
                                        xtype: 'hidden',
                                        id:'c_category_id',
                                        name: 'category_id'
                                },{
                                        xtype: 'textfield',
                                        id: 'c_category_title',
                                        name : 'category_title',
                                        anchor: '95%',
                                        fieldLabel: '카테고리',
                                        labelWidth : 70,
                                        disabled : true
                                },{
                                        xtype : 'checkbox',
                                        id : 'is_master',
                                        name : 'master',
                                        anchor : '95%',
                                        fieldLabel: '마스터 이관',
                                        labelWidth : 70,
                                        listeners: {
                                            check : function(self, checked)
                                            {
                                                if(!checked)
                                                {
                                                    var cat = Ext.getCmp('category');
                                                    cat.disable(true);
                                                }
                                                else if(checked)
                                                {
                                                    var cat = Ext.getCmp('category');
                                                    cat.enable(true);
                                                }
                                            },
                                            afterrender : function(self)
                                            {
                                                var check_value = self.getValue();
                                                if(!check_value)
                                                {
                                                    var cat = Ext.getCmp('category');
                                                    cat.disable(true);
                                                }
                                            }
                                        }
                                },{
                                        xtype: 'treecombo',
                                        id: 'category',
                                        fieldLabel: _text('MN00387'),
                                        treeWidth: '400',
                                        anchor: '95%',
                                        autoScroll: true,
                                        pathSeparator: ' > ',
                                        rootVisible: false,
                                        value: 'category_path',
                                        name: 'tr_category',
                                        listeners: {
                                                render: function(self){										
                                                        var path = category_path;
                                                        if(!Ext.isEmpty(path)){
                                                                path = path.split('/');											
                                                                var catId = path[path.length-1];
                                                                if(path.length <= 1)
                                                                {
                                                                        self.setValue('');
                                                                        self.setRawValue('');
                                                                }
                                                                else
                                                                {
                                                                        self.setValue(catId);												
                                                                        self.setRawValue(catPathTitle);
                                                                }
                                                        }
                                                }
                                        },
                                        loader: new Ext.tree.TreeLoader({
                                                url: '/store/get_categories.php',
                                                baseParams: {
                                                        action: 'get-folders',
                                                        path: category_path
                                                }
                                        }),
                                        root: new Ext.tree.AsyncTreeNode({
                                                id: 0,
                                                text: '전체',
                                                expanded: true
                                        })
                                }]
                    }],
                    buttons : [{
                        id : 'ok',
                        scale : 'medium',
                        text : '설정',
                        handler : function(){
                        
                            var tn = Ext.getCmp('category').treePanel.getSelectionModel().getSelectedNode();
                            var tn_id = '';
                            if(!Ext.isEmpty(tn))
                            {
                                tn_id = tn.attributes.id;
                            }
                            
                            Ext.getCmp('master_form').getForm().submit({
                                url : '/pages/menu/config/archive/php/set_up_config.php',
                                params : {
                                    user_id : '<?=$user_id?>',
                                    target_category_id : tn_id
                        
                                },
                                success : function(form, action) {
                                    Ext.Msg.alert('수정완료', action.result.msg);
                                    win.destroy();
                                    var parent_node = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode().parentNode;
                                    var tree_loader = Ext.getCmp('setup_grid').getLoader();
                                    tree_loader.load(parent_node);
                                },
                                failure: function(form, action) {
                                    switch (action.failureType) {
                                        case Ext.form.Action.CLIENT_INVALID:
                                            Ext.Msg.alert('Failure', 'Form fields may not be submitted with invalid values');
                                            break;
                                        case Ext.form.Action.CONNECT_FAILURE:
                                            Ext.Msg.alert('Failure', 'Ajax communication failed');
                                            break;
                                        case Ext.form.Action.SERVER_INVALID:
                                           Ext.Msg.alert('Failure', action.result.msg);
                                           break;
                                   }
                                }
                            })
                         }   
                    },{
                        id: 'cancel',
                        scale: 'medium',
                        text: '취소',
                        handler : function(btn, e){
                            win.destroy();
                       }                       
                    }]
            }).show();

            return win;
    }
    
    var treegrid = new Ext.ux.tree.TreeGrid({
                        title : 'Archive 설정',
                        id : 'setup_grid',
                        layout : 'fit',
                        
                        columns : [
                            {header : 'Category', dataIndex : 'category_title', width:150},
                            {header : 'Category ID', dataIndex : 'category_id', width:80, hidden:true},
                            {header : '아카이브 방법', dataIndex : 'arc_method', width : 80},
                            {header : '아카이브 시점', dataIndex : 'arc_period', width: 80},
                            {header : '아카이브 후 삭제방법', dataIndex : 'del_method', width: 120},
                            {header : '아카이브 후 삭제시점', dataIndex : 'del_period', width: 120},
                            {header : '리스토어 후 삭제방법', dataIndex : 'res_method', width: 120},
                            {header : '리스토어 후 삭제시점', dataIndex : 'res_period', width: 120},
                            {header : '자동 폐기 방법', dataIndex : 'abr_method', width: 90},
                            {header : '자동 폐기 시점', dataIndex : 'abr_period', width: 90},
                            {header : '마스터 이관', dataIndex : 'is_master', width: 80},
                            {header : '대상 카테고리', dataIndex : 'tr_category_nm', width: 90},
                            {header : '수정일', dataIndex : 'edit_date', width: 110, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
                            {header : '최종수정자', dataIndex : 'edit_user_id', width : 90}
                        ],
                        sm: new Ext.grid.RowSelectionModel({
                            singleSelect : true
                        }),
                        tbar : [
                            {
                                xtype : 'button',
                                text : '아카이브 설정',
                                icon : '/led-icons/Archive.png',
                                handler : function(){
                                    var sel = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode();
                                    if(sel)
                                    {
                                        setup_win();

                                        var c_id = sel.attributes.id;
                                        var c_title = sel.attributes.category_title;
                                        var arc_method = sel.attributes.arc_method;
                                        var arc_period = sel.attributes.arc_period;

                                        Ext.getCmp('c_type').setRawValue('archive');
                                        Ext.getCmp('c_category_id').setRawValue(c_id);
                                        Ext.getCmp('c_category_title').setValue(c_title);
                                        Ext.getCmp('c_method').setValue(arc_method);
                                        Ext.getCmp('c_period').setValue(arc_period);
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
                                    }
                                }
                            },{
                                xtype : 'button',
                                text : '아카이브 후 삭제설정',
                                icon : '/led-icons/delete.png',
                                handler : function(){
                                    var sel = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode();
                                    if(sel)
                                    {
                                        setup_win();

                                        var c_id = sel.attributes.id;
                                        var c_title = sel.attributes.category_title;
                                        var del_method = sel.attributes.del_method;
                                        var del_period = sel.attributes.del_period;

                                        Ext.getCmp('c_type').setRawValue('delete');
                                        Ext.getCmp('c_category_id').setRawValue(c_id);
                                        Ext.getCmp('c_category_title').setValue(c_title);
                                        Ext.getCmp('c_method').setValue(del_method);
                                        Ext.getCmp('c_period').setValue(del_period);
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
                                    }
                                }
                            },{
                                xtype : 'button',
                                text : '리스토어 후 삭제설정',
                                icon : '/led-icons/delete.png',
                                handler : function(){
                                    var sel = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode();
                                    if(sel)
                                    {
                                        setup_win();

                                        var c_id = sel.attributes.id;
                                        var c_title = sel.attributes.category_title;
                                        var del_method = sel.attributes.res_method;
                                        var del_period = sel.attributes.res_period;

                                        Ext.getCmp('c_type').setRawValue('restore');
                                        Ext.getCmp('c_category_id').setRawValue(c_id);
                                        Ext.getCmp('c_category_title').setValue(c_title);
                                        Ext.getCmp('c_method').setValue(res_method);
                                        Ext.getCmp('c_period').setValue(res_period);
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
                                    }
                                }
                            },{
                                xtype : 'button',
                                text : '자동폐기 설정',
                                icon : '/led-icons/bin_closed.png',
                                handler : function(){
                                    var sel = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode();
                                    if(sel)
                                    {
                                        setup_win();

                                        var c_id = sel.attributes.id;
                                        var c_title = sel.attributes.category_title;
                                        var abr_method = sel.attributes.abr_method;
                                        var abr_period = sel.attributes.abr_period;

                                        Ext.getCmp('c_type').setRawValue('abrogate');
                                        Ext.getCmp('c_category_id').setRawValue(c_id);
                                        Ext.getCmp('c_category_title').setValue(c_title);
                                        Ext.getCmp('c_method').setValue(abr_method);
                                        Ext.getCmp('c_period').setValue(abr_period);
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
                                    }
                                }
                            },{
                                xtype : 'button',
                                text : '마스터 이관 설정',
                                icon : '/led-icons/folder_edit.png',
                                handler : function(){
                                     var sel = Ext.getCmp('setup_grid').getSelectionModel().getSelectedNode();
                                    if(sel)
                                    {
                                        is_master_win();

                                        var c_id = sel.attributes.id;
                                        var c_title = sel.attributes.category_title;
                                        var is_master = sel.attributes.is_master;

                                        Ext.getCmp('c_type').setRawValue('master');
                                        Ext.getCmp('c_category_id').setRawValue(c_id);
                                        Ext.getCmp('c_category_title').setValue(c_title);
                                        Ext.getCmp('is_master').setValue(is_master);
                                        
                                    }
                                    else
                                    {
                                        Ext.Msg.alert('오류', '카테고리를 선택해 주세요');
                                    }
                                }
                            }],
                        dataUrl : '/pages/menu/config/archive/php/get_tree_grid_data.php'
                    });

    
    return treegrid;
    
   
})()
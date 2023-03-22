(function(){
    
    var archive_setting = new Ext.Window({
            id : 'archive_setup',
            width : 300,
            height : 400,
            layout : 'anchor',
            modal : true,
            title : '아카이브 설정',
  
            items : [{
                        id : 'archive_setup_form',
                        xtype : 'form',
                        monitorValid : true,
                        border : false,
                        padding : '5',
                        freame : true,
                        
                        items : [{
                                xtype:'combo',
                                anchor : '95%',
                                mode: 'local',
                                value: 'A',
                                forceSelection: true,
                                editable: false,
                                labelAlign : 'right',
                                allowBlank: false,
                                fieldLabel: '아카이브 방법',
                                labelWidth : 70,
                                displayField: 'name',
                                valueField: 'value',
                                triggerAction : 'all',                     
                                store: new Ext.data.ArrayStore({
                                    fields : ['name', 'value'],
                                    data:[
                                        ['자동', 'A'],['수동', 'M'],['미지정', 'N']
                                    ]
                                })                                
                        },{
                            xtype : 'textfield',
                            anchor: '95%',
                            fieldLabel: '아카이브 기간',
                            labelWidth : 70
                        }]
            }],
            buttons : [{
                id : 'ok',
                scale : 'medium',
                text : '설정',
                handler : function(){
                    var form = Ext.getCmp('archive_setup_form').getForm();
                    var val = form.getValues();
                }
            },{
                id: 'cancel',
                scale: 'medium',
                text: '취소'
                
            }]
    });
    
    var treegrid = new Ext.ux.tree.TreeGrid({
                        title : 'Archive 설정',
                        layout : 'fit',
                        
                        columns : [
                      //      {header : 'Category ID', dataIndex : 'category_id', width:80, hidden:true},
                            {header : 'Category', dataIndex : 'category_title', width:150},
                            {header : '아카이브 방법', dataIndex : 'arc_method', width : 80},
                            {header : '아카이브 기간', dataIndex : 'arc_period', width: 80},
                            {header : '삭제 방법', dataIndex : 'del_method', width: 70},
                            {header : '삭제 기간', dataIndex : 'del_period', width: 70},
                            {header : '자동 폐기 방법', dataIndex : 'abr_method', width: 90},
                            {header : '자동 폐기 기간', dataIndex : 'abr_period', width: 90},
                            {header : '수정일', dataIndex : 'edit_date', width: 110, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
                            {header : '최종수정자', dataIndex : 'edit_user_id', width : 90}
                        ],
                        tbar : [
                            {
                                xtype : 'button',
                                text : '아카이브 설정',
                                icon : '/led-icons/Archive.png',
                                handler : function(){
                                    archive_setting.show();
                                }
                            },{
                                xtype : 'button',
                                text : '삭제 설정',
                                icon : '/led-icons/delete.png'
                            },{
                                xtype : 'button',
                                text : '자동 폐기 설정',
                                icon : '/led-icons/bin_closed.png'
                            }
                        ],
                        dataUrl : '/pages/menu/config/archive/php/get_tree_grid_data.php'
                    });

    
    return treegrid;
    
   
})()
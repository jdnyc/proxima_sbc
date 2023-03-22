/**
 * 카테고리 권한설정 윈도우
 * 2018.02.22 Alex
 */
(function(categoryId){
    /**
     * 사용자 그룹 스토어
     */

    var userGroupStore = new Ext.data.JsonStore({
        proxy: new Ext.data.HttpProxy({
            method: 'GET',            
            prettyUrls: false,       
            url: '/store/user/groups.php'
        }),
        autoLoad: true,
        root: 'data',
        fields: [
            'member_group_id', 'member_group_name', 'is_admin'
        ]
    });

    /**
     * 기설정된 카테고리 권한을 불러오는 스토어
     */
    var categoryGrantGridStore = new Ext.data.JsonStore({
        proxy: new Ext.data.HttpProxy({
            method: 'GET',            
            prettyUrls: false,                   
            url: '/store/category/category_grants.php?category_id=' + categoryId
        }),
        autoLoad: true,
        root: 'data',
        fields: [
            'id',
            'category_full_path', 
            'category_name_path', 
            'group_grant', 
            'group.member_group_id',
            'group.member_group_name',
            'group_grant_name'
        ],
        listeners: {
            load: function(self, records, options) {
            }
        }
    });

    /**
     * 기설정된 카테고리 권한을 표출하는 그리드
     */
    var categoryGrantGrid = new Ext.grid.GridPanel({
        layout: 'fit',
        border: false,
        height: 200,
        loadMask: true,
        store: categoryGrantGridStore,
        tbar: [{
            xtype: 'displayfield',
            value: _text('MN02554')
        },'->',{
            xtype: 'button',
            text: _text('MN00390'), // 새로고침
            handler: function(self) {
                categoryGrantGridStore.reload();
            }
        },{
            xtype: 'button',
            text: _text('MN02555'), // 적용
            handler: function(self) {
                var selectedGroups= Ext.getCmp('category_grant_win__group_grid').getSelectionModel().getSelections();
                if(selectedGroups.length === 0) {
                    alert(_text('MSG02006'));//사용자 그룹을 선택해주세요
                    return;
                }
                var selectedRadio = Ext.getCmp('category_grant_win__grant_radiogruop').getValue();
                if(selectedRadio === null) {
                    alert(_text('MSG02007'));//권한을 선택해주세요
                    return;
                }

                var groupIds = [];
                Ext.each(selectedGroups, function(group){
                    groupIds.push(group.get('member_group_id'));
                });                

                var requestData = {
                    category_id: categoryId,
                    member_group_ids: groupIds,
                    grant: selectedRadio.inputValue,
                }

                ajax('/store/category/category_grants.php', 'PUT', requestData, function(r) {
                    categoryGrantGridStore.reload();
                });  
            }
        },{
            xtype: 'button',
            text: _text('MN00034'), // 삭제
            handler: function(self) {
                var selectedRecord = self.findParentByType('grid').getSelectionModel().getSelected();               
                var payload = {
                    id: selectedRecord.get('id')
                };

                ajax('/store/category/category_grants.php', 'DELETE', payload, function(r) {
                    categoryGrantGridStore.reload();
                });                           
            }
        }],
        cm: new Ext.grid.ColumnModel({
            defaults: {
                sortable: false,
                menuDisabled: true
            },
            columns: [
                new Ext.grid.RowNumberer(),
                {header: _text('MN02047'), dataIndex: 'group.member_group_name', width: 100},//사용자 그룹
                {header: _text('MN02192'), dataIndex: 'group_grant_name', width: 100}//권한
            ]
        }),
        sm: new Ext.grid.RowSelectionModel({
            singleSelect: true,
            listeners: {
                rowselect: function(self, i, r) {
                    // 그룹과 권한을 골라준다.
                    var idx = userGroupStore.find('member_group_id', r.get('group.member_group_id'));
                    if(idx === -1) {
                        return;
                    }
                        
                    var groupGrid = Ext.getCmp('category_grant_win__group_grid');
                    groupGrid.getSelectionModel().selectRow(idx, false); 
                    groupGrid.getView().focusRow(idx);   
                    
                    var grantRadioGroup = Ext.getCmp('category_grant_win__grant_radiogruop');
                    grantRadioGroup.setValue(r.get('group_grant'));
                }
            }
        }),
        viewConfig: {
            forceFit: true
        }
    });

    /**
     * 카테고리 권한 설정하는 폼
     */

    var categoryInfoFormStore = new Ext.data.JsonStore({
        id: 'category_grant_win__category_info_form_store',
        proxy: new Ext.data.HttpProxy({
            method: 'GET',            
            prettyUrls: false,       
            url: '/store/category/category_info.php?category_id=' + categoryId
        }),
        //autoLoad: true,        
        fields: [
            'category_id', 'category_full_path', 'category_name_path', 'category_title', 'parent_id', 'content_size'
        ],
        root: 'data'
    });
    var categoryGrantForm = 
        new Ext.form.FormPanel({
            id: 'category_grant_win__category_grant_form',            
            padding: 10,
            labelWidth: 70,
            labelAlign: 'right',
            labelSeparator: '',
            items: [{
                name: 'category_name_path',
                xtype: 'textfield',
                readOnly: true,
                fieldLabel: _text('MN00387')+' '+_text('MN00376'),//'카테고리 경로',
                width: '99%'
            },{
                xtype: 'compositefield',
                fieldLabel: _text('MN02223'),
                items: [{
                    name: 'category_title',
                    xtype: 'textfield', // 카테고리 명
                    flex: 1
                },{
                    xtype: 'button',
                    text: _text('MN00063'), // 변경
                    width: 60,
                    handler: function(self) {
                        
                        var categoryInfoRecord = categoryInfoFormStore.getAt(0);                       
                        var form = Ext.getCmp('category_grant_win__category_grant_form').getForm();                        
                        var parentId = categoryInfoRecord.get('parent_id');
                        var fullPath = categoryInfoRecord.get('category_full_path');

                        var params = {
                            action: 'rename-folder',
                            id: categoryInfoRecord.get('category_id'),
                            parent_id: parentId,
                            newName: form.getValues().category_title,
                            oldName: categoryInfoRecord.get('category_title')
                        };
                        ajaxForm('/store/add_category.php', 'POST', params, function() {
                            categoryInfoFormStore.load({
                                callback: function (records, operation, success) { 
                                    form.loadRecord(categoryInfoFormStore.getAt(0));   
                                    var categoryTree = Ext.getCmp('menu-tree'); 
                                    var node = categoryTree.root.findChild('id', parentId, true);  
                                    node.reload(function() {
                                        categoryTree.selectPath(fullPath);
                                    });                                    
                                }
                            });
                        });
                    }
                }]
            },{
                name: 'content_size',
                xtype: 'textfield',
                readOnly: true,
                fieldLabel: '콘텐츠 크기',
                width: '99%'
            }],
            listeners: {
               afterrender: function(self) {
                    categoryInfoFormStore.load({
                        callback: function (records, operation, success) { 
                            self.getForm().loadRecord(categoryInfoFormStore.getAt(0));        
                        }
                    });
               } 
            }
        });

    /**
     * 카테고리 권한 설정 윈도우
     */
    var groupCheckBoxSelectionModel = new Ext.grid.CheckboxSelectionModel();
    var categoryGrantWin = new Ext.Window({
        title: _text('MN02039'),
        width: 600,
        height: 700,
        layout: 'border',
        modal: true,
        //resizable: false,
        listeners: {
            beforerender: function() {
                
            }
        },
        items: [{
            region: 'north',
            layout: 'fit',
            height: 105,
            items: [
                categoryGrantForm
            ]
        },{
            region: 'center',
            height: 300,
            layout: {
                type: 'hbox',
                padding: 10,
                align:'stretch'
            },
            items: [{
                flex: 1,
                xtype: 'fieldset',                
                minWidth: 150,
                title: _text('MN02047'),
                layout: 'fit',
                items: [{
                    xtype: 'grid',
                    id: 'category_grant_win__group_grid',
                    border: false,
                    loadMask: true,
                    store: userGroupStore,
                    sm: groupCheckBoxSelectionModel,
                    cm: new Ext.grid.ColumnModel({
                        defaults: {
                            sortable: false,
                            menuDisabled: true
                        },
                        columns: [
                            groupCheckBoxSelectionModel,                            
                            {header: _text('MN00117'), dataIndex: 'member_group_name', width: 100} 
                        ]
                    }),
                    viewConfig: {
                        forceFit: true
                    }
                }]
            },{
                xtype: 'fieldset',
                minWidth: 150,
                flex: 1,
                title: _text('MN02048'),
                layout: 'fit',
                items :	[{
                    xtype: 'radiogroup',
                    id: 'category_grant_win__grant_radiogruop',
					hideLabel: true,
					width: 180,
					columns: 1,
                    items: [
                        {boxLabel: _text('MN02037'), name: 'group_grant', inputValue: 4 },//'숨김'
                        {boxLabel: _text('MN02035'), name: 'group_grant', inputValue: 0 },//'권한 없음'
                        {boxLabel: _text('MN00035'), name: 'group_grant', inputValue: 1 },//'읽기'
                        {boxLabel: _text('MN00035')+' / '+_text('MN00041')+' / '+_text('MN00043'), name: 'group_grant', inputValue: 2 },//'읽기 / 생성 / 수정'
                        {boxLabel: _text('MN00035')+' / '+_text('MN00041')+' / '+_text('MN00043')+' / '+_text('MN02036')+' / '+_text('MN00034'), name: 'group_grant', inputValue: 3 },//'읽기 / 생성 / 수정 / 이동 / 삭제'
                        {boxLabel: _text('MN00146'), name: 'group_grant', inputValue: 5} //관리자 권한
                    ],
					listeners:{
						change: function(self, checked){
							
						}
					}
                }]
            }]
        },{
            region: 'south',
            layout: 'fit',
            height: 300,
            items: [
                categoryGrantGrid
            ]
        }],
        buttonAlign: 'right',
        buttons: [{
            text: _text('MN00031'),
            listeners: {
                click: function(){
                    categoryGrantWin.close();
                }
            }
        }]
    });

    return categoryGrantWin;

})
(function() {

    var customSearchForm = new Ext.form.FormPanel({
        frame: true,
        height: 200,
        items: [{
            xtype: 'textfield',
            name: 'name',
            fieldLabel: '이름',
            anchor: '98%',
            listeners: {
                afterrender: function(self) {
                    self.focus(false, 500);
                }
            }
        },{
            xtype: 'textfield',
            fieldLabel: '카테고리',
            name: 'categoryPathName',
            disabled: true,
            anchor: '98%'
        },{
            xtype: 'textfield',
            name: 'keyword',
            fieldLabel: '검색어',            
            disabled: true,
            anchor: '98%'
        },{
            xtype: 'combo',
            fieldLabel: '콘텐츠 상태',
            anchor: '98%',
            triggerAction: 'all',
            typeAhead: true,
            editable: false,
            store: new Ext.data.JsonStore({
				proxy: new Ext.data.HttpProxy({
                    method: 'GET',       
					prettyUrls: false,       
					url: '/store/content/get_content_status.php?filter=0,2,3,4,5'
				}),
				root: 'data',
				idProperty: 'code',
				fields: [
					'code', 'name'
				]
			}),
			value: '없음',
			displayField: 'name',
			valueField: 'code'
        },{
            xtype: 'textfield',
            hidden: true,
            name: 'keyword',
            fieldLabel: '날짜(일)',
            disabled: true,
            anchor: '98%'
        }]
    });

    var customSearchSaveWindow = new Ext.Window({
        id: 'custom_search__save_window',
        title: '새 검색 양식',
        width: 370,		
        autoHeight: true,
        modal: true,
        resizable: false,
        items: [customSearchForm],
        saveData: {},
        listeners: {
            afterrender: function() {                
                
                var categoryTab = Ext.getCmp('tree-tab').getActiveTab();
                var selectedNode = categoryTab.getSelectionModel().getSelectedNode();

                var loadData = {                    
                    keyword: Ext.getCmp('search_input').getValue(),
                    categoryPathName: selectedNode.getPath('text')
                }

                customSearchForm.getForm().setValues(loadData);
            }
        },
        buttons: [{
            text: '확인',
            handler: function(b, e){                
                Ext.Ajax.request({
                    url: '/store/search/save_custom_search.php',
                    jsonData: customSearchSaveWindow.saveData,
                    callback: function(opts, success, response){
						try {
							if(success) {
								var r = Ext.decode(response.responseText);
								if (r.success) {
									Ext.Msg.show({
										title: '확인',
										msg: '새 검색 양식이 저장되었습니다.',
										buttons: Ext.Msg.OK,
										fn: function(){
											customSearchSaveWindow.destroy();										
										}
									});
								} else {				
									Ext.Msg.alert('서버 오류', r.msg);								
								}
							} else {
								Ext.Msg.alert('서버 오류', response.statusText);								
							}
						} catch(e) {
							Ext.Msg.alert(e['name'], e['message']);
						}						
					}
                });
            }
        },{
            text: '취소',
            handler: function(b, e){
                b.ownerCt.ownerCt.close();
            }
        }]			

    });

    customSearchSaveWindow.show();

})()
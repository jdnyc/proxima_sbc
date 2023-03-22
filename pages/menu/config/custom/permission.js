// (function(){
// /*************************************
//  * 1. 설정 -> 권한관리 
//  * 
//  * 
//  * 
//  */

(function () {
   
    var main_store = new Ext.data.JsonStore({
      url: '/pages/menu/config/custom/get_permission.php',
      root: 'data',
      totalProperty: 'total',
      fields: ['id', 'code', 'code_path', 'description', 'parent_id', 'p_depth', 'use','groups','groups_name']
    });
    main_store.load();
    function showWin(action, title, button) {
        Ext.Ajax.request({
            url: '/pages/menu/config/custom/show_permission.php',
            params: {
                action: action,
                title: title,
                button: button
  
            },
            callback: function(self, success, response){
                try {
                    var r = Ext.decode(response.responseText);
                    r.show();
                }catch(e) {
                    Ext.Msg.alert(_text('MN00022'),e);
                }
            }
        });
    }

    function edit_permission(action, url){
      var btn_text= '';
      var disable_permission;
      if(action == _text('MN02031')){
        disable_permission = false;
        btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-plus" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00033');
      } else {
        disable_permission = true;
        btn_text = '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00043');
      }
      new Ext.Window({
        id: 'permission_edit_win',
        title: action,
        width: 600,
        height: 500,
        modal: true,
        layout: 'fit',
        buttonAlign: 'center',
        items: {
          id: 'permission_edit_form',
          cls: 'change_background_panel',
          xtype: 'form',
          defaults: {
            anchor: '100%'
          },
          url: url,
          frame: true,
          items: [{
            xtype: 'hidden',
            value: id,
            name: 'id',
          },{
            xtype: 'textfield',
            name: 'code',
            fieldLabel: _text('MN02030'),
            disabled: disable_permission
          },{
            xtype: 'textfield',
            name: 'code_path',
            fieldLabel: 'Code_path'
          },{
            xtype: 'textarea',
            name: 'description',
            fieldLabel: '설명'
          }
          ,{
            xtype: 'combo',
            hidden: true,
            name: 'member_group_name',
            fieldLabel: '사용자 그룹',
            store: new Ext.data.JsonStore({
                url: '/pages/menu/config/custom/get_combo.php',
                root: 'data',
                totalProperty: 'total',
                fields: [ 'member_group_id','member_group_name']
            }),
            hiddenName: 'member_group_id',
            allowBlank: false,
            valueField: 'member_group_id',
            displayField: 'member_group_name',
            typeAhead: true,
            triggerAction: 'all',
            editable: false
          },{
            xtype: 'checkbox',
            name: 'use',
            status: 'use',
            fieldLabel: '사용 여부'
          },
            {
             xtype: 'textfield',
             name: 'p_depth',
             fieldLabel: 'p_depth'
            }
          
          
          ,{
              xtype: 'fieldset',
              id: 'member_group_id',
              title: _text('MN00111'),
              style: {
                  background: 'white',
              },
              flex: 1,
              labelWidth: 30,
              height: 200,
              items: {
                //   xtype: 'checkboxgroup',
                //   id: 'member_group_id',
                //   name: 'group',
                //   columns: 3,
                //   listeners: {

                    // beforerender: function(self) {
                    //     var s =  [];
                    //     var checkd = false;

                    //     Ext.each()
                    // }
                //   }
              }

          }
          ],
          listeners: {
            afterrender: function(self) {
              if( url == '/pages/menu/config/custom/edit_permission.php'){
                var sm = Ext.getCmp('main_grid').getSelectionModel();
                var rec = sm.getSelected();
                self.getForm().loadRecord(rec);
                var use = rec.json['use'];
                var name = rec.json['member_group_name'];
                use = (use == 1) ? true : false;
                Ext.getCmp('permission_edit_form').getForm().findField('use').setValue(use);
                Ext.getCmp('permission_edit_form').getForm().findField('member_group_id').setValue(name);
              }
            }
          }
        },
        buttons: [{
          text: '',
          scale: 'medium',
          handler: function(){
            Ext.getCmp('permission_edit_form').getForm().submit({
              success: function(from, action){
                if(action.result.success){
               
                }
                else 
                {
                  Ext.Msg.alert(_text('MN01039'), action.result.errormsg); //오류
                }
                Ext.getCmp('main_grid').getSotre().reload();
                Ext.getCmp('permission_edit_win').close();
              },
              failure: function(form, action){
                Ext.Msg.alert(_text('MN01039'), action.result.errormsg); //오류
                Ext.getCmp('permission_edit_win').close();
              }
            });
          }
        },{
          text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00004'),
          scale: 'medium',
          handler: function(){
            Ext.getCmp('permission_edit_win').close();
          }
        }]
      }).show();
    }
  
  
  
    return {
      xtype: 'panel',
      title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">권한 관리</span></span>',
      cls: 'grid_title_customize',
      border: false,
      layout: 'fit',
      items: [{
        xtype: 'grid',
        id: 'main_grid',
        cls: 'proxima_customize',
        stripeRows: true,
        store: main_store,
        autoWidth: true,
        autoScroll: true,
        border: false,
        viewConfig: {
          forceFit: true
        },
        sm: new Ext.grid.RowSelectionModel({
          singleSelect: true
        }),
        tbar: [{
          //추가
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00033') + '"><i class="fa fa-plus-circle" style="font-size:13px;color:white;"></i></span>',
          handler: function (b, e) {
            // edit_permission(_text('MN02031'), '/pages/menu/config/custom/add_permission.php');
            showWin('add', 'MN02025', 'MN00033');
          }
        }, {
          //수정
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00043') + '"><i class="fa fa-edit" style="font-size:13px;color:white;"></i></span>',
          handler: function () {
            var check= Ext.getCmp('main_grid').getSelectionModel().hasSelection();
            if(check)
            {
                showWin('edit', 'MN02027', 'MN00043');
              //edit_permission(_text('MN00043'), '/pages/menu/config/custom/edit_permission.php');
            }
            else 
            {
              Ext.Msg.alert(_text('MN00043'), _text('MSG01005'));
            }
          }
        }, {
          //삭제
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px" title="' + _text('MN00034') + '"><i class="fa fa-minus-circle" style="font-size:13px;color:white;"></i></span>',
          handler: function () {
            var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
            if(check)
            {
              var id = Ext.getCmp('main_grid').getSelectionModel().getSelected().json['id'];
     
              Ext.Msg.show({
                //삭제
                text: _text('MN00034'),
                msg: _text('MSG02039'),
                buttons: {
                  yes: true,
                  no: true
                },
                fn: function(btn){
                  if(btn == 'yes')
                  {
                    Ext.Ajax.request({
                      url: '/pages/menu/config/custom/delete_permission.php',
                      params: {
                        id: id
                      },
                      callback: function(opts, success, response){
                        if(success)
                        {
                          main_store.reload();
                          try
                          {
                            var result = Ext.decode(response.responseText);
                            if(!result.success)
                            {
                              Ext.Msg.show({
                                //오류
                                title: _text('MN01039'),
                                msg: result.msg,
                                icon: Ext.Msg.ERROR,
                                buttons: Ext.Msg.OK
                              });
                            }
                          }
                          catch(e)
                          {
                            Ext.Msg.show({
                              title: _text('MN01039'),
                              msg: e['message'],
                              icon: Ext.Msg.ERROR,
                              buttons: Ext.Msg.OK
                            });
                          }
                        }
                        else
                        {
  
                        }
                      }
                    });
                  }
                }
              });
            }
            else 
            {
              Ext.Msg.alert(_text('MN00034'), _text('MSG01005')); 
            }
          }
        }, {
          //새로고침
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00390') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
          handler: function(b, e) {
              main_store.reload();
          }
        }, '->', {
          xtype: 'textfield',
          id: 'search_word',
          listeners: {
            specialkey: function (field, e) {
              var search_word = Ext.getCmp('search_word').getValue();
              main_store.reload({
                params: {
                  search_word: search_word
                }
              });
            }
          }
        }, {
          //검색
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;top:1px;" title="' + _text('MN00037') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
          handler: function (b, e) {
            var search_word = Ext.getCmp('search_word').getValue();
            main_store.reload({
              params: {
                search_word: search_word
              }
            });
          }
        }],
        listeners: {
          rowdblclick: function (self, index, e) {
            var check = Ext.getCmp('main_grid').getSelectionModel().hasSelection();
            if (check) 
            {
              showWin('edit', 'MN02027', 'MN00043');
            }
            else {
              Ext.Msg.alert(_text('MN00043'), _text('MSG01005'));
            }
          }
        },
        colModel: new Ext.grid.ColumnModel({
          defaultSortable: true,
          defaults: {
            sortable: true,
            menuDisabled: true
          },
          columns: [
            new Ext.grid.RowNumberer(),
            {
              header: '코드 ID',
              //hidden: true,
              sortable: true,
              width: 20,
              dataIndex: 'id',
              align: 'center'
            },

            {   //코드
              header: _text('MN02030'),
              sortable: true,
              dataIndex: 'code',
              width: 100
            }, {
              header: '코드 경로',
              sortable: true,
              dataIndex: 'code_path',
            }, {
              header: '설명',
              sortable: true,
              dataIndex: 'description',
            }, {
                header: '사용자 그룹',
                sortable: true,
                dataIndex: 'groups_name',
                
            }, {
                header: '사용자 그룹 코드',
                sortable: true,
                hidden: true,
                dataIndex: 'groups',
                width: 40,
                align: 'center'
            }, {
              header: '상위 ID',
              //hidden: true,
              sortable: true,
              width: 25,
              dataIndex: 'parent_id',
              align: 'center'
            },{
                header: '사용 여부',
                sortable: true,
                dataIndex: 'use',
                width: 25,
                align: 'center',
                renderer: function (value) {
                  if (value == 1) {
                    return 'Y';
                  } else {
                    return 'N';
                  }
    
                }
            },  {
                header: '경로 레벨(p_depth)',
                sortable: true,
                dataIndex: 'p_depth',
                align: 'center',
                width: 40
            }
          ]
        })
      }]
    };
  
  })()
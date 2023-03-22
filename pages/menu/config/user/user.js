(function() {
  Ext.ns('Ariel.config');
  // 2010-12-01 네비게이션 추가 by CONOZ
  var myPageSize = 50;
  var btnWidth = 30;

  Ariel.config.User = Ext.extend(Ext.Panel, {
    id: 'config_user',
    //>>title: '사용자 관리',
    //cls: 'proxima_customize',
    title: _text('MN00191'),
    width: 700,
    height: 300,
    layout: 'fit',

    renderStatus: function(v, metaData, r, rowIdx, colIdx, s) {
      switch (v) {
        case 'H':
          return '<font color=green>휴직';
          break;
        case 'C':
          return '<font color=blue>재직';
          break;

        case 'T':
          return '<font color=gray>퇴직';
          break;

        case 'K':
          return '<font color=red>직위해제';
          break;

        default:
          return '';
          break;
      }
    },

    initComponent: function(config) {
      Ext.apply(this, config || {});
      var that = this;

      this.store = new Ext.data.JsonStore({
        url: '/pages/menu/config/user/php/get.php',
        remoteSort: true,
        sortInfo: {
          field: 'user_nm',
          direction: 'ASC'
        },
        idProperty: 'member_id',
        root: 'data',
        fields: [
          'member_id',
          'user_id',
          'user_nm',
          'group',
          'occu_kind',
          //'job_rank',
          'job_position',
          'job_duty',
          'dep_tel_num',
          'breake',
          'dept_nm',
          'phone',
          'email',
          'lang',
          'top_menu_mode',
          'org_id',
          'org',
          'action_icon_slide_yn',
          { name: 'created_date', type: 'date', dateFormat: 'YmdHis' },
          { name: 'updated_date', type: 'date', dateFormat: 'YmdHis' },
          { name: 'last_login_date', type: 'date', dateFormat: 'YmdHis' },
          { name: 'hired_date', type: 'date', dateFormat: 'Ymd' },
          { name: 'retire_date', type: 'date', dateFormat: 'Ymd' },
          'dept'
        ],
        listeners: {
          exception: function(self, type, action, opts, response, args) {
            try {
              var r = Ext.decode(response.responseText);
              if (!r.success) {
                //>>Ext.Msg.alert('정보', r.msg);
                console.log(r);
                Ext.Msg.alert(_text('MN00023'), r.msg);
              }
            } catch (e) {
              //>>Ext.Msg.alert('디코드 오류', e);
              console.log(e);
              Ext.Msg.alert(_text('MN00022'), e);
            }
          }
        }
      });

      this.items = new Ext.grid.GridPanel({
        id: 'user_list',
        cls: 'proxima_customize',
        stripeRows: true,
        border: false,
        store: this.store,
        autoWidth: true,
        loadMask: true,
        viewConfig: {
          emptyText: _text('MSG00148'), //'결과 값이 없습니다.',
          forceFit: true
        },
        listeners: {
          viewready: function(self) {
            // 2010-12-01 네비게이션 추가 by CONOZ
            self.getStore().load({
              params: {
                start: 0,
                limit: myPageSize
              }
            });
          },
          ////////////// 오른쪽 클릭 시 이벤트 발생
          rowcontextmenu: function(self, rowIndex, e) {
            var cell = self.getSelectionModel();

            if (!cell.isSelected(rowIndex)) {
              cell.selectRow(rowIndex);
            }

            e.stopEvent();
            if (self.contextmenu) {
              self.contextmenu.showAt(e.getXY());
            }
          },
          rowdblclick: function(self, rowIndex) {
            var sm = Ext.getCmp('user_list').getSelectionModel();
            if (sm.hasSelection()) {
              var records = Ext.getCmp('user_list')
                .getSelectionModel()
                .getSelected();

              that.request({
                action: 'edit',
                member_id: records.get('member_id')
              });
            } else {
              Ext.Msg.alert(_text('MN00023'), _text('MSG00060'));
            }
          }
        },
        /*contextmenu: new Ext.menu.Menu({
                  items:[
                      {
                          name : 'pw_init',
                          text: '패스워드 초기화'
                      }],
                  listeners : {
                      itemclick : function(self,e)
                      {
                          var selected_memId = Ext.getCmp('user_list').getSelectionModel().getSelected().data['member_id'];
                          var selected_useId =  Ext.getCmp('user_list').getSelectionModel().getSelected().data['user_id'];
                          var selected_name = Ext.getCmp('user_list').getSelectionModel().getSelected().data['name'];

                          Ext.Ajax.request({
                              url: '/pages/menu/config/user/user.php',
                              params: {
                                  action: 'email_check',
                                  member_id : selected_memId,
                                  user_id : selected_useId
                              },
                              callback: function(opt, success, response)
                              {
                                  var res = Ext.decode(response.responseText);
                                  var suc = res.success;
                                  var msg = res.msg;

                                  if(suc)
                                  {
                                      var random = parseInt(Math.random()*10000)+10000;

                                      Ext.Ajax.request({
                                              url: '/pages/menu/config/user/user.php',
                                              params: {
                                                  action: 'update_pw',
                                                  member_id : selected_memId,
                                                  user_id : selected_useId,
                                                  name: selected_name,
                                                  password: random
                                              },
                                              callback: function(opt, success, response)
                                              {
                                                  var res2 = Ext.decode(response.responseText);
                                                  var msg2 = res2.msg;

                                                  Ext.Msg.alert('성공',msg2);
                                              }

                                      });

                                  }
                                  else
                                  {
                                      Ext.Msg.alert('오류',msg);
                                  }
                              }

                          });

                      }
                  }
              }),*/
        colModel: new Ext.grid.ColumnModel({
          defaults: {
            sortable: true
          },
          columns: [
            new Ext.grid.RowNumberer(),
            { header: 'number', dataIndex: 'member_id', hidden: 'true' },
            {
              header: '<center>' + _text('MN00195') + '</center>',
              dataIndex: 'user_id',
              width: 90
            },
            { header: _text('MN00196'), dataIndex: 'user_nm', align: 'center' },
            {
              header: '기관',
              dataIndex: 'org_id',
              align: 'center',
              width: 120,
              renderer: function(value, metaData, record) {
                if (record.get('org').length !== 0)
                  return record.get('org').code_itm_nm;
                return value;
              }
            },
            {
              header: _text('MN00181'),
              dataIndex: 'dept_nm',
              align: 'center',
              width: 120,
              renderer: function(value, metaData, record) {
                if (record.get('dept').length !== 0)
                  return record.get('dept').code_itm_nm;
                return value;
              }
            },
            {
              header: '직종',
              dataIndex: 'occu_kind',
              align: 'center',
              width: 80,
              hidden: 'true'
            },
            {
              header: _text('MN00260'),
              dataIndex: 'job_position',
              align: 'center',
              width: 80,
              hidden: 'true'
            },
            {
              header: '직무',
              dataIndex: 'job_duty',
              align: 'center',
              width: 80,
              hidden: 'true'
            },
            {
              header: '<center>' + _text('MN00111') + '</center>',
              dataIndex: 'group',
              width: 300,
              sortable: false
            },
            {
              header: '재직구분',
              dataIndex: 'breake',
              align: 'center',
              renderer: this.renderStatus,
              hidden: 'true'
            },
            {
              header: _text('MN00331'),
              dataIndex: 'hired_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120,
              hidden: 'true'
            },
            {
              header: _text('MN00332'),
              dataIndex: 'retire_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120,
              hidden: 'true'
            },
            {
              header: _text('MN00102'),
              dataIndex: 'created_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120
            },
            {
              header: '수정일자',
              dataIndex: 'updated_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120
            },
            {
              header: _text('MN00104'),
              dataIndex: 'last_login_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120,
              hidden: true
            },
            {
              header: _text('MN00333'),
              dataIndex: 'dep_tel_num',
              align: 'center',
              hidden: true
            },
            {
              header: '<center>' + _text('MN02127') + '</center>',
              dataIndex: 'email',
              width: 200
            },
            { header: _text('MN02208'), dataIndex: 'phone', align: 'center' },
            { header: _text('MN02190'), dataIndex: 'lang', align: 'center' }
          ]
        }),
        // 2010-12-16 사용자 검색 기능 추가 by CONOZ
        tbar: [
          {
            xtype: 'combo',
            id: 'search_f',
            width: 130,
            triggerAction: 'all',
            editable: false,
            mode: 'local',
            store: [
              ['s_user_id', _text('MN00195')],
              ['s_name', _text('MN00196')],
              ['s_user_dept', _text('MN00181')],
              ['s_group', _text('MN00111')] //'그룹'
            ],
            value: 's_name',
            listeners: {
              select: function(self, r, i) {
                //get(2) : datefield
                //get(3) : textfield
                //get(4) : 그룹별 combo
                //get(5) : 부서별 combo
                if (i == 3) {
                    self.ownerCt.get(2).setVisible(false);
                    self.ownerCt.get(3).setVisible(false);
                    self.ownerCt.get(4).setVisible(true);
                    self.ownerCt.get(5).setVisible(false);
                } else if(i === 2){
                    self.ownerCt.get(2).setVisible(false);
                    self.ownerCt.get(3).setVisible(false);
                    self.ownerCt.get(4).setVisible(false);
                    self.ownerCt.get(5).setVisible(true);
                }else {
                    self.ownerCt.get(2).setVisible(false);
                    self.ownerCt.get(3).setVisible(true);
                    self.ownerCt.get(4).setVisible(false);
                    self.ownerCt.get(5).setVisible(false);
                }
              }
            }
          },
          ' ',
          {
            hidden: true,
            xtype: 'datefield',
            id: 'search_v1',
            width: 90,
            format: 'Y-m-d',
            listeners: {
              render: function(self) {
                //self.setValue(new Date());

                self.setMaxValue(new Date());
              }
            }
          },
          {
            xtype: 'textfield',
            width: 120,
            id: 'search_v2',
            listeners: {
              specialKey: function(self, e) {
                var w = self.ownerCt.ownerCt;
                if (e.getKey() == e.ENTER && self.isValid()) {
                  e.stopEvent();
                  w.doSearch(w.getTopToolbar(), this.store);
                }
              }
            }
          },
          {
            xtype: 'combo',
            width: 150,
            //fieldLabel : '그룹 별 필터',
            editable: false,
            id: 'groupfilter',
            typeAhead: true,
            triggerAction: 'all',
            hidden: true,
            editable: false,
            displayField: 'member_group_name',
            valueField: 'member_group_id',
            hiddenName: 'member_group_id',
            hiddenValue: 'member_group_name',
            store: new Ext.data.JsonStore({
              url: '/pages/menu/config/user/php/get_group.php',
              remoteSort: true,
              root: 'data',
              sortInfo: {
                field: 'member_group_id',
                direction: 'DESC'
              },
              idProperty: 'member_group_id',
              root: 'data',
              fields: [
                'member_group_id',
                'member_group_name',
                'description',
                'is_admin'
              ]
            }),
            listeners: {}
          },
          {
            //부서별 콤보 필터
            width:150,
            xtype:'combo',
            hidden:true,
            allowBlank: false,
            editable: false,
            mode: "local",
            displayField: "code_itm_nm",
            //valueField: "code_itm_code",
            //hiddenValue: "code_itm_code",
            valueField: "code_itm_nm",
            hiddenValue: "code_itm_nm",
            typeAhead: true,
            triggerAction: "all",
            lazyRender: true,
            store: new Ext.data.JsonStore({
                restful: true,
                proxy: new Ext.data.HttpProxy({
                    method: "GET",
                    url: '/api/v1/open/data-dic-code-sets/' + 'DEPT' + '/code-items',
                    type: "rest"
                }),
                root: "data",
                fields: [
                { name: "code_itm_code", mapping: "code_itm_code" },
                { name: "code_itm_nm", mapping: "code_itm_nm" },
                { name: "code_path", mapping: "code_path" },
                { name: "id", mapping: "id" }
                ],
                listeners: {
                    load: function (store, r, option) {
                        /**
                        * 스토어를 비운다음 부모 노드를 코드로 찾아서
                        * 코드의 자식 코드 아이템들만 모은다음 
                        * 다시 스토어에 넣어준다.,
                        */
                        var dpCode = 'c104176100'
                        store.remove(r);
                        
                        var deleteIndexArray = [];
                        Ext.each(r, function (code) {
                        var codeId = code.get('code_itm_code');
                        if (codeId == dpCode) {
                            var parentCodePath = code.get('code_path');
                            Ext.each(r, function (code2, i) {
                            var id = code2.get('id');
                            var checkCodePath = parentCodePath + '/' + id;
                            if (checkCodePath != code2.get('code_path')) {
                                deleteIndexArray.push(i);
                            }
                            });
                        };
                        });

                        Ext.each(deleteIndexArray, function (deleteArrayIndex) {
                        r.remove(r[deleteArrayIndex]);
                        });

                        store.add(r);

                    
                    },
                    exception: function (self, type, action, opts, response, args) {
                        try {
                        var r = Ext.decode(response.responseText, true);

                        if (!r.success) {
                            Ext.Msg.alert(_text("MN00023"), r.msg);
                        }
                        } catch (e) {
                        Ext.Msg.alert(_text("MN00023"), r.msg);
                        }
                    }
                }
            }),
            listeners: {
                afterrender: function (self) {
                    self.getStore().load({
                        params: {
                            is_code: 1
                        }
                    });
                },
                select: function (self, record, idx) {
                    //self.setValue(record.get("code_itm_code"));
                    self.setValue(record.get("code_itm_nm"));
                }
            }
          },
          ' ',
          {
            xtype: 'button',
            width: 30,
            cls: 'proxima_button_customize',
            //>>text: '조회',
            text:
              '<span style="position:relative;" title="' +
              _text('MN00037') +
              '"><i class="fa fa-search" style="font-size:13px;color:white"></i></span>',
            handler: function(b, e) {
              var w = b.ownerCt.ownerCt;
              w.doSearch(w.getTopToolbar(), this.store);
            }
          },
          {
            //icon: '/coquette/png/16x16/add_user.png',
            //>>text: '추가',
            width: btnWidth,
            cls: 'proxima_button_customize',
            text:
              '<span style="position:relative;" title="' +
              _text('MN00033') +
              '"><i class="fa fa-user-plus" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              this.request({ action: 'add' });
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/add_user.png',
            //>>text: '수정',
            width: btnWidth,
            cls: 'proxima_button_customize',
            text:
              '<span style="position:relative;" title="' +
              _text('MN00043') +
              '"><i class="fa fa-edit" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              var sm = Ext.getCmp('user_list').getSelectionModel();
              if (sm.hasSelection()) {
                var r = sm.getSelected();
                this.request({
                  action: 'edit',
                  member_id: r.get('member_id')
                });
              } else {
                Ext.Msg.alert(_text('MN00023'), _text('MSG00060'));
              }
            },
            scope: this
          },
          {
            hidden: {{$is_hidden_delete_button}},
            //icon: '/coquette/png/16x16/delete_user.png',
            //>>text: '삭제',
            width: btnWidth,
            cls: 'proxima_button_customize',
            text:
              '<span style="position:relative;" title="' +
              _text('MN00034') +
              '"><i class="fa fa-user-times" style="font-size:13px;color:white"></i></span>',
            handler: function(btn, e) {
              var sm = Ext.getCmp('user_list').getSelectionModel();
              if (sm.hasSelection()) {
                var r = sm.getSelected();
                Ext.Msg.show({
                  icon: Ext.Msg.QUESTION,
                  buttons: Ext.Msg.OKCANCEL,
                  title: _text('MN00034'),
                  msg: _text('MSG00155') + ' "' + r.get('user_nm') + '"?',

                  fn: function(buttonId, text, opts) {
                    var p = {
                      action: 'del',
                      user_id: r.get('user_id'),
                      member_id: r.get('member_id')
                    };
                    if (buttonId == 'ok') this.request(p);
                  },
                  scope: this
                });
              } else {
                Ext.Msg.alert(_text('MN00023'), _text('MSG00061'));
              }
            },
            scope: this
          },
          {
            //icon: '/led-icons/key.png',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00186') +
              '"><i class="fa fa-key" style="font-size:13px;color:white;"></i></span>', //'비밀번호 변경'
            handler: function(btn, e) {
              var sm = Ext.getCmp('user_list').getSelectionModel();
              if (sm.hasSelection()) {
                var r = sm.getSelected();
                new Ariel.user.modifiy({
                  modal: true,
                  width: 300,
                  height: 150,
                  resizable: false,
                  layout: 'fit',
                  buttonAlign: 'center',
                  title: _text('MN00186'), //'비밀번호 변경'
                  contents: r,
                  type: 'passwordChange'
                }).show();
              } else {
                Ext.Msg.alert(_text('MN00023'), _text('MSG01003')); //먼저 사용자를 선택해주세요.
              }
            }
          },
          {
            //icon: '/led-icons/arrow_refresh.png',
            //>>text: '새로고침',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00139') +
              '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              Ext.getCmp('user_list')
                .getStore()
                .load({
                  params: {
                    start: 0,
                    limit: myPageSize
                  }
                });
            }
          },
          '-',
          {
            //hidden : $lv_check_hidden_upload,
            cls: 'proxima_button_customize',
            hidden: true,
            width: 30,
            text:
              '<span style="position:relative;top:1px;" title="' +
              _text('MN00399') +
              '"><i class="fa fa-upload" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              var win = new Ext.Window({
                title: _text('MN00399'),
                width: 450,
                top: 50,
                height: 110,
                modal: true,
                layout: 'fit',
                items: [
                  {
                    xtype: 'form',
                    fileUpload: true,
                    border: false,
                    frame: true,
                    id: 'fileAttachuploadForm',
                    defaults: {
                      labelSeparator: '',
                      labelWidth: 30,
                      anchor: '95%',
                      style: {
                        'padding-top': '5px'
                      }
                    },
                    items: [
                      {
                        xtype: 'fileuploadfield',
                        hidden: true,
                        id: 'fileAttachUpload',
                        name: 'FileAttach',
                        listeners: {
                          fileselected: function(self, value) {
                            Ext.getCmp('fileAttachFakePath').setValue(value);
                          }
                        }
                      },
                      {
                        xtype: 'compositefield',
                        fieldLabel: _text('MN01045'),
                        items: [
                          {
                            xtype: 'textfield',
                            id: 'fileAttachFakePath',
                            allowBlank: false,
                            readOnly: true,
                            flex: 1
                          },
                          {
                            xtype: 'button',
                            text: _text('MN02176'),
                            listeners: {
                              click: function(btn, e) {
                                $(
                                  '#' +
                                    Ext.getCmp(
                                      'fileAttachUpload'
                                    ).getFileInputId()
                                ).click();
                              }
                            }
                          }
                        ]
                      }
                    ],
                    buttonsAlign: 'left',
                    buttons: [
                      {
                        text:
                          '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' +
                          _text('MN00046'), //'저장'
                        scale: 'small',
                        handler: function(b, e) {
                          var regist_form = Ext.getCmp(
                            'fileAttachuploadForm'
                          ).getForm();
                          if (!regist_form.isValid()) {
                            Ext.Msg.alert(_text('MN00023'), _text('MSG01006'));
                            return;
                          }
                          var extension_arr = ['XLS', 'XLSX'];
                          var upload_file = Ext.getCmp(
                            'fileAttachUpload'
                          ).getValue();
                          var filename_arr = upload_file.split('.');
                          if (
                            extension_arr.indexOf(
                              filename_arr[1].toUpperCase()
                            ) === -1
                          ) {
                            Ext.Msg.alert(
                              _text('MN00023'),
                              _text('MN00309') +
                                ' : ' +
                                extension_arr.join(', ')
                            );
                            return;
                          }
                          regist_form.submit({
                            url: '/pages/menu/config/user/upload_user_data.php',
                            params: {},
                            success: function(form, action) {
                              var r = Ext.decode(action.response.responseText);
                              if (r.success) {
                                var excel_path = r.result;
                                win.close();
                                Ext.Ajax.request({
                                  url:
                                    '/pages/menu/config/user/import_user.php',
                                  params: {
                                    excel_path: excel_path
                                  },
                                  callback: function(
                                    option,
                                    success,
                                    response
                                  ) {
                                    if (success) {
                                      Ext.getCmp('user_list')
                                        .getStore()
                                        .reload();
                                    } else {
                                      Ext.Msg.alert(_text('MN00023'), '');
                                    }
                                  }
                                });
                              } else {
                                Ext.Msg.alert(_text('MN00023'), result.result);
                              }
                            },
                            failure: function(form, action) {
                              var r = Ext.decode(action.response.responseText);
                              Ext.Msg.alert(_text('MN00023'), '');
                            }
                          });
                        }
                      },
                      {
                        text:
                          '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                          _text('MN00031'),
                        scale: 'small',
                        handler: function(b, e) {
                          win.close();
                        }
                      }
                    ]
                  }
                ]
              }).show();
            }
          },
          {
            //hidden: $deny_download_proxy,
            cls: 'proxima_button_customize',
            width: 30,
            text:
              '<span style="position:relative;top:1px;" title="' +
              _text('MN00142') +
              '"><i class="fa fa-download" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              Ext.Ajax.request({
                url: '/pages/menu/config/user/export_user.php',
                params: {}
              });
              var form = document.createElement('form');
              form.setAttribute('method', 'post');
              form.setAttribute(
                'action',
                '/pages/menu/config/user/export_user.php'
              );
              form.setAttribute('target', 'aaa');

              document.body.appendChild(form);

              window.open('', '_self', '');
              form.submit();
              document.body.removeChild(form);
            }
          },
          {
            icon: '/led-icons/arrow_join.png',
            text: '사용자 수동 업데이트',
            hidden: true,
            handler: function(b, e) {
              Ext.Msg.show({
                icon: Ext.Msg.QUESTION,
                title: '확인',
                msg: '수동으로 업데이트 하시겠습니까?',
                buttons: Ext.Msg.OKCANCEL,
                fn: function(btnId, text, opts) {
                  if (btnId == 'cancel') return;
                  var wait = Ext.Msg.wait('업데이트중입니다.', '정보');
                  Ext.Ajax.request({
                    url: '/update_member_linkage.php',
                    timeout: 30000000,
                    callback: function(option, success, response) {
                      wait.hide();
                      if (success) {
                        var r = Ext.decode(response.responseText);

                        Ext.Msg.alert(_text('MN00023'), r.msg);
                      } else {
                        Ext.Msg.alert('오류', '시스템 오류');
                      }
                    }
                  });
                }
              });
            }
          },
          {
            icon: '/led-icons/application_view_detail.png',
            text: '업데이트 정보',
            hidden: true,
            handler: function(b, e) {
              var update_store = new Ext.data.JsonStore({
                autoLoad: true,
                url: '/pages/menu/config/user/php/update_history.php',
                root: 'data',
                totalProperty: 'total',
                fields: [
                  'member_id',
                  'user_id',
                  'name',
                  'dept_nm',
                  'occu_kind',
                  'job_rank',
                  'job_position',
                  'job_duty',
                  //'group_name',
                  { name: 'hired_date', type: 'date', dateFormat: 'Ymd' },
                  { name: 'retire_date', type: 'date', dateFormat: 'Ymd' },
                  'dep_tel_num',
                  'breake',
                  { name: 'modify_date', type: 'date', dateFormat: 'YmdHis' }
                ]
              });

              var win = new Ext.Window({
                layout: 'fit',
                title: '사용자 업데이트 정보',
                width: 900,
                height: 300,
                modal: true,
                resizable: false,
                plain: true,
                items: [
                  {
                    id: 'update_inform',
                    xtype: 'grid',
                    store: update_store,
                    viewConfig: {
                      emptyText: '업데이트 정보가 없습니다.',
                      forceFit: true
                    },
                    bbar: {
                      xtype: 'paging',
                      store: update_store,
                      pageSize: 20
                    },
                    tbar: [
                      {
                        icon: '/coquette/png/16x16/edit_profile.png',
                        hidden: true,
                        text: '수정',
                        handler: function(btn, e) {
                          var sm = Ext.getCmp(
                            'update_inform'
                          ).getSelectionModel();

                          if (sm.hasSelection()) {
                            Ext.getCmp('config_user').request({
                              action: 'edit',
                              member_id: sm.getSelected().json.MEMBER_ID,
                              user_id: sm.getSelected().json.USER_ID
                            });
                          }
                        },
                        scope: this
                      },
                      {
                        icon: '/led-icons/arrow_refresh.png',
                        //>>text: '새로고침',
                        text: _text('MN00139'),
                        handler: function(btn, e) {
                          Ext.getCmp('update_inform')
                            .getStore()
                            .reload();
                        },
                        scope: this
                      }
                    ],
                    columns: [
                      new Ext.grid.RowNumberer(),
                      {
                        header: 'number',
                        dataIndex: 'member_id',
                        hidden: 'true'
                      },
                      { header: '사번', dataIndex: 'user_id', align: 'center' },
                      { header: '성명', dataIndex: 'name', align: 'center' },
                      { header: '부서', dataIndex: 'dept_nm', align: 'center' },
                      {
                        header: '직종',
                        dataIndex: 'occu_kind',
                        align: 'center'
                      },
                      {
                        header: '직위',
                        dataIndex: 'job_position',
                        align: 'center'
                      },
                      {
                        header: '직무',
                        dataIndex: 'job_duty',
                        align: 'center'
                      },
                      //{header:'그룹',dataIndex:'group_name',align:'center'},
                      {
                        header: '재직구분',
                        dataIndex: 'breake',
                        align: 'center'
                      },
                      {
                        header: '입사일자',
                        dataIndex: 'hired_date',
                        align: 'center',
                        renderer: Ext.util.Format.dateRenderer('Y-m-d')
                      },
                      {
                        header: '퇴사일자',
                        dataIndex: 'retire_date',
                        align: 'center',
                        renderer: Ext.util.Format.dateRenderer('Y-m-d')
                      },
                      {
                        header: '사내번호',
                        dataIndex: 'dep_tel_num',
                        align: 'center'
                      },
                      {
                        header: '변경일자',
                        dataIndex: 'modify_date',
                        align: 'center',
                        renderer: Ext.util.Format.dateRenderer('Y-m-d')
                      }
                    ],
                    buttons: [
                      {
                        text: '닫기',
                        handler: function() {
                          win.destroy();
                        }
                      }
                    ]
                  }
                ]
              });
              win.show();
            }
          }
        ],
        // 2010-12-01 네비게이션 추가 by CONOZ
        bbar: new Ext.PagingToolbar({
          store: this.store,
          pageSize: myPageSize
        }),
        // 2010-12-16 사용자 검색 기능 추가 by CONOZ
        doSearch: function(tbar, store) {
          var combo_value = tbar.get(0).getValue(),
            params = {};
          params.start = 0;
          params.limit = myPageSize;

          if (combo_value == 's_created_time') {
            if (Ext.isEmpty(tbar.get(2).getValue())) {
              Ext.Msg.alert('정보', '접속일자를 입력해주세요.');
              return;
            }

            params.search_field = combo_value;
            params.search_value = tbar
              .get(2)
              .getValue()
              .format('Y-m-d');
          } else if (combo_value == 's_group') {
            if (Ext.isEmpty(tbar.get(4).getValue())) {
              Ext.Msg.alert('정보', '그룹을 선택해주세요.');
              return;
            }
            params.search_field = combo_value;
            params.search_value = tbar.get(4).getValue();
          } else if(combo_value === 's_user_dept'){
            if( Ext.isEmpty(tbar.get(5).getValue()) )
            {
                Ext.Msg.alert('정보', '부서를 선택해주세요.');
                return;
            }
            params.search_field = combo_value;
            params.search_value = tbar.get(5).getValue();
        } else {
            params.search_field = combo_value;
            params.search_value = tbar.get(3).getValue();
          }
          if (
            Ext.isEmpty(params.search_field) ||
            Ext.isEmpty(params.search_value)
          ) {
            //>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
            Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
          } else {
            Ext.getCmp('user_list')
              .getStore()
              .load({
                params: params
              });
          }
        }
      });

      Ariel.config.User.superclass.initComponent.call(this);

      this.on('rowdblclick', function(self, idx, e) {
        self.request({
          action: 'edit',
          member_id: self
            .getSelectionModel()
            .getSelected()
            .get('member_id')
        });
      });
    },

    request: function(p) {
      var user_id = '';
      if (p.action == 'edit') {
        //var r = p.member_id;
        //user_id = r.get('user_id');
      }
      if (p.action == 'del') {
        var requestUrl = '/api/v1/users/'+p.user_id+'/delete';
      }else{
        var requestUrl = '/store/user/get_form.php';
      }
      //var requestUrl = '/store/user/get_formTest.php';
      Ext.Ajax.request({
        url: requestUrl,
        params: {
          action: p.action,
          //user_id: p.user_id,
          member_id: p.member_id
        },
        callback: function(self, success, response) {
          try {
            var r = Ext.decode(response.responseText);
            if (p.action == 'del') {
              Ext.getCmp('user_list')
                .getStore()
                .reload();
            }

            if (p.action == 'edit' || p.action == 'add') {
              new Ariel.user.modifiy({
                id: 'window_manager_member',
                iconCls: 'ariel_user_add',
                width: 500,
                resizable: true,
                modal: true,
                layout: 'vbox',
                layoutConfig: {
                  align: 'stretch'
                },
                style: { background: 'white' },
                type: 'addEdit',
                action: p.action,
                data: r
              }).show();

              if (p.action == 'edit') {
                Ext.getCmp('userInfo')
                  .getForm()
                  .items.items[1].setDisabled(true);

                var setV = [
                  { member_id: r.user.member_id },
                  { user_id: r.user.user_id },
                  { name: r.user.user_nm },
                  { dept_nm: r.user.dept_nm },
                  { job_position: r.user.job_position },
                  { email: r.user.email },
                  { phone1: r.user.phone.split('-')[0] },
                  { phone2: r.user.phone.split('-')[1] },
                  { phone3: r.user.phone.split('-')[2] }
                ];

                Ext.each(setV, function(el, index, items) {
                  Ext.getCmp('userInfo')
                    .getForm()
                    .setValues(el);
                });

                for (var i = 0; i <= 1; i++) {
                  Ext.getCmp('userInfo').remove(
                    Ext.getCmp('userInfo').getForm().items.items[2]
                  );
                  Ext.getCmp('userInfo').doLayout();
                }
              }
            }
          } catch (e) {
            //>>Ext.Msg.alert('오류', e);
            console.log(e);
            Ext.Msg.alert(_text('MN00022'), e);
          }
        }
      });
    }
  });

  Ariel.config.Group = Ext.extend(Ext.Panel, {
    //>>title: '그룹 관리',MN00115
    title: _text('MN00115'),
    width: 700,
    height: 300,

    renderAuth: function(v, metaData, r, rowIdx, colIdx, s) {
      switch (v) {
        case 'Y':
          return '<font color=red> ' + _text('MN01029') + '</font>'; //관리자
          break;

        default:
          return _text('MSG01002'); //'권한이 지정되지 않았습니다.'
          break;
      }
    },
    renderBasic: function(v, metaData, r, rowIdx, colIdx, s) {
      switch (v) {
        case 'Y':
          return '<font color=red>Y</font>';
          //return '<font color=red> ' + _text('MSG00150');
          break;

        default:
          return 'N';
          //return _text('MSG00151');
          break;
      }
    },

    layout: 'fit',

    initComponent: function(config) {
      // -------------- subgrid 시작  --------------
      var expander = new Ext.grid.RowExpander({
        tpl: new Ext.Template('<div id="myrow-{member_group_id}" ></div>')
      });

      expander.on('expand', expandedRow);

      function expandedRow(obj, record, body, rowIndex) {
        id = 'myrow-' + record.data.member_group_id;
        id2 = 'mygrid-' + record.data.member_group_id;

        var user_data = [];
        if (record.data.members.length > 0) {
          for (var i = 0; i < record.data.members.length; i++) {
            var temp_arr = [];

            temp_arr.push(record.data.members[i].member_id);
            temp_arr.push(record.data.members[i].user_id);
            temp_arr.push(record.data.members[i].user_nm);
            temp_arr.push(record.data.members[i].dept_nm);
            temp_arr.push(record.data.members[i].email);
            temp_arr.push(record.data.members[i].is_denied);
            temp_arr.push(record.data.members[i].is_admin);
            temp_arr.push(record.data.members[i].extra_vars);
            temp_arr.push(record.data.members[i].occu_kind);
            temp_arr.push(record.data.members[i].job_rank);
            temp_arr.push(record.data.members[i].job_position);
            temp_arr.push(record.data.members[i].job_duty);
            temp_arr.push(record.data.members[i].dep_tel_num);
            temp_arr.push(record.data.members[i].breake);
            temp_arr.push(record.data.members[i].member_no);
            temp_arr.push(record.data.members[i].phone);
            temp_arr.push(record.data.members[i].expired_date);
            temp_arr.push(record.data.members[i].last_login_date);
            temp_arr.push(record.data.members[i].created_date);
            temp_arr.push(record.data.members[i].hired_date);
            temp_arr.push(record.data.members[i].retire_date);

            user_data.push(temp_arr);
          }
        }

        var gridX = new Ext.grid.GridPanel({
          stripeRows: true,
          store: new Ext.data.Store({
            reader: new Ext.data.ArrayReader({}, [
              'member_id',
              'user_id',
              'user_nm',
              'dept_nm',
              'email',
              'is_denied',
              'is_admin',
              'extra_vars',
              'occu_kind',
              'job_rank',
              'job_position',
              'job_duty',
              'dep_tel_num',
              'breake',
              'member_no',
              'phone',
              { name: 'expired_date', type: 'date', dateFormat: 'YmdHis' },
              { name: 'last_login_date', type: 'date', dateFormat: 'YmdHis' },
              { name: 'created_date', type: 'date', dateFormat: 'YmdHis' },
              { name: 'hired_date', type: 'date', dateFormat: 'YmdHis' },
              { name: 'retire_date', type: 'date', dateFormat: 'YmdHis' }
            ]),
            data: user_data
          }),
          cm: new Ext.grid.ColumnModel({
            defaults: {
              sortable: true
            },
            columns: [
              new Ext.grid.RowNumberer(),
              /*
                          {header: 'number', dataIndex:'member_id',hidden:'true'},
                          {header: '아이디', dataIndex: 'user_id',	align:'center' ,width:70},
                          {header: '이 름',   dataIndex: 'user_nm',		align:'center'},
                          {header: '부 서',   dataIndex: 'dept_nm',	align:'center'},
                          //	{header: '직종',   dataIndex: 'occu_kind',	align:'center',width:80, hidden:'true'},
                          {header: '직위',   dataIndex: 'job_position',align:'center',width:80},
                          //	{header: '직무',   dataIndex: 'job_duty',	align:'center',width:80, hidden:'true'},
                          {header: '그룹',   dataIndex: 'group',		align:'center'},
                          //	{header: '재직구분',dataIndex:'breake',align:'center', renderer:this.renderStatus},
                          {header: '입사일자', dataIndex: 'hired_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center',width:120},
                          {header: '퇴사일자', dataIndex: 'retire_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center',width:120},
                          {header: '등록일자', dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center',width:120},
                          {header: '마지막 접속일자', dataIndex: 'last_login_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), align:'center',width:120},
                          {header:'사내번호',dataIndex: 'dep_tel_num',align:'center', hidden:'true'}
                          */
              {
                header: _text('MN00195'),
                dataIndex: 'user_id',
                align: 'center',
                width: 70
              },
              { header: 'number', dataIndex: 'member_id', hidden: 'true' },
              {
                header: _text('MN00196'),
                dataIndex: 'user_nm',
                align: 'center'
              },
              {
                header: _text('MN00181'),
                dataIndex: 'dept_nm',
                align: 'center'
              },
              {
                header: '직종',
                dataIndex: 'occu_kind',
                align: 'center',
                width: 80,
                hidden: 'true'
              },
              {
                header: _text('MN00260'),
                dataIndex: 'job_position',
                align: 'center',
                width: 80,
                hidden: 'true'
              },
              {
                header: '직무',
                dataIndex: 'job_duty',
                align: 'center',
                width: 80,
                hidden: 'true'
              },
              {
                header: _text('MN00111'),
                dataIndex: 'group',
                align: 'center',
                sortable: false,
                hidden: 'true'
              },
              {
                header: '재직구분',
                dataIndex: 'breake',
                align: 'center',
                renderer: this.renderStatus,
                hidden: 'true'
              },
              {
                header: _text('MN00331'),
                dataIndex: 'hired_date',
                renderer: Ext.util.Format.dateRenderer('Y-m-d'),
                align: 'center',
                width: 120,
                hidden: 'true'
              },
              {
                header: _text('MN00332'),
                dataIndex: 'retire_date',
                renderer: Ext.util.Format.dateRenderer('Y-m-d'),
                align: 'center',
                width: 120,
                hidden: 'true'
              },
              {
                header: _text('MN00104'),
                dataIndex: 'last_login_date',
                renderer: Ext.util.Format.dateRenderer('Y-m-d'),
                align: 'center',
                width: 120,
                hidden: true
              },
              {
                header: _text('MN00333'),
                dataIndex: 'dep_tel_num',
                align: 'center',
                hidden: true
              },
              { header: _text('MN02127'), dataIndex: 'email', align: 'center' },
              { header: _text('MN00333'), dataIndex: 'phone', align: 'center' },
              {
                header: _text('MN00102'),
                dataIndex: 'created_date',
                renderer: Ext.util.Format.dateRenderer('Y-m-d'),
                align: 'center',
                width: 120
              }
            ]
          }),
          viewConfig: {
            forceFit: true
          },
          width: '90%',
          autoHeight: true,
          id: id2,
          frame: true
        });

        gridX.render(id);
        gridX
          .getEl()
          .swallowEvent(['mouseover', 'mousedown', 'click', 'dblclick']);
      };

      var v_store = new Ext.data.GroupingStore({
        reader: new Ext.data.ArrayReader({}, [
          { name: 'member_id' },
          { name: 'user_id' },
          { name: 'user_nm' },
          { name: 'dept_nm' },
          { name: 'email' },
          { name: 'phone' },
          { name: 'member_created_date', type: 'date', dateFormat: 'YmdHis' },
          { name: 'member_group_id' },
          { name: 'member_group_name' },
          { name: 'is_default' },
          { name: 'description' },
          { name: 'parent_group_id' },
          { name: 'group_created_date', type: 'date', dateFormat: 'YmdHis' },
          { name: 'is_admin' },
          { name: 'group_title' }
        ]),
        autoLoad: true,
        url: '/pages/menu/config/user/php/get_group_user.php',
        root: 'data',
        sortInfo: {
          field: 'user_nm',
          direction: 'ASC'
        },
        groupField: 'group_title',
        listeners: {
          load: function(store, records, successful, operation, eOpts) {}
        }
      });

      var grid_old = new Ext.grid.GridPanel({
        id: 'group_list',
        cls: 'proxima_customize',
        border: false,
        store: v_store, //this.store,
        stripeRows: true,
        loadMask: true,
        //plugins: expander,
        listeners: {
          viewready: function(self) {
            self.getStore().load({
              params: {
                start: 0,
                limit: myPageSize
              }
            });
          }
        },
        viewConfig: {
          emptyText: _text('MSG00148'), //'그룹 정보가 없습니다.',
          forceFit: true
        },
        colModel: new Ext.grid.ColumnModel({
          defaults: {
            sortable: true
          },
          columns: [
            //expander,
            //new Ext.grid.RowNumberer(),

            {
              header: _text('MN00195'),
              dataIndex: 'user_id',
              align: 'center',
              width: 70
            },
            { header: _text('MN00196'), dataIndex: 'user_nm', align: 'center' },
            { header: _text('MN00181'), dataIndex: 'dept_nm', align: 'center' },
            { header: _text('MN02127'), dataIndex: 'email', align: 'center' },
            { header: _text('MN00333'), dataIndex: 'phone', align: 'center' },
            {
              header: _text('MN00102'),
              dataIndex: 'created_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120
            }
          ]
        }),
        tbar: [
          {
            //icon: '/coquette/png/16x16/add_user.png',
            //>>text: '추가',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00033') +
              '"><i class="fa fa-user-plus" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              this.request({ action: 'add' });
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/edit_profile.png',
            //>>text: '수정',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00043') +
              '"><i class="fa fa-user" style="font-size:13px;color:white;"></i></span><span style="position:relative;" title="' +
              _text('MN00043') +
              '"><i class="fa fa-edit" style="font-size:9px;color:white;"></i></span>',
            handler: function(btn, e) {
              var sm = Ext.getCmp('group_list').getSelectionModel();
              if (sm.hasSelection()) {
                this.request({
                  action: 'edit',
                  member_group_id: sm.getSelected().get('member_group_id')
                });
              } else {
                //>>Ext.Msg.alert('정보', '수정하실 그룹를 선택해주세요.');
                Ext.Msg.alert(_text('MN00023'), _text('MSG00063'));
              }
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/delete_user.png',
            //>>text: '삭제',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00034') +
              '"><i class="fa fa-user-times" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              var sm = Ext.getCmp('group_list').getSelectionModel();
              if (sm.hasSelection()) {
                var r = sm.getSelected();

                Ext.Msg.show({
                  icon: Ext.Msg.QUESTION,
                  buttons: Ext.Msg.OKCANCEL,
                  //>>title: '삭제',
                  title: _text('MN00034'),
                  //>>msg: r.get('member_group_name')+' 그룹를 삭제 하시겠습니까?',
                  msg:
                    _text('MSG00155') +
                    ' "' +
                    r.get('member_group_name') +
                    '"?',

                  fn: function(buttonId, text, opts) {
                    var p = {
                      action: 'del',
                      member_group_id: r.get('member_group_id')
                    };
                    if (buttonId == 'ok') this.request(p);
                  },
                  scope: this
                });
              } else {
                //Ext.Msg.alert('정보', '삭제하실 그룹를 선택해주세요.');MSG00022
                Ext.Msg.alert(_text('MN00023'), _text('MSG00064'));
              }
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/edit_profile.png',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00126') +
              '"><i class="fa fa-user-plus" style="font-size:13px;color:white;"></i></span>', //'사용자추가'MN00126
            handler: function(btn, e) {
              var sm = Ext.getCmp('group_list').getSelectionModel();
              if (sm.hasSelection()) {
                var records = sm.getSelected();
                var user_store = new Ext.data.JsonStore({
                  url: '/pages/menu/config/user/php/get.php',
                  remoteSort: true,
                  sortInfo: {
                    field: 'user_nm',
                    direction: 'ASC'
                  },
                  idProperty: 'member_id',
                  root: 'data',
                  fields: [
                    'member_id',
                    'user_id',
                    'user_nm',
                    'group',
                    'occu_kind',
                    //'job_rank',
                    'job_position',
                    'job_duty',
                    'dep_tel_num',
                    'breake',
                    'dept_nm',
                    'phone',
                    'email',
                    {
                      name: 'created_date',
                      type: 'date',
                      dateFormat: 'YmdHis'
                    },
                    {
                      name: 'last_login_date',
                      type: 'date',
                      dateFormat: 'YmdHis'
                    },
                    { name: 'hired_date', type: 'date', dateFormat: 'Ymd' },
                    { name: 'retire_date', type: 'date', dateFormat: 'Ymd' }
                  ],
                  listeners: {
                    exception: function(
                      self,
                      type,
                      action,
                      opts,
                      response,
                      args
                    ) {
                      try {
                        var r = Ext.decode(response.responseText);
                        if (!r.success) {
                          //>>Ext.Msg.alert('정보', r.msg);
                          Ext.Msg.alert(_text('MN00023'), r.msg);
                        }
                      } catch (e) {
                        //>>Ext.Msg.alert('디코드 오류', e);
                        console.log(e);
                        Ext.Msg.alert(_text('MN00022'), e);
                      }
                    }
                  }
                });

                var group_user_store = new Ext.data.JsonStore({
                  url: '/pages/menu/config/user/php/get_group_member.php',
                  remoteSort: true,
                  sortInfo: {
                    field: 'user_nm',
                    direction: 'ASC'
                  },
                  idProperty: 'member_id',
                  root: 'data',
                  fields: ['member_id', 'user_id', 'user_nm'],
                  listeners: {
                    exception: function(
                      self,
                      type,
                      action,
                      opts,
                      response,
                      args
                    ) {
                      try {
                        var r = Ext.decode(response.responseText);
                        if (!r.success) {
                          //>>Ext.Msg.alert('정보', r.msg);
                          Ext.Msg.alert(_text('MN00023'), r.msg);
                        }
                      } catch (e) {
                        //>>Ext.Msg.alert('디코드 오류', e);
                        console.log(e);
                        Ext.Msg.alert(_text('MN00022'), e);
                      }
                    }
                  }
                });

                var win = new Ext.Window({
                  id: 'edit_group_user',
                  cls: 'change_background_panel',
                  border: false,
                  modal: true,
                  width: 1000,
                  height: 700,
                  resizable: false,
                  layout: 'vbox',
                  buttonAlign: 'center',
                  layoutConfig: {
                    align: 'stretch',
                    defaultMargins: {
                      top: 5,
                      right: 5,
                      bottom: 5,
                      left: 5
                    }
                  },
                  title: _text('MN00193'), //사용자 수정 '그룹사용자 추가'
                  items: [
                    {
                      xtype: 'fieldset',
                      title: _text('MN01001'), //그룹',
                      layout: 'fit',
                      flex: 1,
                      bodyStyle: 'padding : 5px',
                      autoScroll: true,
                      items: [
                        {
                          xtype: 'grid',
                          cls: 'proxima_customize',
                          //anchor:'95%',
                          id: 'group_listview',
                          columnSort: true,
                          stripeRows: true,
                          reserveScrollOffset: true,
                          autoScroll: true,
                          emptyText: _text('MSG00148'), //결과 값이 없습니다.'등록된 데이터가 없습니다.',
                          multiSelect: true,
                          store: new Ext.data.ArrayStore({
                            fields: [
                              { name: 'member_group_id' },
                              { name: 'member_group_name' },
                              { name: 'description' },
                              { name: 'created_date' }
                            ],
                            listeners: {
                              load: function(self) {}
                            }
                          }),
                          columns: [
                            {
                              header: _text('MN02148'),
                              dataIndex: 'member_group_name',
                              width: 200
                            }, //'그룹명'
                            {
                              header: _text('MN00049'),
                              dataIndex: 'description',
                              width: 720
                            } //'설명'
                          ],
                          listeners: {
                            render: function(self) {
                              Ext.getCmp('group_listview')
                                .getStore()
                                .add(records);
                            }
                          }
                        }
                      ]
                    },
                    {
                      xtype: 'fieldset',
                      title: _text('MN00189'), //'사용자',
                      layout: 'fit',
                      flex: 2,

                      bodyStyle: 'padding : 5px',
                      autoScroll: true,

                      items: [
                        {
                          xtype: 'panel',
                          layout: 'hbox',
                          layoutConfig: {
                            align: 'stretch',
                            defaultMargins: {
                              top: 2,
                              right: 2,
                              bottom: 2,
                              left: 2
                            }
                          },
                          items: [
                            {
                              xtype: 'grid',
                              cls: 'proxima_customize',
                              //border: false,
                              id: 'all_user_list',
                              flex: 1,
                              stripeRows: true,
                              store: user_store,
                              viewConfig: {
                                emptyText: _text('MSG00148'), //결과 값이 없습니다.'업데이트 정보가 없습니다.',
                                forceFit: true
                              },
                              bbar: {
                                xtype: 'paging',
                                store: user_store,
                                pageSize: 20
                              },
                              tbar: [
                                {
                                  xtype: 'combo',
                                  id: 's_field',
                                  width: 130,
                                  triggerAction: 'all',
                                  editable: false,
                                  mode: 'local',
                                  store: [
                                    /*
                                                      ['s_created_time', '등록일자'],
                                                      ['s_user_id', '아이디'],
                                                      ['s_name', '이름'],
                                                      ['s_user_dept', '부서'],
                                                      ['s_job_position','직위']
                                                      ['s_group','그룹']
                                              */
                                    ['s_created_time', _text('MN00104')],
                                    ['s_user_id', _text('MN00195')],
                                    ['s_name', _text('MN00196')],
                                    ['s_user_dept', _text('MN00181')],
                                    ['s_job_position', _text('MN00260')],
                                    ['s_group', _text('MN01001')]
                                  ],
                                  value: 's_created_time',
                                  listeners: {
                                    select: function(self, r, i) {
                                      if (i == 0) {
                                        self.ownerCt.get(2).setVisible(true);
                                        self.ownerCt.get(3).setVisible(false);
                                        self.ownerCt.get(4).setVisible(false);
                                      } else if (i == 5) {
                                        self.ownerCt.get(4).setVisible(true);
                                        self.ownerCt.get(2).setVisible(false);
                                        self.ownerCt.get(3).setVisible(false);
                                      } else {
                                        self.ownerCt.get(3).setVisible(true);
                                        self.ownerCt.get(2).setVisible(false);
                                        self.ownerCt.get(4).setVisible(false);
                                      }
                                    }
                                  }
                                },
                                ' ',
                                {
                                  xtype: 'datefield',
                                  id: 's_value1',
                                  width: 90,
                                  format: 'Y-m-d',
                                  listeners: {
                                    render: function(self) {
                                      //self.setValue(new Date());
                                      self.setMaxValue(new Date());
                                    }
                                  }
                                },
                                {
                                  hidden: true,
                                  //allowBlank: false,
                                  xtype: 'textfield',
                                  width: 120,
                                  id: 's_value2',
                                  listeners: {
                                    specialKey: function(self, e) {
                                      var w = self.ownerCt.ownerCt;
                                      if (
                                        e.getKey() == e.ENTER &&
                                        self.isValid()
                                      ) {
                                        e.stopEvent();
                                        w.doSearch(
                                          w.getTopToolbar(),
                                          this.store
                                        );
                                      }
                                    }
                                  }
                                },
                                {
                                  xtype: 'combo',
                                  width: 150,
                                  //fieldLabel : '그룹 별 필터',
                                  editable: false,
                                  id: 'search_group',
                                  typeAhead: true,
                                  triggerAction: 'all',
                                  hidden: true,
                                  editable: false,
                                  displayField: 'member_group_name',
                                  valueField: 'member_group_id',
                                  hiddenName: 'member_group_id',
                                  hiddenValue: 'member_group_name',
                                  store: new Ext.data.JsonStore({
                                    url:
                                      '/pages/menu/config/user/php/get_group.php',
                                    remoteSort: true,
                                    root: 'data',
                                    sortInfo: {
                                      field: 'member_group_id',
                                      direction: 'DESC'
                                    },
                                    idProperty: 'member_group_id',
                                    root: 'data',
                                    fields: [
                                      'member_group_id',
                                      'member_group_name',
                                      'description',
                                      'is_admin'
                                    ]
                                  }),
                                  listeners: {}
                                },
                                {
                                  xtype: 'button',
                                  //>>text: '조회',
                                  //text: _text('MN00037'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00037') +
                                    '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(b, e) {
                                    var w = b.ownerCt.ownerCt;
                                    w.doSearch(w.getTopToolbar(), this.store);
                                  }
                                },
                                {
                                  //icon: '/led-icons/arrow_refresh.png',
                                  //>>text: '새로고침',
                                  //text: _text('MN00139'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00139') +
                                    '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    Ext.getCmp('all_user_list')
                                      .getStore()
                                      .load({
                                        params: {
                                          start: 0,
                                          limit: myPageSize
                                        }
                                      });
                                  },
                                  scope: this
                                }
                              ],
                              columns: [
                                new Ext.grid.RowNumberer(),
                                {
                                  header: 'number',
                                  dataIndex: 'member_id',
                                  hidden: 'true'
                                },
                                {
                                  header: _text('MN00188'),
                                  dataIndex: 'user_id',
                                  align: 'center'
                                }, //'사번'
                                {
                                  header: _text('MN00196'),
                                  dataIndex: 'user_nm',
                                  align: 'center'
                                }, //'성명'
                                {
                                  header: _text('MN00181'),
                                  dataIndex: 'dept_nm',
                                  align: 'center'
                                }, //'부서'
                                //{header:_text('MN00188'),dataIndex:'occu_kind',align:'center' ,hidden:'true'},//'직종'
                                {
                                  header: _text('MN00260'),
                                  dataIndex: 'job_position',
                                  align: 'center',
                                  hidden: 'true'
                                }, //'직위'
                                //{header:_text('MN00188'),dataIndex:'job_duty',align:'center',hidden:'true'},//'직무'
                                //{header:_text('MN00188'),dataIndex:'group_name',align:'center'},//'그룹'
                                {
                                  header: _text('MN00337'),
                                  dataIndex: 'breake',
                                  align: 'center',
                                  hidden: 'true'
                                }, //'재직구분'
                                {
                                  header: _text('MN00331'),
                                  dataIndex: 'hired_date',
                                  align: 'center',
                                  renderer: Ext.util.Format.dateRenderer(
                                    'Y-m-d'
                                  ),
                                  hidden: 'true'
                                }, //'입사일자'
                                {
                                  header: _text('MN00332'),
                                  dataIndex: 'retire_date',
                                  align: 'center',
                                  renderer: Ext.util.Format.dateRenderer(
                                    'Y-m-d'
                                  ),
                                  hidden: 'true'
                                }, // '퇴사일자'
                                {
                                  header: _text('MN00333'),
                                  dataIndex: 'dep_tel_num',
                                  align: 'center',
                                  hidden: 'true'
                                } //'사내번호'
                              ],
                              listeners: {
                                viewready: function(self) {
                                  // 2010-12-01 네비게이션 추가 by CONOZ
                                  self.getStore().load({
                                    params: {
                                      start: 0,
                                      limit: myPageSize
                                    }
                                  });
                                }
                              },

                              doSearch: function(tbar, store) {
                                var combo_value = tbar.get(0).getValue(),
                                  params = {};
                                params.start = 0;
                                params.limit = myPageSize;

                                if (combo_value == 's_created_time') {
                                  if (Ext.isEmpty(tbar.get(2).getValue())) {
                                    Ext.Msg.alert(
                                      _text('MN00024'),
                                      _text('MSG00041')
                                    ); //Ext.Msg.alert('정보', '검색 값를 선택하세요.');
                                    return;
                                  }

                                  params.search_field = combo_value;
                                  params.search_value = tbar
                                    .get(2)
                                    .getValue()
                                    .format('Y-m-d');
                                } else if (combo_value == 's_group') {
                                  if (Ext.isEmpty(tbar.get(4).getValue())) {
                                    Ext.Msg.alert(
                                      _text('MN00024'),
                                      _text('MSG00098')
                                    ); //Ext.Msg.alert('정보', '그룹을 선택해주세요.');
                                    return;
                                  }
                                  params.search_field = combo_value;
                                  params.search_value = tbar.get(4).getValue();
                                } else {
                                  params.search_field = combo_value;
                                  params.search_value = tbar.get(3).getValue();
                                }
                                if (
                                  Ext.isEmpty(params.search_field) ||
                                  Ext.isEmpty(params.search_value)
                                ) {
                                  //>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
                                  Ext.Msg.alert(
                                    text('MN00023'),
                                    _text('MSG00007')
                                  );
                                } else {
                                  Ext.getCmp('all_user_list')
                                    .getStore()
                                    .load({
                                      params: params
                                    });
                                }
                              }
                            },
                            {
                              xtype: 'fieldset',
                              width: 70,
                              layout: 'vbox',
                              layoutConfig: {
                                type: 'vbox',
                                align: 'stretch',
                                pack: 'center'
                              },
                              border: false,
                              items: [
                                {
                                  xtype: 'button',
                                  //width: 60,
                                  //height: 40,
                                  //margins: '0 0 50 0',
                                  // text: '사용자추가'
                                  //text: _text('MN00126'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00126') +
                                    '"><i class="fa fa-chevron-right" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    var sm = Ext.getCmp(
                                      'all_user_list'
                                    ).getSelectionModel();
                                    if (sm.hasSelection()) {
                                      var selections = sm.getSelections();
                                      for (
                                        var i = 0;
                                        i < selections.length;
                                        i++
                                      ) {
                                        var member_id = selections[i].get(
                                          'member_id'
                                        );
                                        var is_member = Ext.getCmp(
                                          'group_user_list'
                                        )
                                          .getStore()
                                          .find('member_id', member_id);
                                        // 기존 스토어에 해당 사용자가 없으면 추가
                                        if (is_member == -1) {
                                          Ext.getCmp('group_user_list')
                                            .getStore()
                                            .add(selections[i]);
                                        }
                                      }
                                    } else {
                                      Ext.Msg.alert(
                                        _text('MN00023'),
                                        _text('MN00219')
                                      );
                                    }
                                  }
                                },
                                {
                                  xtype: 'button',
                                  //margins: '0 0 50 0',
                                  //width: 60,
                                  //height: 40,
                                  // text: '사용자삭제'
                                  //text: _text('MN00408'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00408') +
                                    '"><i class="fa fa-chevron-left" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    var sm = Ext.getCmp(
                                      'group_user_list'
                                    ).getSelectionModel();
                                    if (sm.hasSelection()) {
                                      var selections = sm.getSelections();
                                      for (
                                        var i = 0;
                                        i < selections.length;
                                        i++
                                      ) {
                                        var member_id = selections[i].get(
                                          'member_id'
                                        );
                                        var index = Ext.getCmp(
                                          'group_user_list'
                                        )
                                          .getStore()
                                          .find('member_id', member_id);
                                        Ext.getCmp('group_user_list')
                                          .getStore()
                                          .removeAt(index);
                                      }
                                    } else {
                                      Ext.Msg.alert(
                                        _text('MN00023'),
                                        _text('MSG00221')
                                      );
                                    }
                                  }
                                }
                              ]
                            },
                            {
                              xtype: 'grid',
                              id: 'group_user_list',
                              cls: 'proxima_customize',
                              flex: 1,
                              stripeRows: true,
                              store: group_user_store,
                              viewConfig: {
                                emptyText: _text('MSG00148'), //결과 값이 없습니다.
                                forceFit: true
                              },
                              bbar: {
                                xtype: 'paging',
                                store: group_user_store,
                                pageSize: 20
                              },
                              tbar: [
                                {
                                  //icon: '/led-icons/arrow_refresh.png',
                                  //>>text: '새로고침',
                                  //text: _text('MN00139'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00139') +
                                    '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    Ext.getCmp('group_user_list')
                                      .getStore()
                                      .reload();
                                  },
                                  scope: this
                                }
                              ],
                              columns: [
                                new Ext.grid.RowNumberer(),
                                {
                                  header: 'number',
                                  dataIndex: 'member_id',
                                  hidden: 'true'
                                },
                                {
                                  header: _text('MN00188'),
                                  dataIndex: 'user_id',
                                  align: 'center'
                                }, //'사번'
                                {
                                  header: _text('MN00196'),
                                  dataIndex: 'user_nm',
                                  align: 'center'
                                }, //'성명'
                                {
                                  header: _text('MN00181'),
                                  dataIndex: 'dept_nm',
                                  align: 'center'
                                } //'부서'
                              ],
                              listeners: {
                                viewready: function(self) {
                                  // 2010-12-01 네비게이션 추가 by CONOZ

                                  self.getStore().load({
                                    params: {
                                      start: 0,
                                      limit: myPageSize,
                                      member_group_id: records.get(
                                        'member_group_id'
                                      )
                                    }
                                  });
                                }
                              }
                            }
                          ]
                        }
                      ]
                    }
                  ],

                  buttons: [
                    {
                      text:
                        '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
                        _text('MN00043'),
                      scale: 'medium',
                      handler: function() {
                        var group_members = Ext.getCmp(
                          'group_user_list'
                        ).getStore().data;
                        var members = [];

                        Ext.each(group_members.items, function(i) {
                          members.push(i.data.member_id);
                        });

                        Ext.Ajax.request({
                          url: '/store/user/group_user_oracle.php',
                          params: {
                            member_group_id: records.get('member_group_id'),
                            'members[]': members
                          },
                          callback: function(opts, success, resp) {
                            if (success) {
                              try {
                                Ext.getCmp('group_list')
                                  .getStore()
                                  .reload();
                                Ext.getCmp('edit_group_user').close();
                              } catch (e) {
                                console.log(e);
                                Ext.Msg.alert(_text('MN00022'), e);
                              }
                            } else {
                              console.log(resp);
                              Ext.Msg.alert(_text('MN01098'), resp.statusText); //'서버 오류'
                            }
                          }
                        });
                      }
                    },
                    {
                      text:
                        '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                        _text('MN00004'),
                      scale: 'medium',
                      handler: function() {
                        Ext.getCmp('edit_group_user').close();
                      }
                    }
                  ]
                }).show();
              } else {
                //Ext.Msg.alert('정보', '그룹에 추가할 사용자를 선택해주세요.');MSG00218
                Ext.Msg.alert(_text('MN00023'), _text('MSG00218'));
              }
            }
          },
          '-',
          {
            //icon: '/led-icons/arrow_refresh.png',
            //>>text: '새로고침',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00139') +
              '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              Ext.getCmp('group_list')
                .getStore()
                .reload();
            }
          },
          '-',
          {
            //text: '모두 열기',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN02146') +
              '"><i class="fa fa-folder-open" style="font-size:13px;color:white;"></i></span>',
            // icon: '/led-icons/folder-open.gif',
            handler: function() {
              expander.expandAll(
                Ext.getCmp('group_list')
                  .getStore()
                  .getTotalCount()
              );
            }
          },
          {
            //text: '모두 접기',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN02147') +
              '"><i class="fa fa-folder" style="font-size:13px;color:white;"></i></span>',
            //icon: '/led-icons/folder.gif',
            handler: function() {
              expander.collapseAll(
                Ext.getCmp('group_list')
                  .getStore()
                  .getTotalCount()
              );
            }
          }
        ]
      });

      this.items = new Ext.grid.GridPanel({
        id: 'group_list',
        cls: 'grid_title_customize proxima_customize',
        border: false,
        store: v_store, //this.store,
        stripeRows: true,
        loadMask: true,
        //plugins: expander,
        features: [
          {
            groupHeaderTpl: new Ext.XTemplate(
              '<tpl for=".">',
              '<input type="button" value={name}></div>',
              '</tpl>'
            ),
            ftype: 'groupingsummary'
          }
        ],
        listeners: {
          viewready: function(self) {
            self.getStore().load({
              params: {
                start: 0,
                limit: myPageSize
              }
            });
          }
        },
        viewConfig: {
          emptyText: _text('MSG00148'), //'그룹 정보가 없습니다.',
          forceFit: true
        },
        view: new Ext.grid.GroupingView({
          forceFit: true,
          groupHeaderTpl: new Ext.XTemplate(
            '<tpl for=".">',
            '<input type="button" value={name}></div>',
            '</tpl>'
          ),
          groupTextTpl: '{group}'
          //groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Items" : "Item"]})'
        }),
        colModel: new Ext.grid.ColumnModel({
          defaults: {
            sortable: true
          },
          columns: [
            //expander,
            //new Ext.grid.RowNumberer(),
            /*
                      {header: '그룹명', dataIndex: 'member_group_name',width:100},
                      {header: '기본값', dataIndex: 'is_default',align:'center',renderer:this.renderBasic,width:60},
                      {header: '설명', dataIndex: 'description',align:'center',width:300},
                      {header: '등록일자', dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 140,align:'center'},
                      {header: '권한', dataIndex: 'is_admin',align:'center',renderer:this.renderAuth,width:150}
                      
                      {id: 'member_group_id', header: 'MEMBER GROUP ID', dataIndex: 'member_group_id', hidden:true},
                      {header: _text('MN02148'), dataIndex: 'member_group_name',width:100},
                      {header: _text('MN00155'), dataIndex: 'is_default',width:60,align:'center',renderer:this.renderBasic},
                      {header: _text('MN00049'), dataIndex: 'description',width:300},
                      {header: _text('MN02380'), dataIndex: 'parent_group_name',align:'center'},
                      {header: _text('MN00102'), dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), width: 140,align:'center'},
                      {header: _text('MN00110'), dataIndex: 'is_admin',align:'center',renderer:this.renderAuth,width:150}*/
            {
              header: _text('MN00195'),
              dataIndex: 'group_title',
              hidden: true
            },
            { header: _text('MN00195'), dataIndex: 'user_id', width: 70 },
            { header: _text('MN00196'), dataIndex: 'user_nm' },
            { header: _text('MN00181'), dataIndex: 'dept_nm' },
            { header: _text('MN02127'), dataIndex: 'email' },
            { header: _text('MN00333'), dataIndex: 'phone', align: 'center' },
            {
              header: _text('MN00102'),
              dataIndex: 'member_created_date',
              renderer: Ext.util.Format.dateRenderer('Y-m-d'),
              align: 'center',
              width: 120
            }
          ]
        }),
        tbar: [
          {
            //icon: '/coquette/png/16x16/add_user.png',
            //>>text: '추가',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00033') +
              '"><i class="fa fa-user-plus" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              this.request({ action: 'add' });
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/edit_profile.png',
            //>>text: '수정',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00043') +
              '"><i class="fa fa-user" style="font-size:13px;color:white;"></i></span><span style="position:relative;" title="' +
              _text('MN00043') +
              '"><i class="fa fa-edit" style="font-size:9px;color:white;"></i></span>',
            handler: function(btn, e) {
              var sm = Ext.getCmp('group_list').getSelectionModel();
              if (sm.hasSelection()) {
                this.request({
                  action: 'edit',
                  member_group_id: sm.getSelected().get('member_group_id')
                });
              } else {
                //>>Ext.Msg.alert('정보', '수정하실 그룹를 선택해주세요.');
                Ext.Msg.alert(_text('MN00023'), _text('MSG00063'));
              }
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/delete_user.png',
            //>>text: '삭제',
            cls: 'proxima_button_customize',
            width: btnWidth,
            hidden:true,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00034') +
              '"><i class="fa fa-user-times" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              var sm = Ext.getCmp('group_list').getSelectionModel();
              if (sm.hasSelection()) {
                var r = sm.getSelected();

                Ext.Msg.show({
                  icon: Ext.Msg.QUESTION,
                  buttons: Ext.Msg.OKCANCEL,
                  //>>title: '삭제',
                  title: _text('MN00034'),
                  //>>msg: r.get('member_group_name')+' 그룹를 삭제 하시겠습니까?',
                  msg:
                    _text('MSG00155') +
                    ' "' +
                    r.get('member_group_name') +
                    '"?',

                  fn: function(buttonId, text, opts) {
                    var p = {
                      action: 'del',
                      member_group_id: r.get('member_group_id')
                    };
                    if (buttonId == 'ok') this.request(p);
                  },
                  scope: this
                });
              } else {
                //Ext.Msg.alert('정보', '삭제하실 그룹를 선택해주세요.');MSG00022
                Ext.Msg.alert(_text('MN00023'), _text('MSG00064'));
              }
            },
            scope: this
          },
          {
            //icon: '/coquette/png/16x16/edit_profile.png',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00126') +
              '"><i class="fa fa-plus" style="font-size:13px;color:white;"></i></span>', //'사용자추가'MN00126
            handler: function(btn, e) {
              var sm = Ext.getCmp('group_list').getSelectionModel();
              if (sm.hasSelection()) {
                var records = sm.getSelected();
                var user_store = new Ext.data.JsonStore({
                  url: '/pages/menu/config/user/php/get.php',
                  remoteSort: true,
                  sortInfo: {
                    field: 'user_nm',
                    direction: 'ASC'
                  },
                  idProperty: 'member_id',
                  root: 'data',
                  fields: [
                    'member_id',
                    'user_id',
                    'user_nm',
                    'group',
                    'occu_kind',
                    //'job_rank',
                    'job_position',
                    'job_duty',
                    'dep_tel_num',
                    'breake',
                    'dept_nm',
                    'phone',
                    'email',
                    {
                      name: 'created_date',
                      type: 'date',
                      dateFormat: 'YmdHis'
                    },
                    {
                      name: 'last_login_date',
                      type: 'date',
                      dateFormat: 'YmdHis'
                    },
                    { name: 'hired_date', type: 'date', dateFormat: 'Ymd' },
                    { name: 'retire_date', type: 'date', dateFormat: 'Ymd' }
                  ],
                  listeners: {
                    exception: function(
                      self,
                      type,
                      action,
                      opts,
                      response,
                      args
                    ) {
                      try {
                        var r = Ext.decode(response.responseText);
                        if (!r.success) {
                          //>>Ext.Msg.alert('정보', r.msg);
                          console.log(r);
                          Ext.Msg.alert(_text('MN00023'), r.msg);
                        }
                      } catch (e) {
                        //>>Ext.Msg.alert('디코드 오류', e);
                        console.log(e);
                        Ext.Msg.alert(_text('MN00022'), e);
                      }
                    }
                  }
                });

                var group_user_store = new Ext.data.JsonStore({
                  url: '/pages/menu/config/user/php/get_group_member.php',
                  remoteSort: true,
                  sortInfo: {
                    field: 'user_nm',
                    direction: 'ASC'
                  },
                  idProperty: 'member_id',
                  root: 'data',
                  fields: ['member_id', 'user_id', 'user_nm'],
                  listeners: {
                    exception: function(
                      self,
                      type,
                      action,
                      opts,
                      response,
                      args
                    ) {
                      try {
                        var r = Ext.decode(response.responseText);
                        if (!r.success) {
                          //>>Ext.Msg.alert('정보', r.msg);
                          console.log(r);
                          Ext.Msg.alert(_text('MN00023'), r.msg);
                        }
                      } catch (e) {
                        //>>Ext.Msg.alert('디코드 오류', e);
                        console.log(e);
                        Ext.Msg.alert(_text('MN00022'), e);
                      }
                    }
                  }
                });

                var win = new Ext.Window({
                  id: 'edit_group_user',
                  cls: 'change_background_panel',
                  border: false,
                  modal: true,
                  width: 1000,
                  height: 700,
                  resizable: false,
                  layout: 'vbox',
                  buttonAlign: 'center',
                  layoutConfig: {
                    align: 'stretch',
                    defaultMargins: {
                      top: 5,
                      right: 5,
                      bottom: 5,
                      left: 5
                    }
                  },
                  title: _text('MN00193'), //사용자 수정 '그룹사용자 추가'
                  items: [
                    {
                      xtype: 'fieldset',
                      title: _text('MN01001'), //그룹',
                      layout: 'fit',
                      flex: 1,
                      bodyStyle: 'padding : 5px',
                      autoScroll: true,
                      items: [
                        {
                          xtype: 'grid',
                          cls: 'proxima_customize',
                          //anchor:'95%',
                          id: 'group_listview',
                          columnSort: true,
                          stripeRows: true,
                          reserveScrollOffset: true,
                          autoScroll: true,
                          emptyText: _text('MSG00148'), //결과 값이 없습니다.'등록된 데이터가 없습니다.',
                          multiSelect: true,
                          store: new Ext.data.ArrayStore({
                            fields: [
                              { name: 'member_group_id' },
                              { name: 'member_group_name' },
                              { name: 'description' },
                              { name: 'created_date' }
                            ],
                            listeners: {
                              load: function(self) {}
                            }
                          }),
                          columns: [
                            {
                              header: _text('MN02148'),
                              dataIndex: 'member_group_name',
                              width: 200
                            }, //'그룹명'
                            {
                              header: _text('MN00049'),
                              dataIndex: 'description',
                              width: 720
                            } //'설명'
                          ],
                          listeners: {
                            render: function(self) {
                              Ext.getCmp('group_listview')
                                .getStore()
                                .add(records);
                            }
                          }
                        }
                      ]
                    },
                    {
                      xtype: 'fieldset',
                      title: _text('MN00189'), //'사용자',
                      layout: 'fit',
                      flex: 2,

                      bodyStyle: 'padding : 5px',
                      autoScroll: true,

                      items: [
                        {
                          xtype: 'panel',
                          layout: 'hbox',
                          layoutConfig: {
                            align: 'stretch',
                            defaultMargins: {
                              top: 2,
                              right: 2,
                              bottom: 2,
                              left: 2
                            }
                          },
                          items: [
                            {
                              xtype: 'grid',
                              cls: 'proxima_customize',
                              //border: false,
                              id: 'all_user_list',
                              flex: 1,
                              stripeRows: true,
                              store: user_store,
                              viewConfig: {
                                emptyText: _text('MSG00148'), //결과 값이 없습니다.'업데이트 정보가 없습니다.',
                                forceFit: true
                              },
                              bbar: {
                                xtype: 'paging',
                                store: user_store,
                                pageSize: 20
                              },
                              tbar: [
                                {
                                  xtype: 'combo',
                                  id: 's_field',
                                  width: 130,
                                  triggerAction: 'all',
                                  editable: false,
                                  mode: 'local',
                                  store: [
                                    /*
                                                      ['s_created_time', '등록일자'],
                                                      ['s_user_id', '아이디'],
                                                      ['s_name', '이름'],
                                                      ['s_user_dept', '부서'],
                                                      ['s_job_position','직위']
                                                      ['s_group','그룹']
                                              */
                                    ['s_created_time', _text('MN00104')],
                                    ['s_user_id', _text('MN00195')],
                                    ['s_name', _text('MN00196')],
                                    ['s_user_dept', _text('MN00181')],
                                    ['s_job_position', _text('MN00260')],
                                    ['s_group', _text('MN01001')]
                                  ],
                                  value: 's_created_time',
                                  listeners: {
                                    select: function(self, r, i) {
                                      if (i == 0) {
                                        self.ownerCt.get(2).setVisible(true);
                                        self.ownerCt.get(3).setVisible(false);
                                        self.ownerCt.get(4).setVisible(false);
                                      } else if (i == 5) {
                                        self.ownerCt.get(4).setVisible(true);
                                        self.ownerCt.get(2).setVisible(false);
                                        self.ownerCt.get(3).setVisible(false);
                                      } else {
                                        self.ownerCt.get(3).setVisible(true);
                                        self.ownerCt.get(2).setVisible(false);
                                        self.ownerCt.get(4).setVisible(false);
                                      }
                                    }
                                  }
                                },
                                ' ',
                                {
                                  xtype: 'datefield',
                                  id: 's_value1',
                                  width: 90,
                                  format: 'Y-m-d',
                                  listeners: {
                                    render: function(self) {
                                      //self.setValue(new Date());
                                      self.setMaxValue(new Date());
                                    }
                                  }
                                },
                                {
                                  hidden: true,
                                  //allowBlank: false,
                                  xtype: 'textfield',
                                  width: 120,
                                  id: 's_value2',
                                  listeners: {
                                    specialKey: function(self, e) {
                                      var w = self.ownerCt.ownerCt;
                                      if (
                                        e.getKey() == e.ENTER &&
                                        self.isValid()
                                      ) {
                                        e.stopEvent();
                                        w.doSearch(
                                          w.getTopToolbar(),
                                          this.store
                                        );
                                      }
                                    }
                                  }
                                },
                                {
                                  xtype: 'combo',
                                  width: 150,
                                  //fieldLabel : '그룹 별 필터',
                                  editable: false,
                                  id: 'search_group',
                                  typeAhead: true,
                                  triggerAction: 'all',
                                  hidden: true,
                                  editable: false,
                                  displayField: 'member_group_name',
                                  valueField: 'member_group_id',
                                  hiddenName: 'member_group_id',
                                  hiddenValue: 'member_group_name',
                                  store: new Ext.data.JsonStore({
                                    url:
                                      '/pages/menu/config/user/php/get_group.php',
                                    remoteSort: true,
                                    root: 'data',
                                    sortInfo: {
                                      field: 'member_group_id',
                                      direction: 'DESC'
                                    },
                                    idProperty: 'member_group_id',
                                    root: 'data',
                                    fields: [
                                      'member_group_id',
                                      'member_group_name',
                                      'description',
                                      'is_admin'
                                    ]
                                  }),
                                  listeners: {}
                                }, {
                                  //부서별 콤보 필터
                                  width:150,
                                  xtype:'combo',
                                  hidden:true,
                                  allowBlank: false,
                                  editable: false,
                                  mode: "local",
                                  displayField: "code_itm_nm",
                                  //valueField: "code_itm_code",
                                  //hiddenValue: "code_itm_code",
                                  valueField: "code_itm_nm",
                                  hiddenValue: "code_itm_nm",
                                  typeAhead: true,
                                  triggerAction: "all",
                                  lazyRender: true,
                                  store: new Ext.data.JsonStore({
                                      restful: true,
                                      proxy: new Ext.data.HttpProxy({
                                          method: "GET",
                                          url: '/api/v1/open/data-dic-code-sets/' + 'DEPT' + '/code-items',
                                          type: "rest"
                                      }),
                                      root: "data",
                                      fields: [
                                      { name: "code_itm_code", mapping: "code_itm_code" },
                                      { name: "code_itm_nm", mapping: "code_itm_nm" },
                                      { name: "code_path", mapping: "code_path" },
                                      { name: "id", mapping: "id" }
                                      ],
                                      listeners: {
                                          load: function (store, r, option) {
                                              /**
                                              * 스토어를 비운다음 부모 노드를 코드로 찾아서
                                              * 코드의 자식 코드 아이템들만 모은다음 
                                              * 다시 스토어에 넣어준다.,
                                              */
                                              var dpCode = 'c104176100'
                                              store.remove(r);
                                              
                                              var deleteIndexArray = [];
                                              Ext.each(r, function (code) {
                                              var codeId = code.get('code_itm_code');
                                              if (codeId == dpCode) {
                                                  var parentCodePath = code.get('code_path');
                                                  Ext.each(r, function (code2, i) {
                                                  var id = code2.get('id');
                                                  var checkCodePath = parentCodePath + '/' + id;
                                                  if (checkCodePath != code2.get('code_path')) {
                                                      deleteIndexArray.push(i);
                                                  }
                                                  });
                                              };
                                              });
              
                                              Ext.each(deleteIndexArray, function (deleteArrayIndex) {
                                              r.remove(r[deleteArrayIndex]);
                                              });
              
                                              store.add(r);
              
                                              if (!Ext.isEmpty(combo.getValue())) {
                                              combo.setValue(combo.getValue());
                                              }
                                          },
                                          exception: function (self, type, action, opts, response, args) {
                                              try {
                                              var r = Ext.decode(response.responseText, true);
              
                                              if (!r.success) {
                                                  Ext.Msg.alert(_text("MN00023"), r.msg);
                                              }
                                              } catch (e) {
                                              Ext.Msg.alert(_text("MN00023"), r.msg);
                                              }
                                          }
                                      }
                                  }),
                                  listeners: {
                                      afterrender: function (self) {
                                          self.getStore().load({
                                              params: {
                                                  is_code: 1
                                              }
                                          });
                                      },
                                      select: function (self, record, idx) {
                                          //self.setValue(record.get("code_itm_code"));
                                          self.setValue(record.get("code_itm_nm"));
                                      }
                                  }
                              }, {
                                  xtype: 'button',
                                  //>>text: '조회',
                                  //text: _text('MN00037'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00037') +
                                    '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(b, e) {
                                    var w = b.ownerCt.ownerCt;
                                    w.doSearch(w.getTopToolbar(), this.store);
                                  }
                                },
                                {
                                  //icon: '/led-icons/arrow_refresh.png',
                                  //>>text: '새로고침',
                                  //text: _text('MN00139'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00139') +
                                    '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    Ext.getCmp('all_user_list')
                                      .getStore()
                                      .load({
                                        params: {
                                          start: 0,
                                          limit: myPageSize
                                        }
                                      });
                                  },
                                  scope: this
                                }
                              ],
                              columns: [
                                new Ext.grid.RowNumberer(),
                                {
                                  header: 'number',
                                  dataIndex: 'member_id',
                                  hidden: 'true'
                                },
                                {
                                  header: _text('MN00188'),
                                  dataIndex: 'user_id',
                                  align: 'center'
                                }, //'사번'
                                {
                                  header: _text('MN00196'),
                                  dataIndex: 'user_nm',
                                  align: 'center'
                                }, //'성명'
                                {
                                  header: _text('MN00181'),
                                  dataIndex: 'dept_nm',
                                  align: 'center'
                                }, //'부서'
                                //{header:_text('MN00188'),dataIndex:'occu_kind',align:'center' ,hidden:'true'},//'직종'
                                {
                                  header: _text('MN00260'),
                                  dataIndex: 'job_position',
                                  align: 'center',
                                  hidden: 'true'
                                }, //'직위'
                                //{header:_text('MN00188'),dataIndex:'job_duty',align:'center',hidden:'true'},//'직무'
                                //{header:_text('MN00188'),dataIndex:'group_name',align:'center'},//'그룹'
                                {
                                  header: _text('MN00337'),
                                  dataIndex: 'breake',
                                  align: 'center',
                                  hidden: 'true'
                                }, //'재직구분'
                                {
                                  header: _text('MN00331'),
                                  dataIndex: 'hired_date',
                                  align: 'center',
                                  renderer: Ext.util.Format.dateRenderer(
                                    'Y-m-d'
                                  ),
                                  hidden: 'true'
                                }, //'입사일자'
                                {
                                  header: _text('MN00332'),
                                  dataIndex: 'retire_date',
                                  align: 'center',
                                  renderer: Ext.util.Format.dateRenderer(
                                    'Y-m-d'
                                  ),
                                  hidden: 'true'
                                }, // '퇴사일자'
                                {
                                  header: _text('MN00333'),
                                  dataIndex: 'dep_tel_num',
                                  align: 'center',
                                  hidden: 'true'
                                } //'사내번호'
                              ],
                              listeners: {
                                viewready: function(self) {
                                  // 2010-12-01 네비게이션 추가 by CONOZ
                                  self.getStore().load({
                                    params: {
                                      start: 0,
                                      limit: myPageSize
                                    }
                                  });
                                }
                              },

                              doSearch: function(tbar, store) {
                                var combo_value = tbar.get(0).getValue(),
                                  params = {};
                                params.start = 0;
                                params.limit = myPageSize;

                                if (combo_value == 's_created_time') {
                                  if (Ext.isEmpty(tbar.get(2).getValue())) {
                                    Ext.Msg.alert(
                                      _text('MN00024'),
                                      _text('MSG00041')
                                    ); //Ext.Msg.alert('정보', '검색 값를 선택하세요.');
                                    return;
                                  }

                                  params.search_field = combo_value;
                                  params.search_value = tbar
                                    .get(2)
                                    .getValue()
                                    .format('Y-m-d');
                                } else if (combo_value == 's_group') {
                                  if (Ext.isEmpty(tbar.get(4).getValue())) {
                                    Ext.Msg.alert(
                                      _text('MN00024'),
                                      _text('MSG00098')
                                    ); //Ext.Msg.alert('정보', '그룹을 선택해주세요.');
                                    return;
                                  }
                                  params.search_field = combo_value;
                                  params.search_value = tbar.get(4).getValue();
                                } else {
                                  params.search_field = combo_value;
                                  params.search_value = tbar.get(3).getValue();
                                }
                                if (
                                  Ext.isEmpty(params.search_field) ||
                                  Ext.isEmpty(params.search_value)
                                ) {
                                  //>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
                                  Ext.Msg.alert(
                                    _text('MN00023'),
                                    _text('MSG00007')
                                  );
                                } else {
                                  Ext.getCmp('all_user_list')
                                    .getStore()
                                    .load({
                                      params: params
                                    });
                                }
                              }
                            },
                            {
                              xtype: 'fieldset',
                              width: 70,
                              layout: 'vbox',
                              layoutConfig: {
                                type: 'vbox',
                                align: 'stretch',
                                pack: 'center'
                              },
                              border: false,
                              items: [
                                {
                                  xtype: 'button',
                                  //width: 60,
                                  //height: 40,
                                  //margins: '0 0 50 0',
                                  // text: '사용자추가'
                                  //text: _text('MN00126'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00126') +
                                    '"><i class="fa fa-chevron-right" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    var sm = Ext.getCmp(
                                      'all_user_list'
                                    ).getSelectionModel();
                                    if (sm.hasSelection()) {
                                      var selections = sm.getSelections();
                                      for (
                                        var i = 0;
                                        i < selections.length;
                                        i++
                                      ) {
                                        var member_id = selections[i].get(
                                          'member_id'
                                        );
                                        var is_member = Ext.getCmp(
                                          'group_user_list'
                                        )
                                          .getStore()
                                          .find('member_id', member_id);
                                        // 기존 스토어에 해당 사용자가 없으면 추가
                                        if (is_member == -1) {
                                          Ext.getCmp('group_user_list')
                                            .getStore()
                                            .add(selections[i]);
                                        }
                                      }
                                    } else {
                                      Ext.Msg.alert(
                                        _text('MN00023'),
                                        _text('MN00219')
                                      );
                                    }
                                  }
                                },
                                {
                                  xtype: 'button',
                                  //margins: '0 0 50 0',
                                  //width: 60,
                                  //height: 40,
                                  // text: '사용자삭제'
                                  //text: _text('MN00408'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00408') +
                                    '"><i class="fa fa-chevron-left" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    var sm = Ext.getCmp(
                                      'group_user_list'
                                    ).getSelectionModel();
                                    if (sm.hasSelection()) {
                                      var selections = sm.getSelections();
                                      for (
                                        var i = 0;
                                        i < selections.length;
                                        i++
                                      ) {
                                        var member_id = selections[i].get(
                                          'member_id'
                                        );
                                        var index = Ext.getCmp(
                                          'group_user_list'
                                        )
                                          .getStore()
                                          .find('member_id', member_id);
                                        Ext.getCmp('group_user_list')
                                          .getStore()
                                          .removeAt(index);
                                      }
                                    } else {
                                      Ext.Msg.alert(
                                        _text('MN00023'),
                                        _text('MSG00221')
                                      );
                                    }
                                  }
                                }
                              ]
                            },
                            {
                              xtype: 'grid',
                              id: 'group_user_list',
                              cls: 'proxima_customize',
                              flex: 1,
                              stripeRows: true,
                              store: group_user_store,
                              viewConfig: {
                                emptyText: _text('MSG00148'), //결과 값이 없습니다.
                                forceFit: true
                              },
                              bbar: {
                                xtype: 'paging',
                                store: group_user_store,
                                pageSize: 20
                              },
                              tbar: [
                                {
                                  //icon: '/led-icons/arrow_refresh.png',
                                  //>>text: '새로고침',
                                  //text: _text('MN00139'),
                                  cls: 'proxima_button_customize',
                                  width: 30,
                                  text:
                                    '<span style="position:relative;top:1px;" title="' +
                                    _text('MN00139') +
                                    '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
                                  handler: function(btn, e) {
                                    Ext.getCmp('group_user_list')
                                      .getStore()
                                      .reload();
                                  },
                                  scope: this
                                }
                              ],
                              columns: [
                                new Ext.grid.RowNumberer(),
                                {
                                  header: 'number',
                                  dataIndex: 'member_id',
                                  hidden: 'true'
                                },
                                {
                                  header: _text('MN00188'),
                                  dataIndex: 'user_id',
                                  align: 'center'
                                }, //'사번'
                                {
                                  header: _text('MN00196'),
                                  dataIndex: 'user_nm',
                                  align: 'center'
                                }, //'성명'
                                {
                                  header: _text('MN00181'),
                                  dataIndex: 'dept_nm',
                                  align: 'center'
                                } //'부서'
                              ],
                              listeners: {
                                viewready: function(self) {
                                  // 2010-12-01 네비게이션 추가 by CONOZ

                                  self.getStore().load({
                                    params: {
                                      start: 0,
                                      limit: myPageSize,
                                      member_group_id: records.get(
                                        'member_group_id'
                                      )
                                    }
                                  });
                                }
                              }
                            }
                          ]
                        }
                      ]
                    }
                  ],

                  buttons: [
                    {
                      text:
                        '<span style="position:relative;top:1px;"><i class="fa fa-edit" style="font-size:13px;"></i></span>&nbsp;' +
                        _text('MN00043'),
                      scale: 'medium',
                      handler: function() {
                        var group_members = Ext.getCmp(
                          'group_user_list'
                        ).getStore().data;
                        var members = [];

                        Ext.each(group_members.items, function(i) {
                          members.push(i.data.member_id);
                        });

                        Ext.Ajax.request({
                          url: '/store/user/group_user_oracle.php',
                          params: {
                            member_group_id: records.get('member_group_id'),
                            'members[]': members
                          },
                          callback: function(opts, success, resp) {
                            if (success) {
                              try {
                                //  var res = Ext.decode(response.responseText);
                                //  var suc = res.success;
                                //  var msg = res.msg;
                                Ext.getCmp('group_list')
                                  .getStore()
                                  .reload();
                                Ext.getCmp('edit_group_user').close();
                              } catch (e) {
                                console.log(e);
                                Ext.Msg.alert(_text('MN00022'), e);
                              }
                            } else {
                              console.log(resp);
                              Ext.Msg.alert(_text('MN01098'), resp.statusText); //'서버 오류'
                            }
                          }
                        });
                      }
                    },
                    {
                      text:
                        '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                        _text('MN00004'),
                      scale: 'medium',
                      handler: function() {
                        Ext.getCmp('edit_group_user').close();
                      }
                    }
                  ]
                }).show();
              } else {
                //Ext.Msg.alert('정보', '그룹에 추가할 사용자를 선택해주세요.');MSG00218
                Ext.Msg.alert(_text('MN00023'), _text('MSG00218'));
              }
            }
          },
          '-',
          {
            //icon: '/led-icons/arrow_refresh.png',
            //>>text: '새로고침',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00139') +
              '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
            handler: function(btn, e) {
              Ext.getCmp('group_list')
                .getStore()
                .reload();
            }
          },
          '-',
          {
            //text: '모두 열기',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN02146') +
              '"><i class="fa fa-folder-open" style="font-size:13px;color:white;"></i></span>',
            // icon: '/led-icons/folder-open.gif',
            handler: function(btn) {
              //expander.expandAll(Ext.getCmp('group_list').getStore().getTotalCount());
              //btn.ownerCt.ownerCt.expandAllGroups();
              btn.ownerCt.ownerCt.view.expandAllGroups();
            }
          },
          {
            //text: '모두 접기',
            cls: 'proxima_button_customize',
            width: btnWidth,
            text:
              '<span style="position:relative;" title="' +
              _text('MN02147') +
              '"><i class="fa fa-folder" style="font-size:13px;color:white;"></i></span>',
            //icon: '/led-icons/folder.gif',
            handler: function(btn) {
              //expander.collapseAll(Ext.getCmp('group_list').getStore().getTotalCount());
              btn.ownerCt.ownerCt.view.collapseAllGroups();
            }
          }
        ]
      });

      Ariel.config.Group.superclass.initComponent.call(this);
    },

    request: function(p) {
      var user_id = '';
      if (p.action == 'edit') {
        var r = Ext.getCmp('group_list')
          .getSelectionModel()
          .getSelected();
        user_id = r.get('member_group_id');
      }

      Ext.Ajax.request({
        url: '/store/get_group_form.php',
        params: {
          action: p.action,
          member_group_id: p.member_group_id
        },
        callback: function(self, success, response) {
          try {
            var r = Ext.decode(response.responseText);
            if (p.action == 'del') {
              Ext.getCmp('group_list')
                .getStore()
                .reload();
            } else {
              r.show();
            }
          } catch (e) {
            //>>Ext.Msg.alert('오류', e);
            console.log(e);
            Ext.Msg.alert(_text('MN00022'), e);
          }
        }
      });
    }
  });

  return {
    xtype: 'tabpanel',
    cls: 'proxima_tabpanel_customize',
    activeTab: 0,
    border: false,

    items: [new Ariel.config.User(), new Ariel.config.Group()]
  };
})()

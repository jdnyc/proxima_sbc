// (function() {
//   Ext.ns('Ariel.DashBoard');
// Ariel.DashBoard.DownLoad = Ext.extend(Ext.tree.TreePanel, {
//   title:
//     '<span class="user_span"><span class="icon_title"><i class="fa fa-download"></i></span><span class="main_title_header">' +
//     _text('MN02209') +
//     '</span></span>',
//   border: false,
//   bodyStyle: { 'background-color': '#eaeaea', overflow: 'hidden' },
//   split: true,
//   rootVisible: false,
//   cls: 'tree_menu grid_title_customize proxima_customize',
//   lines: false,
//  listeners: {
//     render: function(self) {
//       var url = Ariel.DashBoard.Url.download;
//       ajax(url, 'GET', null, function(scope, res) {
//         var rootNode = self.getRootNode();
//         var downloads = res.data;
//        Ext.each(downloads, function(download, i) {
//          var downloadNode = new Ext.tree.TreeNode({
//            id: download.id,
//             text: '<span style="position:relative;top:3px;"><i class="fa ' + download.icon + '" style="font-size:15px;"></i></span>&nbsp;&nbsp;' +
//               download.title,
//             url: download.url,
//              leaf: true
// });
//           rootNode.appendChild(downloadNode);
//       });
//              });     
//            },
//            click: function(node, e) {
//              //var url = "/files/manual/manual_cms.ppt";
//              Ext.Msg.show({
//                title: _text('MN00024'),
//                msg: _text('MSG02030'),
//                buttons: Ext.Msg.OKCANCEL,
//                fn: function(btn) {
//                  if (btn == 'ok') {
//                    var downloadUrl = node.attributes.url;
//                    window.open(downloadUrl);

//   }
//            }
//          });
//        }
//      },
//      root: {
//        expanded: false
//      },
//      initComponent: function(config) {
//        Ariel.DashBoard.DownLoad.superclass.initComponent.call(this);
//      }
//    });
//  // return new Ariel.DashBoard.DownLoad();
//  })();

(function() {
  Ext.ns('Ariel.DashBoard');
  Ariel.DashBoard.DownLoad = Ext.extend(Ext.grid.GridPanel, {
        title: '<span class="user_span"><span class="icon_title"><i class="fa fa-download"></i></span><span class="main_title_header">' +
        _text('MN02209') + '</span></span>',
        cls: 'grid_title_customize proxima_customize',
        permission_code: 'download',
        statusButtonShow: false,
        border: false,
        bodyStyle: { 'background-color': '#eaeaea', overflow: 'hidden' },
        loadMask: true,
        stripeRows: true,
        split: true,
        lines: false,
        frame: false,
        autoWidth: true,
        layout: 'fit',
        pageSize: 30,
        viewConfig: {
            emptyText: '목록이 없습니다',
            forceFit: true,
            border: false
        },
        listeners: {
          viewready: function(self) {
            self.getStore().load({
              params: {
                start: 0,
                limit: this.pageSize
              }
            });
          },
          render: function(self) {
            self.store.load();
          },
          rowdblclick:function(self) {
            var sm= self.getSelectionModel();
            var getRecord = sm.getSelected();
            console.log(getRecord);
            if(sm.hasSelection()){
              Ext.Msg.show({
                title: '다운로드',
                msg: getRecord.data.title + '을 다운로드 받으시겠습니까?',
                buttons: Ext.MessageBox.OKCANCEL,
                fn: function(btn, text, opts){
                  if(btn == 'ok'){
                    window.open('/custom/ktv-nps/download/file_download.php?id='+getRecord.data.id);
                    // Ext.Ajax.request({
                    //   params: {},
                    //   callback: function(opts, success, response){
                    //     if(success){
                    //       try{
                    //         var returnVal = Ext.decode(response.responseText);
                    //         Ext.Msg.alert('확인', '다운로드 되었습니다.');
                    //       } catch(e) {
                    //         Ext.Msg.alert(e['name'], e['message']);
                    //       }
                    //     } else {
                    //       Ext.Msg.alert('status' + response.status, response.statusText);
                    //     }
                    //   }
                    // });
                  }
                }
              })
              
            }
          }

        },
        initComponent: function() {
          this._initialize();
          Ariel.DashBoard.DownLoad.superclass.initComponent.call(this);
        },
       _getPermission: function (permissions, current) {
            var rtn = false;
            Ext.each(permissions, function (permission) {
                if (permission == '*') {
                    rtn = true;
                } else if (permission == current) {
                    rtn = true;
                }
            });
            return rtn;
        },

        
        _initializeByPermission: function(permissions) {
          var _this = this;

          if(this._getPermission(permissions)){
            _this.getTopToolbar().addButton({
              xtype: 'a-iconbutton',
              text: '추가',
              handler: function(self) {
               var win =  _this._addFormWindow(this.store);
              //  var win =  _this._uploadWindow(this.store);
               win.show();
              }
            });
        };
        if(this._getPermission(permissions)){
          _this.getTopToolbar().addButton({
            hidden: _this.statusButtonshow,
            xtype: 'a-iconbutton',
            text: '삭제',
            handler: function(self) {
              var sm = _this.getSelectionModel();
              sel = sm.getSelected();
              if(sm.hasSelection()){
                Ext.Msg.show({
                  title: '다운로드 목록 삭제',
                  buttons: Ext.MessageBox.OKCANCEL,
                  msg: sel.data.title +'을 삭제 하시겠습니까?',
                  fn: function(btn, text, opts){
                    if(btn == 'ok'){
                      Ext.Ajax.request({
                        url: '/store/download/download_delete.php',
                        params: {
                          action: 'delete',
                          id: sel.data.id
                        },
                        callback: function(opts, success, resp){
                          if(success){
                            try {
                              _this.store.reload();
                            } catch(e) {
                              Ext.Msg.alert(e['name'], e['message']);
                            }
                          } else {
                            Ext.Msg.alert('status', + resp.status, resp.statusText);
                          }
                        }
                    });

                  }
                }
                })
              } else {
                Ext.Msg.alert('알림', '삭제하실 목록을 선택해주세요');
              }
            }
            });
          };

          if(true) {
            _this.getTopToolbar().addButton({
              xtype: 'a-iconbutton',
              text: '다운로드',
            handler: function(self, e){
              var sm = _this.getSelectionModel();
              sel = sm.getSelected();
              if(sm.hasSelection()){
                Ext.Msg.show({
                  title: '다운로드',
                  buttons: Ext.MessageBox.OKCANCEL,
                  msg: sel.data.title + '을 다운로드 받으시겠습니까?',
                  fn: function(btn, text, opts){
                    if(btn == 'ok'){
                      window.open('/custom/ktv-nps/download/file_download.php?id='+sel.data.id);
                      // Ext.Ajax.request({
                      //   // params: {},
                      //   callback: function(opts, success, response){
                      //     if(success){
                      //       try{
                      //         var returnVal = Ext.decode(response.responseText);
                      //         Ext.Msg.alert('확인', '다운로드 되었습니다.');
                      //       } catch(e) {
                      //         Ext.Msg.alert(e['name'], e['message']);
                      //       }
                      //     } else {
                      //       Ext.Msg.alert('status' + response.status, response.statusText);
                      //     }
                      //   }
                      // });
                    }
                  }
                })
              } else {
                Ext.Msg.alert('알림', '다운로드 대상을 선택해주세요');
              }
            }
          });
        };
        if(true) {
          _this.getTopToolbar().addButton({
            cls: 'proxima_button_customize',
            width: 30,
            text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
            handler: function (self) {
                _this.getStore().reload();
            },
            scope: this
          })
        }
          _this.getTopToolbar().doLayout();
        },
        _initialize: function () {
          var _this = this;
          // this.addForm = this._addFormWindow(this.store);
          // this._addWin = new Ext.Window({
          //   title: '추가하기',
          //   width: 600,
          //   modal: true

          // });
          this.store = new Ext.data.JsonStore({
            url:'/custom/ktv-nps/store/downloads.php',
            root: 'data',
            totalProperty: 'total',
            fields:  [
              { name: 'id' , type: 'int'},
              { name: 'icon', type: 'str'},
              { name: 'title', type: 'str'},
              { name: 'path', type: 'str'}, 
              { name: 'description', type: 'str'},
              { name: 'published', type: 'str'},
              { name: 'show_order', type: 'int'},
              { name: 'created_at', type: 'date'},
              { name: 'updated_at', type: 'date'}
              ]
          });

          this.colModel = new Ext.grid.ColumnModel({
            defaultSortable: false, 
            defaults: {
              width: 500,
              sortable: false
            },
            
            columns: [
              // new Ext.grid.RowNumberer(),
                {header: 'id' ,dataindex: 'id', hidden: true},
               {header: '<span style="position:relative;top:3px;font-size:13px;">&nbsp;&nbsp; 목록</span>', dataIndex: 'title', height:60, renderer:function(v,meta,record){
                //  console.log(record);
                 var icon = record.get('icon');
                //  console.log(icon);
                //  console.log(v);
                return '<i class="fa '+icon+'"; style ="position:relative;top:3px;font-size:13px" />'+v;
               } },
               {header: 'path', dataIndex: 'path', hidden: true},
               {header: 'description', dataIndex: 'description', hidden: true},
               {header: 'published', dataIndex: 'published', hidden: true},
               {header: '생성날짜', dataIndex: 'created_at', width: 20, xtype: 'datecolumn',format: 'Y-m-d H:i:s', hidden: true},
               {header: '수정날짜', dataIndex: 'updated_at', width: 20, xtype: 'datecolumn',format: 'Y-m-d H:i:s', hidden: true}
            ]

          });

          // this.bbar = {
          //   xtype: 'paging',
          //   pageSize: 20,
          //   displayInfo: true,
          //   store: _this.store
          // };
          this.bbar = new Ext.PagingToolbar({
            // pageSize: 20,
            pageSize: this.pageSize,
            store: this.store,

          })


          this.tbar = [
          //   {
          //   // xtype: 'a-iconbutton',
          //   // text: '추가',
          //   // hadler: function () {
          //   //   //var addForm = this._addFormWindow(this.store);
          //   //   // this.addForm.show();
          //   // //   _this._addWin.show();
          //   //  }
          // }, '-',{
          //   xtype: 'a-iconbutton',
          //   text: '삭제',
          //   handler: function() {
          //     var sm = _this.getSelectionModel();
          //     sel = sm.getSelected();
          //     console.log(sel);
          //     if(sm.hasSelection()){
          //       Ext.Msg.show({
          //         title: '다운로드 목록 삭제',
          //         buttons: Ext.MessageBox.OKCANCEL,
          //         msg: sel.data.title +'을 삭제 하시겠습니까?',
          //         fn: function(btn, text, opts){
          //           if(btn == 'ok'){
          //             Ext.Ajax.request({
          //               url: '/store/download/download_delete.php',
          //               params: {
          //                 action: 'delete',
          //                 id: sel.data.id
          //               },
          //               callback: function(opts, success, resp){
          //                 if(success){
          //                   try {
          //                     _this.store.reload();
          //                   } catch(e) {
          //                     Ext.Msg.alert(e['name'], e['message']);
          //                   }
          //                 } else {
          //                   Ext.Msg.alert('status', + resp.status, resp.statusText);
          //                 }
          //               }
          //           });

          //         }
          //       }
          //       })
          //     } else {
          //       Ext.Msg.alert('알림', '삭제하실 목록을 선택해주세요');
          //     }
          //   }
          // },{
          //   xtype: 'a-iconbutton',
          //   text: '다운로드',
          //   handler: function(self, e){
          //     var sm = _this.getSelectionModel();
          //     sel = sm.getSelected();
          //     if(sm.hasSelection()){
          //       Ext.Msg.show({
          //         title: '다운로드',
          //         buttons: Ext.MessageBox.OKCANCEL,
          //         msg: sel.data.title + '을 다운로드 받으시겠습니까?',
          //         fn: function(btn, text, opts){
          //           if(btn == 'ok'){
          //             window.open('/store/download/file_download.php?id='+sel.data.id);
          //             Ext.Ajax.request({
          //               params: {},
          //               callback: function(opts, success, response){
          //                 if(success){
          //                   try{
          //                     var returnVal = Ext.decode(response.responseText);
          //                     Ext.Msg.alert('확인', '다운로드 되었습니다.');
          //                   } catch(e) {
          //                     Ext.Msg.alert(e['name'], e['message']);
          //                   }
          //                 } else {
          //                   Ext.Msg.alert('status' + response.status, response.statusText);
          //                 }
          //               }
          //             });
          //           }
          //         }
          //       })
          //     } else {
          //       Ext.Msg.alert('알림', '다운로드 대상을 선택해주세요');
          //     }
          //   }
          // },{
          //   cls: 'proxima_button_customize',
          //   width: 30,
          //   text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
          //   handler: function () {
          //       _this.store.reload();
          //   },
          //   scope: this
          // }];
          ];


        },
        
          
        



        //  _getPermission: function(permissions, current){
        //   var rtn = false;
        //   Ext.each(permissions, function(permission){
        //     if(permission == '*'){
        //         rtn = true;
        //     } else if(permission == current){
        //         rtn = true;
        //     }
        //   });
        //   return rtn;
        // },
        // _initializeByPermission: function(permissions) {
        //   var _this = this;
        //   if(this._getPermission(permissions)){
        //     this.getTopToolbar().addButton({
        //       xtype: 'aw-button',
        //       iCls: 'fa fa-plus',
        //       text: '추가',
        //       handler: function(self) {
        //         var win = _this.createWindow('add', '', _this);
        //         win.show();
        //       }
        //     });
        //   };

        //   if(this._getPermission(permissions)){
        //     this.getTopToolbar().addButton({
        //       xtype: 'aw-button',
        //       iCls: 'fa fa-edit',
        //       text: '수정',
        //       handler: function ( self){
        //         var sel = _this.getSelectionModel().getSelected();
        //         if ( Ext.isEmpty(sel)){
        //           Ext.Msg.alert('알림', '목록을 선택하여 주세요');
        //           return;
        //         } else {
        //           var win = _this.createWindow('edit', sel.data, _this);
        //           win.show();
        //         }
        //       }
        //     })
        //   }
        // },
        // createWindow = function(action, list, target) {
        //   var _this = this;
        //   if(Ext.isEmpty(list)){
        //     var title = ''
        //   }

        //   var add_win = new Ext.Window({
        //     layout: 'fit',
        //     id: 
        //   })
        // }

      //   ,
        // _addFormWindow: function(typeSe, store) {
        //   var _this = this;
        //   var form = new Ext.form.FormPanel({
        //     defaultType: 'textfield',
        //     padding: 5,
        //     defaults: {
        //       anchor: '95%'
        //     },
        //     items: [{
        //       fieldLabel: '제목',
        //       allowBlank: false
        //     }]
        //   });

        //   var win = new Ext.window({
        //     title: '추가',
        //     width: 1000,
        //     modal: true,
        //     items: form,
        //     buttons: [{
        //       text: '확인',
        //       scale: 'medium'
        //     }]
        //   });
        //   return win.show();
        // }



      //   _addFormWindow: function(typeSe, store) {
      //     var _this = this;

      //     var form = new Ext.form.FormPanel({
      //       frame: true,
      //       padding: 5,
      //       border: false,
      //       defaults: {
      //         anchor: '95%'
      //       },
      //       items: [{
      //         fieldLabel: '제목',
      //         allowBlank: false, 
      //         xtype: 'textfield',
      //         name: 'title',
      //         EmptyText: '제목을 입력하세요'
      //       },{
      //         fieldLabel: '파일첨부',
      //         xtype: 'fileuploadfield',
      //         id: 'fileAttachUpload',
      //         fileUpload: true,
      //         name: 'fileAttach',
      //         listeners: {
      //           fileselected: function(self, value) {
      //             Ext.getCmp('fileAttachUpload').setValue(value);
      //           }
      //         },
      //         buttonCfg: {
      //           buttonText: '',
      //           hidden: true
      //         }
      //       },{
      //         xtype: 'combo',
      //         fieldLabel: 'icon 선택',
      //         name: 'icon',
      //         store: store,
      //         mode: 'local',
      //         displayField: 'icon',
      //         triggerAction: 'all',
      //         editable: false
      //       },{
      //         xtype: 'checkbox',
      //         fieldLabel: '게시 여부',
      //         name: 'published'
      //       }]
      
      //     });

      //     var win = new Ext.Window({
      //         title: '추가하기',
      //         width: 600,
      //         modal: true,
      //         closable: true,
      //         closeAction: 'hide',
      //         items: form,
      //           buttons: [{
      //             text: '확인',
      //             handler: function(self) {
      //               var getForm = form.getForm();
      //               if(!getForm.isValid()){
      //                 Ext.Msg.show({
      //                   title: '알림',
      //                   msg: '입력되지 않은 값이 있습니다',
      //                   buttons: Ext.Msg.OK,
      //                 });
      //                 return;
      //               }
      //               var formValue = getForm.getValues();
      //               regist_form.submit({
      //                 url: '/store/download/file_upload.php',
      //                 headers: { 'Content-Type': 'multipart/form-data; charset=UTF-8'},
      //                 params: {

      //                 },
      //                 success: function(form, action){
      //                   var r = Ext.decode(action.response.responseText);
      //                   if(r.success){
      //                     Ext.Msg.alert('확인', '등록이 되었습니다');
      //                     var path = r.result; 
      //                     win.close();
      //                     _this.store.load();
      //                   }
      //                 },
      //                 failure: function(form, action) {
      //                   switch(action.failureType){
      //                     case Ext.form.Action.CLIENT_INVALID:
      //                         Ext.Msg.alert(
      //                             "Failure", "Form fields may not be submitted with invalid values");
      //                             break;
      //                     case Ext.form.Action.CONNECT_FAILURE: 
      //                         Ext.Msg.alert("Failure", "Ajax communication failed");
      //                         break;
      //                     case Ext.form.Action.SERVER_INVALID:
      //                         Ext.Msg.alert("Failure", action.result.msg); 
                              
      //                 }
      //             } 
      //               })
      //             }
      //           },{
      //             text: '취소',
      //             scale: 'medium',
      //             handler: function (self) {
      //               win.close();
      //             }
      //           }]
  
      //   });
      //   return win.show();
      // }
      //   ,

      // _getPermission: function(permissions, current){
      //   var rtn = false;
      //   Ext.each(permissions, function(permission) {
      //     if (permission == '*'){
      //       rtn = true;
      //     } else if (permission == current) {
      //       rtn = true;
      //     }
      //   });
      //   return rtn;
      // },
      // _initializeByPermission: function (permissions) {
      //   var _this = this; 
      //   if(true){
      //     _this.getTopToolbar().addButton({
      //       hidden: _this.statusButtonShow,
      //       xtype: 'a-iconbutton',
      //       text: '추가',
      //       handler: function(self){
      //       _this._AddWindow(null, 'add');
      //     }
      //     });
      //   };
      // }

      // _addFormWindow: function (typeSe, store) {
      //   var _this = this;
      //   var form = new Ext.form.FormPanel ({
      //     defaultType: 'textfield',
      //     padding: 5,
      //     frame: true,
      //     border: false,
      //     defaults: {
      //       anchor: '95%'
      //     },
      //     items: [{
      //       fieldLabel: '제목',
      //       allowBlank: false,
      //       xtype: 'textfield',
      //       name: 'title',
      //       EmptyText: '제목을 입력하세요'
      //     },{
      //       fieldLabel: '파일 첨부',
      //       xtype: 'fileuploadfield',
      //       id: 'fileAttachUpload',
      //       name: 'fileAttach'
      //     }]

      //   })
      // }

      // _addFormWindow: function (typeSe, store) {
      //   /**
      //   var _this = this;
      //   var form = new Ext.form.FormPanel({
      //       defaultType: 'textfield',
      //       padding: 5,
      //       defaults: {
      //           anchor: '95%'
      //       },

      //       items: [{
      //           fieldLabel: '제목',
      //           allowBlank: false,
      //           name: 'title'
      //       }, {
      //           xtype: 'textarea',
      //           fieldLabel: '내용',
      //           height: 200,
      //           name: 'ord_ctt'
      //       }]
      //   });

      //   var win = new Ext.Window({
      //       title: '의뢰 추가',
      //       width: 1000,
      //       modal: true,
      //       items: form,
      //       buttons: [{
      //           text: '추가',
      //           scale: 'medium',
      //           handler: function (self) {
      //               var getForm = form.getForm();
      //               if (!getForm.isValid()) {
      //                   Ext.Msg.show({
      //                       title: '알림',
      //                       msg: '입력되지 않은 값이 있습니다.',
      //                       buttons: Ext.Msg.OK,
      //                   });
      //                   return;
      //               }
      //               var formValue = getForm.getValues();
      //               // Ext.Ajax.request({
      //               //     url: Ariel.DashBoard.Url.request,
      //               //     method: 'POST',
      //               //     params: {
      //               //         requestData: Ext.encode(formValue),
      //               //         typeSe: Ext.encode(typeSe)
      //               //     },
      //               //     callback: function (opts, success, resp) {
      //               //         if (success) {
      //               //             try {
      //               //                 store.reload();
      //               //                 win.close();
      //               //                 Ext.Msg.alert('알림', '요청되었습니다.');
      //               //             } catch (e) {
      //               //                 Ext.Msg.alert(e['name'], e['message']);
      //               //             }
      //               //         } else {
      //               //             Ext.Msg.alert('status: ' + resp.status, resp.statusText);
      //               //         }
      //               //     }
      //               // });
      //           }
      //       },
      //       {
      //           text: '취소',
      //           scale: 'medium',
      //           handler: function (self) {
      //               win.close();
      //           }
      //       }]
      //   });
      //   return win.show();
      // }

      
      //   _uploadWindow: function(store){
      //     var _this = this;
      //     var win = new Ext.Window({
      //       title: _text('MN00399'), //'업로드',
      //       width: 450,
      //       top: 50,
      //       height: 110,
      //       modal: true,
      //       layout: 'fit',
      //       items: [{
      //         xtype: 'form',
      //         fileUpload: true,
      //         border: false,
      //         frame: true,
      //         id: 'fileAttachuploadForm',
      //         defaults: {
      //             labelSeparator: '',
      //             labelWidth: 30,
      //             anchor: '95%',
      //             style: {
      //                 'padding-top': '5px'
      //             }
      //         },
      //         items: [{
      //             xtype: 'fileuploadfield',
      //             hidden: true,
      //             id: 'fileAttachUpload',
      //             name: 'FileAttach',
      //             listeners: {
      //                 fileselected: function (self, value) {
      //                     Ext.getCmp('fileAttachFakePath').setValue(value);
      //                 }
      //             }
      //         }, {
      //             xtype: 'compositefield',
      //             fieldLabel: _text('MN01045'), //'첨부 파일',
      //             items: [{
      //                 xtype: 'textfield',
      //                 id: 'fileAttachFakePath',
      //                 allowBlank: false,
      //                 readOnly: true,
      //                 flex: 1
      //             }, {
      //                 xtype: 'button',
      //                 text: _text('MN02176'), //'파일선택',
      //                 listeners: {
      //                     click: function (btn, e) {
      //                         $('#' + Ext.getCmp('fileAttachUpload').getFileInputId()).click();
      //                     }
      //                 }
      //             }]
      //         }],
      //         buttonsAlign: 'left',
      //         buttons: [{
      //           text: '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00046'), //'저장'
      //           scale: 'small',
      //           handler: function(b, e) {
      //             var regist_form = Ext.getCmp('fileAttachuploadForm').getForm();
      //             if(!regist_form.isValid()){
      //                   Ext.Msg.alert(_text('MN00023'), _text('MSG01006')); //알림, 첨부파일을 선택 해 주시기 바랍니다.
      //                   return;
      //             }
      //             //확장자 체크

      //             var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX', 'XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3', 'WAV', 'AI', 'PSD', 'EPS', 'TXT'];
      //             var upload_file = Ext.getCmp('fileAttachUpload').getValue();
      //             var filename_arr = upload_file.split('.');
      //             var extension_index = filename_arr.length - 1;
      //             var file_extension = filename_arr[extension_index].toUpperCase();
      //             if (extension_arr.indexOf(file_extension) === -1) {
      //               Ext.Msg.alert(_text('MN00023'), _text('MN00309') + ' : ' + extension_arr.join(', ')); //알림, 허용 확장자 :
      //               return;
      //           }
      //             var filePath = regist_form.getValues().fileAttachFakePath;
      //             var getForm= Ext.getCmp('fileAttachUpload').getValues();
      //             console.log(filePath);
      //             console.log(regist_form);
      //             console.log(getForm);
      //             regist_form.submit({
      //               url: '/test/file_upload.php',
      //               params: {

      //               },
      //               success: function(form, action) {
      //                 var r = Ext.decode(action.response.responseText);
      //                 if(r.result == 'false'){
      //                   Ext.Msg.alert(_text('MN00023'), r.msg);
      //                   return;
      //                 }
      //                 _this.getStore().load();
      //                 win.close();
      //               },
      //               failure: function(form, action) {
      //                 var r = Ext.decode(action.response.responseText);
      //                 Ext.Msg.alert(_text('MN00023'), r.msg);
      //                 }
      //             });
      //           }
      //         },{
      //           text: '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00031'), //'닫기'
      //           scale: 'small',
      //           handler: function (b, e) {
      //                 win.close();
      //           }
      //         }]
      //     }]
      //   });
      //   return win
      // },



      
        _addFormWindow: function(store) {
          var _this = this;
 // ------------------------------------------------------------------
          var form = new Ext.form.FormPanel({
            id: 'fileAttachUploadForm',
            frame: true,
            padding: 5,
            border: false,
            fileUpload: true,
            autoWidth: true,
            bodyStyle: 'padding:10px 25px 50px 20px;',
            defaults: {
              anchor: '95%'
            },
            items: [{
              fieldLabel: '제목',
              allowBlank: false, 
              xtype: 'textfield',
              name: 'title',
              EmptyText: '제목을 입력하세요'
            },{
              fieldLabel: '파일첨부',
              xtype: 'fileuploadfield',
              id: 'fileAttachUpload',           
              name: 'FileAttach',
              hidden: true,
              listeners: {
                fileselected: function(self, value) {
      
                  Ext.getCmp('fileAttachFakePath').setValue(value);
                }
              }
            },{
              xtype: 'compositefield',

              fieldLabel: _text('MN01045'),  //첨부파일
              items: [{
                xtype: 'textfield',
                width: 350,
                id: 'fileAttachFakePath',
                allowBlank: false,
                readyOnly: true,
                felx: 1
              },{
                xtype: 'button',
                text: '<span style="position:relative;top:1px;" title="' + _text('MN00399') + '"><i class="fa fa-upload" style="font-size:13px;">파일 첨부</i></span>',
                listeners: {
                  click: function (btn, e) {
                    $('#'+ Ext.getCmp('fileAttachUpload').getFileInputId()).click();
                  }
                }


              }]
            },{
              xtype: 'combo',
              fieldLabel: 'icon 선택',
              name: 'icon',
              // store: store,
              store: new Ext.data.ArrayStore({
                fields: [
                  'typeId',
                  'displayText'
                ],
                data: [
                  ['fa-question-circle','도움말 아이콘'],
                  ['fa-windows','윈도우 아이콘'],
                  ['fa-apple','애플 아이콘'],
                  ['fa-microphone','프로그램 아이콘' ]
                ]
              }),
              mode: 'local',
              displayField: 'displayText',
              valueField: 'typeId',
              triggerAction: 'all',
              editable: false
            }
            // ,{
            //   xtype: 'checkbox',
            //   fieldLabel: '게시 여부',
            //   name: 'published'
            // }
          ]
      
          });

          var win = new Ext.Window({
              title: '추가하기',
              width: 600,
              modal: true,
              closable: true,
              // closeAction: 'hide',
              items: form,
                buttonsAlign: 'left',
                buttons: [{
                  text: '확인',
                  handler: function(self) {
                    // var regist_form = Ext.getCmp('upload').getForm();
                    // console.log(regist_form);
                    // var getForm = form.getForm();
                    // // var regist_form = Ext.getCmp('fileAttachUploadForm').getForm();
                  
                    // console.log(getForm);
                    // var data = Ext.encode(getForm.getValues());
                    // var upload = win.get(0).getForm(0).findField('fileAttachUpload').getValue();
                    // console.log(upload);
                    // var file = Ext.encode(upload);
                    // console.log(file)
                    var getForm = form.getForm();
                    var data = getForm.getValues();
                    if(!getForm.isValid()){
                      Ext.Msg.show({
                        title: '알림',
                        msg: '내용이 입력되지 않았습니다.',
                        buttons: Ext.Msg.OK,
                      });
                      return;
                    }
                    // var formValue = getForm.getValues();
                    var extension_arr = ['ZIP', 'HWP', 'DOC', 'DOCX', 'XML', 'PPTX', 'PPT', 'XLS', 'XLSX', 'PDF', 'JPG', 'JPEG', 'PNG', 'MP3', 'WAV', 'AI', 'PSD', 'EPS', 'TXT', 'EXE'];
                    var upload_file = data['fileAttachFakePath']
                    var filename_arr = upload_file.split('.');
                    var extension_index = filename_arr.length - 1;
                    var file_extension= filename_arr[extension_index].toUpperCase();
                    if (extension_arr.indexOf(file_extension) === -1) {
                      Ext.Msg.alert(_text('MN00023'), _text('MN00309') + ' : ' + extension_arr.join(', ')); //알림, 허용 확장자 :
                      return;
                  }
                    getForm.submit({
                      url: '/custom/ktv-nps/download/file_upload.php',
                      // headers: { 'Content-Type': 'multipart/form-data; charset=UTF-8'},
                      params: {
                        form_data: data,
                      },
                      success: function(form, action){
                        var r = Ext.decode(action.response.responseText);
                        if(r.success){
                          Ext.Msg.alert('확인', '등록이 되었습니다');
                      }  
                      _this.getStore().reload();
                      win.close();
                    },
                      failure: function(form, action) {
                        // console.log(action);
                        var r = Ext.decode(action.response.responseText);
                        Ext.Msg.alert(_text('MN00023'), r.msg);
                      } 
                    });
                  }
                
                },{
                  text: '취소',
                  // scale: 'medium',
                  handler: function (self) {
                  win.close();
                  }
                }]
  
          });
          return win;
        }

        
});
})()
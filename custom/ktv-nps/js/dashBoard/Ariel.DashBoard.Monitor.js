(function () {

  function showTaskDetail(workflow_id, content_id, root_task) {

    Ext.Ajax.request({
      url: '/javascript/ext.ux/viewInterfaceWorkFlow.php',
      params: {
        workflow_id: workflow_id,
        content_id: content_id,
        root_task: root_task,
        screen_width: window.innerWidth,
        screen_height: window.innerHeight
      },
      callback: function (options, success, response) {
        if (success) {
          try {
            var r = Ext.decode(response.responseText);
            r.show();
          } catch (e) {
            Ext.Msg.alert(e.name, e.message);
          }
        } else {
          Ext.Msg.alert(_text('MN01098'), response.statusText);//'서버 오류'
        }
      }
    });
  }
  Ext.ns('Ariel.DashBoard');


  Ariel.DashBoard.Monitor = Ext.extend(Ext.grid.GridPanel, {
    viewHome: false,
    border: false,
    initComponent: function (config) {
      var _this = this;
      if (this.viewHome) {
        // var titleWidth = 450;
        var comboWidth = 65;
      } else {
        // var titleWidth = 300;
        var comboWidth = 90;
      }

      var store = new Ext.data.JsonStore({
        url: '/store/get_personal_task.php',
        root: 'data',
        fields: [
          'workflow_id', 'workflow_name', 'content_title', 'content_id',
          'count_complete', 'count_error', 'count_processing', 'count_queue', 'total', 'total_progress',
          'user_id', 'user_name', 'status', 'root_task', 'count_total', 'media_id',
          {
            name: 'register', convert: function (v, record) {

              return record.user_id + '(' + record.user_name + ')';
            }
          },
          { name: 'start_datetime', type: 'date', dateFormat: 'YmdHis' },
          { name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis' },
          { name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis' }
        ]
      });
      this.tbar1 = new Ext.Toolbar({
        dock: 'top',
        border: false,
        items: [{
          hidden: true,
          //icon: '/led-icons/arrow_refresh.png',
          text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00390'),//'새로고침'
          handler: function (btn, e) {
            Ext.getCmp('task_grid').getStore().reload();
          }
        }, {
          hidden: true,
          xtype: 'combo',
          id: 'task_type',
          typeAhead: true,
          triggerAction: 'all',
          mode: 'local',
          width: 120,
          editable: false,
          value: 'all',
          emptyText: '작업구분',
          store: new Ext.data.SimpleStore({
            fields: [
              'type_id',
              'type_nm'
            ],
            data: [
              ['all', '전체'],
              ['regist', '등록'],
              ['transfer', '전송']
            ]
          }),
          valueField: 'type_id',
          displayField: 'type_nm',
          listeners: {
            select: function () {
            }
          }
        }, {
          hidden: true,
          xtype: 'tbspacer',
          width: '20'
        }, {
          hidden: true,
          xtype: 'combo',
          id: 'task_status',
          typeAhead: true,
          triggerAction: 'all',
          mode: 'local',
          width: 120,
          editable: false,
          value: 'all',
          emptyText: '상태구분',
          store: new Ext.data.SimpleStore({
            fields: [
              'status_id',
              'status_nm'
            ],
            data: [['all', '전체'],

            ['pending', '작업대기중'],
            ['queue', '작업대기'],
            ['assigning', '작업할당중'],
            ['processing', '작업중'],
            ['complete', '작업완료'],
            ['cancel', '작업취소'],
            ['canceled', '작업취소'],
            ['error', '실패']
            ]
          }),
          valueField: 'status_id',
          displayField: 'status_nm'
        }, {
          hidden: true,
          xtype: 'tbspacer',
          width: '20'
        }, {
          xtype: 'combo',
          id: 'task_filter_combo',
          itemId: 'filter',
          mode: 'local',
          store: [
            [1, _text('MN02131')],//'전체 자료'
            [2, _text('MN02132')]//'내 자료'
          ],
          value: 2,
          // width: 90,
          width: comboWidth,
          triggerAction: 'all',
          typeAhead: true,
          editable: false,
          listeners: {
            select: function () {
              _this.onSearch();
            }
          }
        }, {
          xtype: 'tbspacer',
          width: 10
        }, {
          xtype: 'combo',
          id: 'task_status_combo',
          itemId: 'status',
          mode: 'local',
          store: [
            [1, '전체상태'],
            [2, '성공'],//complete
            [3, '처리중'],//processing
            [4, '실패']//error
          ],
          value: 1,
          // width: 90,
          width: comboWidth + 10,
          triggerAction: 'all',
          typeAhead: true,
          editable: false
        }, {
          xtype: 'tbspacer',
          width: 10
        }, {
          hidden: true,
          xtype: 'combo',
          id: 'task_search_key',
          typeAhead: true,
          triggerAction: 'all',
          mode: 'local',
          width: 80,
          editable: false,
          value: 'content_title',
          store: [
            ['content_title', '제목']//,
            //['filename', '파일명']
          ]
        }, ' ', {
          // hidden: true,
          // hidden: _this.viewHome,
          xtype: 'datefield',
          id: 'task_grid_search_st_dt',
          format: 'Y-m-d',
          width: 100,
          value: new Date().add(Date.DAY, -5).format('Y-m-d')
        }, {
          // hidden: _this.viewHome,
          text: '~'
        },
        // _text('MN00183'),//' 부터 ' 
        {
          // hidden: true,
          // hidden: _this.viewHome,
          id: 'task_grid_search_en_dt',
          xtype: 'datefield',
          format: 'Y-m-d',
          width: 100,
          value: new Date()
        }, {
          xtype: 'tbspacer',
          width: 10
        }, {
          // hidden: _this.viewHome,
          xtype: 'radiogroup',
          allowBlank: false,
          width: 170,
          columns: [.33, .36, .26],
          items: [
            {
              boxLabel: '오늘',
              name: 'dateCheck',
              checked: true,
              value: 'one',
              listeners: {
                check: function (self, checked) {
                  if (checked) {
                    Ext.getCmp('task_grid_search_st_dt').setValue(new Date());
                    Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                    // _this.onSearch();
                  }
                }
              }
            },
            {
              boxLabel: '일주일',
              name: 'dateCheck',
              value: 'week',
              listeners: {
                check: function (self, checked) {
                  if (checked) {
                    Ext.getCmp('task_grid_search_st_dt').setValue(new Date().add(Date.DAY, -6).format('Y-m-d'));
                    Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                    // _this.onSearch();
                  }
                }
              }
            },
            {
              boxLabel: '한달',
              name: 'dateCheck',
              value: 'month',
              listeners: {
                check: function (self, checked) {
                  if (checked) {
                    Ext.getCmp('task_grid_search_st_dt').setValue(new Date().add(Date.MONTH, -1).add(Date.DAY, 1).format('Y-m-d'));
                    Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                    // _this.onSearch();
                  }
                }
              }
            }
          ],
          listeners: {
            afterrender: function (self) {
              var checked = self.getValue().value;
              switch (checked) {
                case 'one':
                  Ext.getCmp('task_grid_search_st_dt').setValue(new Date());
                  Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                  break;
                case 'week':
                  Ext.getCmp('task_grid_search_st_dt').setValue(new Date().add(Date.DAY, -7).format('Y-m-d'));
                  Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                  break;
                case 'month':
                  Ext.getCmp('task_grid_search_st_dt').setValue(new Date().add(Date.MONTH, -1).format('Y-m-d'));
                  Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                  break;
                default:
                  Ext.getCmp('task_grid_search_st_dt').setValue(new Date());
                  Ext.getCmp('task_grid_search_en_dt').setValue(new Date());
                  break;

              }
            }
          }
        },
        {
          xtype: 'displayfield',
          value: _text('MN00249'),//'제목'
          hidden: true
        }, {
          xtype: 'tbspacer',
          width: 5,
          hidden: true
        }]
      });
      this.tbar2 = new Ext.Toolbar({
        dock: 'top',
        border: false,
        items: [{
          id: 'task_search_value',
          xtype: 'textfield',
          width: 300,
          // width: titleWidth,
          emptyText: _text('MN00249'),
          listeners: {
            specialkey: function (field, e) {
              if (Ext.Ajax.isLoading()) {
                return;
              }
              if (e.getKey() == e.ENTER) {
                _this.onSearch.call(_this);
                // 2022.10.24 EJ BACKSPACE 잘 안되는 오류로 엔터에만 적용
                e.stopEvent();
              }
            }
          }
        }, {
          xtype: 'button',
          //icon: '/led-icons/find.png',
          id: 'task_grid_btn_search',
          text: '<span style="position:relative;" title="' + _text('MN00037') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//'조회'
          handler: _this.onSearch,
          scope: _this
        }, '->', {
          // hidden: true,
          xtype: 'checkbox',
          //boxLabel: '자동 새로고침',
          boxLabel: _text('MN00229'),
          checked: false,
          listeners: {
            check: function (self, checked) {
              var TaskListTabPanel = self.ownerCt.ownerCt;
              if (checked) {
                // TaskListTabPanel.runAutoReload(TaskListTabPanel);
                _this.runAutoReload(_this);
              }
              else {
                // TaskListTabPanel.stopAutoReload();
                _this.stopAutoReload();
              }
            },
            render: function (self) {

              // var TaskListTabPanel = self.ownerCt.ownerCt;
              // TaskListTabPanel.stopAutoReload();
              _this.stopAutoReload();
            }
          }
        }, {

        }]
      })
      Ext.apply(this, {
        defaults: {
          border: false,
          // margins: '10 10 10 10'
        },
        frame: false,
        //title: _text('MN02128'),//'작업 내역'
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '작업내역' + '</span></span>',
        id: 'task_grid',
        // cls: 'proxima_customize',
        cls: 'grid_title_customize proxima_customize',
        //flex: 1,
        loadMask: true,
        store: store,
        viewConfig: {
          forceFit: false,
          emptyText: _text('MSG00148'),//결과 값이 없습니다
          border: false
        },
        listeners: {
          viewready: function (self) {
            _this.onSearch();
          },

          rowcontextmenu: function (self, row_index, e) {
            e.stopEvent();

            self.getSelectionModel().selectRow(row_index);

            var rowRecord = self.getSelectionModel().getSelected();
            var workflow_id = rowRecord.get('workflow_id');
            var content_id = rowRecord.get('content_id');
            var root_task = rowRecord.get('root_task');
            var contentId = rowRecord.get('content_id');

            var menu = new Ext.menu.Menu({
              items: [{
                text: '<span style="position:relative;top:1px;"><i class="fa fa-list" style="font-size:13px;"></i></span>&nbsp;' + _text('MN00241'),//'작업흐름보기'
                //icon: '/led-icons/chart_organisation.png',
                handler: function (btn, e) {
                  showTaskDetail(workflow_id, content_id, root_task);
                  menu.hide();
                }
              }, {
                hidden: true,
                text: '<span style="position:relative;top:1px;"><i class="fa fa-check-square-o" style="font-size:13px;"></i></span>&nbsp;' + _text('MN02129'),//'완료 처리'
                icon: '/led-icons/chart_organisation.png',
                handler: function (btn, e) {

                  menu.hide();
                }
              }, {
                text: '<span style="position:relative;top:1px;"><i class="fa fa-external-link-square" style="font-size:13px;"></i></span>&nbsp;' + '상세보기',//'완료 처리'
                handler: function (btn, e) {
                  var components = [
                    '/custom/ktv-nps/javascript/ext.ux/Custom.ParentContentGrid.js',
                  ];
                  Ext.Loader.load(components, function (r) {
                    new Custom.ContentDetailWindow({
                      content_id: contentId,
                      isPlayer: true,
                      isMetaForm: true,
                      playerMode: 'read',
                      listeners: {
                        afterrender: function (self) {
                          self.buttons[0].hide();
                        }
                      }
                    }).show();
                  });
                }


              }]
            });
            menu.showAt(e.getXY());
          },

          rowdblclick: function (self, row_index, e) {
            var rowRecord = self.getSelectionModel().getSelected();
            var workflow_id = rowRecord.get('workflow_id');
            var content_id = rowRecord.get('content_id');
            var root_task = rowRecord.get('root_task');

            showTaskDetail(workflow_id, content_id, root_task);
          }
        },
        cm: new Ext.grid.ColumnModel({
          defaults: {
            //menuDisabled: true
          },
          columns: [
            {
              header: '순번',
              renderer: function (v, p, record, rowIndex) {
                return rowIndex + 1;
              },
              width: 45
            },
            //'작업 유형'
            {
              header: _text('MN01026'),
              dataIndex: 'interface_work_type_nm',
              align: 'center',
              width: 80,
              hidden: true
            },
            //'작업유형 명'
            { header: _text('MN01028'), dataIndex: 'workflow_name', width: 120 },
            // '제목'
            { header: _text('MN00249'), dataIndex: 'content_title', width: 250 },
            // '미디어ID'
            { header: '미디어ID', dataIndex: 'media_id', width: 150, align: 'center', },
            //'등록자'
            { header: _text('MN00120'), dataIndex: 'register', width: 100 },
            //'상태'
            { header: _text('MN00138'), dataIndex: 'status', width: 70 },
            //'완료 건/총 건'
            {
              header: _text('MN02130'),
              dataIndex: 'count_complete',
              align: 'center',
              width: 80,
              renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
                return value + ' / ' + record.get('count_total');
                // return value + ' / ' + record.get('total');
              }
            },
            //'진행작업 명'
            { header: _text('MN01028'), dataIndex: 'task_job_name', width: 200, hidden: true },
            //'진행률(%)'
            new Ext.ux.ProgressColumn({
              header: _text('MN00261'),
              width: 90,
              dataIndex: 'total_progress',
              align: 'center',
              renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
                return Ext.util.Format.number(pct, "0%");
              }
            }),
            //'작업상태'
            { header: _text('MN00237'), dataIndex: 'task_status_nm', align: 'center', width: 80, hidden: true },
            //'작업생성일'
            {
              header: _text('MN01023'),
              dataIndex: 'creation_datetime',
              align: 'center',
              renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
              width: 150
            }
          ]
        }),
        sm: new Ext.grid.RowSelectionModel({
          singleSelect: true
        }),
        tbar: new Ext.Container({
          height: 66,
          layout: 'anchor',
          border: false,
          defaults: {
            anchor: '100%',
            height: 33
          },
          items: [
            _this.tbar1,
            _this.tbar2,
          ]
        }),
        runAutoReload: function (thisRef) {
          this.stopAutoReload();

          this.intervalID = setInterval(function (e) {
            if (thisRef) {
              thisRef.getStore().reload();
            }
          }, 10000);
        },

        stopAutoReload: function () {
          if (this.intervalID) {
            clearInterval(this.intervalID);
          }
        },
        bbar: {
          xtype: 'paging',
          pageSize: 30,
          displayInfo: true,
          store: store
        }
      }, config || {})


      Ariel.DashBoard.Monitor.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
    },
    onSearch: function () {

      var key = Ext.getCmp('task_search_key').getValue();
      var value = Ext.getCmp('task_search_value').getValue();
      // var filter = this.getTopToolbar().getComponent('filter').getValue();
      // var status = this.getTopToolbar().getComponent('status').getValue();
      var filter = this.tbar1.getComponent('filter').getValue();
      var status = this.tbar1.getComponent('status').getValue();
      var stdt = Ext.getCmp('task_grid_search_st_dt').getValue().format('Ymd');
      var endt = Ext.getCmp('task_grid_search_en_dt').getValue().format('Ymd');

      this.getStore().load({
        params: {
          key: key,
          value: value,
          filter: filter,
          stdt: stdt,
          endt: endt,
          status: status
        }
      });
    }
  })

  // return new Ariel.DashBoard.Monitor();
})()
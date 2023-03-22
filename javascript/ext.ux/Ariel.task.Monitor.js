Ext.ns('Ariel.task.Monitor');
(function () {
  Ariel.task.Monitor.Tab = Ext.extend(Ext.TabPanel, {
    activeTab: undefined, //기본 미선택
    initComponent: function () {
      Ext.apply(this, {
        items: [
          {
            name: 'task_monitor',
            xtype: 'taskmonitorpanel',
            title: '주/부조 전송'
          },
          {
            name: 'fs_task_monitor',
            title: '포털 전송',
            url: '/javascript/ext.ux/Ariel.task.FSMonitor.js',
            layout: 'fit'
          }
        ]
      });

      this.listeners = {
        tabchange: this._onTabChange
      };

      Ariel.task.Monitor.Tab.superclass.initComponent.call(this);
    },
    //탭 변경후
    _onTabChange: function (tab, panel) {
      if (panel) {
        this._activeStoreReload();
      }
    },
    //활성탭 스토어 리로드
    _activeStoreReload: function () {
      var activeTab = this.getActiveTab();
      if (activeTab.get(0) === undefined) {
        var activeTabUrl = Ariel.versioning.numberingScript(activeTab.url);
        Ext.Loader.load([activeTabUrl], function () {
          // console.log('after load fs panel.');
          var panel = new Ariel.task.Monitor.FSPanel();
          // console.log('fs_panel', panel);
          activeTab.add(panel);
          activeTab.doLayout();
        });
      } else {
        // if (activeTab.get(0)) {
        //   var store = activeTab.get(0).getStore();
        //   if (store) {
        //     // store.reload({
        //     // });
        //   }
        // }
      }
    },
    //메인 메뉴에서 화면 활성화시 동작
    _onShow: function () {
      //처음
      if (this.activeTab == undefined) {
        this.setActiveTab(0);
      } else {
        //활성탭 있는경우 리로드만
        this._activeStoreReload();
      }
    }
  });

  Ariel.task.Monitor.Panel = Ext.extend(Ext.Panel, {
    initComponent: function (config) {
      var _this = this;

      Ext.apply(this, {
        layout: 'border',
        items: [
          {
            region: 'center',
            xtype: 'taskmonitorcenter'
          },
          {
            height: 200,
            region: 'south',
            xtype: 'taskmonitorsouth',
            split: true
          }
        ]
      });

      Ariel.task.Monitor.Panel.superclass.initComponent.call(this);

      _this.get(0).on('rowclick', _this.onSelectionChange, _this);
      _this.get(0).getStore().on('load', _this.onSelectionChange, _this);
      _this.get(1).getStore().on('beforeload', _this.onBeforeLoad, _this);
    },

    onSelectionChange: function () {
      this.get(1).getStore().reload();
    },

    onBeforeLoad: function () {
      var record = this.get(0).getSelectionModel().getSelected();
      if (record) {
        this.get(1).getStore().baseParams.task_id = record.get('task_id');
      } else {
        this.get(1).getStore().baseParams.task_id = -1;
      }
    }
  });

  Ariel.task.Monitor.Center = Ext.extend(Ext.grid.GridPanel, {
    pageSize: 30,
    permission_code: 'admin',
    columns: [
      { header: 'id', dataIndex: 'task_id', width: 40, hidden: true },
      { header: '작업 모듈명', dataIndex: 'rule_name', width: 170 },
      { header: '제목', dataIndex: 'title', width: 250 },
      { header: '등록자', dataIndex: 'task_user', width: 130 },
      { header: '상태', dataIndex: 'status', width: 70 },
      new Ext.ux.ProgressColumn({
        header: '진행률(%)',
        width: 105,
        dataIndex: 'progress',
        align: 'center',
        renderer: function (
          value,
          meta,
          record,
          rowIndex,
          colIndex,
          store,
          pct
        ) {
          return Ext.util.Format.number(pct, '0%');
        }
      }),
      {
        header: '전송속도',
        dataIndex: 'transfer_speed',
        width: 80,
        align: 'center'
      },
      { header: '원본', dataIndex: 'source', width: 350, hidden: true },
      { header: '저장경로', dataIndex: 'target', width: 300 },
      {
        header: '재생길이',
        dataIndex: 'sys_video_rt',
        width: 80,
        align: 'center'
      },
      {
        header: '등록일시',
        dataIndex: 'creation_datetime',
        xtype: 'datecolumn',
        format: 'Y-m-d H:i:s',
        align: 'center',
        width: 130
      },
      {
        header: '시작일시',
        dataIndex: 'start_datetime',
        xtype: 'datecolumn',
        format: 'Y-m-d H:i:s',
        align: 'center',
        width: 130
      },
      {
        header: '종료일시',
        dataIndex: 'complete_datetime',
        xtype: 'datecolumn',
        format: 'Y-m-d H:i:s',
        align: 'center',
        width: 130
      },
      {
        header: '소요시간',
        dataIndex: 'complete_datetime',
        align: 'center',
        width: 130,
        renderer: function (value, meta, record, rowIndex, colIndex, store) {
          var start = record.get('start_datetime');
          var end = record.get('complete_datetime');

          if (start && end) {
            return Date.parseDate((end - start) / 1000 - 32400, 'U').format(
              'H:i:s'
            );
          } else {
            return '--:--:--';
          }
        }
      },
      { header: 'QC 확인', dataIndex: 'is_qc_checked', width: 60 },
      { header: 'QC 확인자', dataIndex: 'qc_user', width: 130 },
      { header: '전송 서버', dataIndex: 'name', width: 130 }
    ],
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
    _initializeByPermission: function (permissions) {
      var isAdmin = this._getPermission(permissions);

      if (isAdmin) {
        this.getTopToolbar().getComponent('filter').setValue('1');
      } else {
        this.getTopToolbar().getComponent('filter').setValue('2');
      }
    },
    initComponent: function (config) {
      var _this = this;
      _this.listeners = {
        afterrender: function (self) {
          _this._reload();
        }
      };
      _this.getSel = function () {
        var sel = _this.getSelectionModel().getSelected();
        return sel;
      };
      _this.store = new Ext.data.JsonStore({
        url: '/store/task.php',
        baseParams: {
          position: _this.ownerCt.position
        },
        fields: [
          'source',
          'target',
          'status',
          'title',
          'rule_name',
          'task_user_id',
          'task_user_name',
          'qc_user_id',
          'qc_user_id',
          'sys_video_rt',
          'transfer_speed',
          {
            name: 'task_user',
            convert: function (v, record) {
              return record.task_user_id + '(' + record.task_user_name + ')';
            }
          },
          {
            name: 'qc_user',
            convert: function (v, record) {
              var result = '';
              if (record.qc_user_id) {
                result = record.qc_user_id + '(' + record.qc_user_name + ')';
              }

              return result;
            }
          },
          { name: 'task_id', type: 'int' },
          { name: 'progress', type: 'int' },
          { name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis' },
          { name: 'start_datetime', type: 'date', dateFormat: 'YmdHis' },
          { name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis' },
          { name: 'name', type: 'string' },
          'assign_ip'
        ],
        root: 'data',
        totalProperty: 'total',
        listeners: {
          // beforeload: {
          //   fn: function (self) {
          // self.baseParams = Ext.apply(self.baseParams, {
          //   filter: this.getTopToolbar()
          //     .getComponent('filter')
          //     .getValue(),
          //   position: this.getTopToolbar()
          //     .getComponent('position')
          //     .getValue().value,
          //   search_value: this.getTopToolbar()
          //     .getComponent('search_value')
          //     .getValue(),
          //   start_date: _this.startDateField.getValue(),
          //   end_date: endDateOf(_this.endDateField.getValue())
          // });
          //   },
          //   scope: _this
          // }
        }
      });

      _this._reload = function () {
        var _this = this;
        _this.getStore().reload({
          params: {
            filter: _this.getTopToolbar().getComponent('filter').getValue(),
            position: _this.getTopToolbar().getComponent('position').getValue()
              .value,
            search_value: _this
              .getTopToolbar()
              .getComponent('search_value')
              .getValue(),
            start_date: _this.startDateField.getValue(),
            end_date: endDateOf(_this.endDateField.getValue()),
            limit: _this.pageSize
          }
        });
      };
      _this._setTaskRetry = function () {
        var sel = _this.getSel();
        if (!sel) return;
        var taskId = sel.get('task_id');
        Ext.Ajax.request({
          url: '/store/send_task_action.php',
          params: {
            'task_id_list[]': [taskId],
            action: 'retry'
          },
          callback: function (opt, success, response) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
              Ext.Msg.alert(_text('MN00003'), '재시작 요청되었습니다');
              _this._reload();
            } else {
              Ext.Msg.alert(_text('MN00003'), res.msg);
            }
          }
        });
      };

      _this._setTaskCancel = function () {
        var sel = _this.getSel();
        if (!sel) return;
        var taskId = sel.get('task_id');
        Ext.Ajax.request({
          url: '/store/send_task_action.php',
          params: {
            'task_id_list[]': [taskId],
            action: 'cancel'
          },
          callback: function (opt, success, response) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
              Ext.Msg.alert(_text('MN00003'), '취소 요청되었습니다');
              _this._reload();
            } else {
              Ext.Msg.alert(_text('MN00003'), res.msg);
            }
          }
        });
      };

      _this._setPriorityTask = function () {
        var sel = _this.getSel();
        if (!sel) return;
        var taskId = sel.get('task_id');
        Ext.Ajax.request({
          url: '/store/send_task_action.php',
          params: {
            'task_id_list[]': [taskId],
            action: 'PRIORITY'
          },
          callback: function (opt, success, response) {
            var res = Ext.decode(response.responseText);
            if (res.success) {
              Ext.Msg.alert(_text('MN00003'), '긴급작업 요청되었습니다');
              _this._reload();
            } else {
              Ext.Msg.alert(_text('MN00003'), res.msg);
            }
          }
        });
      };

      _this.startDateField = new Ext.form.DateField({
        name: 'start_date',
        editable: false,
        width: 105,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          render: function (self) {
            var d = new Date();

            self.setMaxValue(d.format('Y-m-d'));
            self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
          },
          select: function () {
            _this._reload();
          }
        }
      });
      _this.endDateField = new Ext.form.DateField({
        xtype: 'datefield',
        name: 'end_date',
        editable: false,
        width: 105,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          render: function (self) {
            var d = new Date();

            self.setMaxValue(d.format('Y-m-d'));
            self.setValue(d.format('Y-m-d'));
          },
          select: function (self, date) {
            var startDateFieldValue = _this.startDateField.getValue();
            if (startDateFieldValue > date) {
              /**
               * 이전 날짜보다 작은 값을 선택 했을 시
               * // 이전날짜 선택시 null 값 입력
               */
              self.setValue(new Date());
              return Ext.Msg.alert(
                '알림',
                '시작날짜보다 이전날짜를 선택할 수 없습니다.'
              );
            }
            _this._reload();
          }
        }
      });

      var _autoReloadButton = new Ext.SplitButton({
        text: '자동 새로고침',
        scale: 'medium',
        handler: function (self) {
          self.showMenu();
        },
        listeners: {
          afterrender: function (self) {
            var autoRefreshButtonText = '자동 새로고침';
            var autoRefreshButton = self;
            self.setText(autoRefreshButtonText + '(off)');
            self.setIcon('/led-icons/cross.png');

            function autoRefreshButtonMenuHandler(self) {
              var oriText = self.text;
              autoRefreshButton.setText(
                autoRefreshButtonText + '(' + oriText + ')'
              );

              var count = self.count;
              if (count === false) {
                if (autoRefreshButton.intervalID) {
                  clearInterval(autoRefreshButton.intervalID);
                }
                autoRefreshButton.setIcon('/led-icons/cross.png');
              } else {
                var count = count * 1000;
                autoRefreshButton.intervalID = setInterval(function (e) {
                  _this._reload();
                }, count);
                autoRefreshButton.setIcon('/led-icons/accept.png');
              }
            }

            self.menu = new Ext.menu.Menu({
              items: [
                {
                  text: 'off',
                  count: false,
                  handler: function (self) {
                    autoRefreshButtonMenuHandler(self);
                  }
                },
                {
                  text: '10s',
                  count: 10,
                  handler: function (self) {
                    autoRefreshButtonMenuHandler(self);
                  }
                },
                {
                  text: '30s',
                  count: 30,
                  handler: function (self) {
                    autoRefreshButtonMenuHandler(self);
                  }
                },
                {
                  text: '1min',
                  count: 60,
                  handler: function (self) {
                    autoRefreshButtonMenuHandler(self);
                  }
                },
                {
                  text: '5min',
                  count: 300,
                  handler: function (self) {
                    autoRefreshButtonMenuHandler(self);
                  }
                }
              ]
            });
          }
        }
      });
      Ext.apply(this, config || {}, {
        loadMask: true,
        tbar: [
          {
            xtype: 'combo',
            itemId: 'filter',
            mode: 'local',
            store: [
              [1, '전체 자료'],
              [2, '내 자료']
            ],
            // value: 2,
            width: 90,
            triggerAction: 'all',
            typeAhead: true,
            editable: false,
            listeners: {
              select: function () {
                _this._reload();
              }
            }
          },
          {
            xtype: 'tbspacer',
            width: 10
          },
          {
            xtype: 'radiogroup',
            allowBlank: false,
            itemId: 'position',
            width: 200,
            columns: [50, 0.5, 0.5],
            items: [
              {
                boxLabel: '전체',
                name: 'position',
                checked: true,
                value: 'all'
              },
              {
                boxLabel: '주조정실',
                name: 'position',
                value: 'main'
              },
              {
                boxLabel: '부조정실',
                name: 'position',
                value: 'sub'
              }
            ],
            listeners: {
              change: function (self, checked) {
                _this._reload();
              }
            }
          },
          '-',
          '등록일시 : ',
          _this.startDateField,
          '~',
          _this.endDateField,
          {
            xtype: 'radioday',
            dateFieldConfig: {
              startDateField: _this.startDateField,
              endDateField: _this.endDateField
            },
            addRadio: {
              yearRadio: true
            },
            width: 200,
            columns: [0.23, 0.3, 0.24, 0.23],
            checkDay: 'one',
            listeners: {
              change: function () {
                // _this._reload();
              }
            }
          },
          '-',
          {
            xtype: 'textfield',
            hidden: true,
            width: 300,
            itemId: 'search_value',
            // width: titleWidth,
            emptyText: '미디어ID',
            listeners: {
              specialkey: function (field, e) {
                if (e.getKey() == e.ENTER) {
                  _this._reload();
                }
              }
            }
          },
          {
            xtype: 'button',
            hidden: true,
            text:
              '<span style="position:relative;" title="' +
              _text('MN00037') +
              '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>', //'조회'
            handler: _this._reload
          },
          {
            xtype: 'aw-button',
            iCls: 'fa fa-refresh',
            text: '새로고침',
            handler: _this._reload,
            scope: _this
          },
          '-',
          {
            xtype: 'aw-button',
            iCls: 'fa fa-repeat',
            text: '재시작 요청',
            handler: _this._setTaskRetry,
            scope: _this
          },
          '-',
          {
            xtype: 'aw-button',
            iCls: 'fa fa-times-circle',
            text: '취소 요청',
            handler: _this._setTaskCancel,
            scope: _this
          },
          '-',
          {
            xtype: 'aw-button',
            iCls: 'fa fa-clock-o',
            text: '긴급 전송 요청',
            handler: _this._setPriorityTask,
            scope: _this
          },
          '->',
          _autoReloadButton
        ],

        bbar: {
          xtype: 'paging',
          pageSize: this.pageSize,
          displayInfo: true,
          store: _this.store
        }
      });

      Ariel.task.Monitor.Center.superclass.initComponent.call(this);

      // _this.on('show', _this._show, _this);
      // _this.on('hide', _this._hide, _this);
    },

    _show: function () {
      var _this = this;

      if (!_this.task) {
        _this.task = {
          run: function () {
            _this.getStore().reload();
          },
          interval: 5000
        };
      }

      if (_env == 'production') {
        Ext.TaskMgr.start(this.task);
      }
    },

    _hide: function () {
      var _this = this;

      if (_this.task) {
        Ext.TaskMgr.stop(this.task);
      }
    },

    _reload: function () {
      this.getStore().reload();
    }
  });

  Ariel.task.Monitor.South = Ext.extend(Ext.grid.GridPanel, {
    initComponent: function () {
      var _this = this;

      var store = new Ext.data.JsonStore({
        url: '/store/get_task_log.php',
        totalProperty: 'total',
        idProperty: 'id',
        root: 'data',
        fields: [
          { name: 'task_log_id' },
          { name: 'task_id' },
          { name: 'description' },
          { name: 'creation_date', type: 'date', dateFormat: 'YmdHis' }
        ]
      });

      Ext.apply(_this, {
        title: _text('MN00048'),
        loadMask: true,
        autoExpandColumn: 'description',
        store: store,
        columns: [
          { header: 'ID', dataIndex: 'task_log_id', width: 70 },
          //>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
          //>>{header: '내용', dataIndex:	'description', id: 'description'}
          {
            header: _text('MN00107'),
            dataIndex: 'creation_date',
            renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
            width: 130,
            align: 'center'
          },
          {
            header: _text('MN00156'),
            dataIndex: 'description',
            id: 'description'
          }
        ],
        selModel: new Ext.grid.RowSelectionModel({
          singleSelect: true
        }),
        viewConfig: {
          //>>emptyText: '기록된 작업 내용이 없습니다.'
          emptyText: _text('MSG00166')
        },
        tbar: [
          {
            //text: '새로고침',
            text: _text('MN00139'),
            icon: '/led-icons/arrow_refresh.png',
            handler: function () {
              _this.getStore().reload();
            }
          }
        ]
      });

      Ariel.task.Monitor.South.superclass.initComponent.call(this);
    }
  });

  Ext.reg('taskmonitor', Ariel.task.Monitor.Tab);
  Ext.reg('taskmonitorpanel', Ariel.task.Monitor.Panel);
  Ext.reg('taskmonitorcenter', Ariel.task.Monitor.Center);
  Ext.reg('taskmonitorsouth', Ariel.task.Monitor.South);
})();

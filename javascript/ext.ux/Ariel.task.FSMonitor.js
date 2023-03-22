Ext.ns('Ariel.task.Monitor');
(function () {
  Ariel.task.Monitor.FSPanel = Ext.extend(Ext.Panel, {
    initComponent: function (config) {
      var _this = this;

      Ext.apply(this, {
        layout: 'border',
        items: [
          {
            region: 'center',
            xtype: 'fs_taskmonitorcenter'
          },
          {
            region: 'south'
          }
          // ,
          // {
          //   height: 200,
          //   region: 'south',
          //   xtype: 'fs_taskmonitorsouth',
          //   split: true
          // }
        ]
      });

      Ariel.task.Monitor.FSPanel.superclass.initComponent.call(this);

      // _this.get(0).on('rowclick', _this.onSelectionChange, _this);
      // _this
      //   .get(0)
      //   .getStore()
      //   .on('load', _this.onSelectionChange, _this);
      // _this
      //   .get(1)
      //   .getStore()
      //   .on('beforeload', _this.onBeforeLoad, _this);
    },

    onSelectionChange: function () {
      // this.get(1)
      //   .getStore()
      //   .reload();
    },

    onBeforeLoad: function () {
      // var record = this.get(0)
      //   .getSelectionModel()
      //   .getSelected();
      // if (record) {
      //   this.get(1).getStore().baseParams.job_id = record.get('id');
      // }
    }
  });

  Ariel.task.Monitor.FSCenter = Ext.extend(Ext.grid.GridPanel, {
    cm: new Ext.grid.ColumnModel({
      defaults: {
        menuDisabled: true,
        sortable: true
      },
      columns: [
        new Ext.grid.RowNumberer(),
        { header: 'id', dataIndex: 'id', width: 40, hidden: true },
        {
          header: '유형',
          dataIndex: 'type',
          width: 60,
          renderer: function (value) {
            var ret = '';
            if (value === 'upload') {
              ret = '업로드';
            } else if (value === 'download') {
              ret = '다운로드';
            }
            return ret;
          }
        },
        {
          header: '파일명',
          dataIndex: 'file_path',
          width: 320,
          renderer: function (v) {
            if (!Ext.isEmpty(v)) {
              var filePathArray = v.split('/');
              var fileName = filePathArray[filePathArray.length - 1];
              return fileName;
            }
          }
        },
        {
          header: '파일경로',
          dataIndex: 'file_path',
          width: 450,
          hidden: true
        },
        {
          header: '파일크기',
          dataIndex: 'filesize',
          width: 80,
          align: 'center'
        },
        {
          header: '소요시간',
          dataIndex: 'job_time',
          width: 75,
          align: 'center'
        },
        {
          header: '전송속도',
          dataIndex: 'transmission_speed',
          width: 80,
          align: 'center'
        },
        { header: '부처명', width: 80, dataIndex: 'instt_nm' },
        {
          header: '등록자',
          dataIndex: 'job_user',
          width: 130,
          renderer: function (
            value,
            metaData,
            record,
            rowIndex,
            colIndex,
            store
          ) {
            if (value == '') {
              value = record.json.user_id;
              return value + '(not found)';
            }
            return value;
          }
        },
        {
          header: '상태',
          dataIndex: 'status',
          width: 70,
          renderer: function (value) {
            var ret = '';
            switch (value) {
              case 'queued':
                ret = '대기중';
                break;
              case 'standby':
                ret = '준비완료';
                break;
              case 'working':
                ret = '진행중';
                break;
              case 'error':
                ret = '오류';
                break;
              case 'finished':
                ret = '완료';
                break;
              case 'canceled':
                ret = '취소됨';
                break;
            }

            if (Ext.isEmpty(ret)) {
              return value;
            }

            return ret;
          }
        },
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
          header: '우선순위',
          dataIndex: 'priority',
          width: 60,
          align: 'center'
        },
        {
          header: '등록일시',
          dataIndex: 'created_at',
          xtype: 'datecolumn',
          format: 'Y-m-d H:i:s',
          align: 'center',
          width: 135
        },
        {
          header: '시작일시',
          dataIndex: 'started_at',
          xtype: 'datecolumn',
          format: 'Y-m-d H:i:s',
          align: 'center',
          width: 135
        },
        {
          header: '종료일시',
          dataIndex: 'finished_at',
          xtype: 'datecolumn',
          format: 'Y-m-d H:i:s',
          align: 'center',
          width: 135
        },
        { header: '제목', dataIndex: 'title', width: 250 },
        { header: '전송량', dataIndex: 'transferred', width: 130 },
        { header: '전송서버', dataIndex: 'file_server_name', width: 150 , hidden:true },
        { header: '클라이언트', dataIndex: 'client', width: 100 },
        {
          hidden: true,
          header: '소요시간',
          align: 'center',
          width: 130,
          renderer: function (value, meta, record, rowIndex, colIndex, store) {
            var start = record.get('started_at');
            var end = record.get('finished_at');

            if (start && end) {
              return Date.parseDate((end - start) / 1000 - 32400, 'U').format(
                'H:i:s'
              );
            } else {
              return '';
            }
          }
        }
      ]
    }),
    _pageSize: 50,
    initComponent: function (config) {
      var _this = this;

      _this.getSel = function () {
        var sel = _this.getSelectionModel().getSelected();
        return sel;
      };
      _this.store = new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestPath('/api/v1/file-server/jobs')),
        fields: [
          { name: 'id', type: 'int' },
          'type',
          'file_path',
          'title',
          // { name: 'transferred', type: 'int' },
          // { name: 'filesize', type: 'int' },
          { name: 'filesize' },
          // { name: 'transferred', type: 'int' },
          'transferred',
          { name: 'job_user' },
          { name: 'status' },
          { name: 'priority', type: 'int' },
          { name: 'progress', type: 'int' },
          { name: 'file_server_id' },
          {
            name: 'file_server_name',
            convert: function (v, record) {
              if (!record.file_server) {
                return '';
              } else {
                return record.file_server;
              }

              return;
            }
          },
          {
            name: 'client',
            convert: function (v, record) {
              if (!record.file_server_id && !record.client_ver) {
                return 'HTTP';
              } else {
                return 'APP';
              }
            }
          },
          {
            name: 'job_user',
            convert: function (v, record) {
              //console.log(record);
              var value = '';
              var jobUser = record.job_user;

              if (!jobUser) {
                return '';
              }

              value = jobUser.user_nm;

              return value;
            }
          },
          { name: 'created_at', type: 'date' },
          { name: 'updated_at', type: 'date' },
          { name: 'started_at', type: 'date' },
          { name: 'finished_at', type: 'date' },
          'time',
          'job_time',
          'transmission_speed',
          'instt_nm'
        ],
        root: 'data',
        totalProperty: 'total',
        listeners: {
          beforeload: {
            fn: function (self) {
              // console.log('beforeload....');
              // console.log('_this.searchText',_this.searchText);
              // console.log('_this.searchTextValue',_this.searchText.getValue());
              // self.baseParams = Ext.apply(self.baseParams, {
              //   start_date: _this.startDateField.getValue().format('Y-m-d'),
              //   end_date: _this.endDateField.getValue().format('Y-m-d'),
              //   search_type: _this.searchCombo.getValue(),
              //   search_text: _this.searchText.getValue(),
              //   type: _this.typeCombo.getValue(),
              //   status: _this.statusCombo.getValue(),
              //   limit: _this._pageSize
              // });
            },
            scope: _this
          }
        }
      });

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
            //self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));
            self.setValue(d.format('Y-m-d'));
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
      _this.searchCombo = new Ext.form.ComboBox({
        width: 90,
        triggerAction: 'all',
        editable: false,
        mode: 'local',
        value: 'title',
        store: [
          ['title', '제목'],
          ['instt_nm', '부처명'],
          ['job_user', '등록자'],
          ['media_id', '미디어ID']
        ],
        listeners: {
          select: function (self, record, index) { }
        }
      });
      _this.searchText = new Ext.form.TextField({
        width: 130,
        listeners: {
          specialkey: function (f, e) {
            if (e.getKey() === e.ENTER) {
              _this._reload();
            }
          }
        }
      });
      _this.typeCombo = new Ext.form.ComboBox({
        width: 90,
        triggerAction: 'all',
        editable: false,
        mode: 'local',
        value: 'all',
        store: [
          ['all', '전체'],
          ['upload', '업로드'],
          ['download', '다운로드']
        ]
      });
      _this.statusCombo = new Ext.form.ComboBox({
        width: 90,
        triggerAction: 'all',
        editable: false,
        mode: 'local',
        value: 'all',
        store: [
          ['all', '전체'],
          ['queued', '대기중'],
          ['standby', '준비완료'],
          ['working', '진행중'],
          ['error', '오류'],
          ['finished', '완료'],
          ['canceled', '취소됨']
        ]
      });

      _this.statusCheckbox = new Ext.form.CheckboxGroup({

        xtype: 'checkboxgroup',
        name: 'status-check',
        itemCls: 'x-check-group-alt',
        // Put all controls in a single column with width 100%
        columns: 6,
        width: 400,
        items: [
          {
            boxLabel: '전체', width: 80, name: 'all', listeners: {
              check: function (self, checked) {
                var toolbar = self.ownerCt.ownerCt;
                Ext.each(toolbar.find('group', 'toggle'), function (i) {
                  i.suspendEvents();
                  i.setValue(checked);
                  i.resumeEvents();
                });
              }
            }
          },
          { boxLabel: '준비완료', width: 80, name: 'status-check', group: 'toggle', status: 'standby' },
          { boxLabel: '진행중', width: 80, name: 'status-check', group: 'toggle', status: 'working' },
          { boxLabel: '오류', width: 80, name: 'status-check', group: 'toggle', status: 'error' },
          { boxLabel: '완료', width: 80, name: 'status-check', group: 'toggle', status: 'finished', checked: true },
          { boxLabel: '취소됨', width: 80, name: 'status-check', group: 'toggle', status: 'canceled' }
        ],
        getStatus: function () {
          var self = this;
          var values = [];
          Ext.each(self.items.items, function (i) {
            if (i.group == 'toggle' && i.checked == true) {
              values.push(i.status);
            }
          });
          if (!Ext.isEmpty(values)) {
            return values.join(',');
          } else {
            return 'all';
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
                  _this.store.reload();
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
          '유형 : ',
          _this.typeCombo,
          {
            xtype: 'tbspacer',
            width: 5
          },
          '상태 : ',
          _this.statusCheckbox,//_this.statusCombo,
          {
            xtype: 'tbspacer',
            width: 5
          },
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
            checkDay: 'one',
            columns: [0.23, 0.3, 0.24, 0.23],
            listeners: {
              change: function () {
                _this._reload();
              }
            }
          },
          _this.searchCombo,
          _this.searchText,
          '-',
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
          {
            xtype: 'aw-button',
            iCls: 'fa fa-clock-o',
            text: '긴급 우선순위 변경',
            handler: function () {
              var sm = _this.getSelectionModel();
              var form = new Ext.FormPanel({
                frame: false,
                border: false,
                items: [
                  {
                    xtype: 'numberfield',
                    name: 'priority',
                    maxValue: 100
                  },
                  {
                    xtype: 'displayfield',
                    value: '1~100까지 입력해주세요.'
                  },
                  {
                    xtype: 'displayfield',
                    value: '숫자가 클수록 우선순위 높음'
                  }
                ]
              });
              var win = new Ext.Window({
                title: '긴급 우선순위 변경',
                modal: true,
                layout: 'fit',
                border: false,
                buttonAlign: 'center',
                width: 320,
                items: form,
                buttons: [
                  {
                    text:
                      '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' +
                      '확인', //update
                    scale: 'medium',
                    handler: function (self, e) {
                      var record = sm.getSelected();
                      var jobId = record.get('id');
                      var getForm = form.getForm();
                      var priorityField = getForm.findField('priority');
                      var priorityValue = priorityField.getValue();
                      if (!priorityField.validate()) {
                        return Ext.Msg.alert(
                          '알림',
                          '100 이하의 숫자로 입력해 주세요.'
                        );
                      }
                      Ext.Ajax.request({
                        method: 'PUT',
                        url: '/api/v1/file-server/jobs/' + jobId + '/priority',
                        params: {
                          priority: priorityValue
                        },
                        callback: function (opts, success, response) {
                          var r = Ext.decode(response.responseText);
                          if (r.success) {
                            _this.store.reload();
                            win.close();
                          } else {
                            Ext.Msg.alert(
                              '알림',
                              '긴급 우선순위 변경이 실패되었습니다.'
                            );
                          }
                        }
                      });
                    }
                  },
                  {
                    text:
                      '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                      _text('MN00004'), //cancel
                    scale: 'medium',
                    handler: function (self) {
                      win.close();
                    }
                  }
                ]
              });

              if (sm.hasSelection()) {
                win.show();
              } else {
                Ext.Msg.alert('알림', '요청하실 목록을 선택해주세요.');
              }
            }
          },
          '->',
          _autoReloadButton
        ],

        bbar: {
          xtype: 'paging',
          pageSize: _this._pageSize,
          displayInfo: true,
          store: _this.store
        }
      });

      Ariel.task.Monitor.FSCenter.superclass.initComponent.call(this);
    },

    _reload: function () {
      // console.log('before reload store', this.getStore());
      var _this = this;

      this.getStore().reload({
        params: {
          start_date: _this.startDateField.getValue().format('Y-m-d'),
          end_date: _this.endDateField.getValue().format('Y-m-d'),
          search_type: _this.searchCombo.getValue(),
          search_text: _this.searchText.getValue(),
          type: _this.typeCombo.getValue(),
          status: _this.statusCheckbox.getStatus(),
          limit: _this._pageSize
        }
      });
      // console.log('store', this.getStore());
    }
  });

  // Ariel.task.Monitor.FSSouth = Ext.extend(Ext.grid.GridPanel, {
  //   initComponent: function() {
  //     var _this = this;

  //     var store = new Ext.data.JsonStore({
  //       url: '/store/get_task_log.php',
  //       totalProperty: 'total',
  //       idProperty: 'id',
  //       root: 'data',
  //       fields: [
  //         { name: 'task_log_id' },
  //         { name: 'task_id' },
  //         { name: 'description' },
  //         { name: 'creation_date', type: 'date', dateFormat: 'YmdHis' }
  //       ]
  //     });

  //     Ext.apply(_this, {
  //       title: _text('MN00048'),
  //       loadMask: true,
  //       autoExpandColumn: 'description',
  //       store: store,
  //       columns: [
  //         { header: 'ID', dataIndex: 'task_log_id', width: 70 },
  //         //>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
  //         //>>{header: '내용', dataIndex:	'description', id: 'description'}
  //         {
  //           header: _text('MN00107'),
  //           dataIndex: 'creation_date',
  //           renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
  //           width: 130,
  //           align: 'center'
  //         },
  //         {
  //           header: _text('MN00156'),
  //           dataIndex: 'description',
  //           id: 'description'
  //         }
  //       ],
  //       selModel: new Ext.grid.RowSelectionModel({
  //         singleSelect: true
  //       }),
  //       viewConfig: {
  //         //>>emptyText: '기록된 작업 내용이 없습니다.'
  //         emptyText: _text('MSG00166')
  //       },
  //       tbar: [
  //         {
  //           //text: '새로고침',
  //           text: _text('MN00139'),
  //           icon: '/led-icons/arrow_refresh.png',
  //           handler: function() {
  //             _this.getStore().reload();
  //           }
  //         }
  //       ]
  //     });

  //     Ariel.task.Monitor.FSSouth.superclass.initComponent.call(this);
  //   }
  // });

  Ext.reg('fs_taskmonitorpanel', Ariel.task.Monitor.FSPanel);
  Ext.reg('fs_taskmonitorcenter', Ariel.task.Monitor.FSCenter);
  // Ext.reg('fs_taskmonitorsouth', Ariel.task.Monitor.FSSouth);
})();

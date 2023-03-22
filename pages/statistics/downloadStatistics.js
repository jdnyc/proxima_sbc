(function () {


  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();
  function cntValue(value, metaData, record, rowIndex) {
    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return value.cnt;
    }
    if (!isValue(value)) {
      return 0;
    }
    return value.cnt;
  }
  function fileSizeValue(value, metaData, record, rowIndex) {
    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return value.total_tb + ' TB';
    }
    if (!isValue(value) || value.filesize_gb == '0' || value.filesize_gb == 0) {
      return '0.00' + ' GB';
    }
    if (isValue(value.total_tb) || value.total_tb == '0' || value.total_tb == 0) {
      if (value.total_tb == '0' || value.total_tb == 0) {
        return '0.00' + ' TB';
      }
      return value.total_tb + ' TB';
    }
    return value.filesize_gb + ' GB';
  }
  /**
   * 다운로드 통계 조회 스토어 로드 함수
   */
  function onSearch() {
    var grid = Ext.getCmp(gridId);
    var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
    var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
    grid.getStore().load({
      params: {
        start_date: startDateFieldValue,
        end_date: endDateFieldValue
      }
    });
  }
  function totalColumn(record) {

    var statisticsRecord = Ext.data.Record.create([
      { name: 'proxy15m1080' },
      { name: 'proxy2m1080' },
      { name: 'proxy' },
      { name: 'proxy360' },
      { name: 'original' }
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      'proxy15m1080': {
        count : 0,
        filesize : 0
      },
      'proxy2m1080': {
        count : 0,
        filesize : 0
      },
      'proxy': {
        count : 0,
        filesize : 0
      },
      'proxy360': {
        count : 0,
        filesize : 0
      },
      'original': {
        count : 0,
        filesize : 0
      },
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);

    let proxys = ['proxy15m1080', 'proxy2m1080', 'proxy', 'proxy360', 'original'];

    Ext.each(record, function (r, i, e) {
      Ext.each(proxys, function (proxy) {
        let originProxy = proxy;
        if (proxy === 'proxy15m1080' || proxy === 'proxy2m1080') {
          if (r.data.type && typeof(r.data.type) === 'string' && r.data.type.indexOf('logo') !== -1) {
            proxy = proxy + 'logo';
          }
        }
        if (isValue(r['data'][originProxy])) {
          statisticsTotalRecord['data'][originProxy]['count'] = Ext.util.Format.number(Number(statisticsTotalRecord['data'][originProxy]['count']) + Number(r['data'][proxy]['count']),'0,000');
          var _fileSize = Number(statisticsTotalRecord['data'][originProxy]['filesize']) + Number(r['data'][proxy]['filesize']);
          statisticsTotalRecord['data'][originProxy]['filesize'] = Ext.util.Format.number(_fileSize,'0,000.00');
        };
      });
    });

    return statisticsTotalRecord;
  }
  /**
   * 엑셀 다운로드시 서브밋 할 폼
   */
  function submitExcelForm() {

    var records = Ext.getCmp(gridId).getStore().data.items;
    var statisticRecords = [];

    //통계 데이터
    Ext.each(records, function (r) {
      statisticRecords.push(r.data);
    });
    // 토탈
    var total = statisticRecords[0];
    // 통계 데이터에서 첫번쨰 배열은 합계 이기 때문에 뺸다.
    statisticRecords.remove(total);

    var url = '/store/statistics/content/statisticsExcelDownLoad.php';

    var form = document.createElement("form");
    form.setAttribute("method", "post"); // POST
    form.setAttribute("target", "_blank"); // 새창

    form.setAttribute("action", url);

    //통계 조회 데이터
    var statisticData = document.createElement("input");
    statisticData.setAttribute("name", "statistic_records");
    statisticData.setAttribute("value", Ext.encode(statisticRecords));
    form.appendChild(statisticData);

    //합계
    var statisticTotal = document.createElement("input");
    statisticTotal.setAttribute("name", "statistic_total");
    statisticTotal.setAttribute("value", Ext.encode(total));
    form.appendChild(statisticTotal);

    //통계 유형
    var statisticsType = document.createElement("input");
    statisticsType.setAttribute("name", "statistics_type");
    statisticsType.setAttribute("value", 'download');
    form.appendChild(statisticsType);

    //Excel save file name
    var fileName = document.createElement("input");
    fileName.setAttribute("name", "file_name");
    fileName.setAttribute("value", '운영통계_다운로드_통계');
    form.appendChild(fileName);

    document.body.appendChild(form);
    form.submit();
  }
  /**
     * 2.클린본, 3.마스터본, 7.클립본, 9.뉴스편집본
     */
  var store = new Ext.data.JsonStore({

    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-download',
      type: 'rest'
    }),
    fields: [
      'type',
      'original',
      'proxy',
      'proxy2m1080',
      'proxy2m1080logo',
      'proxy15m1080',
      'proxy15m1080logo',
      'proxy360'
    ],
    listeners: {
      load: function (store, record) {
        var grid = Ext.getCmp(gridId);
        var cm = grid.getColumnModel();

        // 토탈 합계 컬럼 맨 첫번째에 추가
        store.insert(0, totalColumn(record));
      }
    }
  });


  var startDateField = new Ext.form.DateField({
    xtype: 'datefield',
    id: startDateFieldId,
    editable: false,
    width: 105,
    format: 'Y-m-d',
    altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
    listeners: {
      render: function (self) {
        var d = new Date();
        self.setMaxValue(d.format('Y-m-d'));
        self.setValue(d.add(Date.DAY, -7).format('Y-m-d'));

      }
    }
  });
  var endDateField = new Ext.form.DateField({
    xtype: 'datefield',
    id: endDateFieldId,
    editable: false,
    width: 105,
    format: 'Y-m-d',
    altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
    listeners: {
      render: function (self) {
        var d = new Date();

        self.setMaxValue(d.format('Y-m-d'));
        self.setValue(d.format('Y-m-d'));
      }
    }
  });
  var tbar = new Ext.Toolbar({
    items: [
      startDateField,
      '~',
      endDateField,
      ' ',
      {
        xtype: 'radioday',
        dateFieldConfig: {
          startDateField: Ext.getCmp(startDateFieldId),
          endDateField: Ext.getCmp(endDateFieldId)
        },
        checkDay: 'week'
      }, {
        text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
        cls: 'proxima_button_customize',
        width: 30,
        handler: function (self) {
          onSearch();
        }
      }, {
        xtype: 'button',
        cls: 'proxima_button_customize',
        width: 30,
        text: '<span style="position:relative;top:1px;" title="' + _text('MN00212') + '"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
        handler: function () {
          submitExcelForm();
        }
      }
    ]
  });

  var grid = new Ext.grid.GridPanel({
    id: gridId,
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '다운로드 통계' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    stripeRows: true,
    border: false,
    loadMask: true,
    store: store,
    tbar: tbar,
    viewConfig: {
      forceFit: true
    },
    cm: new Ext.grid.ColumnModel({
      defaults: {
        sortable: false
      },
      columns: [
        {
          header: '구분',
          dataIndex: 'type',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';

              return record.get('total_cm');
            }

            let str = '';            
            switch (value) {
              case 'inside':
                str = '내부 다운로드';
                break;
              case 'inside_logo':
                str = '내부(로고) 다운로드';
                break;
              case 'outside':
                str = '외부 다운로드';
                break;
              case 'outside_logo':
                str = '외부(로고) 다운로드';
                break;
              default:
                str = '';
                break;
            }
            return str;
          }
        },
        {
          header: '전송용 (15M) 수량',
          dataIndex: 'proxy15m1080',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return value.count;
            } else {
              if(record.data.type) {
                if(record.data.type.indexOf('logo') === -1) {
                  return Ext.util.Format.number(value.count,'0,000');
                } else {
                  return Ext.util.Format.number(record.data.proxy15m1080logo.count,'0,000');
                }
              } else {
                return Ext.util.Format.number(value.count,'0,000');
              }
            }
          }
        },
        {
          header: '전송용 (15M) 용량',
          dataIndex: 'proxy15m1080',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            } else {
              if(record.data.type) {
                if(record.data.type.indexOf('logo') === -1) {
                  return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
                } else {
                  return Ext.util.Format.number(record.data.proxy15m1080logo.filesize,'0,000.00') + 'GB';
                }
              } else {
                return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
              }
            }
          }
        },
        {
          header: '고해상도 (2M) 수량',
          dataIndex: 'proxy2m1080',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return value.count;
            } else {
              if(record.data.type) {
                if(record.data.type.indexOf('logo') === -1) {
                  return Ext.util.Format.number(value.count,'0,000');
                } else {
                  return Ext.util.Format.number(record.data.proxy2m1080.count,'0,000');
                }
              } else {
                return Ext.util.Format.number(value.count,'0,000');
              }
            }
          }
        },
        {
          header: '고해상도 (2M) 용량',
          dataIndex: 'proxy2m1080',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return value.filesize + 'GB';
            } else {
              if(record.data.type) {
                if(record.data.type.indexOf('logo') === -1) {
                  return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
                } else {
                  return Ext.util.Format.number(record.data.proxy2m1080.filesize,'0,000.00') + 'GB';
                }
              } else {
                return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
              }
            }
          }
        },
        {
          header: '중해상도 (2M) 수량',
          dataIndex: 'proxy',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.count,'0,000');
            } else {
              return Ext.util.Format.number(value.count,'0,000');
            }
          }
        },
        {
          header: '중해상도 (2M) 용량',
          dataIndex: 'proxy',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            } else {
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            }
          }
        },
        {
          header: '저해상도 (2M) 수량',
          dataIndex: 'proxy360',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.count,'0,000');
            } else {
              return Ext.util.Format.number(value.count,'0,000');
            }
          }
        },
        {
          header: '저해상도 (2M) 용량',
          dataIndex: 'proxy360',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            } else {
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            }
          }
        },
        {
          header: '원본 수량',
          dataIndex: 'original',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.count,'0,000');
            } else {
              return Ext.util.Format.number(value.count,'0,000');
            }
          }
        },
        {
          header: '원본 용량',
          dataIndex: 'original',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            } else {
              return Ext.util.Format.number(value.filesize,'0,000.00') + 'GB';
            }
          }
        }
      ]
    }),
    listeners: {
      afterrender: function (self) {
        onSearch();
      }
    }
  });
  return grid;
})()
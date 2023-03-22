(function () {

  let statisticsTypeValue = 'content';
  let excelFileNameValue = '콘텐츠_통계'
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
      { name: 'origin' },
      { name: 'clip' },
      { name: 'news' },
      { name: 'master' },
      { name: 'clean' },
      { name: 'rowTotal' },
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '요청',
      'origin': 0,
      'clip': 0,
      'news': 0,
      'master': 0,
      'clean': 0,
      'rowTotal': 0
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);

    let proxys = ['origin', 'clip', 'news', 'master', 'clean', 'rowTotal'];

    Ext.each(record, function (r, i, e) {
      Ext.each(proxys, function (proxy) {
        let originProxy = proxy;
        if (isValue(r['data'][originProxy])) {
          statisticsTotalRecord['data'][originProxy] = Number(statisticsTotalRecord['data'][originProxy]) + Number(r['data'][proxy]);
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

    //통계 유형
    var statisticsType = document.createElement("input");
    statisticsType.setAttribute("name", 'statistics_type');
    statisticsType.setAttribute("value", statisticsTypeValue);
    form.appendChild(statisticsType);

    //Excel save file name
    var fileName = document.createElement("input");
    fileName.setAttribute("name", "file_name");
    fileName.setAttribute("value", excelFileNameValue);
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
      url: '/api/v1/statistics-content',
      type: 'rest'
    }),
    fields: [
      'type',
      'origin',
      'clip',
      'news',
      'master',
      'clean',
      'rowTotal'
    ],
    listeners: {
      load: function (store, record) {
        var grid = Ext.getCmp(gridId);
        var cm = grid.getColumnModel();

        // 토탈 합계(= 요청) 컬럼 맨 앞에 추가
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '콘텐츠 통계' + '</span></span>',
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
              case 'request':
                str = '대기';
                break;
              case 'reject':
                str = '반려';
                break;
              case 'deleted':
                str = '삭제';
                break;
              case 'approval':
                str = '승인';
                break;
              default:
                str = '';
                break;
            }
            return str;
          }
        },
        {
          header: '원본',
          dataIndex: 'origin',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(value,'0,000');
          }
        },
        {
          header: '클립본',
          dataIndex: 'clip',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(value,'0,000');
          }
        },
        {
          header: '뉴스편집본',
          dataIndex: 'news',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(value,'0,000');
          }
        },
        {
          header: '마스터본',
          dataIndex: 'master',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(value,'0,000');
          }
        },
        {
          header: '클린본',
          dataIndex: 'clean',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            } 
            return Ext.util.Format.number(value,'0,000');
          }
        },
        {
          header: '합계',
          dataIndex: 'rowTotal',
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(value,'0,000');
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
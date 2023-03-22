(function () {

  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();


  function headerStyle(value, metaData, record) {
    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return value;
    }
    if (!isValue(value)) {
      return 0;
    }
    return value;
  };

  function onSearch() {
    var grid = Ext.getCmp(gridId);
    var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
    var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
    grid.getStore().load({
      params: {
        start_date: startDateFieldValue,
        end_date: endDateFieldValue,
      }
    });
  };

  function totalColumn(record) {
    var statisticsRecord = Ext.data.Record.create([
      { name: 'request' },
      { name: 'approval' },
      { name: 'reject' },
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      'request': 0,
      'approval': 0,
      'reject': 0,
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);
    var classifications = ['request', 'approval', 'reject'];
    Ext.each(record, function (r, i, e) {
      Ext.each(classifications, function (classification) {
        if (isValue(r['data'][classification])) {
          statisticsTotalRecord['data'][classification] += Number(r['data'][classification]);
        };
      });
    });

    return statisticsTotalRecord;
  }

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
    statisticsType.setAttribute("value", 'review');
    form.appendChild(statisticsType);

    //Excel save file name
    var fileName = document.createElement("input");
    fileName.setAttribute("name", "file_name");
    fileName.setAttribute("value", '방송 심의 통계');
    form.appendChild(fileName);

    document.body.appendChild(form);
    form.submit();
  }

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
      },{
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
  /**
   * Store
   */
  var store = new Ext.data.JsonStore({

    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-review',
      type: 'rest'
    }),
    fields: [
      { name: 'request_date'},
      'request',
      'approval',
      'reject',
      'total'
    ],
    listeners: {
      load: function (store, record) {
          // 토탈 합계 컬럼 맨 첫번째에 추가
          store.insert(0, totalColumn(record));
      }
    }
  });

  var grid = new Ext.grid.GridPanel({
    id: gridId,
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '방송 심의 통계' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    stripeRows: true,
    border: false,
    loadMask: true,
    // width: '100%',
    autoScroll: true,
    store: store,
    tbar: tbar,
    cm: new Ext.grid.ColumnModel({
      defaults: {
        sortable: false
      },
      columns: [
        {
          header: '요청 일자',
          dataIndex : 'request_date',
          align: 'center',
          width: 110,
          renderer: function (value, metaData, record) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 ;"';
              return '합 계';
            }
            return value;
          }
        },
        {
          header: '요청',
          dataIndex : 'request',
          align: 'center',
          width: 60, 
          renderer: function (value, metaData, record) {
            return headerStyle(value, metaData, record);
          }
        },
        {
          header: '승인',
          dataIndex : 'approval',
          align: 'center',
          width: 60, 
          renderer: function (value, metaData, record) {
            return headerStyle(value, metaData, record);
          }
        },
        {
          header: '반려',
          dataIndex : 'reject',
          align: 'center',
          width: 60, 
          renderer: function (value, metaData, record) {
            return headerStyle(value, metaData, record);
          }
        },
        {
          header: '합 계',
          dataIndex : 'total',
          align: 'center',
          width: 100, 
          renderer: function(value, metaData, record) {
            var statusArray = ['request','approval','reject'];
            var total = 0;
            Ext.each(record.data, function (item) {
              Ext.each(statusArray, function (status) {
                total += Number(item[status]);
              });
            });
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return total;
          }
        },
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
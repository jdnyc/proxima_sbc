(function () {

  let pageNm = '의뢰';
  let statisticsTypeValue = 'request';
  let excelFileNameValue = '운영_의뢰_통계';
  let requestFilterId = Ext.id();
  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();
  var userSearchFieldId = Ext.id();

  function cntValue(value, metaData, record, rowIndex) {
    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return value.cnt;
    }
    if (!isValue(value)) {
      return 0;
    }
    return value?.cnt ?? 0;
  }
  /**
   * 통계 조회 스토어 로드 함수
   */
  function onSearch() {
    var grid = Ext.getCmp(gridId);
    var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
    var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
    var requestFilterValue = Ext.getCmp(requestFilterId).getValue();
    var userSearchFieldValue = Ext.getCmp(userSearchFieldId).getValue();
    grid.getStore().load({
      params: {
        start_date: startDateFieldValue,
        end_date: endDateFieldValue,
        filter: requestFilterValue,
        user_search: userSearchFieldValue,
      }
    });
  }
  function totalColumn(record) {

    var statisticsRecord = Ext.data.Record.create([
      { name: 'request' },
      { name: 'working' },
      { name: 'complete' },
      { name: 'cancel' },
      { name: 'totalCnt' }
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      'request': {
        cnt: 0,
        created_date: null,
        ord_status: 'request'
      },
      'working': {
        cnt: 0,
        created_date: null,
        ord_status: 'working'
      },
      'complete': {
        cnt: 0,
        created_date: null,
        ord_status: 'complete'
      },
      'cancel': {
        cnt: 0,
        created_date: null,
        ord_status: 'cancel'
      },
      'totalCnt': {
        cnt: 0
      }
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);
    var classifications = ['request', 'working', 'complete', 'cancel', 'totalCnt'];

    Ext.each(record, function (r, i, e) {
      Ext.each(classifications, function (classification) {
        if (isValue(r['data'][classification])) {
          statisticsTotalRecord['data'][classification]['cnt'] = Number(statisticsTotalRecord['data'][classification]['cnt']) + Number(r['data'][classification]['cnt']);
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
    Ext.each(records, function (record) {
      statisticRecords.push(record.data);
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
   * 'request'
   * 'working'
   * 'complete'
   * 'cancel'
   */
  var store = new Ext.data.JsonStore({

    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-request',
      type: 'rest'
    }),
    fields: [
      'created_date',
      'request',
      'working',
      'complete',
      'cancel',
      'totalCnt'
    ],
    listeners: {
      load: function (store, record) {
        var grid = Ext.getCmp(gridId);

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
      },
      {
        xtype: 'combo',
        id: requestFilterId,
        typeAhead: true,
        triggerAction: 'all',
        mode: 'local',
        width: 100,
        editable: false,
        value: 'video',
        store: new Ext.data.SimpleStore({
          fields: ['value', 'key'],
          data: [
              ['video', '비디오(기본)'],
              ['graphic', '그래픽']
          ]
        }),
        valueField: 'value',
        displayField: 'key',
      },
      {
        id: userSearchFieldId,
        xtype: 'textfield',
        width: 200,
        emptyText: '담당자 아이디,이름',
        listeners: {
          specialkey: function (field, e) {
            if (e.getKey() == e.ENTER) {
              onSearch();
            }
          }
        }
      },
      {
        text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
        cls: 'proxima_button_customize',
        width: 30,
        handler: function (self) {
          onSearch();
        }
      },
      {
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + pageNm + '</span></span>',
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
          header: '날짜', dataIndex: 'created_date', align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';

              return record.get('total_cm');
            }
            return value;
          }
        },
        {
          header: '요청', dataIndex: 'request', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '진행중', dataIndex: 'working', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '완료', dataIndex: 'complete', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '취소', dataIndex: 'cancel', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '합계', dataIndex: 'totalCnt', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
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
(function () {


  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();
  function cntValue(value, metaData, record, rowIndex) {

    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return Ext.util.Format.number(value.cnt,'0,000');
    }
    if (!isValue(value)) {
      return 0;
    }
    return Ext.util.Format.number(value.cnt,'0,000');
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
   * 통계 조회 스토어 로드 함수
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

    /**
       * 0.텔레시네, 2.클린본, 3.마스터본, 7.클립본, 9.뉴스편집본
       */
    var statisticsRecord = Ext.data.Record.create([
      { name: '0' },
      { name: '1' },
      { name: '2' },
      { name: '3' },
      { name: '7' },
      { name: '9' }
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      '0': {
        'cnt': 0,
        'total_tb': 0.00,
      },
      '1': {
        'cnt': 0,
        'total_tb': 0.00,
      },
      '2': {
        'cnt': 0,
        'total_tb': 0.00,
      },
      '3': {
        'cnt': 0,
        'total_tb': 0.00,
      },
      '7': {
        'cnt': 0,
        'total_tb': 0.00,
      },
      '9': {
        'cnt': 0,
        'total_tb': 0.00,
      }
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);


    var udContents = ['0', '1', '2', '3', '7', '9'];

    Ext.each(record, function (r, i, e) {
      Ext.each(udContents, function (udContentId) {
        if (isValue(r['data'][udContentId])) {
          statisticsTotalRecord['data'][udContentId]['cnt'] = Number(statisticsTotalRecord['data'][udContentId]['cnt']) + Number(r['data'][udContentId]['cnt']);
          statisticsTotalRecord['data'][udContentId]['total_tb'] = Number(statisticsTotalRecord['data'][udContentId]['total_tb']) + Number(r['data'][udContentId]['filesize_tb']);
          statisticsTotalRecord['data'][udContentId]['total_tb'] = statisticsTotalRecord['data'][udContentId]['total_tb'].toFixed(2);
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
    statisticsType.setAttribute("value", 'daily');
    form.appendChild(statisticsType);

    //Excel save file name
    var fileName = document.createElement("input");
    fileName.setAttribute("name", "file_name");
    fileName.setAttribute("value", '아카이브_일별_통계');
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
      url: '/api/v1/statistics-archive-daily',
      type: 'rest'
    }),
    fields: [
      'ud_content_title',
      'created_date',
      'cnt',
      'filesize_gb',
      'filesize_tb',
      '0',
      '1',
      '2',
      '3',
      '7',
      '9'
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '일별 통계' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    stripeRows: true,
    border: false,
    loadMask: true,
    store: store,
    tbar: tbar,
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
          header: '원본(수량)', dataIndex: '1', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '원본(용량)', dataIndex: '1', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return fileSizeValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '클립본(수량)', dataIndex: '7', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }

        },
        {
          header: '클립본(용량)', dataIndex: '7', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return fileSizeValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '뉴스편집본(수량)', dataIndex: '9', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '뉴스편집본(용량)', dataIndex: '9', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return fileSizeValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '마스터본(수량)', dataIndex: '3', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '마스터본(용량)', dataIndex: '3', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return fileSizeValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '클린본(수량)', dataIndex: '2', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '클린본(용량)', dataIndex: '2', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return fileSizeValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '텔레시네(수량)', dataIndex: '0', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '텔레시네(용량)', dataIndex: '0', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return fileSizeValue(value, metaData, record, rowIndex);
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
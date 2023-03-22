(function () {

  let statisticsTypeValue = 'content_deleted';
  let excelFileNameValue = '콘텐츠 원본 삭제 통계';

  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();
  function cntValue(value, metaData, record, rowIndex) {
    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return Ext.util.Format.number(value,'0,000') || 0;
    }
    if (!isValue(value)) {
      return 0;
    }
    return Ext.util.Format.number(value,'0,000') || 0;
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
        end_date: endDateFieldValue,
        type: 'delete_media_original'
      }
    });
  }
  function totalColumn(record) {
    // 'origin', 'clean', 'master', 'audio', 'image', 'clip', 'cg', 'news'
    var statisticsRecord = Ext.data.Record.create([
      { name: 'origin' },
      { name: 'clip' },
      { name: 'news' },
      { name: 'master' },
      { name: 'clean' },
      { name: 'image' },
      { name: 'audio' },
      { name: 'cg' },
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      'origin': 0,
      'clip': 0,
      'news': 0,
      'master': 0,
      'clean': 0,
      'image': 0,
      'audio': 0,
      'cg': 0,
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);
    var classifications = ['origin', 'clip', 'news', 'master', 'clean', 'image', 'audio', 'cg'];
    Ext.each(record, function (r, i, e) {
      Ext.each(classifications, function (classification) {
        if (isValue(r['data'][classification])) {
          statisticsTotalRecord['data'][classification] += Number(r['data'][classification]);
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

  var store = new Ext.data.JsonStore({

    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-content-deleted',
      type: 'rest'
    }),
    fields: [
      'dt',
      'origin',
      'clip',
      'news',
      'master',
      'clean',
      'image',
      'audio',
      'cg',
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '원본 삭제' + '</span></span>',
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
          header: '날짜', dataIndex: 'dt', align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';

              return record.get('total_cm');
            }
            return value;
          }
        },
        {
          header: '원본', dataIndex: 'origin', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '클립본', dataIndex: 'clip', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '뉴스편집본', dataIndex: 'news', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '마스터본', dataIndex: 'master', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '클린본', dataIndex: 'clean', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '이미지', dataIndex: 'image', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '오디오', dataIndex: 'audio', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: 'CG', dataIndex: 'gc', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '합계', dataIndex: '', 
          renderer: function(value, metaData, record) {
            let arr = ['origin', 'clean', 'master', 'audio', 'image', 'clip', 'cg', 'news'];
            let total = 0;
            Ext.each(record.data, function (item) {
              Ext.each(arr, function (e) {
                total += Number(item[e]);
              });
            });
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(total,'0,000');
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
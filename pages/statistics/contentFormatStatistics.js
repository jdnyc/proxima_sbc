(function () {

  let statisticsTypeValue = 'content_format';
  let excelFileNameValue = '콘텐츠입수_포맷별_통계';
  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();

  var dynamicColumns = {};

  function cntValue(value, metaData, record, rowIndex) {
    if (record.get('total')) {
      metaData.attr = 'style="background-color: #d7d7d7 !important;"';
      return Ext.util.Format.number(value,'0,000');
    }
    if (!isValue(value)) {
      return 0;
    }
    return Ext.util.Format.number(value,'0,000');
  }
  /**
   * 통계 조회 스토어 로드 함수
   */
  function onSearch() {
    var grid = Ext.getCmp(gridId);
    var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
    var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';

    Ext.Ajax.request({
      method: 'GET',
      url: '/api/v1/statistics-content-get-format',
      params: {
        start_date: startDateFieldValue,
        end_date: endDateFieldValue
      },
      success: function (res) {
        var jsonResult = JSON.parse(res.responseText);
        var dataFieldsArr = jsonResult.data;
        if(dataFieldsArr.length != 0) {
          var tValues = [
            {
              header: '날짜', dataIndex: 'dt', align: 'center',locked:true,
              renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                if (record.get('total')) {
                  metaData.attr = 'style="background-color: #d7d7d7 !important;"';
  
                  return record.get('total_cm');
                }
                return value;
              }
            }
          ];
  
          dataFieldsArr.forEach(e => {
            if(e.length > 30) e = e.replace(' ', '');
            tValues.push({
              header: e,
              dataIndex: e.toLowerCase(),
              resizable: true,
              flex: true,
              width: 200,
              renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                return cntValue(value, metaData, record, rowIndex);
              }
            });
          });
  
          dataFieldsArr.push('dt'.toLowerCase());
  
          var lowerDataFieldsArr = [];
  
          dynamicColumns = {};
          dataFieldsArr.forEach( (e, idx) => {
            if (e.length > 30) {
              e = e.replace(' ', '');
            }
            var lowerCaseData = e.toLowerCase();
            lowerDataFieldsArr.push(lowerCaseData);
            // var dynamicColumnsValue = {};
            // dynamicColumnsValue[lowerCaseData] = e;
            // dynamicColumns.push(dynamicColumnsValue);
            dynamicColumns[lowerCaseData] = e;
          });
  
          var changeStore = new Ext.data.JsonStore({
            root: 'data',
            restful: true,
            remoteSort: true,
            proxy: new Ext.data.HttpProxy({
              method: 'GET',
              url: '/api/v1/statistics-content-format',
              type: 'rest'
            }),
            fields: lowerDataFieldsArr,
            listeners: {
              load: function (store, record) {
                // 토탈 합계 컬럼 맨 첫번째에 추가
                store.insert(0, totalColumn(record, lowerDataFieldsArr));
              }
            }
          });
  
          var tcolumns = tValues;
  
          var colModels = new Ext.grid.ColumnModel({
            defaults: {
              sortable: false
            },
            columns: tcolumns
          });

          grid.reconfigure(changeStore, colModels);
  
          grid.getStore().load({
            params: {
              start_date: startDateFieldValue,
              end_date: endDateFieldValue
            },
            fields: lowerDataFieldsArr
          });

          
        } else {
          var tcolumns = [
            {
              header: '',
              dataIndex: '',
              width: 1000,
              align: 'center'
            }
          ];
          var colModels = new Ext.grid.ColumnModel({
            defaults: {
              sortable: false
            },
            columns: tcolumns
          });

          var changeStore = new Ext.data.JsonStore({
            root: 'data',
            restful: true,
            remoteSort: true,
            proxy: new Ext.data.HttpProxy({
              method: 'GET',
              url: '/api/v1/statistics-content-format',
              type: 'rest'
            })
          });

          grid.reconfigure(changeStore, colModels);
  
          grid.getStore().load({
            params: {
              start_date: startDateFieldValue,
              end_date: endDateFieldValue
            }
          });
        }

      },
      failure: function (err) {
        console.log(err);
      }
    });
  }

  function totalColumn(record, dCol) {
    var statisticsRecord = Ext.data.Record.create([
      dCol
    ]);

    var totalStatistics = {
      'total': true,
      'total_cm': '합계'
    };

    dCol.forEach(e => {
      totalStatistics[e] = 0;
    });

    var statisticsTotalRecord = new statisticsRecord(totalStatistics);

    Ext.each(record, function (r, i, e) {
      Ext.each(dCol, function (col) {
        if (isValue(r['data'][col])) {
          statisticsTotalRecord['data'][col] = Number(statisticsTotalRecord['data'][col]) + Number(r['data'][col]);
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
    if(dynamicColumns.dt) delete dynamicColumns.dt;

    //통계 데이터
    Ext.each(records, function (record) {
      let columnTotal = 0;
      Ext.each(dynamicColumns, function (col) {
        columnTotal += parseInt(record.data[col]);
      });
      record.data['합계'] = columnTotal;
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

    //동적 컬럼
    var statisticsDynamicColumn = document.createElement("input");
    statisticsDynamicColumn.setAttribute("name", "statistics_dynamic_column");
    statisticsDynamicColumn.setAttribute("value", Ext.encode(dynamicColumns));
  
    form.appendChild(statisticsDynamicColumn);

    //Excel save file name
    var fileName = document.createElement("input");
    fileName.setAttribute("name", "file_name");
    fileName.setAttribute("value", excelFileNameValue);
    form.appendChild(fileName);

    document.body.appendChild(form);
    form.submit();
  }
  
  /**
   * 스토어
   */
  var store = new Ext.data.JsonStore({
    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-content-get-format',
      type: 'rest'
    })
  });


  /**
   * 시작일 필터
   */
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
  /**
   * 종료일 필터
   */
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

  /**
   * 툴바 설정
   */
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


  /**
   * 그리드
   */
  var grid = new Ext.grid.GridPanel({
    id: gridId,
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '포맷별' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    stripeRows: true,
    border: false,
    loadMask: true,
    store: store,
    tbar: tbar,
    viewConfig: {
      forceFit: false,
      emptyText: '데이터가 없습니다.',
      deferEmptyText: false
    },
    cm: new Ext.grid.ColumnModel({
      defaults: {
        sortable: false
      },
      columns: [
        {
          header: '',
          dataIndex: '',
          width: 1000,
          align: 'center'
        }
      ],
    }),
    listeners: {
      afterrender: function (self) {
        onSearch();
      }
    }
  });
  return grid;
})()
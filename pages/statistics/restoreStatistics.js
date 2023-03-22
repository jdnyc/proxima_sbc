(function () {

  let statisticsTypeValue = 'restore';
  let excelFileNameValue = '리스토어 통계';
  
  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();
  var contentTypeFieldId = Ext.id();
  var taskStatusFieldId = Ext.id();

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
    var contentTypeFieldValue = Ext.getCmp(contentTypeFieldId).getValue();
    var taskStatusFieldValue = Ext.getCmp(taskStatusFieldId).getValue();
    grid.getStore().load({
      params: {
        start_date: startDateFieldValue,
        end_date: endDateFieldValue,
        content_type: contentTypeFieldValue,
        task_status: taskStatusFieldValue,
      }
    });
  }
  // 합 계
  function totalColumn(record) {
    // 'file_restore', 'file_restore_xdcam', 'dtl_restore', 'dtl_restore_copy'
    var statisticsRecord = Ext.data.Record.create([
      { name: 'file_restore' },
      { name: 'file_restore_xdcam' },
      { name: 'dtl_restore_copy' },
      { name: 'dtl_restore' },
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      'file_restore': 0,
      'file_restore_xdcam': 0,
      'dtl_restore_copy': 0,
      'dtl_restore': 0,
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);
    var classifications = ['file_restore', 'file_restore_xdcam', 'dtl_restore_copy', 'dtl_restore'];
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
      url: '/api/v1/statistics-restore',
      type: 'rest'
    }),
    fields: [
      'dt',
      'file_restore',
      'file_restore_xdcam',
      'dtl_restore_copy',
      'dtl_restore',
    ],
    listeners: {
      load: function (store, record) {
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
  //콤보박스 콘텐츠 유형 ajax
  var contentTypeField = [['all','전체']];
  $.ajax({
    type: 'GET',
    url: "/api/v1/contents/archive-management/content-type",
    async: false,
    success: function(result){
      var contentType = result.data;
      var typeField = contentType;
      for(var i =0; i<typeField.length; i++){
        contentTypeField.push([typeField[i].ud_content_id,typeField[i].ud_content_title]);
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
      '콘텐츠 유형',
      {
        xtype: 'combo',
        id: contentTypeFieldId,
        typeAhead: true,
        triggerAction: 'all',
        mode: 'local',
        width: 150,
        editable: false,
        value: 'all',
        
        store: new Ext.data.SimpleStore({      
          fields: ['value', 'key'],
          data: contentTypeField,
        }),
        valueField: 'value',
        displayField: 'key',
      },   
      '-',
      '작업 상태',
      {
        xtype: 'combo',
        id: taskStatusFieldId,
        typeAhead: true,
        triggerAction: 'all',
        mode: 'local',
        width: 100,
        editable: false,
        value: 'all',
        store: new Ext.data.SimpleStore({
          fields: ['value', 'key'],
          data: [
              ['all', '전체'],
              ['pending', '대기중'],
              ['queue', '대기'],
              ['assigning', '할당중'],
              ['processing', '작업중'],
              ['complete', '작업완료'],
              ['cancel', '작업취소'],
              ['canceled', '작업취소'],
              ['error', '실패']
          ]
        }),
        valueField: 'value',
        displayField: 'key',
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '리스토어 통계' + '</span></span>',
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
          header: '날짜', dataIndex: 'dt',width: 100,align:'center', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
              return record.get('total_cm');
            }
            return value;
          }
        },
        {
          header: '니어라인 리스토어', dataIndex: 'file_restore', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '니어라인 변환 리스토어', dataIndex: 'file_restore_xdcam', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: 'DTL 리스토어', dataIndex: 'dtl_restore_copy', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: 'DTL 변환 리스토어', dataIndex: 'dtl_restore', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '합계', dataIndex: '', 
          renderer: function(value, metaData, record) {
            let arr = ['file_restore', 'file_restore_xdcam', 'dtl_restore_copy', 'dtl_restore'];
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
(function () {

  let statisticsTypeValue = 'user_approval';
  let excelFileNameValue = '사용신청_승인_통계';
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

    var statisticsRecord = Ext.data.Record.create([
      { name: 'c104176100100' },
      { name: 'c104176100101' },
      { name: 'c104176100102' },
      { name: 'c104176100103' },
      { name: 'c104176100104' },
      { name: 'c104176100105' },
      { name: 'c104176100106' },
      { name: 'inside' },
      { name: 'outside' },
    ]);
    var totalStatistics = {
      'total': true,
      'total_cm': '합계',
      'c104176100100': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100100',
        dept_nm: '기획편성부',
      },
      'c104176100101': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100101',
        dept_nm: '방송기술부',
      },
      'c104176100102': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100102',
        dept_nm: '방송영상부',
      },
      'c104176100103': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100103',
        dept_nm: '방송보도부',
      },
      'c104176100104': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100104',
        dept_nm: '방송제작부',
      },
      'c104176100105': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100105',
        dept_nm: '운영관리부',
      },
      'c104176100106': {
        cnt: 0,
        created_date: null,
        dept_code: 'c104176100106',
        dept_nm: '온라인콘텐츠부',
      },
      'inside': {
        cnt: 0
      },
      'outside': {
        cnt: 0
      }
    };


    var statisticsTotalRecord = new statisticsRecord(totalStatistics);
    var deptCodes = ['c104176100100', 'c104176100101', 'c104176100102', 'c104176100103', 'c104176100104', 'c104176100105', 'c104176100106', 'inside', 'outside'];

    Ext.each(record, function (r, i, e) {
      Ext.each(deptCodes, function (deptCode) {
        if (isValue(r['data'][deptCode])) {
          statisticsTotalRecord['data'][deptCode]['cnt'] = Number(statisticsTotalRecord['data'][deptCode]['cnt']) + Number(r['data'][deptCode]['cnt']);
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
   * 'c104176100100'
   * 'c104176100101'
   * 'c104176100102'
   * 'c104176100103'
   * 'c104176100104'
   * 'c104176100105'
   * 'c104176100106'
   */
  var store = new Ext.data.JsonStore({

    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-user-approval',
      type: 'rest'
    }),
    fields: [
      'created_date',
      'c104176100100',
      'c104176100101',
      'c104176100102',
      'c104176100103',
      'c104176100104',
      'c104176100105',
      'c104176100106',
      'inside',
      'outside'
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '사용신청 승인' + '</span></span>',
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
          header: '내부(건)', dataIndex: 'inside', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            // let insideArr = [
            //   'c104176100100',
            //   'c104176100101',
            //   'c104176100102',
            //   'c104176100103',
            //   'c104176100104',
            //   'c104176100105',
            //   'c104176100106'
            // ];
            // let insideCnt = 0;
            // Ext.each(record.data, (item) => {
            //   Ext.each(insideArr, inside => {
            //     insideCnt += Number(item[inside].cnt);
            //   });
            // });
            // if (record.get('total')) {
            //   metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            // }
            // return insideCnt ?? 0;

            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '외부(건)', dataIndex: 'outside', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            if (record.get('total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return value.cnt || 0;
          }
        },
        {
          header: '방송영상부', dataIndex: 'c104176100102', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '방송기술부', dataIndex: 'c104176100101', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '방송제작부', dataIndex: 'c104176100104', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '방송보도부', dataIndex: 'c104176100103', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '기획편성부', dataIndex: 'c104176100100', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '운영관리부', dataIndex: 'c104176100105', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            return cntValue(value, metaData, record, rowIndex);
          }
        },
        {
          header: '온라인콘텐츠부', dataIndex: 'c104176100106', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
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
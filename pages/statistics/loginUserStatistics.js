(function () {


  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();
  var isInternalFieldId = Ext.id();
  var userSearchFieldId = Ext.id();

  function hourStyle(value, metaData, record, rowIndex) {
    if (record.get('is_total')) {
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
    var isInternalFieldValue = Ext.getCmp(isInternalFieldId).getValue();
    var userSearchFieldValue = Ext.getCmp(userSearchFieldId).getValue();

    grid.getStore().load({
      params: {
        start_date: startDateFieldValue,
        end_date: endDateFieldValue,
        is_internal: isInternalFieldValue,
        user_search: userSearchFieldValue,
      }
    });
  }
  // Column 합계
  function totalColumn(record) { 
    var statisticsRecord = Ext.data.Record.create([
      { name: '01' },
      { name: '02' },
      { name: '03' },
      { name: '04' },
      { name: '05' },
      { name: '06' },
      { name: '07' },
      { name: '08' },
      { name: '09' },
      { name: '10' },
      { name: '11' },
      { name: '12' },
      { name: '13' },
      { name: '14' },
      { name: '15' },
      { name: '16' },
      { name: '17' },
      { name: '18' },
      { name: '19' },
      { name: '20' },
      { name: '21' },
      { name: '22' },
      { name: '23' },
      { name: '24' }
    ]);
   
    var totalStatistics = {
      'is_total': true,
      'total_cm': '합계',
      '01':0,'02':0,'03':0,'04':0,'05':0,'06':0,'07':0,'08':0,'09':0,'10':0,'11':0,'12':0,'13':0,'14':0,'15':0,'16':0,'17':0,'18':0,'19':0,'20':0,'21':0,'22':0,'23':0,'24':0
    };
    var statisticsTotalRecord = new statisticsRecord(totalStatistics);

    var hourStatistics = ['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24'];

    Ext.each(record, function (r, i, e) {
      Ext.each(hourStatistics, function (hour) {
        if (isValue(r['data'])) { 
          statisticsTotalRecord['data'][hour] = Number(statisticsTotalRecord['data'][hour]) + Number(r['data'][hour]);
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
    statisticsType.setAttribute("value", 'login_user');
    form.appendChild(statisticsType);

    //Excel save file name
    var fileName = document.createElement("input");
    fileName.setAttribute("name", "file_name");
    fileName.setAttribute("value", '접속자 로그인 통계');
    form.appendChild(fileName);

    document.body.appendChild(form);
    form.submit();
  }
  /**
     * Store
     */
  var store = new Ext.data.JsonStore({

    root: 'data',
    restful: true,
    remoteSort: true,
    proxy: new Ext.data.HttpProxy({
      method: 'GET',
      url: '/api/v1/statistics-login-user',
      type: 'rest'
    }),
    fields: [
      'dept_nm',
      '01',
      '02',
      '03',
      '04',
      '05',
      '06',
      '07',
      '08',
      '09',
      '10',
      '11',
      '12',
      '13',
      '14',
      '15',
      '16',
      '17',
      '18',
      '19',
      '20',
      '21',
      '22',
      '23',
      '24',
      'total'
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
        xtype: 'combo',
        id: isInternalFieldId,
        typeAhead: true,
        triggerAction: 'all',
        mode: 'local',
        width: 100,
        editable: false,
        value: 'internal',
        store: new Ext.data.SimpleStore({
          fields: ['value', 'key'],
          data: [
              ['internal', '내부(기본)'],
              ['external', '외부']
          ]
        }),
        valueField: 'value',
        displayField: 'key',
      },
      {
        id: userSearchFieldId,
        xtype: 'textfield',
        width: 200,
        emptyText: '사용자 아이디,이름',
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
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '접속자 통계' + '</span></span>',
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
          header: '부서 / 기관',
          dataIndex : 'dept_nm',
          align: 'center',
          width: 110,
          renderer: function (value, metaData, record) {
            if (record.get('is_total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';

              return record.get('total_cm');
            }
            return value;
          }
        },
        {
          header: '01시',
          dataIndex : '01',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '02시',
          dataIndex : '02',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '03시',
          dataIndex : '03',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '04시',
          dataIndex : '04',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '05시',
          dataIndex : '05',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '06시',
          dataIndex : '06',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '07시',
          dataIndex : '07',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '08시',
          dataIndex : '08',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '09시',
          dataIndex : '09',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '10시',
          dataIndex : '10',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '11시',
          dataIndex : '11',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '12시',
          dataIndex : '12',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '13시',
          dataIndex : '13',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '14시',
          dataIndex : '14',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '15시',
          dataIndex : '15',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '16시',
          dataIndex : '16',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '17시',
          dataIndex : '17',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '18시',
          dataIndex : '18',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '19시',
          dataIndex : '19',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '20시',
          dataIndex : '20',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '21시',
          dataIndex : '21',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '22시',
          dataIndex : '22',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '23시',
          dataIndex : '23',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '24시',
          dataIndex : '24',
          align: 'center',
          width: 40, 
          renderer: function (value, metaData, record) {
            return hourStyle(value, metaData, record);
          }
        },
        {
          header: '합계',
          dataIndex : 'total',
          align: 'center',
          renderer: function(value, metaData, record) {
            var hourArray = ['01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23','24'];
            var total = 0;
            Ext.each(record.data, function (item) {
              Ext.each(hourArray, function (hour) {
                total += Number(item[hour]);
              });
            });
            if (record.get('is_total')) {
              metaData.attr = 'style="background-color: #d7d7d7 !important;"';
            }
            return Ext.util.Format.number(total,'0,000');
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

/**
 * 조회 조건:
 * 필터링 : 기간, 내부/외부 구분
 * 검색어: 사용자명, 아이디, 부서명
 * 컬럼: 아이디, 이름, 기관, 부서, 로그인일시, 접속IP
 * 페이징: 20건씩
 */
(function () {

var gridId = Ext.id();
var startDateFieldId = Ext.id();
var endDateFieldId = Ext.id();
var isInternalFieldId = Ext.id();
var searchFieldId = Ext.id();
var pageSize = 20;

/**
 * 통계 조회 스토어 로드 함수
 */
function onSearch() {
  var grid = Ext.getCmp(gridId);
  var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
  var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
  var isInternalFieldValue = Ext.getCmp(isInternalFieldId).getValue();
  var searchFieldValue = Ext.getCmp(searchFieldId).getValue();

  grid.getStore().load({
	params: {
	  start_date: startDateFieldValue,
	  end_date: endDateFieldValue,
	  is_internal: isInternalFieldValue,
    search_value: searchFieldValue,
    limit: pageSize,
	}
  });
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
	url: '/api/v1/user-login-history',
	type: 'rest'
  }),
  fields: [
	  'user_id',
    'user_nm',
    'org_nm',
    'dept_nm',
    {name: 'login_date', type: 'date', dateFormat: 'YmdHis'},
    'login_ip'
  ],
  listeners: {
  }
});

// 엑셀 다운로드
function submitExcelForm() {
  var url = '/store/statistics/content/userLoginHistoryExcelDownload.php';

  var form = document.createElement("form");
  form.setAttribute("method", "post"); // POST
  form.setAttribute("target", "_blank"); // 새창

  form.setAttribute("action", url);

  var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
  var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
  var isInternalFieldValue = Ext.getCmp(isInternalFieldId).getValue();
  var searchFieldValue = Ext.getCmp(searchFieldId).getValue();

  //통계 유형
  var statisticsType = document.createElement("input");
  statisticsType.setAttribute("name", "statistics_type");
  statisticsType.setAttribute("value", 'user_login_history');
  form.appendChild(statisticsType);

  //통계 조회 데이터
  var startDate = document.createElement("input");
  startDate.setAttribute("name", "start_date");
  startDate.setAttribute("value", startDateFieldValue);
  form.appendChild(startDate);

  var endDate = document.createElement("input");
  endDate.setAttribute("name", "end_date");
  endDate.setAttribute("value", endDateFieldValue);
  form.appendChild(endDate);

  var isInternal = document.createElement("input");
  isInternal.setAttribute("name", "is_internal");
  isInternal.setAttribute("value", isInternalFieldValue);
  form.appendChild(isInternal);

  var searchValue = document.createElement("input");
  searchValue.setAttribute("name", "search_value");
  searchValue.setAttribute("value", searchFieldValue);
  form.appendChild(searchValue);

  //Excel save file name
  var fileName = document.createElement("input");
  fileName.setAttribute("name", "file_name");
  fileName.setAttribute("value", '사용자 접속이력');
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
      checkDay: 'one'
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
    },{
      id: searchFieldId,
      xtype: 'textfield',
      width: 200,
      //아이디,이름,부서
      emptyText: _text('MN00195')+','+_text('MN00223')+','+_text('MN00181'),
      listeners: {
        specialkey: function (field, e) {
          if (e.getKey() == e.ENTER) {
            onSearch();
          }
        }
      }
    },{
      // 검색 버튼
      text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
      cls: 'proxima_button_customize',
      width: 30,
      handler: function (self) {
        onSearch();
      }
    },{
      // 엑셀 다운로드
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

var bbar = new Ext.PagingToolbar({
  pageSize: pageSize,
  store: store,
});

var grid = new Ext.grid.GridPanel({
  id: gridId,
  title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '사용자 접속이력' + '</span></span>',
  cls: 'grid_title_customize proxima_customize',
  stripeRows: true,
  border: false,
  loadMask: true,
  // width: '100%',
  autoScroll: true,
  store: store,
  tbar: tbar,
  bbar: bbar,
  cm: new Ext.grid.ColumnModel({
    defaults: {
      sortable: false
    },
    columns: [
      {
        header: '아이디',
        dataIndex : 'user_id',
        align: 'center',
      },
      {
        header: '이름',
        dataIndex : 'user_nm',
        align: 'center',
      },
      {
        header: '기관',
        dataIndex : 'org_nm',
        align: 'center',
        width: 200,
      },
      {
        header: '부서',
        dataIndex : 'dept_nm',
        align: 'center',
        width: 200,
      },
      {
        header: '로그인일시',
        dataIndex : 'login_date',
        align: 'center',
        width: 200,
        renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'
      },
      {
        header: '접속IP',
        dataIndex : 'login_ip',
        align: 'center',
        width: 150,
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
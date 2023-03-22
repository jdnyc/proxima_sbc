(function () {


  var gridId = Ext.id();
  var startDateFieldId = Ext.id();
  var endDateFieldId = Ext.id();

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

  /**
   * 엑셀 다운로드시 서브밋 할 폼
   */
  function submitExcelForm(url, startDateValue, endDateValue) {

    var form = document.createElement("form");
    form.setAttribute("method", "post"); // POST
    form.setAttribute("target", "_blank"); // 새창
    form.setAttribute("action", url);

    var startDate = document.createElement("input");
    startDate.setAttribute("name", "start_date");
    startDate.setAttribute("value", startDateValue);
    form.appendChild(startDate);

    var endDate = document.createElement("input");
    endDate.setAttribute("name", "end_date");
    endDate.setAttribute("value", endDateValue);
    form.appendChild(endDate);

    document.body.appendChild(form);
    form.submit();
  }
  function submitExcelExportForm(action) {

    var startDateValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
    var endDateValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';

    var isFrame = document.getElementsByName('_excelDownload');
    if (isFrame.length > 0) {
      var form = document.getElementsByName('_excelDownloadForm')[0];

      var startDate = document.createElement("input");
      startDate.setAttribute("name", "start_date");
      startDate.setAttribute("value", startDateValue);
      form.appendChild(startDate);

      var endDate = document.createElement("input");
      endDate.setAttribute("name", "end_date");
      endDate.setAttribute("value", endDateValue);
      form.appendChild(endDate);

      form.submit();
    } else {
      var doc = document,
        frame = doc.createElement('iframe');
      frame.style.display = 'none';
      frame.name = '_excelDownload';
      doc.body.appendChild(frame);

      form = doc.createElement('form');
      form.method = "post";
      form.name = '_excelDownloadForm';
      form.target = "_excelDownload";
      form.action = action;

      var startDate = document.createElement("input");
      startDate.setAttribute("name", "start_date");
      startDate.setAttribute("value", startDateValue);
      form.appendChild(startDate);

      var endDate = document.createElement("input");
      endDate.setAttribute("name", "end_date");
      endDate.setAttribute("value", endDateValue);
      form.appendChild(endDate);

      frame.appendChild(form);
      form.submit();
    }
    return true;
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
      url: '/api/v1/statistics-archive-week',
      type: 'rest'
    }),
    fields: [
      'ud_content_title',
      'created_date',
      'cnt',
      'filesize_gb',
      'filesize_tb'
    ],
    listeners: {
      load: function (store, record) {

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
          var startDate = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
          var endDate = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
          submitExcelExportForm('/api/v1/statistics-archive-week/export-excel');
          // submitExcelForm('/api/v1/statistics-archive-week/export-excel', startDate, endDate);
        }
      }
    ]
  });

  var grid = new Ext.grid.GridPanel({
    id: gridId,
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '주간 통계' + '</span></span>',
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
        { header: '유형', dataIndex: 'ud_content_title' },
        { header: '수량(건)', dataIndex: 'cnt', renderer: function (value, metaData, record, rowIndex, colIndex, store) {
          return Ext.util.Format.number(value,'0,000');
        } },
        {
          header: '용량', dataIndex: 'filesize_tb', renderer: function (v) {
            if (!Ext.isEmpty(v)) {
              return v + ' TB';
            } else {
              return '0.00 TB';
            }
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
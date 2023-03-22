(function () {
  Ext.ns('Ariel.DashBoard');
  Ariel.DashBoard.Notice = Ext.extend(Ext.grid.GridPanel, {
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '공지사항' + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    // cls: 'proxima_customize',
    id: 'main_notice_grid',
    loadMask: true,
    border: false,
    viewConfig: {
      emptyText: _text('MSG00148'),
      forceFit: false,
      border: false
    },
    defaults: {
      border: false,
      // margins: '10 10 10 10'
    },
    frame: false,

    listeners: {
      render: function (self) {

        self.store.load();
      },
      rowdblclick: function (self, index, e) {
        var sel = self.getSelectionModel().getSelected();
        var n_id = sel.get('notice_id');
        var n_type = sel.get('notice_type');
        self._showDetail(n_id);
      },
    },
    initComponent: function () {

      this._initialize();

      Ariel.DashBoard.Notice.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
      this.store = new Ext.data.JsonStore({
        url: '/store/notice/get_list.php',
        root: 'data',
        totalProperty: 'total',
        baseParams: {
          type: 'list'
        },
        _isAlertFirst: true,
        fields: [
          { name: 'notice_id' },
          { name: 'notice_title' },
          { name: 'notice_content' },
          { name: 'from_user_id' },
          { name: 'from_user_nm' },
          { name: 'notice_type' },
          { name: 'member_group_id' },
          { name: 'notice_type_text' },
          { name: 'depnm' },
          { name: 'mname' },
          { name: 'depcd' },
          { name: 'created_date', type: 'date', dateFormat: 'YmdHis' },
          { name: 'is_new' },
          { name: 'total_notice_new' },
          { name: 'file_flag' },
          { name: 'read_flag' },
          { name: 'notice_start', type: 'date', dateFormat: 'YmdHis' },
          { name: 'notice_end', type: 'date', dateFormat: 'YmdHis' },
          {
            name: 'notice_date', convert: function (v, record) {
              if (Ext.isEmpty(record.notice_start) && Ext.isEmpty(record.notice_end)) {
                return;
              } else {

                var time_s = record.notice_start
                var st = Date.parseDate(time_s, 'YmdHis');

                var time_e = record.notice_end
                var et = Date.parseDate(time_e, 'YmdHis');

                return st.format('Y-m-d') + ' ~ ' + et.format('Y-m-d');
              }
            }
          },
          {
            name: 'title_flag_read', convert: function (v, record) {

              if (record.read_flag == 0) {
                record.read_flag = '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -3px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>';
              } else {
                record.read_flag = '';
              }
              return record.read_flag;
            }
          },
          {
            name: 'flag_file', convert: function (v, record) {
              if (record.file_flag > 0) {
                record.read_flag = '<span style="position:relative;top:1px;"><i class="fa fa-paperclip" style="font-size:13px;"></i></span>';
              } else {
                record.read_flag = '';
              }
              return record.read_flag;
            }
          }
        ],
        listeners: {
          load: function (self) {
            // if (self._isAlertFirst) {
            //   self._isAlertFirst = false;

            //   if (!Ext.isEmpty(self.reader.jsonData.data)) {
            //     var row = self.reader.jsonData.data[0];

            //     if (Ext.util.Cookies.get('notice_popup_cookie_' + row.notice_id) != 'N') {
            //       noticePopup(row.notice_id);
            //     }
            //   }
            // }
            if (self.reader.jsonData.total > 0) {
              var total_notice_count = self.reader.jsonData.new_total;
              if (total_notice_count > 0) {
                Ext.fly('total_new_notice').dom.innerHTML = '<font size=2px color="#FFFFFF" class="new_count_icon"><b>&nbsp' + total_notice_count + '&nbsp</b></font>';
              } else {
                Ext.fly('total_new_notice').dom.innerHTML = '';
              }
            }
          }
        }
      });

      this.colModel = new Ext.grid.ColumnModel({
        defaultSortable: false,
        columns: [
          new Ext.grid.RowNumberer(),
          { header: '<span class="fa-stack " style="position:relative;height: 17px;margin-top: -9px;"><i class="fa fa-certificate fa-stack-1x" style="font-size:17px;color:#ff6600;"></i><strong class="fa fa-inverse fa-stack-1x fa-text" style="position:relative;font-size:10px;font-weight:bold;">N</strong></span>', dataIndex: 'title_flag_read', width: 30, align: 'center', hidden: true },
          { header: _text('MN00249'), dataIndex: 'notice_title', width: 250 },//title
          { header: _text('MN02207'), dataIndex: 'notice_date', width: 160, align: 'center' },//공지기간
          { header: _text('MN00102'), width: 130, dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center' },//등록일자
          { header: _text('MN02206'), dataIndex: 'from_user_nm', width: 120 }//등록자
        ]
      });
      this.sm = new Ext.grid.RowSelectionModel({
        singleSelect: true
      });
      this.bbar = {
        xtype: 'paging',
        pageSize: 20,
        displayInfo: true,
        store: _this.store
      };
      this.tbar = [{
        //>>text: '새로고침',
        cls: 'proxima_button_customize',
        width: 30,

        text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
        handler: function () {
          _this.store.reload();
        },
        scope: this
      }];
    },
    _showDetail: function (n_id) {
      Ext.Ajax.request({
        //url: '/store/notice/main_notice_form.php',
        url: '/pages/menu/config/notice/win_notice.php',
        params: {
          action: 'view',
          type: 'view',
          notice_id: n_id,
          screen_width: window.innerWidth,
          screen_height: window.innerHeight
        },
        callback: function (self, success, response) {
          try {
            var r = Ext.decode(response.responseText);

            r.show();
          }
          catch (e) {
            //>>Ext.Msg.alert('오류', e);
            Ext.Msg.alert('오류', e);
          }
        }
      });
    }
  });
  // return new Ariel.DashBoard.Notice();
})()
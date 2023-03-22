(function () {


  var myPageSize_notice = 25;//페이지 제한

  Ext.override(Ext.PagingToolbar, {
    doload: function (start) {
      var o = {}, pn = this.getParams();
      o[pn.start] = start;
      o[pn.limit] = this.pageSize;
      if (this.fireEvent('beforechange', this, o) !== false) {
        var options = Ext.apply({}, this.store.lastOptions);
        options.params = Ext.applyIf(o, options.params);
        this.store.load(options);
      }
    }
  });

  function format_date(str_date) {
    return str_date.substr(0, 4) + '-' + str_date.substr(4, 2) + '-' + str_date.substr(6, 2);
  }

  var notice_store = new Ext.data.JsonStore({//공지사항 스토어
    url: '/store/notice/get_list.php',
    root: 'data',
    baseParams: {
      type: 'admin_list'
    },
    totalProperty: 'total',
    fields: [
      { name: 'notice_id' },
      { name: 'notice_type' },
      { name: 'notice_type_text' },
      { name: 'from_user_id' },
      { name: 'to_user_id' },
      { name: 'member_group_id' },
      { name: 'from_user_nm' },
      { name: 'to_user_nm' },
      { name: 'member_group_name' },
      { name: 'notice_title' },
      { name: 'created_date', type: 'date', dateFormat: 'YmdHis' },
      { name: 'notice_content' },
      { name: 'notice_start', type: 'date', dateFormat: 'YmdHis' },
      { name: 'notice_end', type: 'date', dateFormat: 'YmdHis' },
      {
        name: 'notice_date', convert: function (v, record) {
          if (Ext.isEmpty(record.notice_start) && Ext.isEmpty(record.notice_end)) {
            return;
          } else {//Ext.util.Format.dateRenderer(record.start_date, 'Y-m-d')
            return format_date(record.notice_start) + ' ~ ' + format_date(record.notice_end);
          }
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
      },
      { name: 'notice_popup_at'}
    ],
    listeners: {
      beforeload: function () {
        baseParams = {
          limit: myPageSize_notice,
          start: 0
        }
      }
    }
  });
  notice_store.load({ params: { start: 0, limit: myPageSize_notice } });

  var notice_bbar = new Ext.PagingToolbar({//공지사항 페이징
    store: notice_store,
    pageSize: myPageSize_notice
  });

  var msg = function (title, msg) {//메세지 함수
    Ext.Msg.show({
      title: title,
      msg: msg,
      minWidth: 200,
      modal: true,
      icon: Ext.Msg.INFO,
      buttons: Ext.Msg.OK
    });
  };

  function moveSelectedRow(grid, direction) {
    var record = grid.getSelectionModel().getSelected();
    if (!record) {
      return;
    }
    var index = grid.getStore().indexOf(record);
    if (direction < 0) {
      index--;
      if (index < 0) {
        return;
      }
    } else {
      index++;
      if (index >= grid.getStore().getCount()) {
        return;
      }
    }
    grid.getStore().remove(record);
    grid.getStore().insert(index, record);
    grid.getSelectionModel().selectRow(index, true);
  }


  function showWin(type, action, notice_id) {
    Ext.Ajax.request({
      url: '/pages/menu/config/notice/win_notice.php',
      params: {
        action: action,
        type: type,
        notice_id: notice_id,
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
          Ext.Msg.alert(_text('MN00022'), e);
        }
      }
    });
  }

  var notice_grid = new Ext.grid.GridPanel({//////공지사항 그리드///////
    id: 'notice_grid',
    stripeRows: true,
    border: false,
    store: notice_store,
    sm: new Ext.grid.RowSelectionModel({
      singleSelect: true
    }),
    tbar: [{
      //icon: '/led-icons/pencil.png',
      //style:'border-style:solid;border-width:thin;border-color:#bebebe',

      //>>text: '등록',
      //text: _text('MN00038'),
      cls: 'proxima_button_customize',
      width: 30,
      text: '<span style="position:relative;top:1px;" title="' + _text('MN00038') + '"><i class="fa fa-plus" style="font-size:13px;color:white;"></i></span>',
      handler: function (btn, e) {
        showWin('insert', 'user', 0);
      }
    }, {
      //icon: '/led-icons/bandaid.png',
      //style:'border-style:solid;border-width:thin;border-color:#bebebe',
      //>>text: '수 정',
      //text: _text('MN00043'),
      cls: 'proxima_button_customize',
      width: 30,
      text: '<span style="position:relative;top:1px;" title="' + _text('MN00043') + '"><i class="fa fa-edit" style="font-size:13px;color:white;"></i></span>',
      handler: function (btn, e) {
        var edit_sel = notice_grid.getSelectionModel().getSelected();
        if (!edit_sel)
          //>>msg('알림', '수정하실 글을 선택해주세요');
          msg(_text('MN00023'), _text('MSG00160'));
        else {
          var notice_id = edit_sel.get('notice_id');
          showWin('edit', 'edit', notice_id);
        }
      }
    }, {
      //icon: '/led-icons/bin_closed.png',
      //style:'border-style:solid;border-width:thin;border-color:#bebebe',
      //>>text: '삭 제',
      //text: _text('MN00034'),
      cls: 'proxima_button_customize',
      width: 30,
      text: '<span style="position:relative;top:1px;" title="' + _text('MN00034') + '"><i class="fa fa-ban" style="font-size:13px;color:white;"></i></span>',
      handler: function () {
        var del_sel = notice_grid.getSelectionModel().getSelected();
        if (!del_sel)
          //>>msg('알림', '삭제하실 글을 선택해주세요');
          msg(_text('MN00023'), _text('MSG00082'));
        else {
          Ext.Msg.show({
            icon: Ext.Msg.QUESTION,
            //>>title: '확인',
            //>>msg: '삭제 하시겠습니까?',
            title: _text('MN00024'),
            msg: _text('MSG00161'),
            buttons: Ext.Msg.OKCANCEL,
            fn: function (btnId, text, opts) {
              if (btnId == 'cancel') return;

              var del_id = del_sel.get('notice_id');
              Ext.Ajax.request({
                url: '/pages/menu/config/notice/notice_del.php',
                params: {
                  id: del_id
                },
                callback: function (options, success, response) {
                  if (success) {
                    try {
                      var r = Ext.decode(response.responseText);
                      if (r.success) {
                        notice_store.reload();
                        //>>msg('알림', '삭제 완료');
                        //msg(_text('MN00023'), _text('MSG00040'));
                      }
                      else {
                        //>>Ext.Msg.alert('확인', r.msg);
                        Ext.Msg.alert(_text('MN00024'), r.msg);
                      }
                    }
                    catch (e) {
                      Ext.Msg.alert(e['name'], e['message']);
                    }
                  }
                  else {
                    Ext.Msg.alert(_text('MN01098'), response.statusText);//'서버 오류'
                    Ext.Msg.alert(_text('MSG00024'), response.statusText);
                  }
                }
              });
            }
          });
        }
      }
    }],
    columns: [
      //{header: 'No.', width: 20, dataIndex: 'no',align:'center'},
      new Ext.grid.RowNumberer(),
      //{header : '<span style="position:relative;top:1px;"><i class="fa fa-paperclip" style="font-size:13px;"></i></span>', dataIndex : 'flag_file', width : 50, align:'center'},//첨부파일 attached file
      { header: _text('MN00249'), dataIndex: 'notice_title', width: 200 },//제목
      { header: _text('MN00222'), dataIndex: 'notice_type_text', width: 60, align: 'center' },//유형
      { header: '팝업여부', dataIndex: 'notice_popup_at', width: 60, align: 'center' },
      { header: _text('MN02207'), dataIndex: 'notice_date', width: 130, align: 'center' },//공지기간
      { header: _text('MN00102'), width: 120, dataIndex: 'created_date', renderer: Ext.util.Format.dateRenderer('Y-m-d'), align: 'center' },//등록일자
      { header: _text('MN02206'), dataIndex: 'from_user_id', width: 200 },//작성자
    ],
    viewConfig: {
      forceFit: true,
      emptyText: _text('MSG00148')//결과 값이 없습니다
    },
    listeners: {
      rowdblclick: function (self, idx, e) {
        var edit_sel = notice_grid.getSelectionModel().getSelected();
        var notice_id = edit_sel.get('notice_id');
        showWin('edit', 'user', notice_id);
      }
    },
    bbar: [notice_bbar]
  });
  return {
    border: false,
    loadMask: true,
    layout: 'fit',
    title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + _text('MN00145') + '</span></span>',
    cls: 'grid_title_customize proxima_customize',
    items: [
      notice_grid
    ]
  };
})()
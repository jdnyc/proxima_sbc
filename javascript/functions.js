// 2018.02.13 khk javascript에서 권한을 확인하기 위한 함수
function checkGrant(contentIds, userId, accessGrant, popupGrant, cb) {
  Ext.Ajax.request({
    url: '/store/checkGrant.php',
    jsonData: {
      contentIds: contentIds,
      userId: userId,
      accessGrant: accessGrant,
      popupGrant: popupGrant
    },
    callback: function(options, success, response) {
      if (success) {
        try {
          var result = Ext.decode(response.responseText);
          if (result.success) {
            cb(result.data);
          } else {
            Ext.Msg.alert(_text('MN00022'), result.msg);
          }
        } catch (e) {
          Ext.Msg.alert(e['name'], e['message']);
        }
      } else {
        Ext.Msg.alert(_text('MN00022'), response.statusText);
      }
    }
  });
}

// 2018.02.13 khk 권한체크 하고 모든 콘텐츠에 작업 권한이 있는지 확인하는 함수
function checkAllAllowed(data, showMessage) {
  var isAllAllowed;
  var messageVisible = false;
  if (showMessage === undefined) {
    messageVisible = true;
  } else {
    messageVisible = showMessage;
  }

  for (var i = 0; i < data.length; i++) {
    if (i == 0) {
      isAllAllowed = data[i].isAllow;
    } else {
      isAllAllowed &= data[i].isAllow;
    }
  }

  if (!isAllAllowed && messageVisible) {
    Ext.Msg.alert('확인', '선택된 콘텐츠에 대한 작업 권한이 없습니다.');
  }

  return isAllAllowed;
}

// 등록자가 로그인한 사용자이면 권한 허용
function checkAllowGrantForMyContent(regUserId, userId) {
  return regUserId == userId;
}

window.global_review_status;

function mergeObjectProperty(objectArray) {
  //Object를 배열로 받아 합침 mergeObjectProperty([obj1, obj2, ...])
  var resultObject = {};
  for (var i = 0, length = objectArray.length; i < length; i++) {
    var object = objectArray[i];
    for (var property in object) {
      if (object.hasOwnProperty(property)) {
        resultObject[property] = object[property];
      }
    }
  }
  return resultObject;
}

function popupWindow(url, x, y, args, action) {
  var win = window.open(
    url + Ext.encode(args) + '&action=' + action,
    'popup',
    'status=no,menubar=no,scrollbars=no,resizable=no,toolbar=no'
  );
  var cx = Math.ceil((window.screen.width - x) / 2);
  var cy = Math.ceil((window.screen.height - y) / 2);

  win.resizeTo(Math.ceil(x), Math.ceil(y));
  win.moveTo(Math.ceil(cx), Math.ceil(cy));

  win.focus();
}

function byteCalculation(bytes) {
  var bytes = parseInt(bytes);
  var s = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];
  var e = Math.floor(Math.log(bytes) / Math.log(1024));

  if (e == '-Infinity') return '0 ' + s[0];
  else return (bytes / Math.pow(1024, Math.floor(e))).toFixed(2) + ' ' + s[e];
}

function callModuleRen(url, p) {
  Ext.Ajax.request({
    url: url,
    params: p,
    callback: function(options, success, response) {
      if (success) {
        try {
          Ext.decode(response.responseText);
        } catch (e) {
          Ext.Msg.alert(e['name'], e['message']);
        }
      } else {
        Ext.Msg.alert(_text('MN00022'), response.statusText);
      }
    }
  });
}

function callModule(url, p) {
  Ext.Ajax.request({
    url: url,
    params: {
      args: Ext.encode(p)
    },
    callback: function(opts, success, response) {
      if (success) {
        try {
          var r = Ext.decode(response.responseText);
          if (r.success === false) {
            Ext.Msg.alert('확인', r.msg);
          }
        } catch (e) {
          //Ext.Msg.alert('a', response.responseText);
          Ext.Msg.alert(e['name'], e['message']);
        }
      } else {
        Ext.Msg.alert(_text('MN01098'), response.statusText); //'서버 오류'
      }
    }
  });
}

function buildDownloadConfirm(records) {
  Ext.Ajax.request({
    url: '/store/edit_list_content.php',
    callback: function(opts, success, response) {
      if (success) {
        try {
          var r = Ext.decode(response.responseText);
          r.addDownloadList(records);
          r.show();
        } catch (e) {
          Ext.Msg.alert('오류', e);
        }
      } else {
        Ext.Msg.alert(
          '오류',
          response.statusText + '( ' + response.status + ' )'
        );
      }
    }
  });
}

function requestBatch(args) {
  popupWindow('/modules/batcheditmetadata.php?args=', 1008, 680, args);
}
function requestBatchAccept(args) {
  popupWindow(
    '/modules/batcheditmetadata.php?args=',
    1008,
    680,
    args,
    'accept'
  );
}

function callBatchForm(args) {
  return callModule('/modules/batch/store/form.php', args);
}

function checkSelected(records) {
  if (records.length == 0) {
    //"선택된 콘텐츠가 없습니다."
    Ext.Msg.alert(_text('MN00024'), _text('MSG00066'));
    return false;
  }

  return true;
}

// 콘텐츠 목록에서 선택된 항목을 반환
function getSelectedContents() {
  var sm = Ext.getCmp('tab_warp')
    .getActiveTab()
    .get(0)
    .getSelectionModel();
  var records = sm.getSelections();

  return records;
}

function myPageDownload(records, isWorkpanelClear) {
  new Ext.Window({
    modal: true,
    width: 300,
    height: 150,
    title: '다운로드 사유',
    border: false,
    layout: 'border',

    items: [
      {
        region: 'center',
        xtype: 'textarea',
        frame: true,
        allowBlank: false,
        name: 'report_download',
        emptyText: '다운로드 사유를 적어주세요.'
      }
    ],

    listeners: {
      show: function(self) {
        self.get(0).focus(false, 500);
      }
    },

    buttons: [
      {
        text: '다운로드',
        handler: function(b, e) {
          var o = document.getElementById('GeminiAxCtrl');
          if (!o) {
            alert('다운로드 프로그램이 설치되어있지 않습니다.');
          }

          var type = 'mypfr',
            s = b.ownerCt.ownerCt.get(0);

          if (!s.isValid()) {
            Ext.Msg.alert('확인', '다운로드 사유를 입력하세요');
            return;
          }

          var ids = [];
          Ext.each(records, function(r) {
            ids.push(r.get('media_id'));
          });

          Ext.Ajax.request({
            url: '/store/get_download_list.php',
            params: {
              //content_id: ids.join(','),
              media_id: ids.join(','),
              summary: s.getValue(),
              type: type
            },
            callback: function(opts, success, resp) {
              if (success) {
                try {
                  var rtn = Ext.decode(resp.responseText);
                  if (rtn.success) {
                    if (isWorkpanelClear) {
                      Ext.getCmp('workpanel')
                        .getStore()
                        .removeAll();
                    }
                    GeminiAxCtrl.MultiDownload(rtn.data);
                    Ext.Msg.alert(
                      '확인',
                      '다운로드 프로그램이 실행되었습니다.'
                    );
                  } else {
                    Ext.Msg.alert('다운로드 리스트 생성도 중 오류', rtn.msg);
                  }
                } catch (e) {
                  Ext.Msg.alert(e['name'], e['message']);
                }
              } else {
                Ext.Msg.alert('서버 통신 오류', resp.statusText);
              }

              b.ownerCt.ownerCt.close();
            }
          });
        }
      },
      {
        text: '취소',
        handler: function(b, e) {
          b.ownerCt.ownerCt.close();
        }
      }
    ]
  }).show();
}

function doDownloadMediaSelectedItems(content_id, records) {
  new Ext.Window({
    modal: true,
    width: 300,
    height: 150,
    title: '다운로드 사유',
    border: false,
    layout: 'border',

    items: [
      {
        region: 'center',
        xtype: 'textarea',
        frame: true,
        allowBlank: false,
        name: 'report_download',
        emptyText: '다운로드 사유를 적어주세요.'
      }
    ],

    listeners: {
      show: function(self) {
        self.get(0).focus(false, 500);
      }
    },

    buttons: [
      {
        text: '다운로드',
        handler: function(b, e) {
          var s = b.ownerCt.ownerCt.get(0);
          if (!s.isValid()) {
            return;
          }

          var ids = [];
          Ext.each(records, function(r) {
            ids.push(r.get('media_id'));
          });
          Ext.Ajax.request({
            url: '/store/get_download_media_item.php',
            params: {
              content_id: content_id,
              media_ids: ids.join(','),
              summary: s.getValue()
            },
            callback: function(opts, success, resp) {
              if (success) {
                var o = document.getElementById('GeminiAxCtrl');
                if (o) {
                  o.MultiDownload(resp.responseText);
                  Ext.Msg.alert('확인', '다운로드 프로그램이 실행되었습니다.');
                } else {
                  Ext.Msg.alert(
                    '확인',
                    '다운로드 프로그램이 설치되어있지 않습니다.'
                  );
                }
              } else {
                Ext.Msg.alert('서버 확인', resp.statusText);
              }
              b.ownerCt.ownerCt.close();
            }
          });
        }
      },
      {
        text: '취소',
        handler: function(b, e) {
          b.ownerCt.ownerCt.close();
        }
      }
    ]
  }).show();
}

function doBatchEditContentCheck(records) {
  var bReturn = false,
    beforeContentMetaType,
    rs = [];

  Ext.each(records, function(r, i, a) {
    if (bReturn) return;

    if (
      !Ext.isEmpty(beforeContentMetaType) &&
      beforeContentMetaType !== r.get('meta_table_id')
    ) {
      bReturn = true;
    } else {
      beforeContentMetaType = r.get('meta_table_id');
    }

    rs.push({
      content_id: r.get('content_id')
    });
  });

  if (bReturn) {
    Ext.Msg.alert(
      '정보',
      '콘텐츠 유형이 동일하지 않습니다.<br />' +
        '일괄 수정을 하기위해서는 동일한 유형의 콘텐츠을 선택하여주세요'
    );
    return;
  }

  requestBatch(rs);
}

function sprintf() {
  if (!arguments || arguments.length < 1 || !RegExp) {
    return;
  }
  var str = arguments[0];
  var re = /([^%]*)%('.|0|\x20)?(-)?(\d+)?(\.\d+)?(%|b|c|d|u|f|o|s|x|X)(.*)/;
  var a = (b = []),
    numSubstitutions = 0,
    numMatches = 0;
  while ((a = re.exec(str))) {
    var leftpart = a[1],
      pPad = a[2],
      pJustify = a[3],
      pMinLength = a[4];
    var pPrecision = a[5],
      pType = a[6],
      rightPart = a[7];

    //alert(a + '\n' + [a[0], leftpart, pPad, pJustify, pMinLength, pPrecision);

    numMatches++;
    if (pType == '%') {
      subst = '%';
    } else {
      numSubstitutions++;
      if (numSubstitutions >= arguments.length) {
        alert(
          'Error! Not enough function arguments (' +
            (arguments.length - 1) +
            ', excluding the string)\nfor the number of substitution parameters in string (' +
            numSubstitutions +
            ' so far).'
        );
      }
      var param = arguments[numSubstitutions];
      var pad = '';
      if (pPad && pPad.substr(0, 1) == "'") pad = leftpart.substr(1, 1);
      else if (pPad) pad = pPad;
      var justifyRight = true;
      if (pJustify && pJustify === '-') justifyRight = false;
      var minLength = -1;
      if (pMinLength) minLength = parseInt(pMinLength);
      var precision = -1;
      if (pPrecision && pType == 'f')
        precision = parseInt(pPrecision.substring(1));
      var subst = param;
      if (pType == 'b') subst = parseInt(param).toString(2);
      else if (pType == 'c') subst = String.fromCharCode(parseInt(param));
      else if (pType == 'd') subst = parseInt(param) ? parseInt(param) : 0;
      else if (pType == 'u') subst = Math.abs(param);
      else if (pType == 'f')
        subst =
          precision > -1
            ? Math.round(parseFloat(param) * Math.pow(10, precision)) /
              Math.pow(10, precision)
            : parseFloat(param);
      else if (pType == 'o') subst = parseInt(param).toString(8);
      else if (pType == 's') subst = param;
      else if (pType == 'x')
        subst = ('' + parseInt(param).toString(16)).toLowerCase();
      else if (pType == 'X')
        subst = ('' + parseInt(param).toString(16)).toUpperCase();
    }
    str = leftpart + subst + rightPart;
  }
  return str;
}

function ddToNLE(content_id, type, fileserver_addr) {
  Ext.Ajax.request({
    url: '/store/getMediaFiles.php',
    params: {
      content_id: content_id,
      type: type
    },
    callback: function(opts, success, response) {
      if (success) {
        var rtn = Ext.decode(response.responseText);
        if (!rtn.success) {
          Ext.Msg.alert('확인', rtn.msg);
          return;
        }

        var dragger = document.getElementById('GeminiAxCtrl');
        if (dragger) {
          var args =
            fileserver_addr.split('/').join('\\') +
            rtn.files[0].path.split('/').join('\\');
          dragger.DragNDrop(args);
        } else {
          Ext.Msg.alert('확인', 'ActiveX 가 설치되어있지 않습니다.');
          return;
        }
      } else {
        Ext.Msg.alert(_text('MN01098'), response.statusText); //'서버 오류'
      }
    }
  });
}

function timecodeToSec(timecode) {
  var tc, h, i, s, sum;

  //split로 나누면 08이 0으로 들어가서 수정함 2010.10.19 김형기
  //tc = timecode.split(':');

  h = timecode.substr(0, 2) * 3600;
  i = timecode.substr(3, 2) * 60;
  s = timecode.substr(6, 2) * 1;

  h = parseInt(h);
  i = parseInt(i);
  s = parseInt(s);

  //split로 나누면 08이 0으로 들어가서 수정함 2010.10.19 김형기
  //h = parseInt(tc[0]) * 3600;
  //i = parseInt(tc[1]) * 60;
  //s = parseInt(tc[2]);

  sum = h + i + s;

  return sum;
}

function secToTimecode(sec) {
  var h = parseInt(sec / 3600);
  var i = parseInt((sec % 3600) / 60);
  var s = (sec % 3600) % 60;

  h = String.leftPad(h, 2, '0');
  i = String.leftPad(i, 2, '0');
  s = String.leftPad(s, 2, '0');
  var time = h + ':' + i + ':' + s;

  return time;
}

function timecodeToSecFrame(timecode, frame_rate) {
  var tc, h, i, s, f, sum;

  //split로 나누면 08이 0으로 들어가서 수정함 2010.10.19 김형기
  //tc = timecode.split(':');

  h = timecode.substr(0, 2) * 3600;
  i = timecode.substr(3, 2) * 60;
  s = timecode.substr(6, 2) * 1;
  f = timecode.substr(9, 2) * 1;

  h = parseInt(h);
  i = parseInt(i);
  s = parseInt(s);
  f = parseInt(f);

  //split로 나누면 08이 0으로 들어가서 수정함 2010.10.19 김형기
  //h = parseInt(tc[0]) * 3600;
  //i = parseInt(tc[1]) * 60;
  //s = parseInt(tc[2]);

  sum = h + i + s + f / frame_rate;
  return sum;
}

function secFrameToTimecode(sec, frame_rate) {
  var h = parseInt(sec / 3600);
  var i = parseInt((sec % 3600) / 60);
  var s = parseInt((sec % 3600) % 60);
  var f = Math.floor((sec - parseInt(sec)) * frame_rate);
  //f= Math.round(f);

  h = String.leftPad(h, 2, '0');
  i = String.leftPad(i, 2, '0');
  s = String.leftPad(s, 2, '0');
  f = String.leftPad(f, 2, '0');
  var time = h + ':' + i + ':' + s + ':' + f;
  return time;
}

function frameToTimecode(frame, frame_rate) {
  var sec = parseInt(frame / frame_rate);
  var h = parseInt(sec / 3600);
  var i = parseInt((sec % 3600) / 60);
  var s = parseInt((sec % 3600) % 60);
  var f = Math.floor(frame - parseInt(sec) * frame_rate);
  //f= Math.round(f);

  h = String.leftPad(h, 2, '0');
  i = String.leftPad(i, 2, '0');
  s = String.leftPad(s, 2, '0');
  f = String.leftPad(f, 2, '0');
  var time = h + ':' + i + ':' + s + ':' + f;
  return time;
}

function checkInOut(secIn, secOut) {
  var txt = null;
  if (Ext.isEmpty(secIn)) {
    txt = _text('MSG00191'); //'In 값이 비어 있습니다.'
  } else if (Ext.isEmpty(secOut)) {
    txt = _text('MSG00192'); // 'Out 값이 비어 있습니다.';
  } else if (secIn == secOut) {
    txt = _text('MSG00193'); // 'In 과 Out 값이 동일합니다.';
  } else if (secIn > secOut) {
    txt = _text('MSG00194'); // 'Out 보다 In 값이 더 큽니다.';
  }
  //	else if ( (secOut - secIn) < 10)
  //	{
  //		txt = _text('MSG00195');// 'Out 보다 In 값이 더 큽니다.';
  //	}

  return txt;
}

function runDownload() {
  var g = Ext.getCmp('tab_warp').getActiveTab();
  if (g.title != 'TV방송프로그램' && g.title != '라디오방송프로그램') {
    Ext.Msg.alert(
      '정보',
      '"검색 결과" 또는 "최근 방송" 탭에서만 다운로드가 가능합니다.'
    );
    return;
  }

  if (Ext.isEmpty(window.current_focus)) {
    Ext.Msg.alert('확인', '다운로드할 콘텐츠를 선택하여 주세요.');
    return;
  }
  var records = current_focus.getSelectionModel().getSelections();

  if (!checkSelected(records)) return;

  doDownload(records);
}

winSearchReceiveWrapper = {
  init: function() {
    return new Ext.Window({
      title: '수신자 검색',
      width: 450,
      height: 250,
      layout: 'vbox',
      layoutConfig: {
        align: 'stretch'
      },
      border: false,
      modal: true,

      items: [
        {
          layout: 'column',

          items: [
            {
              xtype: 'textfield',
              id: 'field_reviewer',
              allowBlank: false,
              fieldLabel: '수신자',
              emptyText: '검색하실 이름을 입력하세요',
              columnWidth: 1,
              listeners: {
                specialkey: function(f, e) {
                  e.stopEvent();
                  if (e.getKey() == e.ENTER) {
                    Ext.getCmp('result_list_reviewer').store.load({
                      params: {
                        name: Ext.getCmp('field_reviewer').getValue()
                      }
                    });
                  }
                }
              }
            },
            {
              xtype: 'button',
              text: '검색',
              width: 100,
              handler: function(b, e) {
                Ext.getCmp('result_list_reviewer').store.load({
                  params: {
                    name: Ext.getCmp('field_reviewer').getValue()
                  }
                });
              }
            }
          ]
        },
        {
          xtype: 'grid',
          flex: 1,
          id: 'result_list_reviewer',
          loadMask: true,
          viewConfig: {
            forceFit: true,
            emptyText: '검색 결과가 없습니다.'
          },
          store: new Ext.data.JsonStore({
            url: '/store/get_reviewer_list.php',
            root: 'data',
            fields: ['EMPNO', 'KOR_NM', 'DEPT_NM', 'EMAIL']
          }),
          sm: new Ext.grid.RowSelectionModel({
            singleSelect: true
          }),
          columns: [
            { header: '사번', dataIndex: 'EMPNO' },
            { header: '이름', dataIndex: 'KOR_NM' },
            { header: '부서', dataIndex: 'DEPT_NM' },
            { header: '이메일', dataIndex: 'EMAIL' }
          ],
          listeners: {
            rowdblclick: function(self, i, e) {
              Ext.getCmp('batch_review_form').isChange = 'Y';
              Ext.getCmp('batch_review_list')
                .getSelectionModel()
                .getSelected()
                .set('changed', '변경됨');

              var r = self.getSelectionModel().getSelected();

              var existsRecord = Ext.getCmp(
                'batch_receiver_list'
              ).store.indexOf(r);
              var hasSelection = self.getSelectionModel().hasSelection();

              if (existsRecord > -1) {
                //App.setAlert(App.STATUS_NOTICE, r.get('KOR_NM') + '(' + r.get('EMPNO') + \") 중복된 자료입니다.\");
                return;
              }

              Ext.getCmp('batch_receiver_list')
                .getStore()
                .add(r);
            }
          }
        }
      ],

      buttons: [
        {
          text: '추가',
          handler: function(b, e) {
            Ext.getCmp('batch_review_form').isChange = 'Y';
            Ext.getCmp('batch_review_list')
              .getSelectionModel()
              .getSelected()
              .set('changed', '변경됨');

            var r = Ext.getCmp('result_list_reviewer')
              .getSelectionModel()
              .getSelected();

            var existsRecord = Ext.getCmp('batch_receiver_list').store.indexOf(
              r
            );
            var hasSelection = Ext.getCmp('result_list_reviewer')
              .getSelectionModel()
              .hasSelection();

            if (hasSelection && existsRecord == -1) {
              Ext.getCmp('batch_receiver_list')
                .getStore()
                .add(r);
            }
          }
        },
        {
          text: '닫기',
          handler: function(b, e) {
            b.ownerCt.ownerCt.close();
          }
        }
      ]
    });
  }
};
winSearchRecevie = winSearchReceiveWrapper.init;

Ext.namespace('Ext.ux');
Ext.ux.UserCustomField = Ext.extend(Ext.form.CompositeField, {
  initComponent: function() {
    var config = {
      //readOnly: true
      submitValue: true,
      listeners: {
        change: function(self, newValue, oldValue) {
          that.get(0).setValue(newValue);
        },
        render: function(self) {},
        afterrender: function(self) {
          self.items.items[0].setValue(self.getValue());
        }
      }
    };

    Ext.apply(this, Ext.apply(this.initialConfig, config));

    var that = this;

    var user_win_id = Ext.id();
    var fieldId = Ext.id();
    var user_list_id = Ext.id();
    var selected_list_id = Ext.id();

    var user_search_store = new Ext.data.JsonStore({
      url: '/pages/menu/config/user/php/get.php',
      remoteSort: true,
      sortInfo: {
        field: 'user_id',
        direction: 'ASC'
      },
      idProperty: 'member_id',
      root: 'data',
      fields: [
        'member_id',
        'user_id',
        'user_nm',
        'group',
        'occu_kind',
        'job_position',
        'job_duty',
        'dep_tel_num',
        'breake',
        'dept_nm',
        { name: 'created_time', type: 'date', dateFormat: 'YmdHis' },
        { name: 'last_login', type: 'date', dateFormat: 'YmdHis' },
        { name: 'hired_date', type: 'date', dateFormat: 'YmdHis' },
        { name: 'retire_date', type: 'date', dateFormat: 'YmdHis' }
      ],
      listeners: {
        exception: function(self, type, action, opts, response, args) {
          try {
            var r = Ext.decode(response.responseText);
            if (!r.success) {
              //>>Ext.Msg.alert('정보', r.msg);
              Ext.Msg.alert(_text('MN00023'), r.msg);
            }
          } catch (e) {
            //>>Ext.Msg.alert('디코드 오류', e);
            Ext.Msg.alert(_text('MN00022'), e);
          }
        }
      }
    });

    function array_search(needle, haystack) {
      for (var i in haystack) {
        if (haystack[i] == needle) return i;
      }
      return false;
    }

    function showUser(that) {
      var user_win = new Ext.Window({
        title: _text('MN00190'),
        //modal:true,
        //closeAction : 'hide',
        width: 850,
        height: 420,
        layout: 'fit',
        items: [
          {
            xtype: 'panel',
            layout: 'hbox',
            border: false,
            padding: 10,
            buttonAlign: 'center',
            buttons: [
              {
                //icon: '/led-icons/accept.png',
                text:
                  '<span style="position:relative;top:1px;"><i class="fa fa-check" style="font-size:13px;"></i></span>&nbsp;' +
                  _text('MN00036'), //'입력'
                handler: function() {
                  var set_re;
                  var users = new Array();
                  var ids = new Array();
                  var sel = Ext.getCmp(selected_list_id).getStore();
                  Ext.each(sel.data.items, function(r) {
                    users.push(r.data.user_nm);
                    ids.push(r.data.member_id);
                  });
                  var bvalue = that.getValue();
                  if (!Ext.isEmpty(bvalue)) {
                    bvalue += ',';
                  }
                  Ext.getCmp(fieldId).setValue(bvalue + users.join());
                  that.setValue(bvalue + users.join());

                  user_win.close();
                }
              },
              {
                //icon:'/led-icons/cancel.png',
                text:
                  '<span style="position:relative;top:1px;"><i class="fa fa-close" style="font-size:13px;"></i></span>&nbsp;' +
                  _text('MN00031'), //'닫기'
                handler: function(self) {
                  user_win.close();
                }
              }
            ],
            items: [
              new Ext.grid.GridPanel({
                id: user_list_id,
                cls: 'proxima_customize',
                stripeRows: true,
                flex: 1,
                height: 330,
                border: true,
                store: user_search_store,
                loadMask: true,
                listeners: {
                  viewready: function(self) {
                    self.getStore().load({
                      params: {
                        start: 0,
                        limit: 20
                      }
                    });
                  },
                  rowdblclick: function(self, row_index, e) {
                    var sel = Ext.getCmp(user_list_id)
                      .getSelectionModel()
                      .getSelected();
                  }
                },
                colModel: new Ext.grid.ColumnModel({
                  defaults: {
                    sortable: true
                  },
                  columns: [
                    new Ext.grid.RowNumberer(),
                    {
                      header: _text('MN00195'),
                      dataIndex: 'user_id',
                      align: 'center'
                    },
                    //{header: 'number', dataIndex:'member_id',hidden:'true'},
                    {
                      header: _text('MN00223'),
                      dataIndex: 'user_nm',
                      align: 'center'
                    },
                    {
                      header: _text('MN00181'),
                      dataIndex: 'dept_nm',
                      align: 'center'
                    }
                  ]
                }),
                viewConfig: {
                  forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({
                  //singleSelect: true
                }),
                tbar: [
                  {
                    xtype: 'combo',
                    id: 'search_f',
                    width: 120,
                    triggerAction: 'all',
                    editable: false,
                    mode: 'local',
                    store: [
                      ['s_user_id', _text('MN00195')],
                      ['user_nm', _text('MN00223')],
                      ['s_dept_nm', _text('MN00181')]
                    ],
                    value: 'user_nm',
                    listeners: {
                      select: function(self, r, i) {}
                    }
                  },
                  ' ',
                  {
                    allowBlank: false,
                    xtype: 'textfield',
                    listeners: {
                      specialKey: function(self, e) {
                        var w = self.ownerCt.ownerCt;
                        if (e.getKey() == e.ENTER && self.isValid()) {
                          e.stopEvent();
                          w.doSearch(w.getTopToolbar(), user_search_store);
                        }
                      }
                    }
                  },
                  ' ',
                  {
                    //icon:'/led-icons/magnifier_zoom_in.png',
                    xtype: 'button',
                    //>>text: '조회'
                    //text: '<span style="position:relative;top:1px;"><i class="fa fa-search" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00037'),
                    cls: 'proxima_button_customize',
                    width: 30,
                    text:
                      '<span style="position:relative;top:1px;" title="' +
                      _text('MN00037') +
                      '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
                    handler: function(b, e) {
                      var w = b.ownerCt.ownerCt;
                      w.doSearch(w.getTopToolbar(), user_search_store);
                    }
                  },
                  {
                    //icon: '/led-icons/arrow_refresh.png',
                    //>>text: '초기화',Clear Conditions
                    //text: '<span style="position:relative;top:1px;"><i class="fa fa-rotate-left" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02096'),
                    cls: 'proxima_button_customize',
                    width: 30,
                    text:
                      '<span style="position:relative;top:1px;" title="' +
                      _text('MN02096') +
                      '"><i class="fa fa-rotate-left" style="font-size:13px;color:white;"></i></span>',
                    handler: function(btn, e) {
                      btn.ownerCt.items.items[2].setValue('');
                      Ext.getCmp(user_list_id)
                        .getStore()
                        .load({
                          params: {
                            start: 0,
                            limit: 20
                          }
                        });
                    }
                  }
                ],
                bbar: new Ext.PagingToolbar({
                  store: user_search_store,
                  pageSize: 20
                }),
                doSearch: function(tbar, store) {
                  var combo_value = tbar.get(0).getValue(),
                    params = {};
                  params.start = 0;
                  params.limit = 20;

                  if (combo_value == 's_created_time') {
                    params.search_field = combo_value;
                    params.search_value = tbar
                      .get(2)
                      .getValue()
                      .format('Y-m-d');
                  } else {
                    params.search_field = combo_value;
                    params.search_value = tbar.get(2).getValue();
                  }
                  if (
                    Ext.isEmpty(params.search_field) ||
                    Ext.isEmpty(params.search_value)
                  ) {
                    //>>Ext.Msg.alert('정보', '검색어를 입력해주세요.');
                    Ext.Msg.alert(_text('MN00023'), _text('MSG00007'));
                  } else {
                    Ext.getCmp(user_list_id)
                      .getStore()
                      .load({
                        params: params
                      });
                  }
                }
              }),
              {
                //xtype : 'panel',
                layout: 'fit',
                height: 330,
                width: 50,
                border: false,
                items: [
                  {
                    layout: {
                      type: 'vbox',
                      pack: 'center',
                      align: 'center'
                    },
                    defaults: { margins: '0 0 10 0' },
                    border: false,
                    style: {
                      background: 'white'
                    },

                    items: [
                      {
                        xtype: 'button',
                        //width : 25,
                        //text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-right" style="font-size:13px;"></i></span>',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text:
                          '<span style="position:relative;top:1px;"><i class="fa fa-chevron-right" style="font-size:13px;color:white;"></i></span>',
                        handler: function(self) {
                          var sel = self.ownerCt.ownerCt.ownerCt
                            .get(0)
                            .getSelectionModel()
                            .getSelections();
                          var arr_sel = new Array();
                          if (sel.length < 1) {
                            Ext.Msg.show({
                              title: _text('MN00023'), //알림
                              msg: _text('MSG01005'), //먼저 대상을 선택 해 주시기 바랍니다.
                              buttons: Ext.Msg.OK
                            });
                            return;
                          } else {
                            var selectedStore = self.ownerCt.ownerCt.ownerCt.get(
                              2
                            ).store;
                            var array_store = new Array();
                            Ext.each(selectedStore.data.items, function(r) {
                              array_store.push(r.data.member_id);
                            });

                            //array_search(needle, haystack)

                            var check_in;
                            Ext.each(sel, function(se) {
                              check_in = array_search(
                                se.get('member_id'),
                                array_store
                              );
                              if (check_in) {
                                Ext.Msg.show({
                                  title: _text('MN00023'),
                                  msg:
                                    _text('MSG02064') +
                                    '</br>' +
                                    se.get('user_nm') +
                                    ' [' +
                                    se.get('user_id') +
                                    ']',
                                  buttons: Ext.Msg.OK
                                }); //알림, 이미 선택하였습니다.
                              } else {
                                selectedStore.add(se);
                              }
                            });
                          }
                        }
                      },
                      {
                        xtype: 'button',
                        //width : 25,
                        //text : '<span style="position:relative;top:1px;"><i class="fa fa-chevron-left" style="font-size:13px;"></i></span>',
                        cls: 'proxima_button_customize',
                        width: 30,
                        text:
                          '<span style="position:relative;top:1px;"><i class="fa fa-chevron-left" style="font-size:13px;color:white;"></i></span>',
                        handler: function(self) {
                          var sel = self.ownerCt.ownerCt.ownerCt
                            .get(2)
                            .getSelectionModel()
                            .getSelections();
                          if (sel.length < 1) {
                            Ext.Msg.show({
                              title: _text('MN00023'), //알림
                              msg: _text('MSG01005'), //먼저 대상을 선택 해 주시기 바랍니다.
                              buttons: Ext.Msg.OK
                            });
                            return;
                          } else {
                            self.ownerCt.ownerCt.ownerCt
                              .get(2)
                              .store.remove(sel);
                          }
                        }
                      }
                    ]
                  }
                ]
              },
              new Ext.grid.GridPanel({
                id: selected_list_id,
                cls: 'proxima_customize',
                stripeRows: true,
                flex: 1,
                height: 330,
                //margins: '27 0 0 0',
                autoScroll: true,
                border: true,
                store: new Ext.data.ArrayStore({
                  autoLoad: false,
                  fields: [
                    { name: 'member_id', type: 'string' },
                    { name: 'user_id', type: 'string' },
                    { name: 'user_nm', type: 'string' },
                    { name: 'dept_nm', type: 'string' }
                  ],
                  data: []
                }),
                loadMask: true,
                listeners: {
                  viewready: function(self) {},
                  rowdblclick: function(self, row_index, e) {}
                },
                colModel: new Ext.grid.ColumnModel({
                  defaults: {
                    sortable: true
                  },
                  columns: [
                    new Ext.grid.RowNumberer(),
                    {
                      header: _text('MN00195'),
                      dataIndex: 'user_id',
                      align: 'center'
                    },
                    {
                      header: 'number',
                      dataIndex: 'member_id',
                      hidden: 'true'
                    },
                    {
                      header: _text('MN00223'),
                      dataIndex: 'user_nm',
                      align: 'center'
                    },
                    {
                      header: _text('MN00181'),
                      dataIndex: 'dept_nm',
                      align: 'center'
                    }
                  ]
                }),
                viewConfig: {
                  forceFit: true
                },
                sm: new Ext.grid.RowSelectionModel({
                  //singleSelect: true
                }),
                tbar: [
                  {
                    xtype: 'displayfield',
                    padding: 10,
                    value: '',
                    height: 22
                  }
                ]
              })
            ],
            listeners: {
              afterrender: function(self) {}
            }
          }
        ]
      }).show();
    }

    //Ext.apply(this, Ext.apply(this.listeners, listeners));

    this.items = [
      {
        flex: 1,
        id: fieldId,
        height: that.height,
        xtype: 'textarea',
        listeners: {
          change: function(self, newValue, oldValue) {
            that.setValue(newValue);
          }
        }
      },
      {
        xtype: 'button',
        cls: 'proxima_button_customize',
        width: 30,
        text:
          '<span style="position:relative;top:1px;" title="' +
          _text('MN00037') +
          '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
        handler: function(b, e) {
          showUser(that);
        }
      }
    ];

    Ext.ux.UserCustomField.superclass.initComponent.call(this);
  }
});

Ext.reg('customfield', Ext.ux.UserCustomField);

/**
 * 2018.08.02 hkkim 파일 업로드 콤포넌트 사용하도록 변경
 */
function proximaWebUploader(option, uploadUrl) {
  if (!Ext.isEmpty(Ext.getCmp('file-upload-window'))) {
    return;
  }

  var view = Ext.getCmp('nps_center').ownerCt;
  view.el.mask();
  var ud_content_tab_data;
  Ext.Ajax.request({
    url: '/interface/mam_ingest/get_meta_json.php',
    method: 'POST',
    params: {
      kind: 'ud_content',
      flag: 'webupload'
    },
    callback: function(opt, success, response) {
      view.el.unmask();
      if (success) {
        var result = Ext.decode(response.responseText);
        ud_content_tab_data = result.data;
        var content_tab = Ext.getCmp('tab_warp');

        var current_ud_content_id = content_tab.getActiveTab().ud_content_id;
        var content_type_allow_data;
        ud_content_tab_data.map(function(val) {
          if (val.ud_content_id == current_ud_content_id) {
            content_type_allow_data = val.allowed_extension.toUpperCase();
          }
        });
        if (Ext.isEmpty(content_type_allow_data)) {
          //get_meta_json에서 업로드 권한 있는것만 나옴.
          Ext.Msg.alert(_text('MN00023'), _text('MSG01002')); //권한이 지정되지 않았습니다.
          return;
        }

        var fileUploadWindow = new Ariel.Nps.FileUploadWindow({
          id: 'file-upload-window',
          option: option,
          uploadUrl: uploadUrl
        });

        fileUploadWindow.show();
      } else {
      }
    }
  });
}

function columnRowIndex(width) {
  return new Ext.grid.Column({
    // header: '순번',
    renderer: function(v, p, record, rowIndex) {
      return rowIndex + 1;
    },
    width: width,
    align: 'center',
    menuDisabled: true,
    sortable: false
  });
}

function isValue(v) {
  if (typeof v === 'undefined' || v === '' || v === null) {
    return false;
  } else {
    return true;
  }
}

function endDateOf(date) {
  var endDateOf = new Date(
    date.getFullYear(),
    date.getMonth(),
    date.getDate(),
    23,
    59,
    59
  );
  return endDateOf;
}

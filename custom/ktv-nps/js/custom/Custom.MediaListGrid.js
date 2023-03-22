Ext.ns("Custom");

Custom.MediaListGrid = Ext.extend(Ext.grid.GridPanel, {
  contentId: null,


  border: false,
  // cls: 'proxima_customize',
  stripeRows: true,
  layout: 'fit',
  loadMask: true,
  split: true,
  // title: _text('MN00173'),



  initComponent: function () {
    this._initialize();
    Custom.MediaListGrid.superclass.initComponent.call(this);
  },
  _initialize: function () {
    var _this = this;
    _this.store = new Ext.data.JsonStore({
      url: '/store/get_media.php',
      root: 'data',
      fields: [
        'task_status',
        'task_progress',
        'bs_content_id',
        'ud_content_id',
        'content_id',
        'media_id',
        'storage_id',
        'media_type',
        'path',
        'filesize',
        'memo',
        { name: 'created_date', type: 'date', dateFormat: 'YmdHis' },
        { name: 'del' },
        'extension',
        'media_type_name'
      ],
      listeners: {
        load: function (self, records, opts) {
          var downloadRecords = _this.downloadPossibleRecords(records);

          self.remove(records);
          self.add(downloadRecords);

          // 미디어 리스트가 없을때 윈도우창 이라면 닫기
          if (downloadRecords.length === 0) {
            if (isValue(_this.ownerCt)) {
              if (_this.ownerCt.getXType() == 'window') {
                var window = _this.ownerCt;
                window.destroy();
                Ext.Msg.alert('알림', '다운로드 가능한 목록이 없습니다.');
              };
            }
          };
        },
        exception: function (self, type, action, opts, response, args) {
          Ext.Msg.alert(_text('MN00022'), response.responseText);
        }
      }
    });
    _this.cm = new Ext.grid.ColumnModel({
      defaults: {
        sortable: false,
        menuDisabled: true
      },
      columns: [
        { header: _text('MN00300'), dataIndex: 'media_type_name', width: 100, },
        { header: '코덱', dataIndex: 'extension', width: 65, align: 'center' },
        { header: _text('MN00301'), dataIndex: 'filesize', width: 70, align: 'center' },
        {
          header: '해상도', dataIndex: 'media_type', width: 70, align: 'center',
          renderer: function (value, metaData, record) {
            switch (value) {
              // 중해상도
              case "proxy":
                return '1280x720';
                break;
              // 저해상도
              case "proxy360":
                return '640x360';
                break;
              // 고해상도
              case "proxy2m1080":
                return '1920x1080';
                break;
              case "proxy2m1080logo":
                return '1920x1080';
                break;
                case "proxy15m1080logo":
                    return '1920x1080';
                    break;
              // 전송용
              case "proxy15m1080":
                return '1920x1080';
                break;
              // 대표이미지
              case "thumb":
                return '640x360';
                break;
              default:
                return;
                break;
            };
          }
        },
        {
          header: '로고', dataIndex: 'media_type', align: 'center', hidden: true,
          renderer: function (value, metaData, record) {
            switch (value) {
              // 중해상도
              case "proxy":
                return '무';
                break;
              // 저해상도
              case "proxy360":
                return '유';
                break;
              // 고해상도
              case "proxy2m1080":
                return '무';
                break;
              case "proxy2m1080logo":
                return '유';
                break;
                case "proxy15m1080logo":
                    return '유';
                    break;
              // 전송용
              case "proxy15m1080":
                return '무';
                break;
              // 대표이미지
              case "thumb":
                return;
                break;
              default:
                return;
                break;
            };
          },
          width: 35
        },
        { header: _text('MN00107'), dataIndex: 'created_date', hidden: true, renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130, align: 'center' },
        { header: _text('MN00502'), dataIndex: 'del', width: 130, align: 'center', hidden: true, },
        { header: _text('MN02241'), dataIndex: 'memo', width: 200 },//MN02241 정보
        { header: _text('MN00242'), dataIndex: 'path', width: 300 }
      ]
    });
    // _this.tbar = [{
    //   cls: 'proxima_btn_customize proxima_btn_customize_new',
    //   width: 30,
    //   text: '<span style="position:relative;top:1px;" title="' + _text('MN00142') + '"><i class="fa fa-download" style="font-size:13px;"></i></span>',
    //   handler: function (btn, e) {
    //     _this.downloadHandler();
    //   }
    // }]
    _this.listeners = {
      afterrender: function (self) {
        self.getStore().load({
          params: {
            content_id: self.contentId
          }
        });
      }
    };
  },
  downloadPossibleRecords: function (records) {
    var possibleDownloadTypes = [
      'thumb',
      'proxy',
      'proxy360',
      'proxy2m1080',
      'proxy2m1080logo',
      'proxy15m1080logo',
      'proxy15m1080',
      'archive',
      'original'
    ];
    var downloadRecords = [];

    Ext.each(records, function (record) {
      if (isValue(record)) {
        var mediaType = record.get('media_type');
        if (possibleDownloadTypes.indexOf(mediaType) !== -1) {
          downloadRecords.push(record);
        };
      }
    });
    return downloadRecords;
  },
  downloadHandler: function () {
    var _this = this;
    var files = this.getSelectionModel().getSelected();
    if (!files) {
      Ext.Msg.alert(_text('MN00024'), _text('MSG00055'));

      return;
    }
    var media_type = files.get('media_type');
    var ud_content_id = files.get('ud_content_id');
    var bs_content_id = files.get('bs_content_id');


    var media_id = files.get('media_id');

    //if(media_type != 'Attach') {
    // if ((media_type == 'original' || media_type == 'archive') && bs_content_id == MOVIE) {
    //   Ext.Msg.alert(_text('MN00023'), _text('MSG01046'));//다운로드 할 수 없는 유형입니다
    //   return;
    // }
    var url = '/store/download.php?media_id=' + media_id + '&media_type=' + media_type + '&ud_content_id=' + ud_content_id;

    var checkList = ['use', 'embargo'];
    var waitMsg = Ext.Msg.wait('처리 중입니다.', '처리중...');
    var ajax = Ext.Ajax.request({
      url: '/api/v1/contents/' + _this.contentId + '/check',
      params: {
        check_list: Ext.encode(checkList),
        media_type: media_type
      },
      callback: function (opts, success, response) {
        var r = Ext.decode(response.responseText);
        waitMsg.hide();
        if (success) {
          Ext.getBody().createChild({
            tag: 'iframe',
            cls: 'x-hidden',
            onload: 'var t = Ext.get(this); t.remove.defer(1000, t); ',
            src: url
          });
        } else {
          Ext.Msg.alert('알림', r.msg);
        };
      }
    });
    return ajax;
  },
  isContentDownloadGrant: function (udContentId, userId) {
    var _this = this;
    var downLoadGrant = 16;
    w = Ext.Msg.wait('처리 중입니다.', '처리중...');
    var ajax = Ext.Ajax.request({
      url: '/api/v1/permission/content-grant',
      params: {
        user_id: userId,
        ud_content_id: udContentId,
        grant: downLoadGrant
      },
      callback: function (opts, success, response) {
        var r = Ext.decode(response.responseText);

        w.hide();
        if (success) {

          if (r.success) {
            _this.downloadHandler();
          } else {
            Ext.Msg.alert('알림', r.msg);
          }
        } else {
          if (response.status == 500) {
            return Ext.Msg.alert('알림', r.msg);
          };
          Ext.Msg.alert('error', response.statusText);
        };

      }
    });
    return ajax;
  }

});
Ext.reg('mediaList', Custom.MediaListGrid);
Ext.ns('Custom');
// Ext.ariel.ContentList.php
Custom.ContentList = Ext.extend(Ext.grid.GridPanel, {
  ddGroup: 'ContentDD',
  stripeRows: true,
  anchor: '97%',
  dragZone: null,
  mode: 'thumb',
  template: {
    tile: null
  },
  currentThumbModeInfo: {
    bodyWidthStyle: '',
    scrollTop: 0
  },
  toolTemplate: new Ext.XTemplate(
    '<tpl for=".">',
    '<div class="x-toolleft x-tool-{id}" >&#160;</div>',
    '</tpl>'
  ),
  listeners: {
    render: function (p) {
      new Ext.Resizable(p.getEl(), {
        handles: 's',
        minHeight: 150,
        pinned: true
      });

      self.dragZone = new Ext.grid.GridDragZone(p, {
        ddGroup: 'ContentDD',
        getDragData: function (grid, ddel, rowIndex, selections) {
          //alert(rowIndex);
        }
      });
    },
    resizeElement: function () {
      var box = this.proxy.getBox();
      p.updateBox(box);
      if (p.layout) {
        p.doLayout();
      }

      return box;
    },
    afterrender: function (comp, e) {
      /*
      var el = Ext.getCmp('tab_warp')
        .getActiveTab()
        .get(0);
      el.on('click', function(event) {
        if (
          event.target.className === 'fa fa-circle test_tag_function' ||
          event.target.className === 'fa fa-circle-o test_tag_function'
        ) {
          elem = event.target;
          var sm = Ext.getCmp('tab_warp')
            .getActiveTab()
            .get(0)
            .getSelectionModel();
          var content_id_data = elem.getAttribute('text_data');
          var list_content_data = Ext.getCmp('tab_warp')
            .getActiveTab()
            .get(0)
            .getStore();
          var list_content = list_content_data.data.items;

          if (sm.hasSelection()) {
            var selection = sm.getSelections();
            var content_id_array = [];
            Ext.each(selection, function(r, i, a) {
              content_id_array.push({
                content_id: r.get('content_id')
              });
            });

            var isExists = false;
            Ext.each(content_id_array, function(r, i, a) {
              if (r.content_id == content_id_data) {
                isExists = true;
              }
            });

            if (isExists == false) {
              for (i = 0; i < list_content.length; i++) {
                if (list_content[i].id == content_id_data) {
                  sm.selectRow(i);
                  break;
                }
              }
            }
          } else {
            for (i = 0; i < list_content.length; i++) {
              if (list_content[i].id == content_id_data) {
                sm.selectRow(i);
                break;
              }
            }
          }

          if (!Ext.isEmpty(Ariel.tag_menu_list)) {
            Ariel.tag_menu_list.showAt(event.getXY());
            return;
          }
        }
      });
      */
    },
    keypress: function (e) {
      e.preventDefault();
      var hasSelection = Ext.getCmp('tab_warp')
        .getActiveTab()
        .get(0)
        .getSelectionModel()
        .hasSelection();
      if (hasSelection && e.getKey() == e.SPACE) {
        var selection = Ext.getCmp('tab_warp')
          .getActiveTab()
          .get(0)
          .getSelectionModel()
          .getSelected();
        Ext.Ajax.request({
          url: '/store/cuesheet/player_window.php',
          params: {
            content_id: selection.get('content_id')
          },
          callback: function (option, success, response) {
            var r = Ext.decode(response.responseText);
            if (success) {
              r.show();
              var player3 = videojs(
                document.getElementById('player3'),
                {},
                function () { }
              );
            } else {
              Ext.Msg.alert(_text('MN01098'), _text('MSG02052'));
              return;
            }
          }
        });
      }
    },
    rowdblclick: function (self, rowIndex, e) {
      var record = self.getSelectionModel().getSelected();
      var contentId = record.get('content_id');

      var components = [
        '/custom/ktv-nps/javascript/ext.ux/Custom.ParentContentGrid.js'
      ];
      Ext.Loader.load(components, function (r) {
        new Custom.ContentDetailWindow({
          content_id: contentId,
          isPlayer: true,
          isMetaForm: true,
          playerMode: 'read',
          listeners: {
            afterrender: function (self) {
              self.buttons[0].hide();
            }
          }
        }).show();
      });
    },
    keypress: function (e) {
      e.preventDefault();
      var hasSelection = Ext.getCmp('tab_warp')
        .getActiveTab()
        .get(0)
        .getSelectionModel()
        .hasSelection();
      if (hasSelection && e.getKey() == e.SPACE) {
        var selection = Ext.getCmp('tab_warp')
          .getActiveTab()
          .get(0)
          .getSelectionModel()
          .getSelected();
        Ext.Ajax.request({
          url: '/store/cuesheet/player_window.php',
          params: {
            content_id: selection.get('content_id')
          },
          callback: function (option, success, response) {
            var r = Ext.decode(response.responseText);
            if (success) {
              r.show();
              var player3 = videojs(
                document.getElementById('player3'),
                {},
                function () { }
              );
            } else {
              Ext.Msg.alert(_text('MN01098'), _text('MSG02052'));
              return;
            }
          }
        });
      }
    }
  },
  constructor: function(config) {
    Ext.apply(this, {}, config || {});
    Custom.ContentList.superclass.constructor.call(this);
  },
  initComponent: function () {
    this._initialize();
    Custom.ContentList.superclass.initComponent.call(this);
  },
  _initialize: function () {
    this.bbar = this.buildPageToolBar();
    var viewConfig = {
      // refresh 후 스크롤바 현재위치에 고정
      scrollToTop: Ext.emptyFn,
      rowTemplate: this.template.tile,
      //>>emptyText: '등록된 자료가 없습니다',
      emptyText: _text('MSG00142'),
      enableRowBody: true,
      forceFit: false,
      showPreview: false,
      listeners: {
        //refresh전에 헤더를 없애고
        //refresh후에 바디 사이즈를 조절한다.
        beforerefresh: function (self) {
          //console.log(this.grid.mode);
          var gridHead = this.grid
            .getGridEl()
            .child('div[class=x-grid3-header]');
          var gridBody = this.grid.getGridEl().child('div[class=x-grid3-body]');
          // 2018.02.13 khk 썸네일 모드일 때 새로고침이 진행될 경우 스크롤 위치를 저장시켜놨다가 100%로
          // 스타일 변경 후 이동 시켜준다. 근데 검색 등 새로고침이 아닐때는 맨 위로 가도록 한다.
          this.grid.currentThumbModeInfo.bodyWidthStyle = gridBody.getStyle(
            'width'
          );
          this.grid.currentThumbModeInfo.scrollTop = this.grid.getView().scroller.dom.scrollTop;
          switch (this.grid.mode) {
            case 'thumb':
            case 'mix':
              gridHead.setStyle('display', 'none');
              break;
            case 'list':
              gridHead.setStyle('display', 'block');
              break;
          }
        },
        refresh: function (self) {
          //console.log(this.grid.mode);
          var gridBody = this.grid.getGridEl().child('div[class=x-grid3-body]');
          switch (this.grid.mode) {
            case 'thumb':
            case 'mix':
              gridBody.setStyle('width', '100%');
              break;
            case 'list':
              break;
          }
        }
      }
    };

    Ext.apply(this.viewConfig, {}, viewConfig || {});

    this.store.on('exception', function (
      self,
      type,
      action,
      opts,
      response,
      args
    ) {
      if (type == 'response') {
        var result = Ext.decode(response.responseText);
        if (!result.success) {
          Ext.Msg.alert(_text('MN00024'), result.msg);
        }
      }
    });

    var contentListGrid = this;
    this.store.on('load', function (self, records, options) {
      //console.log('@@@loaded!!!', contentListGrid);
      //console.log('@@@selections!!!', contentListGrid.getSelectionModel().selections.length);
      //console.log('@@@scrollTop!!!', contentListGrid.getView().scroller.dom.scrollTop);
      //console.log('@@@scrollLeft!!!', contentListGrid.getView().scroller.dom.scrollLeft);
      // 2018.02.13 khk refresh 후 선택된것이 있으면 가로 스크롤만 맨 앞으로 이동시키고
      // 가로 스크롤은 리스트 모드일 경우만...
      if (
        contentListGrid.mode == 'list' &&
        contentListGrid.getView().scroller.dom.scrollLeft != 0
      ) {
        contentListGrid.getView().scroller.dom.scrollLeft = 0;
      }
      // 선택된게 없으면 세로 스크롤도 맨 위로 이동시킴
      if (
        contentListGrid.getSelectionModel().selections.length == 0 &&
        contentListGrid.getView().scroller.dom.scrollTop != 0
      ) {
        contentListGrid.getView().scroller.dom.scrollTop = 0;
      } else {
        // 스크롤 위치 복원 시켜줌
        contentListGrid.restoreScroll();
      }
    });
  },
  restoreScroll: function () {
    //console.log('@@@selections(restoreScroll)!!!', this.getSelectionModel().selections.length);
    //console.log('@@@scrollTop(restoreScroll)!!!', this.getView().scroller.dom.scrollTop);
    //console.log('@@@scrollLeft(restoreScroll)!!!', this.getView().scroller.dom.scrollLeft);
    //console.log('@@@currentThumbModeInfo.scrollTop(restoreScroll)!!!', this.currentThumbModeInfo.scrollTop);
    if (
      (this.mode == 'thumb' || this.mode == 'mix') &&
      this.currentThumbModeInfo.bodyWidthStyle == '100%' &&
      this.getView().scroller.dom.scrollTop !=
      this.currentThumbModeInfo.scrollTop &&
      this.getSelectionModel().selections.length > 0
    ) {
      //console.log('restoreScroll!!!');
      this.getView().scroller.dom.scrollTop = this.currentThumbModeInfo.scrollTop;
    }
  },
  buildPageToolBar: function () {
    return new Ext.PagingToolbar({
      pageSize: 50,
      store: this.store,
      id: 'myImg02',
      buttonAlign: 'center',
      plugins: [
        new Ext.ux.grid.PageSizer({
          border: true,
          cls: 'content_page_toolbar_number_per_page',
          listeners: {
            beforerender: function (self) {
              self.iconCls = 'myImg02';
              self.forceIcon = 'myImg02';
            }
          }
        })
      ]
    });
  }
});
Ext.reg('contentgrid2', Custom.ContentList);

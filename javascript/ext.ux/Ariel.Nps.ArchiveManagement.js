
Ariel.Nps.ArchiveManagement = Ext.extend(Ext.Container, {
  layout: 'border',
  autoScroll: false,
  border: false,
  isLoading: false,

  setLoading: function (isLoading) {
    this.isLoading = isLoading;
    return true;
  },
  getLoading: function () {

    return this.isLoading;
  },


  initComponent: function () {
    // Ext.apply(this, config || {});
    var _this = this;
    this.items = [{
      xtype: 'treepanel',
      region: 'west',
      width: 250,
      boxMinWidth: 250,
      split: true,
      collapsible: false,
      autoScroll: false,
      // rootVisible :false,
      lines: true,
      cls: 'tree_menu',
      bodyStyle: 'border-right: 1px solid #d0d0d0',
      isLoading: false,
      listeners: {
        click: function (node, e) {
          /**
         * false -> setLoading ->doLayout -> getLoading->if(true) ......-> setLoading(false)->false
        */
          if (_this.getLoading()) {

            return;
          }
          _this.setLoading(true);

          var url = node.attributes.url;

          if (!url) {
            _this.setLoading(false);
            return;
          }
          var components = [
            '/custom/ktv-nps/js/archiveManagement/Ariel.archiveManagement.UrlSet.js',
            '/custom/ktv-nps/js/glossary/Ariel.glossary.UrlSet.js',
            '/custom/ktv-nps/js/archiveManagement/inputFormWindow.js',
            '/custom/ktv-nps/js/custom/Custom.HtmlTable.js',
            '/custom/ktv-nps/js/custom/Custom.ToggleButton.js',
            '/pages/menu/archive_management/offlineTapeList.js',
            '/pages/menu/archive_management/offlineTapeLogList.js'
          ];
          Ext.Loader.load(components, function (r) {
            Ext.Ajax.request({
              url: url,
              timeout: 0,
              callback: function (opts, success, response) {
                try {

                  _this.get(1).removeAll(true);
                  _this.get(1).add(Ext.decode(response.responseText));
                  _this.get(1).doLayout();

                  _this.setLoading(false);

                } catch (e) {
                  Ext.Msg.alert(e['name'], opts.url + '<br />' + e['message']);
                }
              }
            });


          });

        }


      },
      rootVisible: false,
      root: {
        expanded: true,
        children: [{
          text: '오프라인 자료 대출',
          expanded: false,
          children: [{
            text: '대출예약현황'
          }, {
            text: '대출신청'
          }, {
            text: '반납처리/미반납내역'
          }, {
            text: '대출입검색'
          }]
        }, {
          text: '오프라인 자료 관리',
          expanded: false,
          children: [{
            text: '방송원본 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }, {
            text: '촬영테잎 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }, {
            text: '뉴스자료 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }, {
            text: '대한뉴스 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }, {
            text: '대한뉴스KC/OT 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }, {
            text: '문화영화 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }, {
            text: '예비촬영 관리',
            url: 'custom/ktv-nps/js/archiveManagement/originalListGrid.js',
            leaf: true
          }]
        },
        {
          text: '오프라인 자료 검색',
          expanded: false,
          children: [{
            text: '방송원본 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }, {
            text: '촬영테잎 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }, {
            text: '뉴스자료 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }, {
            text: '대한뉴스 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }, {
            text: '대한뉴스KC/OT 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }, {
            text: '문화영화 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }, {
            text: '예비촬영 검색',
            url: 'custom/ktv-nps/js/archiveManagement/originalSearch.js',
            leaf: true
          }]
        }, {
          text: '영상 복사 신청',
          leaf: true
        }, {
          text: '영상자료 판매관리',
          expanded: false,
          children: [{
            text: '주문 관리',
            url: 'custom/ktv-nps/js/archiveManagement/orderListGrid.js',
            leaf: true

          }, {
            text: '가격 관리',
            url: 'custom/ktv-nps/js/archiveManagement/priceListGrid.js',
            leaf: true
          }]
        }, {
          hidden: true,
          text: '아카이브 테이프 관리',
          title: '아카이브 테이프 관리',
          url: '/pages/menu/archive_management/offlineTapeList.js',
          leaf: true
        },
        {
					text: _text('MN02370'),
					title:'인제스트 스케쥴 관리',
					url: '/pages/menu/config/ingest_schedule/ingest_schedule.php',
					leaf: true
				},
        ]
      }
    }, {
      region: 'center',
      headerAsText: false,
      border: false,
      layout: 'fit'
    }]



    Ariel.Nps.ArchiveManagement.superclass.initComponent.call(this);
  }
});



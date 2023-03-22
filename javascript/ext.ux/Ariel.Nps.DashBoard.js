Ext.ns('Ariel.Nps');

Ariel.Nps.DashBoard = Ext.extend(Ext.Container, {
  layout: 'border',
  autoScroll: false,
  border: false,
  initialized: false,
  isLoading: false,
  // private properties
  // 서브메뉴 트리패널
  _subMenu: null,
  // 콘텐츠영역 컨테이너
  _content: null,
  listeners: {
    show: function () {
      var _this = this;
      if (this.initialized) {
        return;
      }
      var _this = this;
      _this.isLoading = true;
      Ext.Loader.load(
        ['/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Url.js'],
        function () {
          var componentsV = Ariel.versioning.numberingScripts(_this.components);

          Ext.Loader.load(componentsV, function (r) {
            _this._subMenu.root.firstChild.select();
            _this._content.add(new Ariel.DashBoard.Home());
            _this._content.doLayout();
            _this.isLoading = false;
            _this.initialized = true;
          });
        }
      );
    }
  },
  initComponent: function () {
    // Ext.apply(this, config || {});
    var _this = this;

    this.components = [
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Url.js',
      '/custom/ktv-nps/js/archiveManagement/Ariel.archiveManagement.UrlSet.js',
      '/custom/ktv-nps/js/glossary/Ariel.glossary.UrlSet.js',

      '/custom/ktv-nps/js/custom/Custom.RadioDay.js',
      '/custom/ktv-nps/js/custom/Custom.ToolBarDate.js',

      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Personalization.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.DownLoad.js',
      // '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.DownloadTest.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Archive.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Home.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Notice.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Monitor.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Request.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Review.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.Storage.js',
      '/custom/ktv-nps/js/dashBoard/Ariel.DashBoard.AuthorityMandate.js',
      '/pages/menu/config/user_request/Custom.UserRequest.js',

      '/custom/ktv-nps/js/folder/Ariel.System.ProgramRequestManagement.js',
      '/javascript/ext.ux/Ariel.Nps.BISProgram.js',
      // '/custom/ktv-nps/store/downloads.js',
      '/custom/ktv-nps/js/custom/Custom.RequestDetailWindow.js'
    ];

    this._subMenu = this._makeSubMenu();
    this._content = new Ext.Container({
      region: 'center',
      headerAsText: false,
      border: false,
      layout: 'fit'
    });

    this.items = [this._subMenu, this._content];

    Ariel.Nps.DashBoard.superclass.initComponent.call(this);
  },
  _makeSubMenu: function () {
    var _this = this;
    var subMenu = new Ext.tree.TreePanel({
      region: 'west',
      width: 250,
      boxMinWidth: 250,
      split: true,
      collapsible: false,
      autoScroll: false,
      rootVisible: false,
      lines: true,
      cls: 'tree_menu',
      bodyStyle: 'border-right: 1px solid #d0d0d0',
      isLoading: false,
      listeners: {
        click: function (node, e) {
          /**
           * false -> setLoading ->doLayout -> getLoading->if(true) ......-> setLoading(false)->false
           */
          if (_this.isLoading) {
            return;
          }
          _this.isLoading = true;

          var view = null;
          try {
            var action = node.attributes.action;

            switch (action) {
              case 'home':
                view = new Ariel.DashBoard.Home();
                break;
              case 'personalization':
                view = new Ariel.DashBoard.Personalization();
                break;
              case 'notice':
                view = new Ariel.DashBoard.Notice();
                break;
              case 'monitor':
                view = new Ariel.DashBoard.Monitor();
                break;
              case 'requestVideo':
                view = new Ariel.DashBoard.Request({
                  hideCnt: true,
                  comboValue: 'video',
                  permission_code: 'request.editor',
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '영상편집 의뢰' +
                    '</span></span>'
                });
                break;
              case 'requestGraphic':
                view = new Ariel.DashBoard.Request({
                  hideCnt: true,
                  comboValue: 'graphic',
                  permission_code: 'request.cg',
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '그래픽 의뢰' +
                    '</span></span>'
                });
                break;
              case 'reviewIngest':
                view = new Ariel.DashBoard.Review({
                  hideCnt: true,
                  comboValue: 'ingest',
                  permission_code: 'review.ingest',
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '방송 심의' +
                    '</span></span>'
                });
                break;
              case 'reviewContent':
                view = new Ariel.DashBoard.Review({
                  hideCnt: true,
                  comboValue: 'content',
                  permission_code: 'review.content',
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '콘텐츠 관리' +
                    '</span></span>'
                });
                break;
              case 'archive':
                // 임시
                view = new Ariel.DashBoard.Archive({
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '아카이브 요청' +
                    '</span></span>'
                });
                break;
              case 'download':
                // 임시
                view = new Ariel.DashBoard.DownLoad();
                break;
              case 'storage':
                view = new Ariel.DashBoard.Storage({
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '스크래치 폴더' +
                    '</span></span>',
                  buttonShow: true,
                  _home: false
                });
                break;
              case 'authorityMandate':
                view = new Ariel.DashBoard.AuthorityMandate({
                  permission_code: 'authority_mandate.restore'
                });
                break;
              case 'userRequestManagement':
                view = new Custom.UserRequest({
                  _home: true,
                  _isAdmin: false,
                  permission_code: 'member_request.pd'
                });
                break;
              case 'folderMngRequest':
                view = new Ariel.System.ProgramRequestManagement({
                  title:
                    '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' +
                    '제작폴더 신청 관리' +
                    '</span></span>',
                  cls: 'grid_title_customize proxima_customize',
                  tbarButtonHide: true
                });
                break;
              // case 'test':
              //   view = new Ariel.DashBoard.DownLoadTest();
              //   break;
              default:
                return;
            }

            _this.get(1).removeAll(true);
            _this.get(1).add(view);
            _this.get(1).doLayout();
          } finally {
            _this.isLoading = false;
          }
        }
      },
      root: {
        text: 'DashBoard',
        expanded: true,
        children: [
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;대시보드',
            expend: true,
            leaf: true,
            action: 'home'
            // icon: '/led-icons/folder.gif'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Home.js',
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;개인설정',
            leaf: true,
            action: 'personalization'
            // icon: '/led-icons/folder.gif'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Personalization.js',
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;공지사항',
            leaf: true,
            action: 'notice'
            // icon: '/led-icons/folder.gif'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Notice.js'
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;작업내역',
            leaf: true,
            action: 'monitor'
            // icon: '/led-icons/folder.gif'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Monitor.js'
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;의뢰',
            expanded: true,
            children: [
              {
                text:
                  '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;영상편집 의뢰',
                leaf: true,
                type: 'video',
                action: 'requestVideo'
                // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Request.js',
              },
              {
                text:
                  '<span style="position:relative;top:1px;"><i class="fa fa-angle-right" style="font-size:18px;"></i></span>&nbsp;그래픽 의뢰',
                leaf: true,
                type: 'graphic',
                action: 'requestGraphic'
                // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Request.js',
              }
            ]
          },
          {
            hidden: true,
            text: '의뢰',
            leaf: true
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Request.js'
          },
          {
            // 방송 심의
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;방송 심의',
            leaf: true,
            type: 'ingest',
            action: 'reviewIngest'
          },
          {
            // 콘텐츠 관리
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;콘텐츠 관리',
            leaf: true,
            type: 'content',
            action: 'reviewContent'
          },
          {
            // text: '사용자 신청 관리',
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;사용자 신청 관리',
            leaf: true,
            action: 'userRequestManagement'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Archive.js'
          },
          {
            // text: '아카이브/리스토어 요청관리',
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;아카이브 요청',
            leaf: true,
            action: 'archive'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Archive.js'
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;다운로드',
            leaf: true,
            action: 'download'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.DownLoad.js'
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;스크래치 폴더',
            leaf: true,
            action: 'storage'
            // url: '/custom/ktv-nps/js/DashBoard/Ariel.DashBoard.Storage.js'
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;리스토어 권한 승계',
            leaf: true,
            action: 'authorityMandate'
          },
          {
            text:
              '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp;제작폴더 신청 관리',
            leaf: true,
            action: 'folderMngRequest'
            // },
            // {
            //   text:
            //     '<span style="position:relative;top:1px;"><i class="fa fa-angle-double-right" style="font-size:18px;"></i></span>&nbsp; 다운로드 테스트',
            //   leaf: true,
            //   action: 'test'
          }
        ]
      }
    });
    return subMenu;
  },
  _componentsV: function () {
    var componentsV = [];
    var v = '3.0.1';
    Ext.each(this.components, function (path) {
      var vPath = path + '?v=' + v;
      componentsV.push(vPath);
    });
    return componentsV;
  }
});

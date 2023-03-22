(function () {
  Ext.ns('Custom');
  Store = new Ext.extend(Object, {
    // 채널 정보 조회
    getChannelStore: function (params) {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/channels.php', params)),
        root: 'data',
        fields: ['id', 'code', 'name', 'broadcast']
      });
    },
    // PGM 그룹 조회
    getPgmGroupStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/pgm-groups.php')),
        root: 'data',
        fields: ['pgmGroupCd', 'pgmGroupNm']
      });
    },
    // 프로그램 조회
    getPgmStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/pgms.php')),
        root: 'data',
        fields: [
          'pgmGrpCd', // 프로그램 그룹 코드
          'pgmGrpNm', // 프로그램 그룹명
          'pgmCd', // 프로그램 코드
          'pgmNm', // 프로그램 명
          'bdStDtm', // 방송 시작 일시
          'bdEdDtm', // 방송 종료 일시
          'showHostInfo', // 쇼 호스트 정보 
          'videoInfo', // 비디오 정보
          'pdInfo' // PD 정보
        ]
      });
    },
    // 상품채널 조회
    getItemChannelStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/item-channels.php')),
        root: 'data',
        fields: [
          'chnCd', // 상품 채널
          'chnNm' // 상품 채널명
        ]
      });
    },
    // 상품 조회
    getItemStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/item.php')),
        root: 'data',
        totalProperty: 'totalCount',
        fields: [
          'itemCd', // 상품 코드
          'itemNm', // 상품명
          'chnCd', // 상품 채널
          'slCls', // 상품 상태
          'slClsNm', // 상품 상태
          'imageUrl', // 이미지 URL
          'dpCateId', // 전시 카테고리 아이디
          'dpCateNm' // 전시 카테고리 명
        ],
        timeoutSec: 60,
        listeners: {
          beforeload: function (self, options) {
            Ext.Ajax.timeout = self.timeoutSec * 1000;
          }
        }
      });
    },
    // 프로그램 별 상품 조회
    getPgmItemsStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/pgm-items.php')),
        root: 'data',
        fields: [
          'itemCd', // 상품 코드
          'itemNm', // 상품명
          'chnCd', // 상품 채널
          'slCls', // 상품 상태 코드
          'slClsNm', // 상품 상태
          'imageUrl', // 이미지 URL
          'dpCateId', // 전시 카테고리 아이디
          'dpCateNm' // 전시 카테고리 명
        ]
      });
    },
    // 동영상 상품 조회
    getVideoItemsStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/video-items.php')),
        root: 'data',
        fields: [
          'itemCd', // 상품 코드
          'itemNm', // 상품명
          'chnCd', // 상품 채널
          'slCls', // 상품 상태 코드
          'slClsNm', // 상품 상태 명
          'slClsNm', // 상품 상태
          'dispOrder', // 전시 순서
          'imageUrl', // 이미지 URL
          'dispYn', // 전시 여부
          'repItemYn', // 대표상품 여부
          'modNm', // 수정자
          'modDtm', // 수정일시
          'dpCateId', // 전시 카테고리 아이디
          'dpCateNm', // 전시 카테고리 명
          'btnId' // 버튼 아이디(내부용)
        ]
      });
    },
    // 사용자 조회
    getUserStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/users.php')),
        root: 'data',
        fields: [
          'user_id', // 사용자 아이디
          'user_nm', // 사용자 명
          'dept_nm' // 부서명
        ]
      });
    },
    // 태그 조회
    getVideoKeywordStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', requestCustomPath('/store/video-keywords.php')),
        root: 'data',
        fields: [
          'keyword'
        ]
      });
    },
    // 카탈로그 이미지 조회
    getCatalogStore: function () {
      return new Ext.data.JsonStore({
        proxy: makeHttpProxy('GET', '/store/catalog/catalogs.php'),
        root: 'data',
        fields: [
          'url',
          'start_frame',
          'start_tc',
          'is_poster',
          'sort'
        ]
      });
    }
  });

  Custom.Store = new Store();
})();
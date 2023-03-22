// 2019.11.13 hkkim 사용안하는것 같음
// (function() {
//   new Ext.Panel({
//     xtype: 'panel',
//     id: 'news_list_article',
//     layout: { type: 'vbox', align: 'stretch' },
//     tbar: [
//       {
//         xtype: 'displayfield',
//         width: 60,
//         value:
//           '<div align="right" style="margin-top:0px;">' +
//           _text('MN00138') +
//           '&nbsp;</div>' //상태
//       },
//       {
//         xtype: 'combo',
//         id: 'apprv_div_cd',
//         width: 95,
//         displayField: 'name',
//         hiddenName: 'apprv_div_cd',
//         hiddenValue: 'value',
//         valueField: 'value',
//         typeAhead: true,
//         triggerAction: 'all',
//         lazyRender: true,
//         mode: 'local',
//         value: 'all',
//         store: new Ext.data.ArrayStore({
//           fields: ['value', 'name'],
//           //data:[['all', '전체'], ['001', '승인'],['002', '미승인'],['003', '가승인']]
//           data: [
//             ['all', _text('MN00008')],
//             ['001', _text('MN02106')],
//             ['002', _text('MN02108')],
//             ['003', _text('MN02109')]
//           ]
//         })
//       },
//       {
//         xtype: 'displayfield',
//         width: 60,
//         value:
//           '<div align="right" style="margin-top:0px;">' +
//           _text('MN00310') +
//           '&nbsp;</div>' //형식
//       },
//       {
//         xtype: 'combo',
//         id: 'artcl_frm_cd',
//         width: 95,
//         triggerAction: 'all',
//         editable: false,
//         mode: 'local',
//         store: new Ext.data.ArrayStore({
//           fields: ['value', 'name'],
//           data: [
//             ['all', _text('MN00008')],
//             ['10598', '데이터'],
//             ['10602', '화면'],
//             ['10604', '리포트'],
//             ['12382', '앵커'],
//             ['10636', '해설'],
//             ['10605', 'HD리포트'],
//             ['10606', '전화'],
//             ['10603', 'HD화면'],
//             ['10607', '동영상폰'],
//             ['10608', '화상전화'],
//             ['10609', '전화녹음'],
//             ['10610', '출연'],
//             ['10611', 'ANC리포트'],
//             ['12654', '인터넷'],
//             ['12653', 'SNS'],
//             ['10625', 'SNG'],
//             ['10626', '화상중계'],
//             ['10633', '특집'],
//             ['10634', 'YHP'],
//             ['10635', '긴급'],
//             ['10624', '중계'],
//             ['10623', '광고'],
//             ['10622', '상식'],
//             ['10621', '인물'],
//             ['10620', '퀴즈'],
//             ['10619', '이슈추적'],
//             ['10618', '그래픽'],
//             ['10617', '스크롤'],
//             ['10616', '녹취구성'],
//             ['10615', '히스토리'],
//             ['10614', '영상단신'],
//             ['10613', '영상구성'],
//             ['10612', '영상리포트'],
//             ['12383', '기타'],
//             ['12655', 'TVU']
//           ]
//         }),
//         mode: 'local',
//         hiddenName: 'artcl_frm_cd',
//         hiddenValue: 'value',
//         valueField: 'value',
//         displayField: 'name',
//         typeAhead: true,
//         triggerAction: 'all',
//         forceSelection: true,
//         editable: false,
//         value: 'all',
//         listeners: {
//           select: function(cmb, record, index) {}
//         }
//       },
//       {
//         xtype: 'displayfield',
//         width: 80,
//         value:
//           '<div align="right" style="margin-top:0px;">' +
//           _text('MN02110') +
//           '&nbsp;</div>' //작성일
//       },
//       {
//         xtype: 'datefield',
//         id: 'start_date_infoReport',
//         name: 'start_date',
//         width: 95,
//         format: 'Y-m-d',
//         listeners: {
//           render: function(self) {
//             var d = new Date();
//             self.setMaxValue(d.format('Y-m-d'));
//             self.setValue(d.add(Date.MONTH, -3).format('Y-m-d'));
//           }
//         }
//       },
//       {
//         xtype: 'displayfield',
//         width: 3,
//         value: ' '
//       },
//       {
//         xtype: 'displayfield',
//         width: 10,
//         value: '~'
//       },
//       {
//         xtype: 'datefield',
//         id: 'end_date_infoReport',
//         name: 'end_date',
//         width: 95,
//         format: 'Y-m-d',
//         listeners: {
//           render: function(self) {
//             var d = new Date();
//             self.setMaxValue(d.format('Y-m-d'));
//             self.setValue(d.format('Y-m-d'));
//           }
//         }
//       },
//       {
//         xtype: 'displayfield',
//         width: 60,
//         value:
//           '<div align="right" style="margin-top:0px;">' +
//           _text('MN00249') +
//           '&nbsp;</div>' //제목
//       },
//       {
//         xtype: 'textfield',
//         id: 'artcl_titl',
//         name: 'artcl_titl',
//         width: 200
//       },
//       {
//         xtype: 'button',
//         cls: 'proxima_button_customize',
//         width: 30,
//         height: 32,
//         //icon: '/led-icons/find.png',
//         text:
//           '<span style="position:relative;top:1px;" title="' +
//           _text('MN00059') +
//           '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>', //조회
//         handler: function(self, e) {
//           var search_text = new Object();
//           search_text.apprv_div_cd = Ext.getCmp('apprv_div_cd').getValue();
//           search_text.artcl_frm_cd = Ext.getCmp('artcl_frm_cd').getValue();
//           search_text.start_date = Ext.getCmp('start_date_infoReport')
//             .getValue()
//             .format('Y-m-d')
//             .trim();
//           search_text.end_date = Ext.getCmp('end_date_infoReport')
//             .getValue()
//             .format('Y-m-d')
//             .trim();
//           search_text.artcl_titl = Ext.getCmp('artcl_titl').getValue();

//           Ext.getCmp('grid_article').store.load({
//             params: {
//               action: 'list_article',
//               search: Ext.encode(search_text)
//             }
//           });

//           Ext.getCmp('grid_detail').store.removeAll();
//         }
//       }
//     ],
//     items: [
//       {
//         xtype: 'tab_article',
//         id: 'grid_article',
//         flex: 3,
//         region: 'center',
//         border: false,
//         gridtype: 'listArticle'
//       },
//       {
//         xtype: 'tab_article',
//         region: 'south',
//         border: false,
//         id: 'grid_detail',
//         flex: 2,
//         //height: 120,
//         gridtype: 'listImage'
//       }
//     ]
//   });
// })();

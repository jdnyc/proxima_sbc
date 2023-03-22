(function () {
    var gridId = Ext.id();
    var startDateFieldId = Ext.id();
    var endDateFieldId = Ext.id();
    var searchTypeId = Ext.id();
    var searchKeywordId = Ext.id();
    var pageSize = 50;

    // 조회
    function onSearch() {
      var grid = Ext.getCmp(gridId);
      var startDateFieldValue = Ext.getCmp(startDateFieldId).getValue().format('Ymd') + '000000';
      var endDateFieldValue = Ext.getCmp(endDateFieldId).getValue().format('Ymd') + '240000';
      var searchType = Ext.getCmp(searchTypeId).getValue();
      var searchKeyword = Ext.getCmp(searchKeywordId).getValue();
    
      grid.getStore().load({
        params: {
          start_date: startDateFieldValue,
          end_date: endDateFieldValue,
          search_type: searchType,
          search_keyword: searchKeyword,
          limit: pageSize,
        }
      });
    };

    // 사용금지 해제
    function onClickedContentUnlock() {
      var sel = Ext.getCmp(gridId).getSelectionModel().getSelections();
      if (Ext.isEmpty(sel)) {
        Ext.Msg.alert('알림', '목록을 선택하여 주세요');
        return;
      } else if(sel.length > 1) {
        Ext.Msg.alert('알림', '한개만 선택하여 주세요');
        return;
      } else if(sel.length === 1) {
        var selectedContentId = sel[0].get('content_id');
        Ext.Msg.show({
          title: '알림',
          msg : '선택한 콘텐츠의 사용금지를 해제하시겠습니까?',
          buttons : Ext.Msg.YESNO,
          fn : function(button){
            if(button == 'yes') {
              Ext.Ajax.request({
              url : '/api/v1/contents/' + selectedContentId + '/prohibited-use',
              method:'put',
              params : {
                content_id : selectedContentId,
                use_prhibt_at : 'N',
                use_prhibt_set_resn : '사용금지 해제'
              },
              callback : function(opt, success, res) {
                if(success) {
                  var msg = Ext.decode(res.responseText);
                  if (msg.success) {
                    Ext.Msg.alert(' 완 료',msg.msg);
                    onSearch();
                  } else {
                    Ext.Msg.alert(' 오 류',msg.msg);
                  }
                } else {
                  Ext.Msg.alert('서버 오류', res.statusText);
                }
              }
            })
            } else {
            }
          }

        });
      }
    };

    // 날짜
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

    // 상단Bar
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
            xtype: 'combo',
            id: searchTypeId,
            typeAhead: true,
            triggerAction: 'all',
            mode: 'local',
            width: 100,
            editable: false,
            value: 'all',
            store: new Ext.data.SimpleStore({
                fields: ['value', 'key'],
                data: [
                    ['all', '전체'],
                    ['title', '제목'],
                    ['user_nm', '사용자'],
                    ['media_id', '미디어ID'],
                    ['use_prhibt_set_resn', '금지 사유'],
                ]
            }),
            valueField: 'value',
            displayField: 'key',
        },{
          id: searchKeywordId,
          xtype: 'textfield',
          width: 200,
          listeners: {
            specialkey: function (field, e) {
              if (e.getKey() == e.ENTER) {
                onSearch();
              }
            }
          }
        },{
          // 검색
            text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
            cls: 'proxima_button_customize',
            width: 30,
            handler: function (self) {
                onSearch();
            }
        },{
          // 초기화
          text: '<span style="position:relative;" title="' + _text('MN02096') + '"><i class="fa fa-times" style="font-size:13px;color:white;"></i></span>',
          cls: 'proxima_button_customize',
          width: 30,
          handler : function(btn, e){
            var sm = Ext.getCmp(gridId).getSelectionModel();
            sm.clearSelections();

            Ext.getCmp(searchTypeId).setValue('all');
            Ext.getCmp(searchKeywordId).setValue('');
            onSearch();
          }
        },'-',{
          // 사용금지 해제
          text: '<span style="position:relative;" title="' + _text('MN02096') + '"><i class="fa fa-unlock" style="font-size:13px;color:white;"></i></span>',
          cls: 'proxima_button_customize',
          width: 30,
          handler : function() {
            onClickedContentUnlock();
          }
        }
    ]
    });
    var selModel = new Ext.grid.CheckboxSelectionModel({
        singleSelect : true,
        checkOnly : true
    });

    var store =  new Ext.data.JsonStore({
        root: 'data',
        restful: true,
        remoteSort: true,
        proxy: new Ext.data.HttpProxy({
          method: 'GET',
          url: '/api/v1/contents/archive-management/use-prohibit',
          type: 'rest'
        }),
        fields: [
          'content_id',
          'media_id',
          'title',
          'ud_content_title',
          'bs_content_title',
          'user_nm',
          'use_prhibt_set_resn',
          {name: 'use_prhibt_set_dt', type: 'date', dateFormat: 'YmdHis'},
        ],
        listeners: {
          
        }
    });

    var bbar = new Ext.PagingToolbar({
      pageSize: pageSize,
      store: store,
    });

    var grid = new Ext.grid.GridPanel({
        id: gridId,
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '사용금지 관리' + '</span></span>',
        cls: 'grid_title_customize proxima_customize',
        stripeRows: true,
        border: false,
        loadMask: true,
        // width: '100%',
        autoScroll: true,
        store: store,
        tbar: tbar,
        bbar: bbar,
        sm : selModel,
        cm: new Ext.grid.ColumnModel({
            defaults: {
                sortable: false
            },
            columns: [
              new Ext.grid.RowNumberer({
                width: 50,
              }),
              {
                  header: '콘텐츠 ID',
                  dataIndex : 'content_id',
                  align: 'center',
              },
              {
                  header: '미디어 ID',
                  dataIndex : 'media_id',
                  align: 'center',
                  width: 140,
              },
              {
                  header: '제목',
                  dataIndex : 'title',
                  width: 270,
                  align: 'center',
              },
              {
                  header: '콘텐츠 종류',
                  dataIndex : 'ud_content_title',
                  align: 'center',
                  width: 80,
              },
              {
                  header: '유형',
                  dataIndex : 'bs_content_title',
                  align: 'center',
                  width: 60,
              },
              {
                  header: '사용자',
                  dataIndex : 'user_nm',
                  align: 'center',
              },
              {
                  header: '금지 사유',
                  dataIndex : 'use_prhibt_set_resn',
                  width: 200,
                  align: 'center',
              },
              {
                  header: '사용금지 일시',
                  dataIndex : 'use_prhibt_set_dt',
                  align: 'center',
                  width: 220,
                  renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), align: 'center'
              },
            ]
        }),
        listeners: {
          afterrender: function (self) {
              onSearch();
          },
          rowclick: {
						fn: function(self, rowIndex, e){
							var index = 0;
							var sm = self.getSelectionModel();
							var sel = Ext.getCmp(gridId).getSelectionModel().getSelections();
							if(sel.length>0)
							{
								for(var i=0;i<sel.length;i++)
								{
									index = Ext.getCmp(gridId).getStore().indexOf(sel[i]);
									sm.selectRow(index,true);
								}
							}
							if(sm.isSelected(rowIndex))
							{
								sm.deselectRow(rowIndex);
							}
							else  sm.selectRow(rowIndex,true);
						}
					}
        }
    });
    return grid;
})()
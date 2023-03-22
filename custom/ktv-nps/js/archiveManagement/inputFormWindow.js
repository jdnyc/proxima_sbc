Ext.ns("Ariel.archiveManagement");

Ariel.OrderStatus = {
  accept: {
    code: '1',
    text: '접수처리'
  },
  copy: {
    code: '2',
    text: '자료복사'
  },
  delivery: {
    code: '3',
    text: '자료배송'
  },
  sold: {
    code: '4',
    text: '판매완료'
  },
  cancel: {
    code: '5',
    text: '주문취소'
  },
  refund: {
    code: '6',
    text: '환불및반품'
  },
  transform: {
    code: '7',
    text: '자료변환'
  },
  transformDone: {
    code: '8',
    text: '변환완료'
  }
};

Ariel.archiveManagement.inputFormWindow = Ext.extend(Ext.Window, {
  modal: true,
  width: 1000,
  height: 700,
  title: "주문등록",
  layout: "fit",
  // 주문 레코드
  orderRecord: null,
  statusButtons: [],
  // 가격관리 데이터
  priceData: null,


  //private variables
  _buttonToolPanel: null,

  listeners: {
    /**
     * 렌더 이벤트
     * 
     * @param {Ariel.archiveManagement.inputFormWindow} self 
     */
    afterrender: function (self) {

      var _this = this;
      if (self.action == "edit") {
        var orderNum = self.orderRecord.get('order_num');

        // 콘텐츠 불러오는것
        // Ext.Ajax.request({
        //   method: "GET",
        //   url: Ariel.archiveManagement.UrlSet.getOrderItemsByOderNum(orderNum),
        //   callback: function (opts, success, resp) {
        //     var orderItemStore = self.orderEditGrid.getStore();
        //     var orderItemRecord = Ext.decode(resp.responseText).data.orderItems;
        //     // var orderItemRecord = Ext.decode(resp.responseText).data;
        //     var price = Ext.decode(resp.responseText).data.priceData;

        //     orderItemStore.data.priceData = price;

        //     // 에디트 그리드 수정창
        //     var OrderRecord = Ext.data.Record.create([
        //       { name: "order_num" },
        //       { name: "prognm" },
        //       { name: "prolength" },
        //       { name: "prognum" },
        //       { name: "progtitle" },
        //       { name: "progdate" },
        //       { name: "proglength" },
        //       { name: "method" },
        //       { name: "price" },
        //       { name: "amount" },
        //       { name: "idx" },
        //       { name: "idx" },
        //       { name: "prices" }
        //     ]);

        //     Ext.each(orderItemRecord, function (r, i, e) {
        //       var orderRecord = new OrderRecord(r);

        //       orderItemStore.add(orderRecord);
        //     });
        //   }
        // });

        var orderCustomerRecord = self.orderRecord.get("order_customer");

        self.inputForm.getForm().setValues(self.orderRecord.data);
        self.inputForm.getForm().setValues(orderCustomerRecord);
      }
    }
  },
  initComponent: function () {

    this._initialize();

    if (this.action == "edit") {
      var status = this._findOrderStatusByCode(this.orderRecord.get("status"));
      this._updateStatusControls(status);
    }
    Ariel.archiveManagement.inputFormWindow.superclass.initComponent.call(this);
  },

  /**
   * 주문상태코드로 주문상태 객체 찾기
   * 
   * @param {string} code 상태코드('1', '2', '3'..)
   * @returns {{code: string, text: string}}
   */
  _findOrderStatusByCode: function (code) {
    var OrderStatus = Ariel.OrderStatus;
    var keys = Object.keys(OrderStatus);
    var status = null;
    Ext.each(keys, function (key) {
      if (OrderStatus[key] && code === OrderStatus[key].code) {
        status = OrderStatus[key];
        return false;
      }
    });

    return status;
  },

  _initialize: function () {
    var _this = this;

    if (_this.action == "edit") {
      var orderNum = this.orderRecord.get('order_num');
    } else {
      var orderNum = 0;
    }

    var bankInfo = new Custom.HtmlTable({
      action: this.action,
      orderRecord: this.orderRecord,
      inputFormWindow: _this
    });

    this.sm = new Ext.grid.CheckboxSelectionModel();

    // eitorGridStore
    var store = new Ext.data.JsonStore({
      remoteSort: true,
      restful: true,
      height: 200,
      proxy: new Ext.data.HttpProxy({
        method: "GET",
        // url: Ariel.archiveManagement.UrlSet.orderOrderNumParam(orderNum),
        url: Ariel.archiveManagement.UrlSet.getOrderItemsByOderNum(orderNum),
        type: "rest"
      }),
      remoteSort: true,
      totalProperty: "total",
      // root: "data['orderItems']",
      root: "data",
      fields: [
        "content_id",
        "order_num",
        "progsection",
        "idx",
        "prognm",
        "prognum",
        "datanum",
        { name: "progtitle" },
        "progdate",
        "proglength",
        "method",
        "prolength",
        "amount",
        "price",
        "homepage_key",
        "id",
        "regist_user_id",
        "updt_user_id",
        "regist_dt",
        "updt_dt",
        "delete_dt",
        "prices",
        "tc_in",
        "tc_out",
        "status"
      ]
    });

    var columns = new Ext.grid.ColumnModel({
      defaults: {
        align: "center",
        menuDisabled: true,
        sortable: false
      },
      columns: [
        _this.sm,
        {
          header: '다운로드',
          width: 70,
          sortable: false,
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {

            function createGridButton(value, id, record, handlerFunc, rowIndex) {
              new Ext.Button({
                text: value,
                value: rowIndex,
                handler: handlerFunc,
                width: 60
              }).render(document.body, id);
            }

            // var id = 'x-btn-container1-' + rowIndex;
            var id = Ext.id();
            createGridButton.defer(50, this, ['다운로드', id, record, function (btn, e) {
              Ext.Msg.alert('handler 알림', 'habdler 코드');
            }, rowIndex]);

            return ('<div id="' + id + '"></div>');

          }
        },
        {
          header: '미리보기',
          width: 70,
          sortable: false,
          align: 'center',
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {

            function createGridButton(value, id, record, handlerFunc, rowIndex) {
              new Ext.Button({
                text: value,
                value: rowIndex,
                handler: handlerFunc,
                width: 60
              }).render(document.body, id);
            }

            // var id = 'x-btn-container2-' + rowIndex;
            var id = Ext.id();
            createGridButton.defer(50, this, ['미리보기', id, record, function (btn, e) {
              var contentId = record.get('content_id');
              var win = new Custom.ContentDetailWindow({
                content_id: contentId,
                isPlayer: true,
                isMetaForm: false,
                playerMode: 'inout',
                listeners: {
                  ok: function (self, select) {

                    var tc_in = select.player.set_in;
                    var tc_out = select.player.set_out;
                    /**
                   * in out 선택영역
                   * tc_in보다 tc_out이 더 큰 영역을 선택했을때
                   * 둘 다 선택하지 않았을때
                   * 하나만 선택했을때
                   */
                    if ((tc_in <= tc_out) || (_this._isEmpty(tc_in) && _this._isEmpty(tc_out)) || (_this._isEmpty(tc_in) && !_this._isEmpty(tc_out)) || (!_this._isEmpty(tc_in) && _this._isEmpty(tc_out))) {
                      var proglength = _this._secondFormat(select.content.sys_meta.sys_video_rt);
                      _this._setInOut(record, tc_in, tc_out, proglength);
                    } else {
                      Ext.Msg.alert('알림', '선택할 수 없는 영역입니다.');
                    }

                    self.close();
                  }
                }
              }).show();
            }, rowIndex]);

            return ('<div id="' + id + '"></div>');
          }
        },
        { header: "프로그램명", dataIndex: "prognm", width: 250 },
        {
          header: "회차",
          dataIndex: "prognum",
          editor: { xtype: "numberfield" },
          width: 70
        },
        {
          header: '자료번호',
          width: 50,
          renderer: function (v, p, record, rowIndex) {
            return rowIndex + 1;
          }
        },
        {
          header: "제목",
          dataIndex: "progtitle",
          editor: { xtype: "textfield" },
          width: 250
        },

        {
          header: "제작(본방)일",
          dataIndex: "progdate",
          editor: { xtype: "textfield" },
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {

            if (!(value == null)) {
              return _this._columnValueDateFormat(value);
            }
          }
        },
        { header: "in", dataIndex: "tc_in", width: 70 },
        { header: "out", dataIndex: "tc_out", width: 70 },
        {
          header: "길이(초)",
          dataIndex: "proglength",
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            var tcIn = record.get('tc_in');
            var tcOut = record.get('tc_out');
            if (((tcOut == -1) && (tcIn == 0)) || (tcOut < 0)) {
              var sec = record.get('proglength');
            } else {
              var sec = tcOut - tcIn;
            }
            return sec;
          },
          width: 70
        },
        {
          header: "길이",
          dataIndex: "",
          width: 100,
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {

            // var proglength = record.get('proglength');
            var tcOut = record.get('tc_out');
            var tcIn = record.get('tc_in');
            if (((tcOut == -1) && (tcIn == 0)) || (tcOut < 0)) {
              var proglength = record.get('proglength');
              var length = _this._formatHisDate(proglength);
            } else {
              var proglength = tcOut - tcIn;
              if (proglength == null) {
                return null;
              } else {
                var length = _this._formatHisDate(proglength);
              }
            }
            return length;
          }
        },
        {
          header: "규격",
          dataIndex: "method",
          width: 70,
          editor: _this._inCodeComboBox()
        },
        {
          header: "금액(원)",
          dataIndex: "price",
          width: 70,
          // editor: { xtype: "textfield" },
          renderer: function (value, metaData, record, rowIndex, colIndex, store) {
            var priceData = _this.priceData;
            return _this._totalPrice(priceData, record);
          }
        },
        {
          header: "수량(개)",
          dataIndex: "amount",
          width: 70,
          editor: { xtype: "numberfield" }
          // renderer: function (value, metaData, record, rowIndex, colIndex, store) {
          //     return value;
          // }
        },
        { header: "주문번호", dataIndex: "order_num", width: 140 },
        { header: "일련번호", dataIndex: "idx", width: 70 },
        { header: "변환상태", dataIndex: "status", width: 70 }
      ]
    });

    this.orderEditGrid = new Ext.grid.EditorGridPanel({
      // layout: 'fit',
      height: 200,
      // loadMask: true,
      // stripeRows: true,
      frame: false,
      clicksToEdit: 1,
      // autoWidth: true,
      viewConfig: {
        emptyText: "목록이 없습니다.",
        // forceFit: true,
        border: false
      },
      store: store,
      sm: _this.sm,
      cm: columns,
      tbar: [
        "주문 상세내역",
        "->",
        {
          //>>text: '새로고침',
          cls: 'proxima_button_customize',
          width: 30,
          text: '<span style="position:relative;" title="' + _text('MN00139') + '"><i class="fa fa-refresh" style="font-size:13px;color:white;"></i></span>',
          handler: function () {
            store.reload();
          }
        },
        {
          xtype: "a-iconbutton",
          text: "추가",
          handler: function (self) {
            _this._contentListWindow(store);
          }
        }, {
          xtype: "a-iconbutton",
          text: "삭제",
          handler: function (self) {

            var checkItems = _this.sm.getSelections();

            var checkItemArr = [];
            Ext.each(checkItems, function (r, idx, e) {
              checkItemArr[idx] = r.data.content_id;
            });

            if (_this.sm.hasSelection()) {
              Ext.Msg.show({
                title: "알림",
                msg: "삭제하시겠습니까?.",
                buttons: Ext.Msg.OKCANCEL,
                fn: function (btnId, text, opts) {
                  if (btnId == "ok") {
                    Ext.each(checkItems, function (r, idx, e) {
                      store.remove(r);
                    });
                  }
                }
              });
            } else {
              Ext.Msg.alert("알림", "목록을 선택해주세요.");
            }
          }
        }
      ],
      listeners: {
        afterrender: function (self) {
          var editGrid = self;
          store.load();


          // Ext.Ajax.request({
          //   method: "GET",
          //   url: Ariel.archiveManagement.UrlSet.price,
          //   callback: function (opts, success, resp) {

          //     priceData = Ext.decode(resp.responseText).data;


          //     editGrid.priceData = priceData;
          //   }
          // });
        },
        afteredit: function (e) {
          // bankInfo._setValuePrice(e);
        }
      }
    });

    this._buttonToolPanel = new Ext.Container({
      xtype: 'container',
      region: "north",
      layout: 'hbox',
      height: 35,
      defaults: { margins: '0 5 0 0' },
      layoutConfig: {
        padding: '5',
        pack: 'center',
        align: 'middle'
      },
      items: this._makeStatusButtons()
    });

    this.inputForm = new Ext.form.FormPanel({
      layout: "border",
      items: [
        this._buttonToolPanel,
        {
          xtype: "fieldset",
          width: 400,
          title: "주문내역",
          region: "center",
          items: [
            {
              // orders
              xtype: "compositefield",
              items: [
                {
                  xtype: "numberfield",
                  name: "order_num",
                  fieldLabel: "주문번호",
                  readOnly: true,
                  flex: 3
                },
                {
                  xtype: "displayfield",
                  flex: 1
                },
                {
                  xtype: "displayfield",
                  hidden: true,
                  format: "Y-m-d",
                  value: "신청일:",
                  flex: 1
                },
                {
                  hidden: true,
                  xtype: "datefield",
                  altFormats: "Y-m-d|Ymd|YmdHis",
                  name: "order_date",
                  format: "Ymd",
                  value: new Date(),
                  flex: 3,
                  readOnly: true
                },
                {
                  xtype: "displayfield",
                  flex: 1
                },
                {
                  hidden: true,
                  xtype: "textfield",
                  name: "status",
                  value: "1",

                  flex: 2
                }
              ]
            },
            // {
            //     xtype: 'radiogroup',
            //     fieldLabel: '사용주체',
            //     name: 'purpose',
            //     allowBlank: false,
            //     items: _this._radioGropInCodeItem('사용주체', 'purpose', _this.radioData, '*OR002')
            // },
            _this._radioGropInCodeItem(
              "사용주체",
              "purpose",
              _this.radioData,
              "*OR002"
            ),
            {
              xtype: "textfield",
              fieldLabel: "사용목적",
              width: 400,
              name: "usepo"
            },
            // {
            //     xtype: 'radiogroup',
            //     fieldLabel: '배송방법',
            //     name: 'delivery',
            //     // allowBlank: false,
            //     items: _this._radioGropInCodeItem('배송방법', 'delivery', _this.radioData, '*OR003')
            // },
            _this._radioGropInCodeItem(
              "배송방법",
              "delivery",
              _this.radioData,
              "*OR003"
            ),
            {
              xtype: "textarea",
              fieldLabel: "메세지",
              name: "memo",
              width: 400,
              autoscroll: true
            },
            {
              xtype: "textarea",
              fieldLabel: "특이사항",
              name: "memo1",
              width: 400,
              autoscroll: true
            },
            {
              xtype: "fieldset",
              title: "결제계좌",
              items: [
                bankInfo,
                {
                  xtype: "compositefield",
                  items: [{
                    xtype: "displayfield",
                    value: "입금인:",
                    flex: 1
                  }, {
                    xtype: "textfield",
                    name: "bank_deposit",
                    flex: 3
                  }, {
                    xtype: "displayfield",
                    value: "입금일:",
                    flex: 1
                  }, {
                    xtype: "datefield",
                    altFormats: "Y-m-d|Ymd|YmdHis",
                    format: "Y-m-d",
                    // readOnly: true,
                    name: "receipt_date_order",
                    // value: null,
                    flex: 3,
                    listeners: {
                      select: function (self, date) {
                        var selectDate = date;
                        var toDate = new Date();
                        if (toDate > selectDate) {
                          self.setValue(toDate);
                          Ext.Msg.alert('알림', '이전 날짜를 입금일로 선택할 수 없습니다.');
                        };
                      }
                    }
                  }, {
                    text: '저장',
                    xtype: 'button',
                    scale: "small",
                    margins: { top: 1, right: 0, bottom: 0, left: 0 },
                    flex: 1,
                    handler: function (self) {
                      var form = _this.inputForm.getForm();
                      var bankDeposit = form.findField('bank_deposit').getValue();
                      var receiptDateOrder = form.findField('receipt_date_order').getValue();
                      if (Ext.isEmpty(bankDeposit)) {
                        return Ext.Msg.alert('알림', '입금인이 입력되지 않았습니다.');
                      };
                      if (Ext.isEmpty(receiptDateOrder)) {
                        return Ext.Msg.alert('알림', '입금일이 입력되지 않았습니다.');
                      };
                      _this.inputForm.getForm().submit({
                        method: _this.method,
                        url: _this.url,
                        success: function (form, action) {
                          _this.onAfterSave();
                          Ext.Msg.alert('알림', '결제계좌 정보가 입력되었습니다.');

                        }
                      });
                    },
                    listeners: {
                      afterrender: function (self) {
                        if (_this.action == "edit") {
                          self.show();
                        } else {
                          self.hide();
                        };
                      }
                    }
                  }],
                  listeners: {
                    afterrender: function (self) {
                      if (_this.action == "edit") {
                        var orderRecord = _this.orderRecord.data;
                        var form = _this.inputForm.getForm();
                        form
                          .findField("bank_deposit")
                          .setValue(orderRecord.bank_deposit);
                        form
                          .findField("receipt_date_order")
                          .setValue(orderRecord.receipt_date);
                      }
                    }
                  }
                }
              ]
            }
          ]
        },
        {
          xtype: "fieldset",
          width: 400,
          title: "주문자 배송내역",
          region: "east",
          items: [
            {
              xtype: "textfield",
              fieldLabel: "이름",
              name: "cust_nm"
            },
            {
              xtype: "numberfield",
              fieldLabel: "주소",
              width: 60,
              autoCreate: {
                tag: "input",
                type: "text",
                size: "20",
                autocomplete: "off",
                maxlength: "5"
              },
              name: "zipcode"
            },
            {
              xtype: "textfield",
              width: 250,
              name: "address1"
            },
            {
              xtype: "textfield",
              fieldLabel: "나머지주소",
              width: 250,
              name: "address2"
            },
            {
              xtype: "textfield",
              fieldLabel: "전화번호",
              name: "phone"
            },
            {
              xtype: "textfield",
              fieldLabel: "입금인",
              name: "cust_bank_deposit"
            },
            {
              xtype: "datefield",
              fieldLabel: "입금예정일",
              altFormats: "Y-m-d|Ymd|YmdHis",
              format: "Y-m-d",
              value: new Date(),
              name: "receipt_date"
            }
          ]
        },
        {
          //title: '주문 상세내역',
          region: "south",
          height: 200,
          items: _this.orderEditGrid
        }
      ]
    });

    this.items = this.inputForm;

    this.buttonAlign = "left";
    this.fbar = [
      "->",
      {
        text: "저장",
        scale: "medium",
        handler: function (self) {
          var selDataArr = [];
          var selData = _this.orderEditGrid.getStore();

          selData.each(function (records, idx) {
            var priceData = _this.priceData;
            var price = _this._totalPrice(priceData, records);

            selDataArr[idx] = records.data;
            selDataArr[idx].price = price;
          });

          _this.inputForm.getForm().submit({
            method: _this.method,
            url: _this.url,
            params: {
              selData: Ext.encode(selDataArr)
            },
            success: function (form, action) {
              if (!_this.inputForm.getForm().isValid()) {
                Ext.Msg.show({
                  title: "알림",
                  msg: "입력되지 않은 값이 있습니다.",
                  buttons: Ext.Msg.OK
                });
                return;
              }

              Ext.Msg.show({
                title: "알림",
                msg: _this.text + " 되었습니다",
                buttons: Ext.Msg.OK,
                fn: function (btnId, text, opts) {
                  if (btnId == "ok") {
                    _this.onAfterSave();
                    _this.close();
                  }
                }
              });
            }
          });
        }
      },
      {
        text: "닫기",
        scale: "medium",
        handler: function (self) {
          _this.close();
        }
      }
    ];
  },
  /**
   * 콘텐츠 조회 그리드
   */
  _addItemsGrid: function () {
    var _this = this;
    var sm = new Ext.grid.CheckboxSelectionModel();
    var store = new Ext.data.JsonStore({
      remoteSort: true,
      restful: true,
      height: 200,
      proxy: new Ext.data.HttpProxy({
        method: "GET",
        url: Ariel.archiveManagement.UrlSet.content,
        type: "rest",

        listeners: {
          load: function (self, o, options) {

            // var orderItemStore = _this.inputForm.get(2).get(0).getStore();
            var orderItemStore = _this.orderEditGrid.getStore();

            var price = o.reader.jsonData.data.priceData;

            orderItemStore.data.priceData = price;
          }
        }
      }),
      remoteSort: true,
      totalProperty: "total",
      // root: "data['contents']",
      root: "data",
      fields: [
        "content_id",
        { name: "rn", type: "int" },
        "title",
        "method",
        "progdate",
        "proglength",
        "prognum",
        "progtitle",
        "amount",
        "datanum",
        "prices",
        "usr_meta",
        "sys_meta"
      ]
    });
    var grid = new Ext.grid.GridPanel({
      layout: "fit",
      height: 200,
      loadMask: true,
      stripeRows: true,
      frame: false,
      autoWidth: true,
      viewConfig: {
        emptyText: "목록이 없습니다.",
        forceFit: true,
        border: false
      },
      store: store,
      sm: sm,
      cm: new Ext.grid.ColumnModel({
        defaults: {
          align: "center",
          menuDisabled: true,
          sortable: false
        },
        columns: [
          sm,
          // { header: 'NO', dataIndex: '' },
          new Ext.grid.RowNumberer({
            header: "NO",
            width: 60
          }),
          {
            header: "미디어ID", dataIndex: "usr_meta", renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!_this._isEmpty(value)) {
                return value.media_id;
              }
            }
          },
          // { header: "프로그램명", dataIndex: "title" },
          {
            header: "프로그램명", dataIndex: "usr_meta", renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!_this._isEmpty(value)) {
                return value.progrm_nm;
              }
            }
          },
          {
            header: "회차", dataIndex: "usr_meta", renderer: function (value, metaData, record, rowIndex, colIndex, store) {
              if (!_this._isEmpty(value)) {
                return value.tme_no;
              }

            }
          },
          { header: "부제", dataIndex: "title" },
          {
            header: "길이", dataIndex: "sys_meta", renderer: function (value, metaData, record, rowIndex, colIndex, store) {

              if (!_this._isEmpty(value)) {
                return value.sys_video_rt;
              }

            }
          }
        ]
      }),
      tbar: [
        // "미디어ID: ",
        {
          hidden: true,
          xtype: "textfield",
          name: "mediaId"
        },
        "검색어: ",
        {
          xtype: "textfield",
          name: "keyword"
        },
        {
          xtype: "a-iconbutton",
          text: "조회",
          handler: function (self) {
            grid.getStore().load({
              params: {
                keyword: grid
                  .getTopToolbar()
                  .find("name", "keyword")[0]
                  .getValue(),
                mediaId: grid
                  .getTopToolbar()
                  .find("name", "mediaId")[0]
                  .getValue()
              }
            });
          }
        }
      ],
      bbar: new Ext.PagingToolbar({
        pageSize: 30,
        store: store
      }),
      listeners: {
        rowdblclick: function (self, rowIndex, e) {
          var sm = self.getSelectionModel();
          var getRecord = sm.getSelected();
          var contentId = getRecord.get('content_id');
          var sm = grid.getSelectionModel();
          var store = _this.orderEditGrid.getStore();
          var checkItem = sm.selections.items[0];
          var win = new Custom.ContentDetailWindow({
            content_id: contentId,
            isPlayer: true,
            isMetaForm: false,
            playerMode: 'inout',
            listeners: {
              ok: function (self, select) {

                var tc_in = select.player.set_in;
                var tc_out = select.player.set_out;

                /**
                 * in out 선택영역
                 * tc_in보다 tc_out이 더 큰 영역을 선택했을때
                 * 둘 다 선택하지 않았을때
                 * 하나만 선택했을때
                 */
                if ((tc_in <= tc_out) || (_this._isEmpty(tc_in) && _this._isEmpty(tc_out)) || (_this._isEmpty(tc_in) && !_this._isEmpty(tc_out)) || (!_this._isEmpty(tc_in) && _this._isEmpty(tc_out))) {
                  var checkEqualContent = true;
                  Ext.each(store.data.items, function (r) {
                    if ((r.get('content_id') == checkItem.get('content_id'))) {
                      checkEqualContent = false;
                    }
                  });

                  if (checkEqualContent) {
                    var orderRecord = Ext.data.Record.create([
                      { name: "order_num" },
                      { name: "prognm" },
                      { name: "prolength" },
                      { name: "prognum" },
                      { name: "progtitle" },
                      { name: "progdate" },
                      { name: "proglength" },
                      { name: "method" },
                      { name: "price" },
                      { name: "amount" },
                      { name: "idx" },
                      { name: "idx" },
                      { name: "title" },
                      { name: "prices" },
                      { name: "status" },
                      { name: 'tc_in' },
                      { name: 'tc_out' }
                    ]);

                    // 프로그램명 회차 부제

                    var proglength = _this._secondFormat(checkItem.get('sys_meta').sys_video_rt);
                    // checkItem.data.prognm = checkItem.get('usr_meta').progrm_nm;

                    // 프로그램 날짜
                    checkItem.data.progdate = checkItem.get('usr_meta').prod_de;

                    // checkItem.data.datanum = checkItem.get('content_id');
                    // checkItem.data.datanum = checkItem.get('usr_meta').media_id;
                    // 제호 횟수
                    checkItem.data.prognum = checkItem.get('usr_meta').tme_no;




                    // _this.testFunction(rt);

                    checkItem.set('amount', 1);
                    checkItem.set('status', "대기");
                    checkItem.set('method', "CD");
                    checkItem.set('proglength', proglength);
                    checkItem.set('progtitle', checkItem.get('title'));
                    // checkItem.set('prognm', checkItem.get('title'));

                    _this._setInOut(checkItem, tc_in, tc_out, proglength);

                    var selectRecord = new orderRecord(checkItem.data);

                    store.add(selectRecord);

                  } else {
                    Ext.Msg.alert('알림', '이미 추가된 콘텐츠 입니다.');
                  }

                  self.close();
                  grid.ownerCt.close();
                } else {
                  Ext.Msg.alert('알림', '선택할 수 없는 영역입니다.');
                }

              },
              load: function (self, data) {
              }
            }
          }).show();
        }
      }
    });
    return grid;
  },

  /**
   * 주문 상태 변경
   * 
   * @param {Ariel.OrderStatus} status 
   */
  _changeStatus: function (status) {
    var _this = this;
    // ajax DB에 상태 변경



    var statusCode = status.code;
    var statusText = status.text;
    var orderNum = this.orderRecord.get('order_num');

    Ext.Ajax.request({
      method: "POST",
      url: Ariel.archiveManagement.UrlSet.orderUpdateStatus(orderNum),
      params: {
        status: statusCode
      },
      callback: function (opts, success, resp) {
        if (success) {
          try {


            // _this.onAfterSave();

            Ext.Msg.alert(
              "알림",
              "(" +
              statusText +
              ") 으로 진행상태가 변경되었습니다."
            );

            // 성공하면 로컬 변수 변경
            _this.orderRecord.set('status', statusCode);

          } catch (e) {
            Ext.Msg.alert(e["name"], e["message"]);
          }
        } else {
          Ext.Msg.alert("status: " + resp.status, resp.statusText);
        }
      }
    });


  },

  /**
   * 상태 관련 UI컨트롤들(Label, Button 등) 상태 업데이트
   * 
   * @param {Ariel.OrderStatus} status 
   */
  _updateStatusControls: function (status) {
    // 타이틀  
    this._setTitle(status.text);


    if (!Object.keys(this.statusButtons).length) {
      return;
    }
    var OrderStatus = Ariel.OrderStatus;


    // 버튼
    switch (status.code) {
      // 접수상태
      case OrderStatus.accept.code:
        this._getStatusButton('transform').show();
        this._getStatusButton('delivery').hide();
        this._getStatusButton('delivery_cancel').hide();
        this._getStatusButton('sold').hide();
        this._getStatusButton('cancel').show();
        this._getStatusButton('refund').show();
        break;
      // 자료변환
      case OrderStatus.transform.code:

        this._getStatusButton('transform').hide();
        this._getStatusButton('delivery').hide();
        this._getStatusButton('delivery_cancel').hide();
        this._getStatusButton('sold').hide();
        this._getStatusButton('cancel').hide();
        this._getStatusButton('refund').hide();
        break;
      // 변환완료
      case OrderStatus.transformDone.code:
        this._getStatusButton('transform').hide();
        this._getStatusButton('delivery').show();
        this._getStatusButton('delivery_cancel').hide();
        this._getStatusButton('sold').hide();
        this._getStatusButton('cancel').show();
        this._getStatusButton('refund').show();
        break;
      // 자료배송
      case OrderStatus.delivery.code:
        this._getStatusButton('transform').hide();
        this._getStatusButton('delivery').hide();
        this._getStatusButton('delivery_cancel').show();
        this._getStatusButton('sold').show();
        this._getStatusButton('cancel').show();
        this._getStatusButton('refund').show();
        break;
      // 판매완료
      case OrderStatus.sold.code:
        this._getStatusButton('transform').hide();
        this._getStatusButton('delivery').hide();
        this._getStatusButton('delivery_cancel').hide();
        this._getStatusButton('sold').hide();
        this._getStatusButton('cancel').show();
        this._getStatusButton('refund').show();
        break;
      // 주문취소
      case OrderStatus.cancel.code:
        this._getStatusButton('transform').hide();
        this._getStatusButton('delivery').hide();
        this._getStatusButton('delivery_cancel').hide();
        this._getStatusButton('sold').hide();
        this._getStatusButton('cancel').hide();
        this._getStatusButton('refund').show();
        break;
      default:

        this._getStatusButton('transform').hide();
        this._getStatusButton('delivery').hide();
        this._getStatusButton('delivery_cancel').hide();
        this._getStatusButton('sold').hide();
        this._getStatusButton('cancel').hide();
        this._getStatusButton('refund').hide();
        break;
    }
    this._buttonToolPanel.doLayout();
  },

  _getStatusButton: function (key) {

    return this.statusButtons[key];
  },

  _setTitle: function (statusText) {
    this.title = "주문등록 [" + statusText.fontcolor("RED") + "]";
    var changeTitle = "주문등록 [" + statusText.fontcolor("RED") + "]";
    return this.setTitle(changeTitle);
  },

  // 진행상태 버튼
  _updateStatusButton: function (buttonName, statusCode, _this) {
    var updateStatusButton = new Ext.Button({
      text: buttonName,
      scale: "medium",
      handler: function (self) {
        var orderNum = _this.orderRecord.data.order_num;
        // _this._changeBanckDeposit(orderNum, 2, _this);
        Ext.Ajax.request({
          method: "POST",
          // url: '/api/v1/content-orders/' + orderNum + '/update-status',
          url: Ariel.archiveManagement.UrlSet.orderUpdateStatus(orderNum),
          // url: Ariel.archiveManagement.UrlSet.ordersBankDeposit(orderNum),
          params: {
            status: statusCode
          },
          callback: function (opts, success, resp) {
            if (success) {
              try {
                // _this.onAfterSave();
                _this.close();
                Ext.Msg.alert(
                  "알림",
                  "(" + buttonName + ") 으로 진행상태가 변경되었습니다."
                );
              } catch (e) {
                Ext.Msg.alert(e["name"], e["message"]);
              }
            } else {
              Ext.Msg.alert("status: " + resp.status, resp.statusText);
            }
          }
        });
      }
    });
    if (_this.action == "edit") {
      return updateStatusButton.enable();
    } else if (_this.action == "add") {
      return updateStatusButton.disable();
    }
  },
  _inCodeComboBox: function () {
    var _this = this;
    var combo = new Ext.form.ComboBox({
      xtype: "combo",
      allowBlank: false,
      editable: false,
      mode: "local",
      displayField: "code_itm_nm",
      valueField: "code_itm_code",
      hiddenValue: "code_itm_code",
      typeAhead: true,

      // beforeValue: '',
      triggerAction: "all",
      lazyRender: true,
      store: new Ext.data.JsonStore({
        restful: true,
        proxy: new Ext.data.HttpProxy({
          method: "GET",
          url: Ariel.glossary.UrlSet.codeSetIdParamCodeItems("OR_PRICE"),
          type: "rest"
        }),
        root: "data",
        fields: [
          { name: "code_itm_code", mapping: "code_itm_code" },
          { name: "code_itm_nm", mapping: "code_itm_nm" },
          { name: "id", mapping: "id" },
          { name: "prices", mapping: "prices" }
        ],

        listeners: {
          load: function (store, r, option) { },
          exception: function (self, type, action, opts, response, args) {
            try {
              var r = Ext.decode(response.responseText, true);

              if (!r.success) {
                Ext.Msg.alert(_text("MN00023"), r.msg);
              }
            } catch (e) {
              Ext.Msg.alert(_text("MN00023"), r.msg);
            }
          }
        }
      }),
      listeners: {
        afterrender: function (self) {
          self.getStore().load({
            params: {
              is_code: 1
            }
          });
        },
        select: function (self, record, idx) {
          self.setValue(record.get("code_itm_code"));
        }
      }
    });
    return combo;
  },
  _renameKeys: function (obj, oldKeys, newKeys) {
    Ext.each(oldKeys, function (r, i, e) {
      if (r == newKeys) {
        // return this;
      }

      if (obj.hasOwnProperty(r)) {
        obj[newKeys[i]] = obj[r];
        delete obj[r];
      }
    });

    return obj;
  },
  _customToggleButton: function (text, cancelText, code, win) {
    return new Custom.ToggleButton({
      text: text,
      cancelText: cancelText,
      code: code,
      win: win
    });
  },
  _makeToggleButton: function (text, pushedText, orderStatus) {
    var _this = this;
    var toggleButton = new Ext.Button({
      enableToggle: true,
      // 기본 버튼 텍스트
      text: text,
      // 눌러졌을때 텍스트
      pushedText: pushedText,
      // 버튼이 담당할 상태
      orderStatus: orderStatus,
      // 눌렀을때 처리 할 주문 정보
      orderRecord: _this.orderRecord,

      scale: "medium",

      listeners: {
        click: function (self) {
          // 처리 상태로 바꾸는거고...
          var orderNum = self.orderRecord.get("order_num");
          if (self.pressed) {
            // 눌러졌다..
            // 입금날짜
            var receiptDate = self.orderRecord.get("receipt_date");
            // 입금인
            var bankDeposit = self.orderRecord.get("order_customer")
              .bank_deposit;

            if (receiptDate == null || bankDeposit == null) {
              Ext.Msg.alert(
                "알림",
                "입금이 확인되지 않아 상태를 변경할 수 없습니다."
              );
              return;
            }

            var toChangeStatus = self.orderStatus;
            var currentStatus = Number(self.orderRecord.get("status"));
            // 자료복사 버튼을 클릭할때(지금 현재 상태값은 주문접수(1) 상태 이고 바뀔 값은 자료복사(2))
            if ((currentStatus + 1 == 2) == (toChangeStatus == 2)) {
              /**
               * api 호출 후 변환상태(STATUS 필드)를 변환중으로 업데이트
               */

              Ext.Msg.alert("알림", "변환을 시작합니다.");
              _this._updateOrderStatus(orderNum, toChangeStatus);


              // _this.orderEditGrid.getStore().reload();
            } else {
              // 자료복사 버튼이 아닐때
              if (currentStatus + 1 == toChangeStatus) {
                // 취소버튼
                // 변환된 상태라 지금 상태값이 1이 더 많은거 같은데
                // 그래서 toggle은 false이고 false일때는 코드값을 1 빼서 다시 바꾸면 원래 상태 값으로 돌아온다
                _this._updateOrderStatus(orderNum, toChangeStatus);
              } else if (currentStatus == toChangeStatus) {
                _this._statusAjaxResponse(orderNum, self, _this);
                _this._updateOrderStatus(orderNum, toChangeStatus);
              } else {
                Ext.Msg.alert("알림", "지금 변경할 수 없는 상태값 입니다.");
              }
            }
          } else {
            // 해제하는(이전 상태로 돌아가는) 거고
            if (self.orderStatus == self.orderRecord.get("status")) {
              var beforeStatus = Number(self.orderStatus) - 1;
              _this._updateOrderStatus(orderNum, beforeStatus);
            }
          }
        }
      }
    });
    return toggleButton;
  },


  _statusAjaxResponse: function (orderNum, self) {
    if (self.toggle) {
      var codeNum = self.code;
    } else {
      var codeNum = self.code - 1;
    }

    return Ext.Ajax.request({
      method: "POST",
      // url: '/api/v1/content-orders/' + orderNum + '/update-status',
      url: Ariel.archiveManagement.UrlSet.orderUpdateStatus(orderNum),
      params: {
        status: codeNum,
        toggle: self.toggle
      },
      callback: function (opts, success, resp) {
        if (success) {
          try {
            var response = Ext.decode(resp.responseText).data;

            // _this.onAfterSave();
            // _this.close();

            // 에디터 그리드
            // _this.items.items[0].items.items[2].items.items[0].getStore().proxy.setUrl(Ariel.archiveManagement.UrlSet.getOrderItemsByOderNum(20190910103813));
            // _this.items.items[0].items.items[2].items.items[0].getStore().reload();

            // console.log(_this.items.items[0].items.items[2].items.items[0].getStore());

            Ext.Msg.alert(
              "알림",
              "(" +
              response.code.code_itm_nm +
              ") 으로 진행상태가 변경되었습니다."
            );
          } catch (e) {
            Ext.Msg.alert(e["name"], e["message"]);
          }
        } else {
          Ext.Msg.alert("status: " + resp.status, resp.statusText);
        }
      }
    });
  },
  // 주문 상태 업데이트
  _updateOrderStatus: function (orderNum, orderStatus) {
    return Ext.Ajax.request({
      method: "POST",
      // url: '/api/v1/content-orders/' + orderNum + '/update-status',
      url: Ariel.archiveManagement.UrlSet.orderUpdateStatus(orderNum),
      params: {
        status: orderStatus
      },
      callback: function (opts, success, resp) {
        if (success) {
          try {
            var response = Ext.decode(resp.responseText).data;

            // _this.onAfterSave();

            Ext.Msg.alert(
              "알림",
              "(" +
              response.code.code_itm_nm +
              ") 으로 진행상태가 변경되었습니다."
            );
          } catch (e) {
            Ext.Msg.alert(e["name"], e["message"]);
          }
        } else {
          Ext.Msg.alert("status: " + resp.status, resp.statusText);
        }
      }
    });
  },

  _radioGropInCodeItem: function (filedLabel, name, record, key) {
    var codeItem = [];
    var codeType = [];

    if (!(record == null)) {
      Ext.each(record, function (r, idx, e) {
        var codeTypes = r.data.type;

        if (codeTypes == key) {
          var codeItems = r.data.items;

          Ext.each(codeItems, function (r2, idx2, e2) {
            if (idx2 == 0) {
              var i = {
                boxLabel: r2.code_itm_nm,
                name: name,
                inputValue: r2.code_itm_code,
                checked: true
              };
            } else {
              var i = {
                boxLabel: r2.code_itm_nm,
                name: name,
                inputValue: r2.code_itm_code
              };
            }

            codeItem.push(i);
          });
          codeType[codeTypes] = codeItem;
          codeItem = [];
        }
      });

      return new Ext.form.RadioGroup({
        fieldLabel: filedLabel,
        name: name,
        allowBlank: false,
        items: codeType[key]
      });
    } else {
      return false;
    }
  },

  /**
   * 상태 버튼 만들기
   * 
   * @returns {Ext.Button[]}
   */
  _makeStatusButtons: function () {
    var inputWin = this;
    var form = inputWin;

    // 자료변환
    // 자료배송
    // 배송취소
    // 판매완료
    // 주문취소
    // 환불반품
    var buttons = [];
    // 자료변환
    var button = new Ext.Button({
      text: '자료변환',
      scale: "medium",
      handler: function (btn) {

        // 변환요청 성공하면 버튼을 숨긴다  
        inputWin._changeStatus(Ariel.OrderStatus.transform);
        inputWin._updateStatusControls(Ariel.OrderStatus.transform);
      }
    });
    buttons.push(button);
    this.statusButtons['transform'] = button;

    // 자료배송
    button = new Ext.Button({
      text: '자료배송',
      scale: "medium",
      hidden: true,
      handler: function (btn) {
        // 자료배송으로 상태변경 요청 성공하면 버튼을 숨기고 배송취소 버튼을 보여준다


        inputWin._changeStatus(Ariel.OrderStatus.delivery);
        inputWin._updateStatusControls(Ariel.OrderStatus.delivery);
      }
    });
    buttons.push(button);
    this.statusButtons['delivery'] = button;

    // 배송취소
    button = new Ext.Button({
      text: '배송취소',
      scale: "medium",
      hidden: true,
      handler: function (btn) {
        // 변환완료로 상태변경 요청 성공하면 버튼을 숨기고 자료배송 버튼을 보여준다
        inputWin._changeStatus(Ariel.OrderStatus.transformDone);
        inputWin._updateStatusControls(Ariel.OrderStatus.transformDone);
      }
    });
    buttons.push(button);
    this.statusButtons['delivery_cancel'] = button;

    // 판매완료
    button = new Ext.Button({
      text: '판매완료',
      scale: "medium",
      handler: function (btn) {
        // 판매완료로 상태변경 요청 성공하면 버튼을 숨긴다
        inputWin._changeStatus(Ariel.OrderStatus.sold);
        inputWin._updateStatusControls(Ariel.OrderStatus.sold);
      }
    });
    buttons.push(button);
    this.statusButtons['sold'] = button;

    // 주문취소
    button = new Ext.Button({
      text: '주문취소',
      scale: "medium",
      handler: function (btn) {
        // 주문취소 상태로 상태변경 요청 성공하면 버튼을 숨긴다
        // 판매완료 버튼 숨김
        inputWin._changeStatus(Ariel.OrderStatus.cancel);
        inputWin._updateStatusControls(Ariel.OrderStatus.cancel);
      }
    });
    buttons.push(button);
    this.statusButtons['cancel'] = button;

    // 환불반품
    button = new Ext.Button({
      text: '환불반품',
      scale: "medium",
      handler: function (btn) {
        // 환불반품 상태로 상태변경 요청 성공하면 버튼을 숨긴다
        // 판매완료 버튼 숨김
        inputWin._changeStatus(Ariel.OrderStatus.refund);
        inputWin._updateStatusControls(Ariel.OrderStatus.refund);
      }
    });
    buttons.push(button);
    this.statusButtons['refund'] = button;

    return buttons;
  },
  _totalPrice: function (priceData, record) {

    var min = 1000000;
    Ext.each(priceData, function (r, i, e) {
      if (r.method == record.get('method')) {
        var priceSec = r.prolength * 60;

        var tcOut = record.get('tc_out');
        var tcIn = record.get('tc_in');


        // var recordSec = record.get('proglength');

        var recordSec = tcOut - tcIn;



        abs = priceSec - recordSec < 0
          ? -(priceSec - recordSec)
          : priceSec - recordSec;

        if (abs < min) {
          min = abs;
          near = r.price;
        }
      }
    });


    var amount = record.data.amount;

    var priceValue = 0;
    if (amount == null) {
      priceValue;
    } else {
      priceValue = near * amount;
    }
    return priceValue;
  },
  _contentListWindow: function (store) {
    var _this = this;
    var win = new Ext.Window({
      modal: true,
      width: 1000,
      height: 500,
      title: "추가",
      layout: "fit",
      items: _this._addItemsGrid(),
      buttons: [
        {
          text: "확인",
          scale: "medium",
          handler: function (self) {
            var grid = win.get(0);
            var sm = grid.getSelectionModel();

            if (sm.hasSelection()) {
              // 에디터 그리드의 스토어
              // var orderItemStore = _this.inputForm.get(2).get(0).getStore();
              var storeItemArr = store.data.items;
              var checkItemArr = sm.selections.items;




              // 선택한 아이템과 스토어의 아이템이 중복된 배열요소
              var overLapItemArr = storeItemArr.filter(function (val) {
                return checkItemArr.indexOf(val) == -1;
              });

              /**
               * 중복된 선택값 지우지
               */
              var deleteArr = [];
              var checkContentId = [];
              Ext.each(checkItemArr, function (r, idx, e) {
                Ext.each(storeItemArr, function (r2, idx2, e) {
                  if (r.data.content_id == r2.data.content_id) {
                    deleteArr.push(r);
                  }
                });
                checkContentId.push(r.data.content_id);
              });

              Ext.each(deleteArr, function (r, idx, e) {
                checkItemArr.remove(r);
              });

              var orderRecord = Ext.data.Record.create([
                { name: "order_num" },
                { name: "prognm" },
                { name: "prolength" },
                { name: "prognum" },
                { name: "progtitle" },
                { name: "progdate" },
                { name: "proglength" },
                { name: "method" },
                { name: "price" },
                { name: "amount" },
                { name: "idx" },
                { name: "idx" },
                { name: "title" },
                { name: "prices" },
                { name: "status" },
                { name: 'tc_in' },
                { name: 'tc_out' }
              ]);

              Ext.encode(checkContentId);

              Ext.each(checkItemArr, function (r, i, e) {
                _this._renameKeys(r.data, ["title"], ["progtitle"]);

                // 프로그램명 회차 부제
                // _this._renameKeys2(r.data, "usr_meta", ['progrm_nm', 'subtl'], ["prognm", "proglength"]);
                var proglength = _this._secondFormat(r.data.sys_meta.sys_video_rt);
                r.data.prognm = r.data.usr_meta.progrm_nm;
                // r.data.progtitle = r.data.usr_meta.progrm_nm;
                r.data.progdate = r.data.usr_meta.prod_de;
                // r.data.datanum = r.data.usr_meta.media_id;
                r.data.prognum = r.data.usr_meta.tme_no;

                // _this.testFunction(rt);
                r.data.proglength = proglength;
                r.data.method = "CD";
                r.data.status = "대기";
                r.data.amount = 1;
                r.data.tc_in = 0;
                r.data.tc_out = -1;

                // delete r.data.title;
                // delete r.data.usr_meta;
                // console.log(r.data);

                var selectRecord = new orderRecord(r.data);

                store.add(selectRecord);
              });

              /**
               * 가격 가져와서 붙히기
               */

              Ext.Msg.show({
                title: "알림",
                msg: "선택되었습니다.",
                buttons: Ext.Msg.OK,
                fn: function (btnId, text, opts) {
                  if (btnId == "ok") {
                    win.close();
                  }
                }
              });
            } else {
              Ext.Msg.alert("알림", "목록을 선택해주세요.");
            }
          }
        },
        {
          text: "취소",
          scale: "medium",
          handler: function (self) {
            win.close();
          }
        }
      ]
    });
    return win.show();
  },
  _secondFormat: function (str) {
    var res;
    if (str == null) {
      return;
    };
    res = str.replace(/[^0-9]/g, "");
    var h = res.substr(0, 2) * 3600;
    var m = res.substr(2, 2) * 60;
    var s = res.substr(4, 2) * 1;
    var ss = h + m + s;
    return Math.floor(ss);
  },
  _columnValueDateFormat: function (value) {
    /**
     * 20190801 형식 변환
     */

    var y = value.substr(0, 4);
    var m = value.substr(4, 2);
    var d = value.substr(6, 2);
    var ymd = y + '-' + m + '-' + d;
    return ymd;

  },
  _formatHisDate: function (date) {
    var hour = parseInt(date / 3600);
    var min = parseInt((date % 3600) / 60);
    var sec = date % 60;
    if (hour.toString().length == 1) hour = "0" + hour;
    if (min.toString().length == 1) min = "0" + min;
    if (sec.toString().length == 1) sec = "0" + sec;
    var length = hour + ":" + min + ":" + sec;
    return length;
  },
  /**
   * 문자열이 빈 문자열인지 체크하여 결과값을 리턴한다.
   * @param str       : 체크할 문자열
   */
  _isEmpty: function (str) {
    if (typeof str == "undefined" || str == null || str == "" || typeof str == "NaN")
      return true;
    else
      return false;
  },
  _setInOut: function (record, tc_in, tc_out, proglength) {
    var _this = this;

    if (!_this._isEmpty(tc_in) && !_this._isEmpty(tc_out)) {
      record.set('tc_in', Math.floor(tc_in));
      record.set('tc_out', Math.floor(tc_out));
    } else if (_this._isEmpty(tc_in) && _this._isEmpty(tc_out)) {
      // in out을 안잡아 줬을 때
      record.set('tc_in', 0);
      record.set('tc_out', -1);
    } else if (_this._isEmpty(tc_in) && !_this._isEmpty(tc_out)) {
      // console.log('tc_out 만 값이 있을때');
      record.set('tc_in', 0);
      record.set('tc_out', Math.floor(tc_out));
    } else if (!_this._isEmpty(tc_in) && _this._isEmpty(tc_out)) {
      // console.log('tc_in만 값이 있을 떄');
      record.set('tc_in', Math.floor(tc_in));
      record.set('tc_out', proglength);
    };
  }
});

Ext.ns("Custom");

Custom.ToggleButton = Ext.extend(Ext.Button, {
  win: null,

  text: null,
  scale: "medium",
  toggle: true,

  // handler: function (self) {

  //     var button = this;
  //     var _this = self.win;

  //     var orderNum = _this.selectRecord.data.order_num;
  //     this._statusAjaxResponse(orderNum, self, _this);

  // },
  initComponent: function () {
    this._buttonChange();
    this.handler = function (self) {
      // self -> 버튼

      // 윈도우창
      var _this = self.win;

      var selectRecord = _this.orderRecord.data;

      // 지금 상태의 코드값
      var status = selectRecord.status;

      // 변경된(될) 코드값
      var changeStatus = self.code;

      var orderNum = selectRecord.order_num;

      // 입금날짜
      var receiptDate = selectRecord.receipt_date;
      // 입금인
      var bankDeposit = selectRecord.order_customer.bank_deposit;

      // 입금날짜 입금인이 없으면 입금되지 않은 상태라서 상태를 변경할 수 없다
      if (receiptDate != null && bankDeposit != null) {
        if (self.toggle == false && status == changeStatus) {
          this._statusAjaxResponse(orderNum, self, _this);
        } else {
          // 자료복사 버튼을 클릭할때(지금 현재 상태값은 주문접수(1) 상태 이고 바뀔 값은 자료복사(2))
          if ((Number(status) + 1 == 2) == (changeStatus == 2)) {
            /**
             * api 호출 후 변환상태(STATUS 필드)를 변환중으로 업데이트
             */
            this._statusAjaxResponse(orderNum, self, _this);
          } else {
            // 자료복사 버튼이 아닐때
            if (Number(status) + 1 == changeStatus) {
              // 취소버튼
              // 변환된 상태라 지금 상태값이 1이 더 많은거 같은데
              // 그래서 toggle은 false이고 false일때는 코드값을 1 빼서 다시 바꾸면 원래 상태 값으로 돌아온다
              this._statusAjaxResponse(orderNum, self, _this);
            } else if (Number(status) == changeStatus) {
              this._statusAjaxResponse(orderNum, self, _this);
            } else {
              Ext.Msg.alert("알림", "지금 변경할 수 없는 상태값 입니다.");
            }
          }
        }
      } else {
        Ext.Msg.alert(
          "알림",
          "입금이 확인되지 않아 상태를 변경할 수 없습니다."
        );
      }
    };

    /**
     * 수정상태가 아니면 상태변경 버튼을 사용할 수 없다.
     */
    if (this.win.action == "edit") {
      //자료복사를 클릭하고 변환상태가 변환중 일때는 버튼을 클릭할 수 없다

      if (this.code == 2 && this.toggle == false) {
        this.disable();
      } else {
        this.enable();
      }
    } else {
      this.disable();
    }

    Custom.ToggleButton.superclass.initComponent.call(this);
  },
  _buttonChange: function () {
    var _this = this;

    var win = _this.win;

    var code = _this.code;

    if (win.action == "edit") {
      var status = win.orderRecord.get(status);

      if (code <= Number(status)) {
        _this.text = _this.cancelText;
        _this.toggle = false;
      } else {
        _this.text = _this.text;
        _this.toggle = true;
      }
    }
  },
  _statusAjaxResponse: function (orderNum, self, _this) {
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

            _this.onAfterSave();
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
  }
});

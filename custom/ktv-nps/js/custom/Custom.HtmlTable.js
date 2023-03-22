(function () {
  Ext.ns("Custom");

  Custom.HtmlTable = Ext.extend(Ext.BoxComponent, {
    // properties
    price: 0,

    constructor: function (config) {
      this.border = false;

      Ext.apply(this, {}, config || {});

      this.html = this._makeHtml();
      this.self = null;
      Custom.HtmlTable.superclass.constructor.call(this);
    },

    _makeHtml: function (price) {
      var html = [];
      var _this = this;

      // 테이블 헤더 데이터
      var tableHeaderData = ["은행명", "계좌번호", "예금주", "총액(원)"];

      // 테이블의 값 데이터
      var tableValueData = ["신한은행", "389-01-131011", "한국정책방송원", "0"];

      html.push('<div class="custom-html-panel">');
      html.push('<table class="custom-table">');
      // header 시작
      html.push("<thead>");
      html.push('<tr style="background:#eeeeee">');
      Ext.each(tableHeaderData, function (value, idx) {
        html.push("<th>" + value + "</th>");
      });
      html.push("</tr>");
      html.push("</thead>");
      // header 끝

      // body 시작
      html.push("<tbody>");
      html.push("<tr>");
      Ext.each(tableValueData, function (value, idx) {
        if (_this.action == "edit") {
          // 총액(원) 컬럼일때...
          if (idx === 3) {
            if (_this.inputFormWindow.orderRecord.get("receipt_amt") == null) {
              var defaultValue = value;
              html.push("<th>" + defaultValue + "</th>");
            } else {

              html.push("<th>" + _this.orderRecord.get('receipt_amt') + "</th>");
            }
          } else {
            html.push("<th>" + value + "</th>");
          }
        } else {
          html.push("<th>" + value + "</th>");
        }
      });
      html.push("</tr>");

      html.push("</tbody>");
      // body 끝

      html.push("</table>");
      html.push("</div>");

      return html.join("");
    }
  });
})();
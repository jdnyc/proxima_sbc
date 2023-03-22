(function () {
  Ext.ns("Custom");

  Custom.HtmlTable = Ext.extend(Ext.BoxComponent, {
    // properties
    /*
    {
      header: '상품코드',
      dataIndex: 'itemCd',
      align: 'center',
      width: 70
    }
    */
    columns: null,

    valueFieldName: null,

    // private variables
    _data: [],

    constructor: function (config) {
      this.addEvents("select");

      this.border = false;

      Ext.apply(this, {}, config || {});

      Custom.HtmlTable.superclass.constructor.call(this);
    },

    setColumns: function (columns) {
      this.columns = columns;
    },

    renderTable: function (data) {
      var _this = this;
      var html = this._makeHtml(data);
      this.update(html);

      // var code = document.getElementById("video_code");
      // code.on({
      //   click: function() {
      //     alert("ok");
      //   }
      // });

      this._data = data;

      var el = this.getEl();
      el.on({
        click: function (e, t, o) {
          if (t.tagName !== "A") {
            return;
          }
          var key = t.getAttribute("key");
          var subKey = t.getAttribute("sub-key");

          var record = null;
          if (subKey === "undefined") {
            // 메인 노드에 정보가 있을때
            record = new Ext.data.Record(_this._data[key], key);
          } else {
            // 서브 노드에 정보가 있을때
            record = new Ext.data.Record(_this._data[key].videos[subKey], key);
          }
          _this.fireEvent("select", _this, record, key);
        }
      });
    },

    _makeHtml: function (data) {
      if (Ext.isEmpty(data)) {
        return '<span style="color: white;">조회된 데이터가 없습니다.</span>'
      }
      var html = [];
      var _this = this;
      /*
      header: '상품코드',
      dataIndex: 'itemCd',
      align: 'center',
      width: 70
      */
      html.push('<div class="custom-html-panel">');
      html.push('<table class="custom-table">');
      // header 시작
      html.push("<thead>");
      html.push('<tr style="background:#eeeeee">');
      Ext.each(this.columns, function (col, idx) {
        html.push("<th>" + col.header + "</th>");
      });
      html.push("</tr>");
      html.push("</thead>");
      // header 끝

      // body 시작
      html.push("<tbody>");
      /*
      data json 구조
      [{
        field1: 1,
        field2: '2',
        field3: [
          '3','4'
        ],
        field4: [
          5,7
        ]
      },{
        field1: 1,
        field2: '2',
        field3: 3,
        field4: 4
      }]
      */
      if (data) {
        Ext.each(data, function (item, idx) {
          var rowSpan = _this._getMaxRowSpan(item);
          item.key = idx;
          // console.log("item", item, rowSpan);
          // rowSpan만큼 순회 하면서 td를 추가한다.
          for (var arrIdx = 0; arrIdx < rowSpan; arrIdx++) {
            html.push("<tr>");
            Ext.each(_this.columns, function (col, idx) {
              var cellValue = _this._getCellValue(col.dataIndex, item, arrIdx);
              // console.log("col", col);
              // console.log("idx", idx);
              // console.log("cellValue", cellValue);
              var td = "<td";

              td += _this._getStyle(col);

              if (!Ext.isEmpty(cellValue.value)) {
                if (arrIdx === 0) {
                  if (!cellValue.isArray) {
                    td += ' rowspan="' + rowSpan + '">';
                  } else {
                    td += ">";
                  }
                  td += cellValue.value + "</td>";
                } else if (cellValue.isArray) {
                  td += ">" + cellValue.value + "</td>";
                } else {
                  td = null;
                }
              } else {
                td = '<td>&nbsp;</td>';
              }
              if (!Ext.isEmpty(td)) {
                html.push(td);
              }
              // console.log("td", td);
            });
            html.push("</tr>");
          }
        });
      }
      html.push("</tbody>");
      // body 끝

      html.push("</table>");
      html.push("</div>");

      return html.join("");
    },

    _getStyle: function (col) {
      var style = "";
      if (!col.align && !col.width) {
        return style;
      }

      if (col.align === "center") {
        style += "text-align: center;";
      }

      if (col.width && col.width >= 0) {
        style += 'width="' + col.width + '"px;';
      }

      if (style !== "") {
        style = ' style="' + style + '"';
      }

      return style;
    },

    _getMaxRowSpan: function (item) {
      // item은 1depth까지의 배열만 허용된다.
      var keys = Object.keys(item);
      var maxRowSpan = 1;
      Ext.each(keys, function (key) {
        var tmpValue = item[key];
        if (Array.isArray(tmpValue) && tmpValue.length > 0) {
          // console.log(maxRowSpan, tmpValue.length);
          if (maxRowSpan < tmpValue.length) {
            maxRowSpan = tmpValue.length;
            return false;
          }
        }
      });
      return maxRowSpan;
    },

    _getCellValue: function (dataIndex, item, rowIdx) {
      var _this = this;
      var keys = Object.keys(item);
      var value = {
        isArray: false,
        value: null
      };
      Ext.each(keys, function (key) {
        // console.log(dataIndex);
        //console.log(key);
        // console.log(key === dataIndex);
        // console.log(item[key]);
        var tmpValue = item[key];
        if (Array.isArray(tmpValue) && tmpValue.length > 0) {
          var subItem = tmpValue[rowIdx];
          subItem.key = item.key;
          subItem.subKey = rowIdx;
          value = _this._getCellValue(dataIndex, subItem, rowIdx);
          value.isArray = true;
          return value === null;
        }

        if (key === dataIndex) {
          value.isArray = false;
          value.value = tmpValue;
          return false;
        }
      });

      if (_this.valueFieldName && dataIndex === _this.valueFieldName) {
        // console.log("item", item);
        // console.log("item.key", item.key);
        if (item.key !== undefined) {
          value.value =
            '<a key="' +
            item.key +
            '" sub-key="' +
            item.subKey +
            '" href="javascript:void(0);" style="color: blue">' +
            value.value +
            "</a>";
        } else {
          value.value = '<a href="javascript:void(0);" style="color: blue">' + value.value + "</a>";
        }
      }
      return value;
    }
  });

  Ext.reg("c-item-list-grid", Custom.HtmlTable);
})();
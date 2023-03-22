(function () {
  Ext.ns("Custom");
  /**  커스텀 트리콤보
   *  기존 ux 트리콤보를 확장
   * 트리로더에서 계층형 노드정보를 한번에 로드하여 사용
   * xtype c-tree-combo
   * name 속성값을 로드할 코드명으로 사용함 대문자 처리
   * 처음 value 속성으로 노드를 로드하며
   * 이후엔 setNode를 사용하여 node 찾기 및 선택 가능
   *
   */
  Custom.TreeCombo = Ext.extend(Ext.ux.TreeCombo, {
    // Properties
    fieldLabel: null,
    icon: null,
    path: null,
    name: null,
    value: "0",
    pathSeparator: " > ",
    rootVisible: false,
    rootId: "0",
    rootText: "Root",
    url: "/api/v1/data-dic-code-sets/{code}/code-nodes",
    requestMethod: "POST",
    params: {
      sorters: ["depth", "parnts_id", "sort_ordr"],
      dir: "ASC"
    },
    listeners: {
      afterrender: function (self) { },
      click: function (self) { },
      select: function (self, node) { }
    },
    constructor: function (config) {
      Ext.apply(this, {}, config || {});
      Custom.TreeCombo.superclass.constructor.call(this);
    },

    initComponent: function (config) {
      this._initItems(config);
      Custom.TreeCombo.superclass.initComponent.call(this);
    },
    _initItems: function (config) {
      var _this = this;
      //투트 생성
      _this.root = _this._makeRoot(_this.rootId, _this.rootText, true);
      //첫값 전달
      if (_this.value == null || _this.value == undefined) {
        _this.params.selId = _this.rootId;
      } else {
        _this.params.selId = _this.value;
      }

      //url 생성
      _this.url = _this.url.replace("{code}", _this.name.toUpperCase());
      //로더 생성
      _this.loader = _this._makeTreeLoader(
        _this.url,
        _this.requestMethod,
        _this.params
      );
    },

    getFindNode: function (node, value) {
      var findNode = node.findChild("code", value);
      if (findNode != null) {
        return findNode;
      } else {
        node.eachChild(function (child) {
          var start = 0;
          if (child.attributes.code) {
            var length = child.attributes.code.length;
            if (child.attributes.dp > 1) {
              start = start + length * (child.attributes.dp - 1);
            }
            if (value.substr(start, length) == child.attributes.code) {
              findNode = child;
              return;
            }
          }
        });
      }
      return findNode;
    },
    setNode: function (value) {
      var findNode = null;
      var checkNode = this.root;
      var isEnd = true;

      while (isEnd) {
        if (checkNode && checkNode.attributes.code == value) {
          findNode = checkNode;
          isEnd = false;
        } else if (checkNode && checkNode.hasChildNodes()) {
          checkNode = this.getFindNode(checkNode, value);
          if (checkNode) {
            checkNode.expand();
          }
        } else {
          isEnd = false;
        }
      }
      if (findNode) {
        findNode.select();
        this.setValue(findNode.id);
      }
      return findNode;
    },

    _makeTreeLoader: function (url, requestMethod, params) {
      var _this = this;
      var treeLoader = new Ext.tree.TreeLoader({
        url: url,
        requestMethod: requestMethod,
        baseParams: params,
        listeners: {
          load: function (self, node, response) {
            if (_this.value) {
              nodeId = _this.value;
            } else {
              nodeId = node.id;
            }
            n = node.findChild("id", nodeId);
            if (n) {
              n.select();
              _this.setValue(nodeId);
              _this.setRawValue(n.text);
            } else {
              _this.setNode(nodeId);
            }
          }
        }
      });

      return treeLoader;
    },
    _makeRoot: function (id, text, expand) {
      var root = new Ext.tree.AsyncTreeNode({
        id: id,
        text: text,
        expanded: expand
      });
      return root;
    },
    setValue: function (v) {
      this.startValue = this.value = v;
      if (this.treePanel) {
        var n = this.treePanel.getNodeById(v);
        if (n) {
          var subRaw = this.treePanel.pathSeparator.length;
          if (this.treePanel.rootVisible == false) {
            subRaw = subRaw + this.treePanel.root.text.length;
          }
          this.setRawValue(n.getPath("text").substr(subRaw));
        }
      }
    },

    getValue: function () {
      return this.value;
    },

    onTreeNodeClick: function (node, e) {
      //this.setRawValue(node.getPath('text').substr(6));
      this.value = node.id;
      this.fireEvent("select", this, node);
      this.setValue(this.value);
      this.collapse();
      this.focus();
      // 해당 항목은 extjs에서 트리노드 형태의 항목중 2댑스의 항목을 클릭할때 포커스를 자동으로 빼버리는 현상때문에 추가함
      // 딜레이를 0.1초 주고 포커스를 다시 맞춰줌 extjs 종특이라 특별한 방법이 없음
      sleep(100);
      this.focus();
    }
  });

  Ext.reg("c-tree-inner-combo", Custom.TreeCombo);
})();

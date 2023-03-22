(function() {
  Custom.TreeCombo2 = Ext.extend(Ext.form.CompositeField, {
    // 검색창 숨김:false, 보임:true
    searchMode: true,
    searchFieldId: Ext.id(),

    // Properties
    fieldLabel: null,
    icon: null,
    path: null,
    name: null,
    value: '0',
    pathSeparator: ' > ',
    rootVisible: false,
    rootId: '0',
    rootText: 'Root',
    url: '/api/v1/data-dic-code-sets/{code}/code-nodes',
    requestMethod: 'POST',
    params: {
      sorters: ['depth', 'parnts_id', 'sort_ordr'],
      dir: 'ASC'
    },
    layout: 'hbox',
    rootVisible: false,
    newLoaderEvent: false,
    constructor: function(config) {
      Ext.apply(this, {}, config || {});
      Custom.TreeCombo2.superclass.constructor.call(this);
    },
    initComponent: function(config) {
      this._initialize();
      Custom.TreeCombo2.superclass.initComponent.call(this);
    },
    _initialize: function() {
      var _this = this;
      var searchField = _this._makeSearchField();
      this.items = [searchField, _this._makeTreeCombo()];
    },
    _makeTreeCombo: function() {
      var _this = this;
      if (_this.allowBlank === false) {
        var allowBlank = false;
      } else {
        var allowBlank = true;
      }
      var treeCombo = new Custom.TreeCombo({
        flex: 5,
        hidden: _this.hidden,
        name: _this.name,
        url: _this.url,
        params: _this.params,
        rootId: _this.rootId,
        rootText: _this.rootText,
        allowBlank: allowBlank,
        value: _this.value,
        rootVisible: _this.rootVisible,
        listeners: {
          select: function(treeCombo, node) {
            _this.setValue(node.id);
          }
        }
      });
      if (_this.newLoaderEvent) {
        treeCombo
          .getTree()
          .getLoader()
          .un('load');
        treeCombo
          .getTree()
          .getLoader()
          .on('load', function(self, node, response) {
            _this.newLoaderEvent(_this, self, node, response);
          });
      }
      return treeCombo;
    },
    _makeSearchField: function() {
      var _this = this;
      var searchFieldHidden = false;
      if (_this.readOnly == true || _this.searchMode == false) {
        searchFieldHidden = true;
      }
      var searchField = new Ext.form.TextField({
        hidden: searchFieldHidden,
        flex: 1,
        itemId: 'searchField',
        enableKeyEvents: true,
        emptyText: '검색어를 입력해주세요.',
        listeners: {
          specialkey: function(f, e) {
            if (e.getKey() == e.ENTER) {
              _this._doSearch();
            }
          },
          keyup: function(f, e) {
            if (e.getKey() == e.BACKSPACE) {
              _this._searchAfterListShow();
            }

            if (e.getKey() == e.UP) {
            }

            if (e.getKey() == e.DOWN) {
              _this._arrowSelect();
            }

            if (!e.isSpecialKey()) {
              _this._searchAfterListShow();
            }
          },
          afterrender: function(field) {
            field.getEl().on('click', function(event, el) {
              _this._searchAfterListShow();
            });
          }
        }
      });
      return searchField;
    },
    _getTreeCombo: function() {
      var _this = this;
      var compositeItems = _this.items.items;
      var treeCombo = null;
      Ext.each(compositeItems, function(compositeItem) {
        if (compositeItem.name == _this.name) {
          treeCombo = compositeItem;
        }
      });
      return treeCombo;
    },
    _getSearchField: function() {
      var _this = this;

      var _this = this;
      var compositeItems = _this.items.items;
      var searchField = null;
      Ext.each(compositeItems, function(compositeItem) {
        if (compositeItem.itemId == 'searchField') {
          searchField = compositeItem;
        }
      });
      return searchField;

      // return searchField;
    },
    _searchNode: function() {
      var _this = this;
      var tree = _this._getTreeCombo().getTree();
      var node = tree.getRootNode();
      var loader = tree.getLoader();
      var searchText = _this._getSearchField().getValue();
      var nodes = node.childNodes;
      Ext.each(nodes, function(child) {
        var name = child.text;
        if (name.toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
          child.getUI().show();
        } else {
          child.getUI().hide();
        }
      });
      loader.doPreload(node);
    },
    _searchAfterListShow: function() {
      var _this = this;
      _this._searchNode();
      _this._getTreeCombo().onTriggerClick();
    },
    _doSearch: function() {
      var _this = this;

      var tree = _this._getTreeCombo().getTree();
      var node = tree.getRootNode();
      var nodes = node.childNodes;

      var searchCount = 0;
      var searchNodeId = null;
      Ext.each(nodes, function(child) {
        if (!child.hidden) {
          searchNodeId = child.id;
          searchCount = searchCount + 1;
          searchNode = child;
        }
      });

      // 검색항목이 없을떄
      if (searchCount == 0) {
        return Ext.Msg.alert('알림', '검색된 항목이 없습니다.');
      }

      // 검색항목이 하나 일때
      if (searchCount == 1) {
        // _this._getTreeCombo().setNode(searchNodeId);
        var node = _this._firstNode();

        _this.setValue(node.id);
        _this._getTreeCombo().collapse();
      } else {
        // 목록은 있으나 선택을 안했을 시
        // var sm = _this._getTreeCombo().getTree().getSelectionModel();
        // if (Ext.isEmpty(sm.getSelectedNode())) {
        //   Ext.Msg.alert('알림', '목록을 선택해주세요.');
        // };
      }
    },
    _firstNode: function() {
      var _this = this;
      var tree = _this._getTreeCombo().getTree();
      var node = tree.getRootNode();
      var nodes = node.childNodes;

      var listNodes = [];
      Ext.each(nodes, function(child) {
        if (!child.hidden) {
          listNodes.push(child);
          return false;
        }
      });
      listNodes[0].select();
      return listNodes[0];
    },
    _arrowSelect: function() {
      var _this = this;
      var tree = _this._getTreeCombo().getTree();
      var node = tree.getRootNode();
      var nodes = node.childNodes;

      var listNodes = [];
      Ext.each(nodes, function(child) {
        if (!child.hidden) {
          listNodes.push(child);
          return false;
        }
      });
      listNodes[0].select();
    },
    setValue: function(v) {
      var _this = this;
      _this.startValue = _this.value = v;
      _this._getTreeCombo().startValue = _this._getTreeCombo().value = v;
      if (_this._getTreeCombo().treePanel) {
        var n = _this
          ._getTreeCombo()
          .getTree()
          .getNodeById(v);
        if (n) {
          var subRaw = _this._getTreeCombo().pathSeparator.length;
          if (_this._getTreeCombo().rootVisible == false) {
            subRaw = subRaw + _this._getTreeCombo().root.text.length;
          }
          _this._getTreeCombo().setRawValue(n.getPath('text').substr(subRaw));
        }
      }
    },
    getValue: function() {
      return this.value;
    }
  });
  Ext.reg('c-tree-combo', Custom.TreeCombo2);
})();

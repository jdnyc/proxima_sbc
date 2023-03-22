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
      var rootNodeId = node.id;
      var loader = tree.getLoader();
      var searchText = _this._getSearchField().getValue();
      var sm = tree.getSelectionModel();
      var depth1Nodes = node.childNodes;
      var filterNodes = new Object();
      var filterNodesPath = [];

      //처음에 다 열어서 하위 노드 들도 로드
      tree.expandAll();
      
      // 전체 노드들 초기화
      var allNodes = tree.nodeHash;
      tree.collapseAll();
      if(!_this.rootVisible){
        // 루트 노드가 숨김처리 이면 검색할 객체에서 제외
        delete allNodes[rootNodeId];
      };
      
      // 전체 노드를 반복문 돌려서 필터링 
      for(var nodeId in allNodes){
        var n = allNodes[nodeId];
        if (n.text.toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
          filterNodesPath.push(n.getPath());
        }else{
          n.getUI().hide();
        }
      }
      
      Ext.each(filterNodesPath, function(filterNodePath){
        var nodeIds = filterNodePath.split('>');
        Ext.each(nodeIds,function(nodeId){
          if("" != nodeId.trim()){
            if(!_this.rootVisible){
              // root를 숨겼다면 검색하지 않으니까
              if("0" != nodeId.trim()){
                tree.getNodeById(String(nodeId.trim())).getUI().show();
                if(searchText != ""){
                  
                  tree.getNodeById(String(nodeId.trim())).expand();
                  // tree.getNodeById(String(nodeId.trim())).getUI().expand();
                }
              }
            }else{
              tree.getNodeById(String(nodeId.trim())).getUI().show();
            }
          }
        });
      });

      // 선택된 노드 값 펼쳐서 보여주기
      if(!_this.rootVisible){
        if(_this.value != rootNodeId){
          if(!Ext.isEmpty(sm.getSelectedNode())){
            var selectedNodePath = sm.getSelectedNode().getPath();
            var selectedNodeIds = selectedNodePath.split('>');
            Ext.each(selectedNodeIds,function(selectedNodeId){
              var id = String(selectedNodeId.trim());
              if((rootNodeId != id) && (id != "")){
                tree.getNodeById(id).expand();
                // tree.getNodeById(id).getUI().expand();
              }   
            });
          };
        }
      }
        

      


// 2뎁스만 되던거
      // var depthCheck = false;
      //   Ext.each(depth1Nodes,function(depth1Node){
          
      //       if(depth1Node.hasChildNodes()){
      //           depthCheck=true;
            
      //         // 2뎁스 자식 노드 활성화
      //         if(!depth1Node.childrenRendered){
      //           if(!depth1Node.isExpanded()){
      //             depth1Node.expand();
      //             depth1Node.collapse();
      //           }
      //         }
              
              
      //         depth1Node.getUI().hide();
      //         // 뎁스 1 필터링
      //         if (depth1Node.text.toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
      //             filterNodes[depth1Node.id] = depth1Node.getDepth();
      //         }
      //         var depth2Node = depth1Node.childNodes;
      //         Ext.each(depth2Node, function(depth2Node){
      //           depth2Node.getUI().hide();
      //           // 뎁스 2 필터링
      //           if (depth2Node.text.toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
      //             filterNodes[depth2Node.id] = depth2Node.getDepth();
      //           }
      //         });
      //       }else{
      //         // 1뎁스 밖에 없는 노드 따로 처리
      //         if (depth1Node.text.toLowerCase().indexOf(searchText.toLowerCase()) != -1) {
      //           depth1Node.getUI().show();
      //         } else {
      //           depth1Node.getUI().hide();
      //         }
      //       }
          
      //   });

      //   if(depthCheck){
          
      //     for(var nodeId in filterNodes){
      //       var findNode = node.findChild('id',nodeId,true);
            
      //       if(filterNodes[nodeId] == 1){
      //         node.findChild('id',nodeId).getUI().show();
      //         // 1뎁스 일 경우
      //         continue;
      //       }else{
      //         findNode.parentNode.getUI().show();
      //         if(!Ext.isEmpty(searchText)){
      //           findNode.parentNode.getUI().expand();
      //         }
      //       }
      //       findNode.getUI().show();
      //     }
      //   }

      // Ext.each(depth1Nodes, function(depth1Node){
      //   var depth2NodeCount = depth1Node.childNodes.length;
      //   var depth2NodeCheckCount = 0;
      //   Ext.each(depth1Node.childNodes,function(depth2Node){
      //     if(!depth2Node.hidden){
      //       depth2NodeCheckCount++;
      //     };
      //   });
      //   if(depth2NodeCount == depth2NodeCheckCount){
      //     if(Ext.isEmpty(tree.getSelectionModel().getSelectedNode())){
      //       depth1Node.getUI().collapse();
      //     }else{
      //       var selectedNode = tree.getSelectionModel().getSelectedNode();
      //       if(!selectedNode.hasChildNodes()){
      //         var selectedParentNode = selectedNode.parentNode;
      //       };
      //       if((selectedParentNode != depth1Node) && (depth1Node.hasChildNodes())){
              
      //         depth1Node.getUI().collapse();
      //       }
      //     };
      //   }
      // });  
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
      var hasChild = false;
      var expandNodes = tree.nodeHash;
      var expandNodeDepthAndId = _this.expandNodePathes();
      
      if(Ext.isEmpty(expandNodeDepthAndId)){
        return Ext.Msg.alert('알림', '검색된 항목이 없습니다.');
      };
      
      var oneCheck = true;
      var oneNodeId = "0";
      
      Ext.each(expandNodeDepthAndId,function(expandNodes,depth){

        if(!_this.rootVisible){
          if(depth != 0){
            oneNodeId = expandNodes[0];
            if(expandNodes.length != 1){
              oneCheck = false;
            };
          }
        }
      });
      
      if(oneCheck){
        var searchOneNode = tree.getNodeById(oneNodeId);
        searchOneNode.select();
        _this.setValue(searchOneNode.id);
      }else{
        return Ext.Msg.alert('알림', '목록을 선택해주세요.');
      }


      // 검색후 하나 일때
      // Ext.each(nodes, function(child) {
      //   if(child.hasChildNodes()){
      //     var depth2Nodes = child.childNodes;
      //     Ext.each(depth2Nodes,function(depth2Node){
      //       if(!depth2Node.hidden){
      //         searchNodeId = depth2Node.id;
      //         searchCount = searchCount + 1;
      //         searchNode = depth2Node;
      //       };
      //     });
      //     if(searchCount == 0){
      //       // 자식이 없을때 부모는 있는지
      //       if (!child.hidden) {
      //         searchNodeId = child.id;
      //         searchCount = searchCount + 1;
      //         searchNode = child;
      //       }
    
      //     }
      //   }else{
      //     if (!child.hidden) {
      //       searchNodeId = child.id;
      //       searchCount = searchCount + 1;
      //       searchNode = child;
      //     }
      //   }
      // });

      // 검색항목이 없을떄
      // if (searchCount == 0) {
      //   return Ext.Msg.alert('알림', '검색된 항목이 없습니다.');
      // }

      // // 검색항목이 하나 일때
      // if (searchCount == 1) {
      //   // _this._getTreeCombo().setNode(searchNodeId);
      //   var node = _this._firstNode();
      //   _this.setValue(node.id);
        
      //   if(node.hasChildNodes()){
      //     searchNode.select();
      //     _this.setValue(searchNode.id);
      //   }
        
      //   _this._getTreeCombo().collapse();
      // } else {
      //   // 목록은 있으나 선택을 안했을 시
      //   // var sm = _this._getTreeCombo().getTree().getSelectionModel();
      //   // if (Ext.isEmpty(sm.getSelectedNode())) {
      //   //   Ext.Msg.alert('알림', '목록을 선택해주세요.');
      //   // };
      // }
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
      if(Ext.isEmpty(v)){
        return false;
      };
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
    expandNodePathes: function(){
      var tree = this._getTreeCombo().getTree();
      var nodePathArray = [];
      var nodeIdArray = [];
      var nodeHash = tree.nodeHash;
      var nodePathArray = Array();
      for(var nodeId in nodeHash){
        var node = nodeHash[nodeId];
        if(!node.hidden){
          nodePathArray[node.getDepth()] = [];
        }
      }
      for(var nodeId in nodeHash){
        var node = nodeHash[nodeId];
        if(!node.hidden){
          nodePathArray[node.getDepth()].push(nodeId);
        }
      }
      return nodePathArray;
    },
    getValue: function() {
      return this.value;
    }
  });
  Ext.reg('c-tree-combo', Custom.TreeCombo2);
})();

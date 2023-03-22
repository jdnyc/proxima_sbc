(function () {
  Ext.ns("Custom");
  Custom.TagField = Ext.extend(Ext.Container, {
    // Properties
    layout: 'column',

    style: {
      padding: '5px 0px 0px 0px'
    },

    // private variables

    constructor: function (config) {

      config.style = Ext.apply({}, this.style, config.style);

      Ext.apply(this, {}, config || {});

      this._init();

      Custom.TagField.superclass.constructor.call(this);
    },

    initComponent: function () {
      Custom.TagField.superclass.initComponent.call(this);
    },

    insertTag: function (index, tagName) {
      if (this.tagExists(tagName)) {
        return;
      }
      var tagLabel = this._makeTagLabel(tagName);
      this.insert(index, tagLabel);
      this.doLayout();
    },

    addTag: function (tagName) {
      if (this.tagExists(tagName)) {
        return;
      }
      var tagLabel = this._makeTagLabel(tagName);
      this.add(tagLabel);
      this.doLayout();
    },

    getValue: function () {
      var tagNames = [];
      if (!this.items) {
        return tagNames;
      }
      this.items.each(function (item) {
        tagNames.push(item.tagName);
      });
      return tagNames;
    },

    setValue: function (tags) {
      if (!tags || !Ext.isArray(tags)) {
        return;
      }

      this.removeAll();

      var _this = this;
      Ext.each(tags, function (tag) {
        _this.addTag(tag);
      });
    },

    getTagCount: function () {
      return this.items.getCount();
    },

    tagExists: function (tagName) {
      var tags = this.getValue();
      return (tags.indexOf(tagName) >= 0);
    },

    _makeTagLabel: function (tagName) {
      var _this = this;
      var tagLabel = new Custom.TagLabel({
        tagName: tagName,
        listeners: {
          close: function (tag) {
            _this.remove(tag);
            _this.doLayout();
          }
        }
      });
      tagLabel.on('close', _this._onCloseTag, this);
      return tagLabel;
    },

    _cloneTagLabel: function (tag) {
      if (!tag) {
        return;
      }
      return this._makeTagLabel(tag.tagName);
    },

    _init: function () {

      var _this = this;
      // event listeners
      this.listeners = {
        render: _this._initDropZone
      };

    },

    _onCloseTag: function (tag) {
      tag.un('close', this._onCloseTag, this);
      this.remove(tag);
      this.doLayout();
    },

    _changeOrder: function (fromIndex, toIndex) {
      var fromTag = this.getComponent(fromIndex);
      var toTag = this._cloneTagLabel(fromTag);
      this.remove(fromTag);
      this.insert(toIndex, toTag);

      this.doLayout();
      return toTag;
    },

    _initDropZone: function (self) {
      self.dropZone = new Ext.dd.DropZone(self.getEl(), {

        getTargetFromEvent: function (e) {
          return self.getEl();
        },

        // onNodeOver: function (target, dd, e, data) {
        //   //return Ext.dd.DropZone.prototype.dropAllowed;
        //   var dragEl = Ext.get(data.sourceEl);

        //   // 컨테이너 기준 x 좌표를 찾는다.
        //   var x = e.xy[0];
        //   console.log('x', x);
        //   var SPACE = 20;

        //   draggingEl = {
        //     leftBound: ((dragEl.getLeft() - SPACE) < 0) ? 0 : (dragEl.getLeft() - SPACE),
        //     rightBound: dragEl.getRight() + SPACE
        //   }

        //   // 실제 콤포넌트의 인덱스를 찾아서
        //   var btnId = dragEl.id;

        //   var fromIndex = self.items.findIndex('id', btnId);
        //   console.log('fromIndex', fromIndex);

        //   var toIndex = fromIndex;
        //   if (x < draggingEl.leftBound && toIndex > 0) {
        //     toIndex--;
        //   } else if (x > draggingEl.rightBound && toIndex < (self.items.length - 1)) {
        //     toIndex++;
        //   }
        //   console.log('toIndex', toIndex);

        //   // 인덱스가 변경되었으면 오더링을 다시 해준다.
        //   if (toIndex >= 0 && fromIndex >= 0 && fromIndex !== toIndex) {
        //     // hbox일때는 아래처럼 가능
        //     // var reorderInfo = {};
        //     // reorderInfo[fromIndex] = toIndex;
        //     // self.items.reorder(reorderInfo);
        //     // self.doLayout();
        //     // column layout일 경우...
        //     self._changeOrder(fromIndex, toIndex);
        //   }
        //   return Ext.dd.DropZone.prototype.dropAllowed;
        // },
        onNodeOver: function (target, dd, e, data) {
          //return Ext.dd.DropZone.prototype.dropAllowed;
          var dragEl = Ext.get(data.sourceEl);

          // 컨테이너 기준 x 좌표를 찾는다.
          var x = e.xy[0];
          var y = e.xy[1];

          // 무한 스왑 문제를 해결하기 위한 방향 변수 1은 오른쪽 -1은 왼쪽 이동
          var directionX = 1;
          var directionY = 1;
          var beforeX = x;
          var beforeY = y;

          // 무한 스왑 문제를 해결하기 위한 tmp 변수
          if (data.sourceEl.x) {
            beforeX = data.sourceEl.x;
            directionX = beforeX < x ? 1 : -1;

            if (beforeX === x) {
              return Ext.dd.DropZone.prototype.dropAllowed;
            }
          }

          if (data.sourceEl.x) {
            beforeY = data.sourceEl.y;
            directionY = beforeY < y ? 1 : -1;
          }

          console.log('data.sourceEl.x', data.sourceEl.x);
          console.log('x', x);
          console.log('direction', directionX);
          // console.log('x, y', x, y);
          var SPACE = 20;

          // draggingEl = {
          //   leftBound: ((dragEl.getLeft() - SPACE) < 0) ? 0 : (dragEl.getLeft() - SPACE),
          //   rightBound: dragEl.getRight() + SPACE
          // }

          // 실제 콤포넌트의 인덱스를 찾아서
          var btnId = dragEl.id;
          // console.log('btnId', btnId);

          var fromIndex = self.items.findIndex('id', btnId);
          // console.log('fromIndex', fromIndex);

          var toIndex = fromIndex;

          // 마우스 포인터 위치에 따라 모든 tag들에서 마우스 포인터가 위치한 곳의 tag와 
          // 순서를 바꾼다.

          var idx = 0;
          var canChange = false;
          self.items.each(function (tag) {

            var box = tag.getBox();
            // 높이 값은 모든 태그가 동일해서 문제가 없지만 
            // 가로 태그는 길이가 다르기 때문에 두 태그 경계에서 무한 스와핑 되는 문제가 발생하므로
            // 태그의 중심을 지날때만 작동하도록 한다.
            if (x > box.x && x < box.x + box.width &&
              y > box.y && y < box.y + box.height) {
              // 수평 이동과 수직이동 로직을 나눠야 하겠다.
              // 수직이동을 우선으로 처리해야 한다.
              // console.log('dragEl.y', dragEl.getY());
              if (directionY > 0) {
                if (y > dragEl.getY() + box.height) {
                  canChange = true;
                  return false;
                }
              } else {
                if (box.y + box.height < dragEl.getY()) {
                  canChange = true;
                  return false;
                }
              }

              // 경계 내로 들어오면 이전 위치와 비교해서 중앙을 넘어갈 때만 swap시킨다.
              var centerX = box.x + Math.round(box.width / 2);
              // console.log('centerX', centerX);
              // console.log('beforeX', beforeX);
              // console.log('X', x);
              // console.log('direction', direction);
              if (directionX > 0) {
                // 왼쪽 --> 오른쪽 이동 시
                // 현재 커서가 위치한 태그의 중앙을 넘기는 순간 canChange값 true로 설정
                canChange = (beforeX < centerX && x >= centerX);
              } else {
                // 오른쪽 --> 왼쪽 이동 시
                // 현재 커서가 위치한 태그의 중앙을 넘기는 순간 canChange값 true로 설정
                canChange = (beforeX > centerX && x <= centerX);
              }
              // console.log('@tag: ', tag);
              // console.log('@box: ', box);
              // console.log('@idx1: ', idx);
              return false;
            }
            idx++;
          });

          if (canChange) {

            // console.log('@idx: ', idx);
            toIndex = idx;
            if (toIndex >= 0 && fromIndex >= 0 && fromIndex !== toIndex) {

              var toTag = self._changeOrder(fromIndex, toIndex);
              // 드래그 소스를 새로 생성된 태그로 교체해줘야 계속 작동을 한다
              data.sourceEl = toTag.getEl();
            }

          }
          data.sourceEl.x = x;
          data.sourceEl.y = y;
          return Ext.dd.DropZone.prototype.dropAllowed;
        },

        onNodeDrop: function (target, dd, e, data) {
          // !Important: We assign the dragged element to be set to new drop position
          return true;
        }

      });
    }

  });

  Ext.reg("c-tag-field", Custom.TagField);
})();
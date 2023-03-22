(function () {
  Ext.ns("Custom");
  Custom.TagPanel = Ext.extend(Ext.Container, {
    // Properties
    toolbarTitle: '',
    maxTagLength: 60,
    maxTagCount: 10,
    blacklistCharacters: ['@'],
    labelStyle: 'color: white; margin-left: 5px;',
    style: {
      padding: '5px'
    },
    // private variables

    constructor: function (config) {

      Ext.apply(this, {}, config || {});

      Custom.TagPanel.superclass.constructor.call(this);
    },

    initComponent: function () {

      this._initItems();
      Custom.TagPanel.superclass.initComponent.call(this);
    },

    getValue: function () {
      return this._tagField.getValue();
    },

    setValue: function (tags) {
      this._tagField.setValue(tags);
    },

    _initItems: function () {
      var titleLabel = new Ext.form.Label({
        text: this.toolbarTitle,
        style: {
          color: '#15a4fa'
        }
      });
      this._tagField = new Custom.TagField({
        style: {
          backgroundColor: '#333333',
        }
      });
      this._tagInput = this._makeTagInput();
      this.items = [{
          xtype: 'container',
          style: {
            padding: '5px',
            backgroundColor: '#1f1f1f'
          },
          items: [
            titleLabel,
            new Ext.Spacer({
              height: 5
            }),
            this._tagField
          ]
        },
        new Ext.Spacer({
          height: 5
        }),
        this._tagInput
      ];
    },

    _makeTagInput: function () {
      var _this = this;
      this._tagCombo = new Ext.form.ComboBox({
        width: 200,
        height: 30,
        store: Custom.Store.getVideoKeywordStore(),
        displayField: 'keyword',
        valueField: 'keyword',
        minChar: 1,
        typeAhead: false,
        emptyText: '',
        editable: true,
        hideTrigger: true,
        triggerAction: 'all',
        enableKeyEvents: true,
        _beforeKeyword: null,
        listeners: {
          keydown: function (self, e) {
            if (e.getKey() == e.ENTER && e.ctrlKey) {
              e.stopEvent();
              // getValue로 가져오면 서버에 있는 데이터만 태그에 넣을 수 있으므로 dom에서 가져오자.
              var tag = _this._tagCombo.el.dom.value;
              _this.addTag(tag);
            }
          },
          keyup: function (self, e) {
            var keyword = e.target.value;
            if (e.getKey() >= 37 && e.getKey() <= 40) {
              return;
            }
            e.stopEvent();
            if (e.getKey() == e.ENTER ||
              e.getKey() == e.ESC) {
              return;
            }

            if (self._beforeKeyword == keyword) {
              return;
            }
            self.getStore().load({
              params: {
                keyword: keyword
              }
            });
          }
        }
      });
      var btn = new Ariel.IconButton({
        text: '등록',
        handler: function (btn, e) {
          var tag = _this._tagCombo.el.dom.value;
          _this.addTag(tag);
        }
      });
      var tagInput = new Ext.form.CompositeField({
        hideLabel: true,
        items: [
          _this._tagCombo, btn
        ]
      });
      return tagInput;
    },

    insertTag: function (index, tag) {
      this.addTag(tag, index);
    },

    addTag: function (tag, index) {
      var _this = this;
      if (Ext.isEmpty(tag)) {
        return;
      }

      if (this._tagField.getTagCount() >= this.maxTagCount) {
        Ext.Msg.alert('알림', '동영상 키워드는 10개까지 입력할 수 있습니다.');
        return;
      }

      if (this.maxTagLength > 0 && getByteLength(tag) > this.maxTagLength) {
        Ext.Msg.alert('알림', '동영상 키워드는 최대 ' + this.maxTagLength + '바이트(한글기준' + (this.maxTagLength / 2) + '자)까지 입력할 수 있습니다.');
        return;
      }

      if (this._tagField.tagExists(tag)) {
        Ext.Msg.alert('알림', '이미 등록되어 있는 키워드 입니다.');
        return;
      }

      if (this._blacklistCharContained(tag)) {
        Ext.Msg.alert('알림', '동영상 키워드에 입력 제한된 텍스트가 포함 되어 있습니다.');
        return;
      }

      var params = {
        keyword: tag
      };
      Custom.Api.createVideoKeyword(params, function (self, res) {
        if (index !== undefined) {
          _this._tagField.insertTag(index, tag);
        } else {
          _this._tagField.addTag(tag);
        }
      });
      this._tagCombo.getStore().removeAll();
      this._tagCombo.setValue('');
      this._tagCombo.focus();
    },

    _blacklistCharContained: function (tag) {
      if (!this.blacklistCharacters && this.blacklistCharacters.length <= 0) {
        return;
      }

      var ret = false;
      Ext.each(this.blacklistCharacters, function (blacklistChar) {
        if (tag.indexOf(blacklistChar) >= 0) {
          ret = true;
          return false;
        }
      });

      return ret;
    }

  });

  Ext.reg("c-tag-panel", Custom.TagPanel);
})();
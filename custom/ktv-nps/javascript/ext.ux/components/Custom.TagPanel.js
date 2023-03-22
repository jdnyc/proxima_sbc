(function () {
  Ext.ns('Custom');
  // Custom.TagPanel = Ext.extend(Ext.form.TextField,    {
  //     width: 300
  // });
  Custom.TagPanel = Ext.extend(Ext.Container, {
    // Properties
    toolbarTitle: '',
    maxTagLength: 60,
    maxTagCount: 50,
    blacklistCharacters: ['@'],
    separator: '#',
    isShowSeparator: true, //라벨 표기시 구분자 포함여부
    isTrim: true, //공백제거
    autoHeight: true,
    theme: null,
    // labelStyle: 'color: white; margin-left: 5px;',
    style: {
      // padding: '5px'
    },
    listeners: {
      afterrender: function (self) {
        var keywordsStr = self.value;
        if (Ext.isEmpty(keywordsStr)) {
          return;
        }
        keywordsStr = keywordsStr.split(this.separator);
        self.setValue(keywordsStr);
      },
      //form에서 name으로 찾을때는 valueField로 찾아지기 때문에 valueField에서도 setValue 할시에 똑같이 처리
      setvalueinvaluefield: function (v, outSideSetValue) {
        if (outSideSetValue) {
          this.setValue(v);
        }
      }
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
      var _this = this;
      Ext.each(tags, function (tag) {
        _this.addTag(tag);
      });
      //   this._tagField.setValue(tags);
    },

    _initItems: function () {
      var titleLabel = new Ext.form.Label({
        text: this.toolbarTitle,
        style: {
          color: '#15a4fa'
        }
      });
      this._tagInput = this._makeTagInput();
      this._tagField = this._makeTagField();

      this._valueField = this._makeValueField();
      // return;
      this.items = [
        this._tagInput,
        {
          xtype: 'container',
          items: [
            titleLabel,
            new Ext.Spacer({
              height: 5
            }),
            this._tagField
          ]
        },
        this._valueField,
        new Ext.Spacer({
          height: 5
        })
      ];
    },
    _makeTagInput: function () {
      var _this = this;

      this._tagTextField = new Ext.form.TextField({
        //   width: 120,
        minChar: 1,
        flex: 1,
        emptyText: '',
        editable: true,
        enableKeyEvents: true,
        controlButtonDown: false,
        listeners: {
          specialkey: function (f, e) {
            if (e.getKey() == e.ENTER) {
              _this._ok();
            }
          },
          keyup: function (f, e) {
            if (e.getKey() == e.CONTROL) {
              f.controlButtonDown = false;
            }
          },
          keydown: function (f, e) {
            if (e.getKey() == e.CONTROL) {
              f.controlButtonDown = true;
            }
            if (f.controlButtonDown && e.getKey() == e.C) {
              var tempElem = document.createElement('textarea');
              tempElem.value = _this.getValueField().getValue();
              document.body.appendChild(tempElem);

              tempElem.select();
              document.execCommand('copy');
              document.body.removeChild(tempElem);
              f.focus();
            }
          }
        }
      });
      // var btn = new Ariel.IconButton({
      var btn = {
        xtype: 'button',
        text: '등록',
        width: 50,
        handler: function (btn, e) {
          _this._ok();
        }
      };
      var tagInput = new Ext.form.CompositeField({
        hideLabel: true,
        items: [
          _this._tagTextField,
          btn
          //   ,{xtype:'displayfield', value:'※ 대표키워드 등록 후 하단의 수정버튼을 누르세요.'}
        ]
      });
      return tagInput;
    },
    _makeValueField: function () {
      var _this = this;
      var valueField = new Ext.form.TextField({
        hidden: true,
        name: _this.name,
        value: _this.value,
        outSideSetValue: true,
        // hidden: true,
        applySetValue: function (v) {
          if (this.emptyText && this.el && !Ext.isEmpty(v)) {
            this.el.removeClass(this.emptyClass);
          }
          Ext.form.TextField.superclass.setValue.apply(this, arguments);
          this.applyEmptyText();
          this.autoSize();
          return this;
        },
        initValue: function () {
          this.outSideSetValue = false;
          if (this.value !== undefined) {
            this.setValue(this.value);
          } else if (
            !Ext.isEmpty(this.el.dom.value) &&
            this.el.dom.value != this.emptyText
          ) {
            this.setValue(this.el.dom.value);
          }

          this.originalValue = this.getValue();
        },
        setValue: function (v) {
          this.applySetValue(v);
          _this.fireEvent('setvalueinvaluefield', v, this.outSideSetValue);
          this.outSideSetValue = true;
        }
      });
      return valueField;
    },
    _makeTagField: function () {
      var _this = this;
      var tagField = new Custom.TagField({
        // height:(!Ext.isEmpty(_this.height)) ? _this.height : 70,
        height: 70,
        theme: _this.theme,
        isShowSeparator: this.isShowSeparator,
        separator: this.separator,
        autoScroll: true
      });
      tagField.on('close', function (self, tag) {
        _this._valueFieldInSetTagFieldValue();
      });
      tagField.on('changeOrder', function (self, fromIndex, toIndex) {
        _this._valueFieldInSetTagFieldValue();
      });
      return tagField;
    },
    insertTag: function (index, tag) {
      this.addTag(tag, index);
    },

    addTag: function (tag, index) {
      function getByteLength(s, b, i, c) {
        // 한글을 3바이트로 하고 싶으면
        //for(b=i=0;c=s.charCodeAt(i++);b+=c>>11?3:c>>7?2:1);
        // 한글을 2바이트로 하고 싶으면
        for (
          b = i = 0;
          (c = s.charCodeAt(i++));
          b += c >> 11 ? 2 : c >> 7 ? 2 : 1
        );
        return b;
      }
      var _this = this;
      if (Ext.isEmpty(tag)) {
        return;
      }

      if (this._tagField.getTagCount() >= this.maxTagCount) {
        Ext.Msg.alert('알림', '키워드는 50개까지 입력할 수 있습니다.');
        return;
      }

      // if (this.maxTagLength > 0 && getByteLength(tag) > this.maxTagLength) {
      //   Ext.Msg.alert(
      //     '알림',
      //     '키워드는 최대 ' +
      //       this.maxTagLength +
      //       '바이트(한글기준' +
      //       this.maxTagLength / 2 +
      //       '자)까지 입력할 수 있습니다.'
      //   );
      //   return;
      // }

      if (this._tagField.tagExists(tag)) {
        Ext.Msg.alert('알림', '이미 등록되어 있는 키워드 입니다.');
        return;
      }

      if (this._blacklistCharContained(tag)) {
        Ext.Msg.alert(
          '알림',
          '키워드에 입력 제한된 텍스트가 포함 되어 있습니다.'
        );
        return;
      }

      if (index !== undefined) {
        _this._tagField.insertTag(index, tag);
      } else {
        _this._tagField.addTag(tag);
      }

      this._valueFieldInSetTagFieldValue();
      // var params = {
      //     keyword: tag
      // };
      // this.createVideoKeyword(tag, function (self, res) {
      //     if (index !== undefined) {
      //         _this._tagField.insertTag(index, tag);
      //     } else {
      //         _this._tagField.addTag(tag);
      //     }
      // });
      // this._tagTextField.getStore().removeAll();
      this._tagTextField.setValue('');
      this._tagTextField.focus();
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
    },
    getValueField: function () {
      return this._valueField;
    },

    _valueFieldInSetTagFieldValue: function () {
      var _this = this;
      var tags = this.getValue();
      var strTags = '';

      tags = _this._trimTags(tags);
      tags = _this._setSeparatorTag(tags);
      for (var i = 0; i < tags.length; i++) {
        strTags = strTags + tags[i];
      }
      this.getValueField().outSideSetValue = false;
      this.getValueField().setValue(strTags);
    },
    _ok: function () {
      var _this = this;
      var tag = _this._tagTextField.el.dom.value;

      var tags = tag.split(_this.separator);
      tags = _this._trimTags(tags);
      this.setValue(tags);
    },
    _trimText: function (tag) {
      var _this = this;
      //내용에 공백제거, 구분자 제거
      var regex = new RegExp(_this.separator, 'gi');
      tag = tag.replace(regex, '');
      if (_this.isTrim) {
        tag = tag.replace(/^\s+/, '');
        tag = tag.replace(/\s+$/, '');
        tag = tag.replace(/^\s+|\s+$/g, '');
        tag = tag.replace(/\s|\n|\r/g, '');
      }
      return tag;
    },
    _trimTags: function (tags) {
      var _this = this;
      if (tags.length > 0) {
        for (var i = 0; i < tags.length; i++) {
          tags[i] = _this._trimText(tags[i]);
        }
      }
      return tags;
    },
    _setSeparatorTag: function (tags) {
      var _this = this;
      if (_this.isShowSeparator) {
        if (tags.length > 0) {
          for (var i = 0; i < tags.length; i++) {
            tags[i] = _this.separator + tags[i];
          }
        }
      }
      return tags;
    }
  });

  Ext.reg('c-tag-panel', Custom.TagPanel);
})();

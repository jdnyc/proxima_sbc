(function() {
  Ext.ns("Custom");
  Custom.ImageUploadPanel = Ext.extend(Ext.Container, {
    // Properties
    labelStyle: "color: white;",
    style: {
      padding: "5px",
      //color: "white",
      border: "1px solid lightgray"
    },

    selectedFileInfo: null,

    maxSize: 5, // 5MB

    // private variables
    _imageField: null,
    _imageSizeField: null,
    _changePosterField: null,

    listeners: {
      afterrender: function(self) {}
    },

    constructor: function(config) {
      Ext.apply(this, {}, config || {});
      Custom.ImageUploadPanel.superclass.constructor.call(this);
    },

    initComponent: function() {
      this._initItems();
      Custom.ImageUploadPanel.superclass.initComponent.call(this);
    },

    getValue: function() {
      return this._filePathField.getValue();
    },

    /**
     * 이미지 변경
     * @param {string} filePath
     * @param {File} file
     */
    setValue: function(filePath, file) {
      if (file === null || file === undefined) {
        console.log("file object should not null.");
        return;
      }
      this._filePathField.setValue(file.name);
      this._imageField.changePoster(file);
    },

    loadImage: function(url) {
      this._imageField.loadImage(url);
    },

    _initItems: function() {
      var _this = this;

      this._imageField = this._makeImageField();
      this._imageSizeField = this._makeImageSizeField();
      this._changePosterField = this._makeChangeImageField();
      this.layout = "table";
      this.layoutConfig = {
        // The total column count must be specified here
        columns: 2,
        tableAttrs: {
          style: {
            width: "100%"
          }
        }
      };

      this.items = [
        this._imageField,
        this._imageSizeField,
        new Ext.form.CompositeField({
          width: "100%",
          style: {
            marginTop: "5px",
            marginBottom: "5px"
          },
          items: [
            new Ext.form.Label({
              text: "섬네일:",
              style: {
                marginTop: "5px"
              }
            }),
            this._changePosterField
          ]
        })
      ];
    },

    _makeImageField: function() {
      var _this = this;
      var imgHtml = '<img class="poster" src="" >';
      var imageField = new Ext.BoxComponent({
        rowspan: 2,
        cls: "poster-container",
        html: imgHtml,
        changePoster: function(file) {
          var imgEl = this.getEl().first();
          imgEl.addClass("poster");
          imgEl.on("click", function(e) {
            window.open(_this.getValue(), "_blank");
          });

          var img = new Image();
          img.addEventListener("load", function() {
            var hdRatio = 16 / 9;
            var actualRatio = this.naturalWidth / this.naturalHeight;
            if (actualRatio > hdRatio) {
              // 가로가 삐져 나오니까 세로를 가로를 100%로 맞추고 세로를 auto로 해야 함
              imgEl.replaceClass("poster", "poster-width");
            } else {
              imgEl.replaceClass("poster-width", "poster");
            }

            _this._updateImageSizeField(this.naturalWidth, this.naturalHeight);
          });

          var reader = new FileReader();

          reader.onload = function(e) {
            img.src = e.target.result;
            imgEl.set({
              src: e.target.result
            });
          };
          reader.readAsDataURL(file);
        },
        loadImage: function(url) {
          //url = "https://nodejs.org/static/images/logo.svg";
          this.update('<img class="poster" src="' + url + '" >');
          var imgEl = this.getEl().first();
          imgEl.addClass("poster");
          imgEl.on("click", function(e) {
            window.open(url, "_blank");
          });
        }
      });
      return imageField;
    },

    _makeImageSizeField: function() {
      var imageSizeField = new Ext.form.Label({
        text: "섬네일 사이즈: -"
      });
      return imageSizeField;
    },

    _updateImageSizeField: function(width, height) {
      this._imageSizeField.setText("이미지 사이즈: " + width + "x" + height);
    },

    _makeChangeImageField: function() {
      var _this = this;
      this._filePathField = new Ext.form.TextField({
        name: "filename",
        readOnly: true,
        width: 210
      });

      // 필드명 처리
      if (!Ext.isEmpty(this.name)) {
        this._filePathField.name = this.name;
      }

      // 필수값 처리
      if (this.required) {
        this._filePathField.allowBlank = false;
      }
      this._fileInput = new Ext.form.TextField({
        inputType: "file",
        onFileSelected: null,
        name: "file",
        el: null,
        hidden: true,
        _selectedFileInfo: null,
        listeners: {
          render: function(self) {
            self.el = self.getEl();
            self.el.set({
              accept: ".jpg,.png"
            });
            self.el.on("change", self._onElChange, self);
          },
          beforedestroy: function(self) {
            if (!self.el) {
              return;
            }
            self.el.un("change", self._onElChange, self);
          }
        },
        _onElChange: function(e, t, o) {
          if (this.onFileSelected && this.el) {
            this._selectedFileInfo = {
              path: this.el.getValue(),
              files: this.el.getAttribute("files")
            };
            this.onFileSelected(this, this._selectedFileInfo);
          }
        }
      });
      this._fileInput.onFileSelected = function(fileInput, fileInfo) {
        if (fileInfo.files && fileInfo.files[0]) {
          var file = fileInfo.files[0];
          if (!_this._isValidFile(file)) {
            return;
          }
          _this.selectedFileInfo = fileInfo;
          _this.setValue(fileInfo.path, file);
        }
      };

      var changeImageField = new Ext.Container({
        layout: "toolbar",
        items: [
          this._fileInput,
          this._filePathField,
          new Ext.Spacer({
            width: 5
          }),
          {
            xtype: "a-iconbutton",
            text: "찾아보기",
            handler: function(btn, e) {
              //_this._imageField.changePoster('https://cdn.pixabay.com/photo/2019/03/17/11/33/sailing-boat-4060710_1280.jpg');
              _this._fileInput.getEl().dom.click();
              //_this._fileInput.focus();
            }
          }
        ]
      });
      return changeImageField;
    },

    _isValidImage: function(img) {
      // var w = img.naturalWidth;
      // var h = img.naturalHeight;
      // if (w === 640 && h === 360) {
      //   return true;
      // } else if (w === 360 && h === 640) {
      //   return true;
      // } else if (w === 640 && h === 640) {
      //   return true;
      // }
    },

    _isValidFile: function(file) {
      // 확장자 체크
      var allowedExts = ["jpg", "png"];
      var ext = file.name.split(".").pop();
      if (allowedExts.indexOf(ext) < 0) {
        Ext.Msg.alert("알림", "이미지 파일 형식(jpg, png)을 확인 해 주세요.");
        return false;
      } else if (file.size > this.maxSize * 1024 * 1024) {
        Ext.Msg.alert(
          "알림",
          "이미지 최대 용량(" + this.maxSize + "MB)을 초과 했습니다."
        );
        return false;
      }
      return true;
    }
  });

  Ext.reg("c-image-panel", Custom.ImageUploadPanel);
})();

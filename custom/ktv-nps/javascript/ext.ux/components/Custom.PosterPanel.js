(function() {
  Ext.ns("Custom");
  Custom.PosterPanel = Ext.extend(Ext.Container, {
    // Properties
    labelStyle: "color: white;",
    style: {
      padding: "5px",
      color: "white",
      border: "1px solid black"
    },
    contentId: null,

    maxSize: 5, // 5MB

    // private variables
    _posterField: null,
    _posterSizeField: null,
    _changePosterField: null,

    listeners: {
      afterrender: function(self) {
        if (!self.contentId) {
          return;
        }
        Ext.Ajax.request({
          url: requestCustomPath("/store/poster.php", {
            content_id: self.contentId
          }),
          method: "GET",
          success: function(response, opts) {
            try {
              var r = Ext.decode(response.responseText);
              if (r.success && r.data) {
                self.setValue(r.data.poster_url);
              }
            } catch (error) {
              console.error(error);
            }
          }
        });
      }
    },

    constructor: function(config) {
      Ext.apply(this, {}, config || {});
      Custom.PosterPanel.superclass.constructor.call(this);
    },

    initComponent: function() {
      this._initItems();
      Custom.PosterPanel.superclass.initComponent.call(this);
    },

    getValue: function() {
      return this._filePathField.getValue();
    },

    setValue: function(posterUrl) {
      this._filePathField.setValue(posterUrl);
      this._posterField.changePoster(
        posterUrl + "?timestamp=" + new Date().getTime()
      );
    },

    _initItems: function() {
      var _this = this;

      this._posterField = this._makePosterField();
      this._posterSizeField = this._makePosterSizeField();
      this._changePosterField = this._makeChangePosterField();
      this.layout = "table";
      this.layoutConfig = {
        // The total column count must be specified here
        columns: 2,
        tableAttrs: {
          style: {
            width: "95%"
          }
        }
      };
      this.items = [
        this._posterField,
        this._posterSizeField,
        new Ext.form.CompositeField({
          style: {
            marginTop: "5px",
            marginBottom: "5px"
          },
          items: [
            new Ext.form.Label({
              text: "이미지 교체:",
              style: {
                marginTop: "5px"
              }
            }),
            this._changePosterField
          ]
        }),
        new Ext.form.Label({
          text: "* 파일형식 : jpg, png * 최대용량 : " + this.maxSize + "MB",
          style: {
            color: "#28aeff"
          }
        }),
        new Ext.Spacer({
          height: 10
        }),
        new Ext.form.Label({
          style: {
            color: "#28aeff"
          },
          text:
            "이미지 사이즈 : 16:9(가로형) : 640x360, 9:16(세로형) : 360x640, 1:1(정방형) : 640x640"
        })
      ];
    },

    _makePosterField: function() {
      var _this = this;
      var imgHtml = '<img class="poster" src="" >';
      var posterField = new Ext.BoxComponent({
        rowspan: 5,
        cls: "poster-container",
        html: imgHtml,
        changePoster: function(posterUrl) {
          var imgEl = this.getEl().first();
          // imgEl.setStyle({
          //   maxWidth: '100%',
          //   maxHeight: '100%'
          // });
          imgEl.addClass("poster");
          imgEl.on("click", function(e) {
            window.open(_this.getValue(), "_blank");
          });
          var img = new Image();
          img.addEventListener("load", function() {
            imgEl.set({
              src: posterUrl
            });

            var hdRatio = 16 / 9;
            var actualRatio = this.naturalWidth / this.naturalHeight;
            if (actualRatio > hdRatio) {
              // 가로가 삐져 나오니까 세로를 가로를 100%로 맞추고 세로를 auto로 해야 함
              imgEl.replaceClass("poster", "poster-width");
            } else {
              imgEl.replaceClass("poster-width", "poster");
            }

            _this._updatePosterSizeField(this.naturalWidth, this.naturalHeight);
          });
          img.src = posterUrl;
        }
      });
      return posterField;
    },

    _makePosterSizeField: function() {
      var posterSizeField = new Ext.form.Label({
        text: "이미지 사이즈: -"
      });
      return posterSizeField;
    },

    _updatePosterSizeField: function(width, height) {
      this._posterSizeField.setText("이미지 사이즈: " + width + "x" + height);
    },

    _makeChangePosterField: function() {
      var _this = this;
      this._filePathField = new Ext.form.TextField({
        readOnly: true,
        width: 300
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
        if (Ext.isEmpty(_this.contentId)) {
          return;
        }

        if (fileInfo.files && fileInfo.files[0]) {
          if (!_this._isValidFile(fileInfo.files[0])) {
            return;
          }

          var reader = new FileReader();

          reader.onload = function(e) {
            var img = new Image();
            img.onload = function(e) {
              // ajax로 업로드...
              var formData = new FormData();
              formData.append("content_id", _this.contentId);
              formData.append("file", fileInfo.files[0]);

              var waitPopup = Ext.Msg.wait(
                "이미지를 업로드 하는 중입니다.",
                "업로드중..."
              );
              // $.ajax({
              //   url: requestPath("/api/v1/attach-files"),
              //   method: "post",
              //   data: formData,
              //   processData: false,
              //   contentType: false,
              //   success: function(res, action) {
              //     waitPopup.hide();
              //     _this._onUploadSuccess(res, _this);
              //   },
              //   failure: function(res, action) {
              //     waitPopup.hide();
              //     console.error("fail to upload", res);
              //     Ext.Msg.alert("알림", "업로드에 실패 했습니다.");
              //   }
              // });
            };
            img.src = e.target.result;
          };
          reader.readAsDataURL(fileInfo.files[0]);
        }
      };

      var changePosterField = new Ext.Container({
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
              //_this._posterField.changePoster('https://cdn.pixabay.com/photo/2019/03/17/11/33/sailing-boat-4060710_1280.jpg');
              _this._fileInput.getEl().dom.click();
              //_this._fileInput.focus();
            }
          },
          {
            xtype: "a-iconbutton",
            text: "이미지 다운로드",
            handler: function(btn, e) {
              var url = _this.getValue();
              if (url) {
                var link = document.createElement("a");
                link.setAttribute("href", url);
                link.setAttribute("target", "_blank");
                link.style.display = "none";
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
              }
            }
          }
        ]
      });
      return changePosterField;
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
    },

    _onUploadSuccess: function(res, self) {
      if (res.success) {
        var imageUrl = res.data.url;
        self.setValue(imageUrl);
      } else {
        Ext.Msg.alert(
          "알림",
          "업로드 중 오류가 발생했습니다.(" + res.msg + ")"
        );
      }
    }
  });

  Ext.reg("c-poster-panel", Custom.PosterPanel);
})();

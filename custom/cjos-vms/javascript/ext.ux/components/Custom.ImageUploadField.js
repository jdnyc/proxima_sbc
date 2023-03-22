(function () {
  Ext.ns("Custom");
  Custom.ImageUploadField = Ext.extend(Ext.Container, {
    // Properties
    labelStyle: 'color: white;',
    style: {
      padding: '5px',
      color: 'white',
      border: '1px solid black'
    },
    contentId: null,
    valueFieldName: '',
    value: '',

    maxSize: 5, // 5MB
    // private variables

    constructor: function (config) {

      Ext.apply(this, {}, config || {});
      Custom.ImageUploadField.superclass.constructor.call(this);
    },

    initComponent: function () {
      this._initItems();

      // 값 설정
      if (!Ext.isEmpty(this.value)) {
        this._filePathField.setValue(this.value);
      }
      Custom.ImageUploadField.superclass.initComponent.call(this);
    },

    getValue: function () {
      return this._filePathField.getValue();
    },

    setValue: function (imageUrl) {
      this._filePathField.setValue(imageUrl);
    },

    _initItems: function () {
      var _this = this;

      this._uploadField = this._makeUploadField();
      this.layout = 'table';
      this.layoutConfig = {
        // The total column count must be specified here
        columns: 2
      };
      this.items = [
        this._uploadField,
        new Ext.form.Label({
          style: {
            color: '#28aeff',
            marginLeft: '5px'
          },
          text: '* 파일형식 : gif * 최대용량 : ' + this.maxSize + 'MB'
        }),
        new Ext.Spacer({
          height: 5
        }),
      ];
    },

    _makeUploadField: function () {
      var _this = this;
      this._filePathField = new Ext.form.TextField({
        readOnly: true,
        width: 400
      });

      if (!Ext.isEmpty(this.valueFieldName)) {
        this._filePathField.name = this.valueFieldName;
      }

      this._fileInput = new Ext.form.TextField({
        inputType: 'file',
        onFileSelected: null,
        el: null,
        hidden: true,
        _selectedFileInfo: null,
        listeners: {
          render: function (self) {
            self.el = self.getEl();
            self.el.set({
              accept: 'image/gif'
            });
            self.el.on('change', self._onElChange, self);
          },
          beforedestroy: function (self) {
            if (!self.el) {
              return;
            }
            self.el.un('change', self._onElChange, self);
          }
        },
        _onElChange: function (e, t, o) {
          if (this.onFileSelected && this.el) {
            this._selectedFileInfo = {
              path: this.el.getValue(),
              files: this.el.getAttribute('files')
            };
            this.onFileSelected(this, this._selectedFileInfo);
          }
        }
      });
      this._fileInput.onFileSelected = function (fileInput, fileInfo) {
        if (Ext.isEmpty(_this.contentId)) {
          return;
        }

        if (fileInfo.files && fileInfo.files[0]) {
          if (!_this._isValidFile(fileInfo.files[0])) {
            return;
          }

          var reader = new FileReader();

          reader.onload = function (e) {
            var img = new Image();
            img.onload = function (e) {
              // ajax로 업로드...
              var formData = new FormData();
              formData.append('content_id', _this.contentId);
              formData.append('file', fileInfo.files[0]);

              var waitPopup = Ext.Msg.wait('이미지를 업로드 하는 중입니다.', '업로드중...');
              $.ajax({
                url: requestCustomPath('/store/dynamic-image.php'),
                method: 'post',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res, action) {

                  waitPopup.hide();
                  _this._onUploadSuccess(res, _this);

                },
                failure: function (res, action) {

                  waitPopup.hide();
                  console.error('fail to upload', res);
                  Ext.Msg.alert('알림', '업로드에 실패 했습니다.');
                }
              });
            }
            img.src = e.target.result;
          }
          reader.readAsDataURL(fileInfo.files[0]);
        }
      };

      var uploadField = new Ext.Container({
        layout: 'toolbar',
        items: [
          this._fileInput,
          this._filePathField,
          {
            xtype: 'a-iconbutton',
            text: '찾아보기',
            handler: function (btn, e) {
              //_this._posterField.changePoster('https://cdn.pixabay.com/photo/2019/03/17/11/33/sailing-boat-4060710_1280.jpg');
              _this._fileInput.getEl().dom.click();
              //_this._fileInput.focus();
            }
          }
        ]
      });
      return uploadField;
    },

    _isValidImage: function (img) {
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

    _isValidFile: function (file) {

      // 확장자 체크
      var allowedExts = ['gif'];
      var ext = file.name.split('.').pop();
      if (allowedExts.indexOf(ext) < 0) {
        Ext.Msg.alert('알림', '이미지 파일 형식(gif)을 확인 해 주세요.');
        return false;
      } else if (file.size > (this.maxSize * 1024 * 1024)) {
        Ext.Msg.alert('알림', '이미지 최대 용량(' + this.maxSize + 'MB)을 초과 했습니다.');
        return false;
      }
      return true;
    },

    _onUploadSuccess: function (res, self) {
      if (res.success) {
        var imageUrl = res.data.url;
        self.setValue(imageUrl);
      } else {
        Ext.Msg.alert('알림', '업로드 중 오류가 발생했습니다.(' + res.msg + ')');
      }
    }

  });

  Ext.reg("c-imgupload-field", Custom.ImageUploadField);
})();
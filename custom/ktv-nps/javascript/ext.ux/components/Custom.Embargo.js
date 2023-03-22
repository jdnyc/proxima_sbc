(function () {
  Ext.ns("Custom");
  Custom.Embargo = Ext.extend(Ext.form.CompositeField, {
    // 엠바고 여부 값
    value: 'N',
    // 필드셋 여부
    fieldSet: null,
    initComponent: function () {
      this._initialize();

      Custom.Embargo.superclass.initComponent.call(this);
    },
    _initialize: function () {
      var _this = this;
      /**
       * 엠바고 콤보박스
       */
      this.embargoAtCombo = new Ext.form.ComboBox({
        editable: false,
        flex: 1,
        name: 'embg_at',
        triggerAction: 'all',
        editable: false,
        displayField: 'd',
        valueField: 'v',
        mode: 'local',
        value: _this.value,
        fields: [
          'd', 'v'
        ],
        store: [
          ['Y', 'Y'],
          ['N', 'N']
        ],
        listeners: {
          afterrender: function (self) {
            self.setValue(_this.value);

          },
          select: function (self, record, idx) {
            switch (self.getValue()) {
              case 'Y':

                _this._oriEmbargoReasonField().show();
                _this.embargoDateField.setReadOnly(false);
                break;
              case 'N':
                _this._oriEmbargoReasonField().hide();

                _this.embargoDateField.setValue(null);
                _this.embargoDateField.setReadOnly(true);
                _this.embargoTimeField.setValue(null);
                _this.embargoTimeField.setReadOnly(true);

                _this._oriEmbargoReasonField().setValue(null);
                _this._oriEmbargoDateField().setValue(null);
                break;
            }

          }
        }
      });

      /**
       * 엠바고 해제일시 라벨
       */
      var embargoDateFieldLabel = new Ext.form.Label({
        flex: 1,
        text: '엠바고해제일시',
        style: {
          'font-family': '나눔고딕',
          'text-align': 'center',
          'padding-top': '6px',
          'font-size': '11px'
        }
      });
      /**
       * 엠바고 해제일시 필드
       */
      this.embargoDateField = new Ext.form.DateField({
        // name: 'embg_relis_dt_fake',
        flex: 1,
        editable:false,
        format: 'Y-m-d',
        altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
        listeners: {
          select: function (self, date) {
            if(!Ext.isEmpty(_this.embargoTimeField.getValue())){
              var Ymd = date.format('Y-m-d');
              var His = _this.embargoTimeField.getValue();
              var date = Ymd+" "+His;
            }
          
            _this._oriEmbargoDateField().setValue(date);
          },
          change: function(self,newValue,oldValue){
            if(!Ext.isEmpty(newValue)){
              _this.embargoTimeField.setReadOnly(false);
            }else{
              _this.embargoTimeField.setReadOnly(true);
              _this.embargoTimeField.setValue(null);
              _this._oriEmbargoDateField().setValue(null);
            };
          }
        }
      });

      // 
      this.embargoTimeField = new Ext.form.TextField({
        flex:1,
        xtype:'textfield',
        // vtype: 'timecode',
        // plugins: new Ext.ux.plugin.FormatTimecode(),
        readOnly:true,
        listeners:{
          afterrender: function(self){
            if(_this.value == 'N'){
              self.setReadOnly(true);
            }else{
              self.setReadOnly(false);
            }
          },
          change: function(self, newValue, oldValue){
            // Ext.defer(function(){

            // },1000);
            if(Ext.isEmpty(_this.embargoDateField.getValue())){
              return false;
            }
            self.setValue(self.timecode(self.getValue()));
              var Ymd = _this.embargoDateField.getValue().format("Y-m-d");
              var His = self.getValue();
              var date = Ymd+" "+His;
              _this._oriEmbargoDateField().setValue(date);
            
          }
        },
        timecode: function (value) {
          function RightPad(val, size, ch) {
            var result = String(val);
            if(!ch) {
              ch = " ";
            }
            while (result.length < size) {
              result = result+ ch;
            }
            return result;
          }
          // 00:00:00 형식으로 변경 추가
          var reg = /[0-9]{6}/;
          value = value.replace(/[\D]/gi, '');
          value = value.substr(0,6);
          value = RightPad(value, 6, '0');
          if (reg.test(value)) {
            return value.replace(/([0-9]{2})([0-9]{2})([0-9]{2})/, "$1:$2:$3");
          }
          else {
            return value;
          }
        },	
      });
      
      /**
       * 엠바고 유무 , 해제일시 라벨, 해제일시 필드
       */
      this.items = [this.embargoAtCombo, embargoDateFieldLabel, this.embargoDateField, this.embargoTimeField];

      this.listeners = {
        afterrender: function (self) {
          var _this = this;
          var embargoAtComboValue = _this.embargoAtCombo.getValue();
          _this._oriEmbargoDateField().format = "Y-m-d H:i:s";
          switch (embargoAtComboValue) {
            case 'Y':
              _this._oriEmbargoReasonField().show();

              // 기존 숨겨져 있는 엠바고 데이터 필드에 값이 있으면 넣어준다
              var oriEmbargoDateValue = _this._oriEmbargoDateField().getValue();
              if (_this._isValue(oriEmbargoDateValue)){
                _this.embargoDateField.setValue(oriEmbargoDateValue);
                _this.embargoTimeField.setValue(oriEmbargoDateValue.format('H:i:s'));
              }

                

              _this.embargoDateField.setReadOnly(false);
              break;
            case 'N':
              _this._oriEmbargoReasonField().hide();

              _this.embargoDateField.setValue(null);
              _this.embargoDateField.setReadOnly(true);
              break;
          }

          _this._oriEmbargoDateField().hide();
        }
      };
    },
    setValue: function (v) {
      if ((v === null) || (v === ''))
        return this.embargoAtCombo.setValue('N');

      this.embargoAtCombo.setValue(v);
    },
    _isValue: function (v) {
      if ((v === null) || (v === '') || (typeof v === 'undefined')) {
        return false;
      } else {
        return true;
      }
    },
    /**
     * 숨겨진 원래 엠바고 해제일신 필드
     */
    _oriEmbargoDateField: function () {
      var ownerComponent = this._ownerComponent();
      var oriEmbargoDateField = ownerComponent.getForm().findField('embg_relis_dt');

      return oriEmbargoDateField;
    },
    /**
     * 숨겨진 원래 엠바고 사유 필드
     */
    _oriEmbargoReasonField: function () {
      // var ownerComponent = this.ownerCt;
      var ownerComponent = this._ownerComponent();
      var oriEmbargoReasonField = ownerComponent.getForm().findField('embg_resn');

      return oriEmbargoReasonField;
    },
    _ownerComponent: function () {
      var isForm = this._isValue(this.ownerCt.form);
      if (isForm) {
        return this.ownerCt;
      } else {
        var isForm = this._isValue(this.ownerCt.ownerCt.form);
        if (isForm)
          return this.ownerCt.ownerCt;
      }
    }

  });
  Ext.reg("embargo", Custom.Embargo);
})();
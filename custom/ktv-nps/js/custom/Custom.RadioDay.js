Ext.ns("Custom");

Custom.RadioDay = Ext.extend(Ext.form.RadioGroup, {
  startDateField: null,
  endDateField: null,




  style: {
    'margin-left': '10px',
    'margin-right': '10px'
  },
  // 참조할 데이터 필드
  dateFieldConfig: {
    startDateField: null,
    endDateField: null,
  },
  // 오늘 , 주 , 한달 중 숨길 라디오
  basicFieldHidden: {
    one: false,
    week: false,
    month: false,
  },
  // 일년 전체 추가 라디오
  addRadio: {
    yearRadio: false,
    allRadio: false
  },

  //처음 체크될 라디오
  checkDay: 'week',

  width: 170,
  columns: [.34, .36, .25],
  initComponent: function () {
    this._initialize();

    Custom.RadioDay.superclass.initComponent.call(this);
  },
  _initialize: function () {
    var _this = this;
    this.items = [{
      hidden: _this.basicFieldHidden.one,
      boxLabel: '오늘',
      name: 'dateCheck',
      value: 'one',
      listeners: {
        check: function (self, checked) {
          if (checked) {
            _this.dateFieldConfig.startDateField.setValue(new Date());
            _this.dateFieldConfig.endDateField.setValue(_this._endDate());
          }
        }
      }
    }, {
      hidden: _this.basicFieldHidden.week,
      boxLabel: '일주일',
      name: 'dateCheck',
      value: 'week',
      listeners: {
        check: function (self, checked) {
          if (checked) {
            _this.dateFieldConfig.startDateField.setValue(new Date().add(Date.DAY, -6).format('Y-m-d'));
            _this.dateFieldConfig.endDateField.setValue(_this._endDate());
          }
        }
      }
    },
    {
      hidden: _this.basicFieldHidden.month,
      boxLabel: '한달',
      name: 'dateCheck',
      value: 'month',
      listeners: {
        check: function (self, checked) {
          if (checked) {
            _this.dateFieldConfig.startDateField.setValue(new Date().add(Date.MONTH, -1).add(Date.DAY, 1).format('Y-m-d'));
            _this.dateFieldConfig.endDateField.setValue(_this._endDate());
          }
        }
      }
    }];



    // 추가 라디오
    if (this.addRadio.allRadio) {
      this._addAllRadio();
    }
    if (this.addRadio.yearRadio) {
      this._addYearRadio();
    };

    this.listeners = {
      afterrender: function (self) {
        _this._firstCheckRadio();
      }
    };
  },
  /**
   * 처음 랜더링 시
   * checkDay 와 items value  값이 일치하는것을 체크
   */
  _firstCheckRadio: function () {
    var _this = this;
    this.items.each(function (r, i, e) {
      if (r.value == _this.checkDay) {
        r.setValue(true);

      };
    });
  },
  _addAllRadio: function (handler) {
    var _this = this;
    var radio = new Ext.form.Radio({
      boxLabel: '전체',
      name: 'dateCheck',
      value: 'all',
      check: function (self, checked) {
        if (checked) {
          handler
        }
      }
    });
    _this.items.unshift(radio);
  },
  _endDate: function () {
    var endDate = new Date();
    var endDateOf = new Date(endDate.getFullYear()
      , endDate.getMonth()
      , endDate.getDate()
      , 23, 59, 59);
    return endDateOf;
  },
  _addYearRadio: function () {
    var _this = this;
    var radio = new Ext.form.Radio({
      boxLabel: '일년',
      name: 'dateCheck',
      value: 'year',
      listeners: {
        check: function (self, checked) {
          if (checked) {
            _this.dateFieldConfig.startDateField.setValue(new Date().add(Date.YEAR, -1).add(Date.DAY, 1).format('Y-m-d'));
            _this.dateFieldConfig.endDateField.setValue(_this._endDate());
          }
        }
      }
    });
    _this.items.push(radio);
  }

});
Ext.reg('radioday', Custom.RadioDay);
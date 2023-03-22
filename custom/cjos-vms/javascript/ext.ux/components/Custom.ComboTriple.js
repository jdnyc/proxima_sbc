(function () {
    Ext.ns('Custom');
    Custom.ComboTriple = Ext.extend(Ext.form.CompositeField, {
        // Properties
        fieldLabel: null,
        listeners: {
            afterrender: function (self) {
                if (!Ext.isEmpty(self.value)) self.setRawValue(self.value);
            }
        },

        constructor: function (config) {
            Ext.apply(this, {}, config || {});
            Custom.ComboTriple.superclass.constructor.call(this);
        },

        initComponent: function () {
            this._initItems();
            Custom.ComboTriple.superclass.initComponent.call(this);
        },

        /**
         * 필드 생성
         */
        _initItems: function () {
            var _this = this;
            this._displayField1 = this._makeDisplayField(1);
            this._contentCombo1 = this._makeContentCombo(1);
            this._displayField2 = this._makeDisplayField(2);
            this._contentCombo2 = this._makeContentCombo(2);
            this._displayField3 = this._makeDisplayField(3);
            this._contentCombo3 = this._makeContentCombo(3);

            this.items = [];
            this.items.push(this._displayField1);
            this.items.push(this._contentCombo1);
            this.items.push(this._displayField2);
            this.items.push(this._contentCombo2);
            this.items.push(this._displayField3);
            this.items.push(this._contentCombo3);
        },

        _makeContentCombo: function (num) {
            var _this = this;
            var allowBlank = true;
            if (num == 1) {
                allowBlank = false;
            }
            var contentCombo = new Ext.form.ComboBox({
                allowBlank: allowBlank,
                width: 120,
                name: this.fieldCode + '_' + num,
                store: this.comboStore,
                invalidClass: '',
                num: num,
                editable: false,
                triggerAction: 'all',
                typeAhead: true,
                emptyText: '선택',
                mode: 'local',
                tmpValue: '', // 선택없음 처리
                beforeValue: '',
                listeners: {
                    beforeselect: function (self, record, index) {
                        self.beforeValue = self.getValue();
                        if (self.num === 1 && index === 0) {
                            self.tmpValue = self.getValue();
                        } else {
                            self.tmpValue = '';
                        }
                    },
                    select: function (self, record, index) {
                        if (self.num === 1 && index === 0) {
                            self.setValue(self.tmpValue);
                            Ext.Msg.alert('알림', '첫번째 유형은 필수 입력 항목입니다.');
                        }
                        
                        var oldValue = self.beforeValue;
                        var combo1, combo2, combo3;
                        var dupli_flag = 'N';
                        _this.items.each(function (combobox) {
                            if (combobox.name == _this.fieldCode + '_1') combo1 = combobox.getValue();
                            if (combobox.name == _this.fieldCode + '_2') combo2 = combobox.getValue();
                            if (combobox.name == _this.fieldCode + '_3') combo3 = combobox.getValue();
                        });
                        if(combo1 == '선택' || combo1 == '선택없음') combo1 = '';
                        if(combo2 == '선택' || combo2 == '선택없음') combo2 = '';
                        if(combo3 == '선택' || combo3 == '선택없음') combo3 = '';
                        if(combo1 == combo2 && combo1 != '' && combo2 != '') {
                            dupli_flag = 'Y';
                        }
                        if(combo1 == combo3 && combo1 != '' && combo3 != '') {
                            dupli_flag = 'Y';
                        }
                        if(combo2 == combo3 && combo2 != '' && combo3 != '') {
                            dupli_flag = 'Y';
                        }

                        if(dupli_flag == 'Y') {
                            self.setValue(oldValue);
                            Ext.Msg.alert('알림', '콘텐츠유형은 같은 값을 여러개 지정할 수 없습니다.');
                        }
                    }
                }
            });

            return contentCombo;
        },

        _makeDisplayField: function (num) {
            var value = '유형' + num;
            if (num == 1) {
                value = '유형' + num + '<span class=\'usr_meta_required_filed\'>&nbsp;*&nbsp;</span>';
            }
            var displayField = new Ext.form.DisplayField({
                value: value,
                name: 'combolabel',
                style: "text-align:center",
                width: 40
            });

            return displayField;
        },

        setRawValue: function (rawValue) {
            if (!rawValue) {
                return;
            }
            var rawValueList = rawValue.split(',');
            if (rawValueList.length != 3) return;

            var _this = this;

            _this.items.each(function (combobox) {
                if (combobox.name == _this.fieldCode + '_1') combobox.setValue(rawValueList[0]);
                if (combobox.name == _this.fieldCode + '_2') combobox.setValue(rawValueList[1]);
                if (combobox.name == _this.fieldCode + '_3') combobox.setValue(rawValueList[2]);
            });
        }
    });

    Ext.reg('c-combo-triple', Custom.ComboTriple);
})();
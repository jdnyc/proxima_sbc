Ext.ns('Ariel.glossary');
Ariel.glossary.searchField = Ext.extend(Ext.form.TwinTriggerField, {
    initComponent: function (config) {
        var _this = this;


        Ext.apply(this, config || {});
        Ariel.glossary.searchField.superclass.initComponent.call(this);
        this.on('specialkey', function (f, e) {
            if (e.getKey() == e.ENTER) {
                this.onTrigger2Click();
            }
        }, this);
    },


    // trigger1Class:'x-form-clear-trigger',
    trigger2Class: 'x-form-search-trigger',
    hideTrigger1: true,

    onTrigger2Click: function () {
        this.store.reload({
            params: {
                limit: this.pageSize,
                keyword: this.getRawValue()
            }
        });
        // if (this.getRawValue() == '') {
        //     this.store.reload({
        //         params: {
        //             limit: this.pageSize,
        //             search: null
        //         }
        //     });
        // } else {
        //     if (this.searchValue == 'ALL') {

        //         this.store.baseParams = {
        //             limit: this.pageSize,
        //             search: this._AllSelect(this)
        //         }

        //     } else {
        //         this._select(this.searchValue, this, true);
        //     }

        //     this.store.reload({
        //         params: {
        //             start: 0
        //         }
        //     });

        // }
    },
    _select: function (key, _this, k) {
        if (k) {
            _this.store.baseParams = {
                limit: _this.pageSize,
                search: "where UPPER(" + key + ") like '%'||UPPER('" + _this.getRawValue() + "') || '%'"
            }
        } else {
            _this.store.baseParams = {
                limit: _this.pageSize,
                search: "where " + key + " like '" + _this.getRawValue() + "'"
            }
        }
    },
    _AllSelect: function (_this) {
        // var query = 'where';
        var query = '';

        Ext.each(_this.menu, function (r, idx, t) {

            if (idx == 0) {
                query = query + " id like '%" + _this.getRawValue();
            }
            if ((idx > 0) && !(idx == t.length - 1)) {
                query = query + "%' OR " + r + " like '%" + _this.getRawValue();
            }
            if (idx == t.length - 1) {
                query = query + "%' OR " + r + " like '%" + _this.getRawValue() + "%'"
            }

        });
        return query;
    }
});
/**
 * Created by cerori on 2015-04-09.
 */
/**
 * Created by cerori on 2015-04-08.
 */

Ext.ns('Ariel.form.field');
(function() {
    Ariel.form.field.Combo = Ext.extend(Ext.form.ComboBox, {
        triggerAction: 'all',
        //mode: 'local',
        typeAhead: true,
        editable: false,

        initComponent: function () {
            var _this = this;

            _this.store = new Ext.data.JsonStore({
                url: '/pages/menu/config/workflow/workflow_list.php',
                baseParams: {
                    action: 'content_status_type_list'
                },
                fields: [
                    'code', 'name'
                ],
                root: 'data'
            });

            if (_this.value) {
                _this.store.on('load', function (store) {
                    _this.setValue(_this.value);
                })
            }

            Ariel.form.field.Combo.superclass.initComponent.call(this);

            _this.store.load();
        }
    });

    Ext.reg('formfieldcombo', Ariel.form.field.Combo);
})();
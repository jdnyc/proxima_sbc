/*!
 * Ext JS Library 3.1.0
 * Copyright(c) 2006-2009 Ext JS, LLC
 * licensing@extjs.com
 * http://www.extjs.com/license
 */
Ext.ns('Ext.ux.form');

/**
 * @class Ext.ux.form.SpinnerField
 * @extends Ext.form.NumberField
 * Creates a field utilizing Ext.ux.Spinner
 * @xtype spinnerfield
 */
/**
 * @modified by Mitchell Simoens
 * This modification was to allow the Ext.ux.form.SpinnerField to allow
 * a string. To accomplish, all I had to do was change the extended component.
 * Also change the Extension name to not create confusion.
 */
Ext.ux.form.TimeField = Ext.extend(Ext.form.TextField, {
    actionMode: 'wrap',
    deferHeight: true,
    autoSize: Ext.emptyFn,
    onBlur: Ext.emptyFn,
    adjustSize: Ext.BoxComponent.prototype.adjustSize,

	constructor: function(config) {
		var spinnerConfig = Ext.copyTo({}, config, 'incrementValue,alternateIncrementValue,accelerate,defaultValue,triggerClass,splitterClass');

		var spl = this.spinner = new Ext.ux.Spinner(spinnerConfig);
		
		var now = new Date();
		
		config.time = now.getTime();

		var plugins = config.plugins
			? (Ext.isArray(config.plugins)
				? config.plugins.push(spl)
				: [config.plugins, spl])
			: spl;

		Ext.ux.form.TimeField.superclass.constructor.call(this, Ext.apply(config, {plugins: plugins}));
	},
	
	isAM: function() {
		var hour = new Date(this.time).getHours();
		return (hour < 12) ? true: false;
	},
	
	isPM: function() {
		var hour = new Date(this.time).getHours();
		return (hour >= 12) ? true: false;
	},
	
	getValue: function(format) {
		if (!format) {
			if (this.twentyFour) {
				format = "H:i:s";
			} else {
				format = "h:i:s a";
			}
		}
		return new Date(this.time).format(format);
	},

    // private
    getResizeEl: function(){
        return this.wrap;
    },

    // private
    getPositionEl: function(){
        return this.wrap;
    },

    // private
    alignErrorIcon: function(){
        if (this.wrap) {
            this.errorIcon.alignTo(this.wrap, 'tl-tr', [2, 0]);
        }
    },

    validateBlur: function(){
        return true;
    }
});

Ext.reg('timefield', Ext.ux.form.TimeField);

//backwards compat
Ext.form.TimeField = Ext.ux.form.TimeField;

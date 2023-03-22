Ext.ns('Ext.ux.form');

/**
 * @class Ext.ux.form.TimePickerField
 * @extends Ext.form.Field
 */
/**
 * @constructor
 * @param Object
 */
Ext.ux.form.TimePickerField=function(config) {
	Ext.ux.form.TimePickerField.superclass.constructor.call(this, config);
}
Ext.extend(Ext.ux.form.TimePickerField, Ext.form.Field, {
	/**
     * @cfg {String/Object} autoCreate A DomHelper element spec, or true for a
	 * default element spec
     */
    defaultAutoCreate: {tag: 'div'},
	/**
	 * @cfg {String} cls A custom CSS class to apply to the field's underlying element (defaults to 'x-form-timepickerfield').
	 */
	cls: 'x-form-timepickerfield',
	/**
	 * @var {Ext.ux.Clock} The clock component
	 */
	clock: null,
	/**
	 * @var {Object} Clock configurations
	 */
	clockCfg: {
		outerArcState: {
			strokeStyle:		'#B5B8C8',
			lineWidth:			1,
			alpha:				0.8
		},
		hourIndicatorState: {
			strokeStyle:		'#15428B',
			lineWidth:			3
		}
	},
	/**
	 * @var {Object} Additional clock configurations
	 */
	extraClockCfg: {},
	/**
	 * @var {Ext.ux.form.SpinnerField} The spinner for the hours part
	 */
	hoursSpinner: null,
	/**
	 * @var {Ext.ux.form.SpinnerField} The spinner for the minutes part
	 */
	minutesSpinner: null,
	/**
	 * @var {Ext.ux.form.SpinnerField} The spinner for the seconds part
	 */
	secondsSpinner: null,
	/**
	 * @cfg {Object} Default spinner configurations
	 */
	spinnerCfg: {
		width: 40
	},
	// private
	spinnerFixBoundries: function(value){
		if(value<this.field.minValue) {
			value=this.field.maxValue;
		}
		if(value>this.field.maxValue) {
			value=this.field.minValue;
		}
		
		return this.fixPrecision(value);
	},
    // private
    onRender: function(ct, position){
        Ext.ux.form.TimePickerField.superclass.onRender.call(this, ct, position);
        this.rendered=false;
		
		var date=new Date();
		var values={};
		
		// Parse given value
		if(this.value) {
			values=this._valueSplit(this.value);
			date.setHours(values.h);
			date.setMinutes(values.m);
			date.setSeconds(values.s);
			delete this.value;
		}
		else {
			values={h:date.getHours(), m:date.getMinutes(), s:date.getSeconds()};
		}
		
		// Initialize clock
		this.clock=new Ext.ux.Clock({
			hidden: true,
			renderTo: this.el,
			date: date,
			clockCfg: Ext.apply(this.extraClockCfg, {runTask: false}, this.clockCfg)
		});
		
		var spinnerWrap=Ext.DomHelper.append(this.el, {tag: 'div'});
		
		// Base Cfg for spinner components
		var cfg=Ext.apply({}, this.spinnerCfg, {
			renderTo: spinnerWrap,
			readOnly: this.readOnly,
			disabled: this.disabled,
			listeners: {
				spin: {
					fn: this.onSpinnerChange,
					scope: this
				},
				valid: {
					fn: this.onSpinnerChange,
					scope: this
				},
				afterrender: {
					fn: function(spinner) {
						spinner.wrap.applyStyles('float: left');
					},
					single: true
				}
			}
		});
		
		// Create spinner
		this.hoursSpinner=new Ext.ux.form.SpinnerField(
			Ext.apply({}, cfg, {
				minValue: 0,
				maxValue: 23,
				cls: 'first',
				value: values.h
			})
		);
		this.minutesSpinner=new Ext.ux.form.SpinnerField(
			Ext.apply({}, cfg, {
				minValue: 0,
				maxValue: 59,
				value: values.m
			})
		);
		this.secondsSpinner=new Ext.ux.form.SpinnerField(
			Ext.apply({}, cfg, {
				minValue: 0,
				maxValue: 59,
				value: values.s
			})
		);
		
		// Enable circular spinning
		this.hoursSpinner.spinner.fixBoundries=
		this.minutesSpinner.spinner.fixBoundries=
		this.secondsSpinner.spinner.fixBoundries=
		this.spinnerFixBoundries;
		
		Ext.DomHelper.append(spinnerWrap, {tag: 'div', cls: 'x-form-clear-left'});
		
		this.rendered=true;
    },
	_valueSplit: function(v) {
		var split=v.split(':');
		return {
			h: split.length>0 ? split[0] : 0,
			m: split.length>1 ? split[1] : 0,
			s: split.length>2 ? split[2] : 0
		};
	},
	_setDate: function(v) {
		var d=this.clock.getDate();
		d.setHours(  v.h);
		d.setMinutes(v.m);
		d.setSeconds(v.s);
		this.clock.setDate(d);
		return d;
	},
	/**
	 * Eventlistener for the change and spin event of any SpinnerField component.
	 * 
	 * @return void
	 */
	onSpinnerChange: function() {
		if(!this.rendered) {
			return;
		}
		
		var d=this._setDate(this.getRawValue());
		this.fireEvent('change', this, d);
	},
	disable: function() {
		Ext.ux.form.TimePickerField.superclass.disable.call(this);
		
		this.hoursSpinner.disable();
		this.minutesSpinner.disable();
		this.secondsSpinner.disable();
	},
	enable: function() {
		Ext.ux.form.TimePickerField.superclass.enable.call(this);
		
		this.hoursSpinner.enable();
		this.minutesSpinner.enable();
		this.secondsSpinner.enable();
	},
	setReadOnly: function(r) {
		Ext.ux.form.TimePickerField.superclass.setReadOnly.call(this, r);
		
		this.hoursSpinner.setReadOnly(r);
		this.minutesSpinner.setReadOnly(r);
		this.secondsSpinner.setReadOnly(r);
	},
	clearInvalid: function() {
		Ext.ux.form.TimePickerField.superclass.clearInvalid.call(this);
		
		this.hoursSpinner.clearInvalid();
		this.minutesSpinner.clearInvalid();
		this.secondsSpinner.clearInvalid();
	},
	getRawValue: function() {
		return {
			h: this.hoursSpinner.getValue(),
			m: this.minutesSpinner.getValue(),
			s: this.secondsSpinner.getValue()
		};
	},
	setRawValue: function(v) {
		this.hoursSpinner.setValue(v.h);
		this.minutesSpinner.setValue(v.m);
		this.secondsSpinner.setValue(v.s);
	},
	isValid: function(preventMark) {
		return this.hoursSpinner.isValid(preventMark) &&
			this.minutesSpinner.isValid(preventMark) &&
			this.secondsSpinner.isValid(preventMark);
	},
	validate: function() {
		return this.hoursSpinner.validate() &&
			this.minutesSpinner.validate() &&
			this.secondsSpinner.validate();
	},
	getValue: function() {
		var v=this.getRawValue();
		return String.leftPad(v.h, 2, '0')+':'+
			   String.leftPad(v.m, 2, '0')+':'+
			   String.leftPad(v.s, 2, '0');
	},
	setValue: function(value) {
		if(!this.rendered) {
			this.value=value;
			return;
		}
		
		value=this._valueSplit(value);
		this.setRawValue(value);
		this.validate();
	}
});

Ext.form.TimePickerField=Ext.ux.form.TimePickerField;
Ext.reg('timepickerfield', Ext.form.TimePickerField);
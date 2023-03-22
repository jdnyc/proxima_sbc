/*
	Proxima 2.5 MessageBox Custom window
*/

Ext.ns('Ext.ux.ProximaWindow');

Ext.ux.ProximaWindow = Ext.extend(Ext.Window, {

	
	initComponent: function() {		
		
		Ext.ux.ProximaWindow.superclass.initComponent.call(this);		
	},
	baseCls : 'proxima25-window',
	animateTarget : true,
	style:{
			padding:'1px'
	},
	setAnimateTarget : Ext.emptyFn,
	animShow : function(){
		this.el.fadeIn({
			duration: 0.35,
			callback: this.afterShow.createDelegate(this, [true], false),
			scope: this
		});
	},
	animHide : function(){		
		if (this.el.shadow) {
			this.el.shadow.hide();
		}
		this.el.fadeOut({
			duration: 0.35,
			callback: this.afterHide,
			scope: this
		});
	},
	defaultScaleCfg: {
		duration: 0.35,
		easing: 'easeOut'
	},

	scale: function(w, h) {
		var a = Ext.lib.Anim.motion(this.el, Ext.apply({
			height: {to: h},
			width: {to: w}
		}, this.scaleCfg, this.defaultScaleCfg));
		a.onTween.addListener(function(){
			if (this.fixedCenter) {
				this.center();
			}
			this.syncSize();
			this.syncShadow();
		}, this);
		a.animate();
	}

});

Ext.reg('proxima_window', Ext.ux.ProximaWindow);
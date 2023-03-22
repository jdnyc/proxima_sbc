Ext.namespace('Ext.ux');
Ext.ux.IFramePanel = Ext.extend(Ext.Panel, {
	url :null,
	initComponent : function() {
		var config = {
				border : false,
				height : this.height,
				id : this.id,
				header :this.header,
				html: '<div id="divTab-'+this.id+'" style="height: 100%;"><iframe id="frameTab-'+this.id+'" style="overflow:auto;width:100%;height:100%;" frameborder="0"  src="'+this.url+'"></iframe></div>',
				width : this.width,
				url : this.url,
				autoScroll: true,
				url :this.url,
				bodyBorder : true,
				title:this.title,
				frame:this.frame
		};
		// apply config
	Ext.apply(this, Ext.apply(this.initialConfig, config));

	Ext.ux.IFramePanel.superclass.initComponent.call(this);
	},
	close: function(){
		var frm = document.getElementById('frameTab-'+this.id);
		if (frm && frm.parentNode) {
			frm.src='javascript:false';
			frm.parentNode.removeChild(frm);  
			var dv = document.getElementById('divTab-'+this.id);
			if (dv && dv.parentNode) dv.parentNode.removeChild(dv); 
			frm = null;
			dv = null;
			delete frm;
			delete dv;
		}
		Ext.ux.IFramePanel.superclass.destroy.call(this);
	},
	getUrl : function() {return this.url;},
	setUrl : function(url) {
		var frm = document.getElementById('frameTab-'+this.id);
		if (frm){frm.src = url || this.url;}
	}
}
);

Ext.reg('iframePanel', Ext.ux.IFramePanel);
/**
 * Create a custom component. This allows us to pre-configure some settings and
 * not allow over-rides. Also
 */
Ext.ux.PopupWindow = Ext.extend(Ext.Window, {
	url : '',
	title : this.title,
	width : 700,
	height : 600,
	frameEl : null,
	initComponent : function() {
		this.isProcessed = false;
		var config = {
			border : false,
			closable : true,
			closeAction : 'close',
			height : this.height,
			html : '<iframe id="frameWin-'+this.id+'" style="overflow:auto;width:100%;height:100%;" frameborder="0"  src="'+this.url+'"></iframe>',
			layout : 'fit',
			maximizable : true,
			modal : true,
			plain : false,
			title : this.title,
			width : this.width,
			id : this.id,
			url : this.url
		};
		// apply config
	Ext.apply(this, Ext.apply(this.initialConfig, config));

	Ext.ux.PopupWindow.superclass.initComponent.call(this);
	// define custom events
	this.addEvents('success');
	},
	processSuccessful : function() {
		this.isProcessed = true;
		this.fireEvent("success", this);
	},
	hasChanged : function() {
		return this.isProcessed;
	},
	show : function() {
		this.isProcessed = false;
		Ext.ux.PopupWindow.superclass.show.call(this);
	},

	close : function() {
		var frm = document.getElementById('frameWin-'+this.id);
		if (frm && frm.parentNode) {
			frm.src='javascript:false';
			frm.parentNode.removeChild(frm);
			frm = null;
			delete frm;
		}
		Ext.ux.PopupWindow.superclass.close.call(this);
	},

// private
	initTools : function() {
		Ext.ux.PopupWindow.superclass.initTools.call(this);
		if (this.closable) {
			this.addTool( {
				id : 'close',
				handler : this[this.closeAction].createDelegate(this, []),
				qtip : 'Close'
			});
		}
	},
	getUrl : function() {return this.url;},
	setUrl : function(url) {
		this.body.dom.src = this.url || url;
	}

});

Ext.reg('iframeWindow', Ext.ux.PopupWindow);

var dropZoneOverrides = {
	onContainerOver : function(ddSrc, evtObj, ddData) {
		var destGrid  = this.grid;
		var tgtEl    = evtObj.getTarget();
		var tgtIndex = destGrid.getView().findRowIndex(tgtEl);
		this.clearDDStyles();

		// is this a row?
		if (typeof tgtIndex === 'number') {
			var tgtRow       = destGrid.getView().getRow(tgtIndex);
			var tgtRowEl     = Ext.get(tgtRow);
			var tgtRowHeight = tgtRowEl.getHeight();
			var tgtRowTop    = tgtRowEl.getY();
			var tgtRowCtr    = tgtRowTop + Math.floor(tgtRowHeight / 2);
			var mouseY       = evtObj.getXY()[1];

			// below
			if (mouseY >= tgtRowCtr) {
				this.point = 'below';
				tgtIndex ++;
				tgtRowEl.addClass('gridRowInsertBottomLine');
				tgtRowEl.removeClass('gridRowInsertTopLine');
			}
			// above
			else if (mouseY < tgtRowCtr) {
				this.point = 'above';
				tgtRowEl.addClass('gridRowInsertTopLine');
				tgtRowEl.removeClass('gridRowInsertBottomLine')
			}
			this.overRow = tgtRowEl;
		}
		else {
			tgtIndex = destGrid.store.getCount();
		}
		this.tgtIndex = tgtIndex;

		destGrid.body.addClass('gridBodyNotifyOver');

		return this.dropAllowed;
	},
	notifyOut : function() {
		this.clearDDStyles();
	},
	clearDDStyles : function() {
		this.grid.body.removeClass('gridBodyNotifyOver');
		if (this.overRow) {
			this.overRow.removeClass('gridRowInsertBottomLine');
			this.overRow.removeClass('gridRowInsertTopLine');
		}
	},
	onContainerDrop : function(ddSrc, evtObj, ddData){
		var grid        = this.grid;
		var srcGrid     = ddSrc.view.grid;
		var destStore   = grid.store;
		var tgtIndex	= this.tgtIndex;
		var records     = ddData.selections;
		var table		= this.table;
		var id_field	= this.id_field;

		this.clearDDStyles();

		var srcGridStore = srcGrid.store;
		Ext.each(records, srcGridStore.remove, srcGridStore);

		if (tgtIndex > destStore.getCount()) {
			tgtIndex = destStore.getCount();
		}
		destStore.insert(tgtIndex, records);

		var idx = 1;
		var p = new Array();
		srcGridStore.each(function(r){
			p.push({
				table: table,
				id_field: id_field,
				id_value: r.get(id_field),
				sort: idx++
			});
		});

		Ext.Ajax.request({
			url: '/pages/menu/config/custom/user_metadata/php/edit.php',
			params: {
				action: 'sort_field',
				records: Ext.encode(p)
			},
			callback: function(opts, success, response){
                try {
                    var r = Ext.decode(response.responseText, true);
                    if(!r.success) {
						Ext.Msg.alert('오류', r.msg);
                    }
                }catch(e) {
                    alert(e.message + '(responseText: ' + response.responseText + ')');
                }
			}
		})

		return true;
	}
};

var dropZoneOverridesShowOrder = {
	onContainerOver : function(ddSrc, evtObj, ddData) {
		var destGrid  = this.grid;
		var tgtEl    = evtObj.getTarget();
		var tgtIndex = destGrid.getView().findRowIndex(tgtEl);
		var records     = ddData.selections;
		var start_index = 0;
		destGrid.store.each(function(r){
			var is_default = r.get('is_default');
			if (is_default == 1){
				start_index ++;
			}
		});
		var has_default = 0;
		for (i = 0; i < records.length; i++) {
			if (records[i].get('is_default') == 1){
				has_default = 1;
				break;
			}
		}
		if (start_index > tgtIndex || has_default == 1){
			return false;
		}
		this.clearDDStyles();

		// is this a row?
		if (typeof tgtIndex === 'number') {
			var tgtRow       = destGrid.getView().getRow(tgtIndex);
			var tgtRowEl     = Ext.get(tgtRow);
			var tgtRowHeight = tgtRowEl.getHeight();
			var tgtRowTop    = tgtRowEl.getY();
			var tgtRowCtr    = tgtRowTop + Math.floor(tgtRowHeight / 2);
			var mouseY       = evtObj.getXY()[1];

			// below
			if (mouseY >= tgtRowCtr) {
				this.point = 'below';
				tgtIndex ++;
				tgtRowEl.addClass('gridRowInsertBottomLine');
				tgtRowEl.removeClass('gridRowInsertTopLine');
			}
			// above
			else if (mouseY < tgtRowCtr) {
				this.point = 'above';
				tgtRowEl.addClass('gridRowInsertTopLine');
				tgtRowEl.removeClass('gridRowInsertBottomLine')
			}
			this.overRow = tgtRowEl;
		}
		else {
			tgtIndex = destGrid.store.getCount();
		}
		this.tgtIndex = tgtIndex;

		destGrid.body.addClass('gridBodyNotifyOver');

		return this.dropAllowed;
	},
	notifyOut : function() {
		this.clearDDStyles();
	},
	clearDDStyles : function() {
		this.grid.body.removeClass('gridBodyNotifyOver');
		if (this.overRow) {
			this.overRow.removeClass('gridRowInsertBottomLine');
			this.overRow.removeClass('gridRowInsertTopLine');
		}
	},
	onContainerDrop : function(ddSrc, evtObj, ddData){

		var grid        = this.grid;
		var srcGrid     = ddSrc.view.grid;
		var destStore   = grid.store;
		var tgtIndex	= this.tgtIndex;
		var records     = ddData.selections;
		var table		= this.table;
		var id_field	= this.id_field;

		var start_index = 0;
		grid.store.each(function(r){
			var is_default = r.get('is_default');
			if (is_default == 1){
				start_index ++;
			}
		});
		var has_default = 0;
		for (i = 0; i < records.length; i++) {
			if (records[i].get('is_default') == 1){
				has_default = 1;
				break;
			}
		}
		if (start_index > tgtIndex || has_default == 1){
			return false;
		}

		this.clearDDStyles();
		var srcGridStore = srcGrid.store;
		Ext.each(records, srcGridStore.remove, srcGridStore);

		if (tgtIndex > destStore.getCount()) {
			tgtIndex = destStore.getCount();
		}
		destStore.insert(tgtIndex, records);
		Ext.getCmp('save_order_button').enable();

		var idx = 1;
		var p = new Array();
		srcGridStore.each(function(r){
			var is_checked = r.get('is_default'); // check for default items in user metadata
			if (is_checked != 1){
				p.push({
					table: table,
					id_field: id_field,
					id_value: r.get(id_field),
					sort: idx++
				});
			}
		});
		Ext.Ajax.request({
			url: '/pages/menu/config/custom/user_metadata/php/edit.php',
			params: {
				action: 'sort_field',
				records: Ext.encode(p)
			},
			callback: function(opts, success, response){
				try {
					var r = Ext.decode(response.responseText, true);
					if (r.success) {
						//Ext.getCmp('bc_usr_meta_field').getStore().reload();
					} else {
						Ext.Msg.alert(_text('MN00022'), r.msg);
					}
				}catch(e) {
					alert(e.message + '(responseText: ' + response.responseText + ')');
				}
			}
		})

		return true;
	}
};

var newsDropZoneOverrides = {
	onContainerOver : function(ddSrc, evtObj, ddData) {
		var destGrid  = this.grid;
		var tgtEl    = evtObj.getTarget();
		var tgtIndex = destGrid.getView().findRowIndex(tgtEl);
		this.clearDDStyles();

		// is this a row?
		if (typeof tgtIndex === 'number') {
			var tgtRow       = destGrid.getView().getRow(tgtIndex);
			var tgtRowEl     = Ext.get(tgtRow);
			var tgtRowHeight = tgtRowEl.getHeight();
			var tgtRowTop    = tgtRowEl.getY();
			var tgtRowCtr    = tgtRowTop + Math.floor(tgtRowHeight / 2);
			var mouseY       = evtObj.getXY()[1];

			// below
			if (mouseY >= tgtRowCtr) {
				this.point = 'below';
				tgtIndex ++;
				tgtRowEl.addClass('gridRowInsertBottomLine');
				tgtRowEl.removeClass('gridRowInsertTopLine');
			}
			// above
			else if (mouseY < tgtRowCtr) {
				this.point = 'above';
				tgtRowEl.addClass('gridRowInsertTopLine');
				tgtRowEl.removeClass('gridRowInsertBottomLine')
			}
			this.overRow = tgtRowEl;
		}
		else {
			tgtIndex = destGrid.store.getCount();
		}
		this.tgtIndex = tgtIndex;

		destGrid.body.addClass('gridBodyNotifyOver');

		return this.dropAllowed;
	},
	notifyOut : function() {
		this.clearDDStyles();
	},
	clearDDStyles : function() {
		this.grid.body.removeClass('gridBodyNotifyOver');
		if (this.overRow) {
			this.overRow.removeClass('gridRowInsertBottomLine');
			this.overRow.removeClass('gridRowInsertTopLine');
		}
	},
	onContainerDrop : function(ddSrc, evtObj, ddData){
		var grid        = this.grid;
		var srcGrid     = ddSrc.view.grid;
		var destStore   = grid.store;
		var tgtIndex	= this.tgtIndex;
		var records     = ddData.selections;
		var table		= this.table;
		var id_field	= this.id_field;

		this.clearDDStyles();

		var srcGridStore = srcGrid.store;
		Ext.each(records, srcGridStore.remove, srcGridStore);

		if (tgtIndex > destStore.getCount()) {
			tgtIndex = destStore.getCount();
		}
		destStore.insert(tgtIndex, records);

		var idx = 1;
		var p = new Array();
		srcGridStore.each(function(r){
			p.push({
				table: table,
				id_field: id_field,
				id_value: r.get(id_field),
				sort: idx++
			});
		});

		grid.getView().refresh();

		return true;
	}
};

var CueSheetDropZoneOverrides = {
	onContainerOver : function(ddSrc, evtObj, ddData) {
		var destGrid  = this.grid;
		var tgtEl    = evtObj.getTarget();
		var tgtIndex = destGrid.getView().findRowIndex(tgtEl);
		this.clearDDStyles();

		// is this a row?
		if (typeof tgtIndex === 'number') {
			var tgtRow       = destGrid.getView().getRow(tgtIndex);
			var tgtRowEl     = Ext.get(tgtRow);
			var tgtRowHeight = tgtRowEl.getHeight();
			var tgtRowTop    = tgtRowEl.getY();
			var tgtRowCtr    = tgtRowTop + Math.floor(tgtRowHeight / 2);
			var mouseY       = evtObj.getXY()[1];

			// below
			if (mouseY >= tgtRowCtr) {
				this.point = 'below';
				tgtIndex ++;
				tgtRowEl.addClass('gridRowInsertBottomLine');
				tgtRowEl.removeClass('gridRowInsertTopLine');
			}
			// above
			else if (mouseY < tgtRowCtr) {
				this.point = 'above';
				tgtRowEl.addClass('gridRowInsertTopLine');
				tgtRowEl.removeClass('gridRowInsertBottomLine')
			}
			this.overRow = tgtRowEl;
		}
		else {
			tgtIndex = destGrid.store.getCount();
		}
		this.tgtIndex = tgtIndex;

		destGrid.body.addClass('gridBodyNotifyOver');

		return this.dropAllowed;
	},
	notifyOut : function() {
		this.clearDDStyles();
	},
	clearDDStyles : function() {
		this.grid.body.removeClass('gridBodyNotifyOver');
		if (this.overRow) {
			this.overRow.removeClass('gridRowInsertBottomLine');
			this.overRow.removeClass('gridRowInsertTopLine');
		}
	},
	onContainerDrop : function(ddSrc, evtObj, ddData){
		var grid        = this.grid;
		var srcGrid     = ddSrc.view.grid;
		var destStore   = grid.store;
		var tgtIndex	= this.tgtIndex;
		var records     = ddData.selections;
		var table		= this.table;
		var id_field	= this.id_field;

		this.clearDDStyles();

		var srcGridStore = srcGrid.store;
		Ext.each(records, srcGridStore.remove, srcGridStore);
		// Drag & Drop 시 그리드 데이터가 수정이 되었다는 것을 알려주기 위해서 is_dirty 를 true로 변경해줌
		grid.is_dirty = true;

		if (tgtIndex > destStore.getCount()) {
			tgtIndex = destStore.getCount();
		}
		destStore.insert(tgtIndex, records);

		var idx = 1;
		var p = new Array();
		srcGridStore.each(function(r){
			p.push({
				table: table,
				id_field: id_field,
				id_value: r.get(id_field),
				sort: idx++
			});
		});

		grid.getView().refresh();

	/*		// 저장 버튼을 눌렀을때만 저장하도록 하기 위해서 저장하는 부분은 주석처리
		Ext.Ajax.request({
			url: '/store/cuesheet/cuesheet_action.php',
			params: {
				action: 'sort_field',
				records: Ext.encode(p)
			},
			callback: function(opts, success, response){
                            try {
                                var r = Ext.decode(response.responseText, true);
                                if(!r.success) {
                                    Ext.Msg.alert('오류', r.msg);
                                } else {
                                    Ext.getCmp('cuesheet_items').getView().refresh();
                                }
                            }catch(e) {
                                alert(e.message + '(responseText: ' + response.responseText + ')');
                            }
			}
		})
	*/
		return true;
	}
};
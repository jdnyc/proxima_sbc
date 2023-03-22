<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/timecode.class.php');
?>
(function(){
	Ext.ns('Ariel');

	var that = this;
	var lastSeekPos = 0;

	var EditCutDropZoneOverrides = {
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
			//업데이트 time
			var newRecord = new Array();
			srcGridStore.each(function(r){
				var index = r.store.indexOfId( r.id );

				if(index == 0){
					var startsec = 0;
					var start = '00:00:00';
					var end = r.get('duration');
					var endsec = r.get('durationsec');

				}else{
					var bfRecord = r.store.getAt( index - 1 );
					var start = bfRecord.get('endtc');
					var startsec = bfRecord.get('endsec');
					var endsec = bfRecord.get('endsec') + r.get('durationsec');
					var end =   Ext.getCmp('player_warp').secToTimecode(endsec);
				}

				r.set('starttc', start);
				r.set('endtc', end);
				r.set('startsec', startsec);
				r.set('endsec', endsec);
				r.commit();

			});

			return true;
		}
	};


	Ariel.BISWindow = Ext.extend(Ext.Window, {
		//width: '95%',
		tid : null,
		target_grid : null,
		top: 50,
		minWidth:  1024,
		minHeight: 500,
		modal: true,
		layout: 'fit',
		maximizable: true,
		maximized: true,
		listeners: {
			render: function(self){
				self.mask.applyStyles({
					"opacity": "0.5",
					"background-color": "#000000"
				});

			},
			move: function(self, x, y){//창이 윈도우 포지션을 벗어났을때 0으로 셋팅
				var pos = self.getPosition();
				if(pos[0]<0){
					self.setPosition(0,pos[1]);
				}else if(pos[1]<0){
					self.setPosition(pos[0],0);
				}
			},
			close: function(self){
			}
		},

		initComponent: function(config){
			Ext.apply(this, config || {});

			this.items = {
				border: false,
				layout: 'fit',

				items: [
				],
				afterrender: function(self){

				}
			};

			Ariel.BISWindow.superclass.initComponent.call(this);
		}
	});

	new Ariel.BISWindow().show();
})()
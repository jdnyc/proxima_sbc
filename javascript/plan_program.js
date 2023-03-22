Ext.define('Ariel.program.Plan', {
	extend: 'Ext.grid.Panel',
	alias: 'widget.planprogram',

	initComponent: function() {
		var _this = this;
		var store = Ext.create('Ariel.data.DayPlanProgram');

		store.on('beforeload', _this.onStoreBeforeLoad, _this);

		Ext.apply(this, {
			store: store,

			columns: [
				{dataIndex: 'sort_seq', text: '정렬순반', width: 60},
				{dataIndex: 'trff_time', text: '운행시각', xtype: 'datecolumn', format: 'H:i:s', align: 'center'},
				{dataIndex: 'trff_run', text: '운행길이', xtype: 'datecolumn', format: 'H:i:s', align: 'center'},
				{dataIndex: 'mtrl_id', text: '소재ID'},
				{dataIndex: 'epsd_no', text: '회차', width: 60},
				{dataIndex: 'mtrl_nm', text: '소재명', flex: 1},

				{dataIndex: 'trff_seq', text: '운행순번', hidden: true},
				{dataIndex: 'brd_hm', text: '편성시각', width: 60, xtype: 'datecolumn', format: 'H:i', hidden: true},
				{dataIndex: 'brd_run', text: '편성길이', width: 60, hidden: true},
				{dataIndex: 'view_hm', text: '편성표시시각', xtype: 'datecolumn', format: 'H:i', hidden: true},
				{dataIndex: 'tcin', text: 'TCIN', xtype: 'datecolumn', format: 'H:i:s', hidden: true},
				{dataIndex: 'arc_yn', text: '아카이브여부', hidden: true},
				{dataIndex: 'audio_clf', text: '오디오구분', hidden: true},
				{dataIndex: 'brd_ymd', text: '편성일자', hidden: true},
				{dataIndex: 'chan_cd', text: '채널', hidden: true},
				{dataIndex: 'clip_yn', text: '인코딩여부', hidden: true},
				{dataIndex: 'delib_grd', text: '등급', hidden: true},
				{dataIndex: 'device_id', text: '장치ID', hidden: true},
				{dataIndex: 'duration', text: 'DURATION', hidden: true},
				{dataIndex: 'pgm_id', text: '프로그램코드', hidden: true},
				{dataIndex: 'pgm_nm', text: '프로그램명', width: 200, hidden: true},
				{dataIndex: 'mtrl_clf', text: '소재구분', width: 60, hidden: true},
				{dataIndex: 'trff_clf', text: '운행구분', hidden: true},
				{dataIndex: 'trff_info1', text: 'TRFF_INFO1', hidden: true},
				{dataIndex: 'trff_info2', text: 'TRFF_INFO2', hidden: true},
				{dataIndex: 'trff_info3', text: 'TRFF_INFO3', hidden: true},
				{dataIndex: 'trff_info4', text: 'TRFF_INFO4', hidden: true},
				{dataIndex: 'trff_info5', text: 'TRFF_INFO5', hidden: true},
				{dataIndex: 'trff_no', text: '운행안번호', hidden: true},
				{dataIndex: 'trff_ymd', text: '운행일자', hidden: true},
				{dataIndex: 'mtrl_info', text: '소재정보', hidden: true},
				{dataIndex: 'epsd_id', text: '회차ID', hidden: true},
				{dataIndex: 'epsd_nm', text: '부제명', hidden: true},
				{dataIndex: 'event_ctrl', text: 'APC 이벤트제어구분', hidden: true},
				{dataIndex: 'event_out', text: 'APC  OUTPUT', hidden: true},
				{dataIndex: 'event_rate', text: 'APC 이벤트전환속도', hidden: true},
				{dataIndex: 'event_size', text: 'APC 전환비', hidden: true},
				{dataIndex: 'event_som', text: 'APC SOM', hidden: true},
				{dataIndex: 'event_trns', text: 'APC 이벤트전환구분', hidden: true},
				{dataIndex: 'event_typ', text: 'APC 이벤트타입', hidden: true},
				{dataIndex: 'gpi_id', text: 'GPI_ID', hidden: true},
				{dataIndex: 'house_no', text: 'HOUSE_NO', hidden: true},
				{dataIndex: 'logo_id', text: 'LOGO ID', hidden: true},
				{dataIndex: 'mod_dt', text: '수정일시', hidden: true},
				{dataIndex: 'modr', text: '수정자', hidden: true},
				{dataIndex: 'reg_dt', text: '등록일시', hidden: true},
				{dataIndex: 'regr', text: '등록자', hidden: true},
				{dataIndex: 'start_typ', text: '시작구분', hidden: true},
				{dataIndex: 'svr_info', text: '소재정보', hidden: true},
				{dataIndex: 'tape_id', text: '테잎아이디', hidden: true},
			]
		});

		this.callParent(arguments);

		store.load();

		this.query('datefield')[0].on('change', function() {
			store.load();
		});
	},

	tbar: ['운행날짜', {
		xtype: 'datefield',
		width: 120,
		format: 'Y-m-d',
		submitFormat: 'Ymd',
		value: new Date()
	}, {
		text: '새로고침',
		handler: function(btn) {
			btn.ownerCt.ownerCt.getStore().reload();
		}
	}],

	viewConfig: {
		loadMask: true
	},

	onStoreBeforeLoad: function(store, operation) {
		var datefield = this.query('datefield')[0];

		store.getProxy().setExtraParams({
			chan_cd: 'CH_B',
			trff_ymd: Ext.Date.format(datefield.getValue(), 'Ymd'),
			trff_clf: 0,
			trff_no: 1
		});
	}
});
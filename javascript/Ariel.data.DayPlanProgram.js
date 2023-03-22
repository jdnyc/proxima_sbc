Ext.define('Ariel.data.DayPlanProgram', {
	extend: 'Ext.data.Store', 

	autoLoad: true,
	fields: [
		'chan_cd', 'trff_ymd', 'trff_clf', 'trff_no', 'trff_time', 'trff_run', 'mtrl_clf',
		'house_no', 'mtrl_id', 'mtrl_nm', 'mtrl_info', 'tape_id', 'tcin', 'duration', 'arc_yn', 'clip_yn',
		'svr_info', 'brd_ymd', 'view_hm', 'brd_run', 'pgm_id', 'pgm_nm', 'epsd_id', 'epsd_no', 'epsd_nm', 
		'delib_grd', 'brd_typ', 'trff_info1', 'trff_info2', 'trff_info3', 'trff_info4', 'trff_info5', 'device_id',
		'start_typ', 'logo_id', 'cg_id', 'gpi_id', 'audio_clf', 'event_som', 'event_out', 'event_typ', 'event_ctrl', 
		'event_trns', 'event_rate', 'event_size', 'bin_no', 'brd_yn', 'act_time', 'act_run', 'regr', 'reg_dt', 'modr', 'mod_dt',
		{name: 'trff_seq', type: 'int'},
		{name: 'sort_seq', type: 'int'},
		{name: 'brd_hm', type: 'date', dateFormat: 'Hi'},
		{name: 'tcin', type: 'date', dateFormat: 'Hisu'},
		{name: 'trff_run', type: 'date', dateFormat: 'Hisu'},
		{name: 'trff_time', type: 'date', dateFormat: 'Hisu'},
		{name: 'view_hm', type: 'date', dateFormat: 'Hi'}
	],
	proxy: {
		type: 'ajax',
		url: 'store/plan_program.php',
		reader: {
			type: 'json',
			root: 'data'
		}
	},
	sorters: [{
		property: 'sort_seq',
		direction: 'asc'
	}]
});
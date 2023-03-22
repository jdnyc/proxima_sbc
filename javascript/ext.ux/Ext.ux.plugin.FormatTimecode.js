// 네임스페이스 생성
Ext.ns('Ext.ux.plugin');
function RightPad(val, size, ch) {
	var result = String(val);
	if(!ch) {
		ch = " ";
	}
	while (result.length < size) {
		result = result+ ch;
	}
	return result;
}
//console.log('page loaded');
// 새로운 timecodeframe valid type 추가
Ext.apply(Ext.form.VTypes, {
	timecodeframe: function (v) {
		return this.timecodeframeRe.test(v);
//		return true;
	},
	timecodeframeText: '00:00:00:00 형식으로 입력하세요.',
	timecodeframeRe: /^[0-9]{2}:[0-9]{2}:[0-9]{2}:[0-9]{2}$/i,
	timecodeframeMask: /[0-9]/,

	timecode: function (v) {
		return this.timecodeRe.test(v);
	//		return true;
	},
	timecodeText: '00:00:00 형식으로 입력하세요.',
	timecodeRe: /^[0-9]{2}:[0-9]{2}:[0-9]{2}$/i,
	timecodeMask: /[0-9]/,

	brodsttm: function (v) {
		return this.brodsttmRe.test(v);
		//		return true;
	},
	brodsttmText: '00:00 형식으로 입력하세요.',
	brodsttmRe: /^[0-9]{2}:[0-9]{2}$/i,
	brodsttmMask: /[0-9]/


});

Ext.apply(Ext.util.Format, {
	timecodeframe: function (value) {
		// 00:00:00:00 형식으로 변경 추가
		var reg = /[0-9]{8}/;
		value = value.replace(/[\D]/gi, '');
		value = value.substr(0,8);
		value = RightPad(value, 8, '0');
		if (reg.test(value)) {
			return value.replace(/([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/, "$1:$2:$3:$4");
		}
		else {
			return value;
		}
	},	
	timecode: function (value) {
		// 00:00:00 형식으로 변경 추가
		var reg = /[0-9]{6}/;
		value = value.replace(/[\D]/gi, '');
		value = value.substr(0,6);
		value = RightPad(value, 6, '0');
		if (reg.test(value)) {
			return value.replace(/([0-9]{2})([0-9]{2})([0-9]{2})/, "$1:$2:$3");
		}
		else {
			return value;
		}
	},	
	brodsttm: function (value) {
		// 00:00 형식으로 변경 추가
		var reg = /[0-9]{4}/;
		value = value.replace(/[\D]/gi, '');
		value = value.substr(0,4);
		value = RightPad(value, 4, '0');
		if (reg.test(value)) {
			return value.replace(/([0-9]{2})([0-9]{2})/, "$1:$2");
		}
		else {
			return value;
		}
	}
});

// form textfield 에 000000 으로 입력시 00:00:00 형식으로 자동 변경 플러그인
Ext.ux.plugin.FormatTimecodeFrame = Ext.extend(Ext.form.TextField, {
	init: function (c) {
		//console.log('format timecodeframe init');
		c.on('change', this.onChange, this);
	},
	onChange: function (c) {
		//console.log('format timecodeframe onchange');
		c.setValue(Ext.util.Format.timecodeframe(c.getValue()));
	}
});

// form textfield 에 000000 으로 입력시 00:00:00 형식으로 자동 변경 플러그인
Ext.ux.plugin.FormatTimecode = Ext.extend(Ext.form.TextField, {
	init: function (c) {
		//console.log('format timecode init');
		c.on('change', this.onChange, this);
	},
	onChange: function (c) {
		//console.log('format timecode onchange');
        c.setValue(Ext.util.Format.timecode(c.getValue()));
	}
});

// form textfield 에 0000 으로 입력시 00:00 형식으로 자동 변경 플러그인
Ext.ux.plugin.FormatBrodsttm = Ext.extend(Ext.form.TextField, {
	init: function (c) {
		//console.log('format brodsttm init');
		c.on('change', this.onChange, this);
	},
	onChange: function (c) {
		//console.log('format brodsttm onchange');
		c.setValue(Ext.util.Format.brodsttm(c.getValue()));
	}
});
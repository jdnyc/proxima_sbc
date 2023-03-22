<?php
// 2020.01.10 hkkim 사용하지 않는것 같음
// session_start();
// require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/functions.php');
// require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/lang.php');

// $type_list = $db->queryAll("
// 				SELECT	TYPE, NAME
// 				FROM	BC_TASK_TYPE
// 				ORDER BY TYPE ASC
// 			");

// $comp_array = array();

// $allow_list = array(
// 	10, 11, 15, 20, 22, 27, 29, 60, 69, 70, 80
// );

// foreach ($type_list as $list) {
// 	$name = $list['name'];
// 	$type = $list['type'];

// 	if (!in_array($type, $allow_list)) continue;

// 	$comp =	"
// 		{
// 			title: '$name',
// 			layout: 'fit',
// 			items: new Ariel.Task.monitor.TaskPanelWM({taskType: $type })
// 		}
// 	";

// 	$comp_array[] = $comp;
// }
?>

/*
Ext.ns('Ariel.Task.monitor');
var taskMonitorPageSize = 20;

function buildTBarRetry(){
return {
//>>text: '재시작',
text: _text('MN00045'),
icon: '/led-icons/arrow_redo.png',
handler: function(btn, e){
var that = Ext.getCmp('task_tab_wm').getActiveTab().get(0);
if(!that.getSelectionModel().hasSelection()){
//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
Ext.Msg.alert(_text('MN00023'), _text('MSG00114'));
return;
}

var sm = that.getSelectionModel(),
task_id_list = [];

var isVaild = false;
Ext.each(sm.getSelections(), function (r) {


//인제스트용 클라이언트 작업상태이므로 제공안됨
if( r.get('type') == '69' || r.get('type') == '89' || r.get('type') == '29' ){
isVaild = true;
}else{
task_id_list.push(r.get('id'));
}
});

if(isVaild){
Ext.Msg.alert(_text('MN00023'), '클라이언트 작업은 변경 할 수 없습니다.');
return;
}

var name = _text('MN00045');
var action= 'retry';

that.request(name, action, task_id_list, that);
},
scope: this
}
};


function buildTBarStatusCheck2(){
return ['-',{
xtype: 'checkbox',
//>>boxLabel: '전체',
group: 'toggle_all',
boxLabel: _text('MN00246'),
listeners: {
check: function(self, checked){
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});

Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},'-',{
xtype: 'checkbox',
checked: true,
//>>boxLabel: '처리 중',
boxLabel: _text('MN00262'),
status: 'processing',
group: 'toggle',
listeners: {
check: function(self, checked){
if(!checked) {
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle_all'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});
}
Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},'-',{
xtype: 'checkbox',
//>>boxLabel: '대기 중',
boxLabel: _text('MN00160'),
status: 'queue',
group: 'toggle',
listeners: {
check: function(self, checked){
if(!checked){
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle_all'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});
}
Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},'-',{
xtype: 'checkbox',
//>>boxLabel: '성공',
boxLabel: _text('MN00015'),
status: 'complete',
group: 'toggle',
listeners: {
check: function(self, checked){
if(!checked){
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle_all'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});
}
Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},'-',{
xtype: 'checkbox',
//>>boxLabel: '실패',
boxLabel: _text('MN00016'),
status: 'error',
group: 'toggle',
listeners: {
check: function(self, checked){
if(!checked){
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle_all'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});
}
Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},'-',{
xtype: 'checkbox',
boxLabel: '취소',
//>>boxLabel: _text('MN00016'),
status: 'cancel',
group: 'toggle',
listeners: {
check: function(self, checked){
if(!checked){
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle_all'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});
}
Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},{
xtype: 'checkbox',
boxLabel: '삭제',
hidden: true,
//>>boxLabel: _text('MN00016'),
status: 'delete',
group: 'toggle',
listeners: {
check: function(self, checked){
if(!checked){
var toolbar = self.ownerCt;
Ext.each(toolbar.find('group', 'toggle_all'), function(i){
i.suspendEvents();
i.setValue(checked);
i.resumeEvents();
});
}
Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
}
}
},'-']
};


Ariel.Task.monitor.TaskPanelWM = Ext.extend(Ext.grid.EditorGridPanel, {
loadMask: true,
clicksToEdit: 1,
border: false,
listeners: {
viewready: function(self){
//self.getStore().reload();
},
rowcontextmenu: function(self, rowIndex, e){

e.stopEvent();

var sm = self.getSelectionModel();
if (!sm.isSelected(rowIndex)) {
sm.selectRow(rowIndex);
}

var that = this;

var menu = new Ext.menu.Menu({
items: [
buildTBarRetry()
]
});
menu.showAt(e.getXY());
}
},

initComponent: function(){
function renderTaskMonitorStatus(v) {
switch(v){
case 'complete':
//>>v = '성 공';
v = _text('MN00011');
break;

case 'down_queue':
case 'watchFolder':
case 'queue':
case 'pending':
//>>v = '대 기';
v = _text('MN00039');
break;

case 'error':
//>>v = '실 패';
v = _text('MN00012');
break;

case 'assign':
case 'assigning':
v = '작업확인';
break;

case 'processing':
case 'progressing':
//>>v = '처리중';
v = _text('MN00262');
break;

case 'cancel':
//>>v = '취소 대기중';
v = _text('MN00004');
break;

case 'canceling':
//>>v = '취소 중';
v = _text('MN00004');
break;

case 'canceled':
//>>v = '취소됨';
v = _text('MN00004');
break;

case 'retry':
//>>v = '재시작';
v = _text('MN00006');
break;

case 'delete':
//>>v = '재시작';
v = '삭제';
break;
}

return v;
}

function renderTaskMonitorType(v) {
switch(v){
case '60':
v = '전송(FS)';
break;
case '130':
v = '미디어정보';
break;
case '100':
v = '스토리지 삭제';
break;
case '10':
v = '카탈로깅';
break;
case '11':
v = '썸네일';
break;
case '15':
v = 'QC';
break;
case '20':
v = '트랜스코딩(MP4)';
break;
case '22':
v = '트랜스코딩(Image)';
break;
case '27':
v = 'GPU 트랜스코딩';
break;
case '69':
v = '전송(FS-Client)';
break;
case '70':
v = '트랜스코딩(Audio)';
break;
case '80':
v = '전송(FTP)';
break;
}

return v;
}

function renderMonitorDestination(v, metadata, record, rowIndex, colIndex, store) {
metadata.attr = 'style="text-align: left"';
var dest = record.get('destination');
if (dest) {
return '<b>'+dest+'</b> ';
} else {
return v;
}
}

this.store = new Ext.data.JsonStore({
url: '/store/get_task.php',
totalProperty: 'total',
idProperty: 'task_id',
root: 'data',
fields: [
{name: 'id', mapping: 'task_id'},
{name: 'type'},
{name: 't_name'},
{name: 'target'},
{name: 'source'},
{name: 'progress'},
{name: 'reg_user_id'},
{name: 'status'},
{name: 'parameter'},
{name: 'destination'},
{name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
{name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'},
{name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
{name: 'name'},
{name: 'value'},
{name: 'content_id'},
{name: 'media_id'},
{name: 'title'},
{name: 'assign_ip'},
{name: 'assign_system'},
{name: 'user_task_name'},
{name: 'register', convert: function(v, record) {
return record.task_user_id + '(' + record.task_user_name + ')';
}},
],
listeners: {
beforeload: function(self, opts){
var g = Ext.getCmp('task_tab_wm').getActiveTab().get(0);

self.baseParams = {
taskType: g.taskType,
task_status: g.getChecked(Ext.getCmp('task_tab_wm').getTopToolbar()),
limit: taskMonitorPageSize,
start: 0,
start_date: Ext.getCmp('task_tab_wm').getTopToolbar().find('name', 'sdate')[0].getValue().format('Ymd000000'),
end_date: Ext.getCmp('task_tab_wm').getTopToolbar().find('name', 'edate')[0].getValue().format('Ymd240000'),
title: Ext.getCmp('task_tab_wm').getTopToolbar().find('name', 'search_value')[0].getValue()
};
}
}
});
this.bbar = new Ext.PagingToolbar({
store: this.store,
pageSize: taskMonitorPageSize,
displayInfo: true
});

Ext.apply(this, {
request: function(name, action, task_id_list, that, reason){
Ext.Msg.show({
title: _text('MN00024'),
msg: name+' 하시겠습니까?',
icon: Ext.Msg.WARNING,
buttons: Ext.Msg.OKCANCEL,
fn: function(btnId){
if (btnId == 'ok') {
Ext.Ajax.request({
url: '/store/send_task_action.php',
params: {
'task_id_list[]': task_id_list,
action: action,
reason: reason
},
callback: function(options, success, response){
if(success) {
try {
var r = Ext.decode(response.responseText);
if(!r.success) {
//>>Ext.Msg.alert('오류', r.msg);
Ext.Msg.alert(_text('MN00022'), r.msg);
} else {
Ext.Msg.alert(_text('MN00024'), name+ ' '+ _text('MN00011'));

if(!Ext.isEmpty(that.getStore())) that.getStore().reload();
}
} catch (e) {
Ext.Msg.alert(e['name'], e['message']);
}
} else {
Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'( '+response.status+' )');
}
}
});
}
}
});
},

buildReason: function(name,action, id, that){
new Ext.Window({
title: name +' 사유',
width: 400,
height: 209,
resizable: false,
modal: true,
layout: 'fit',
items: {
xtype: 'form',
baseCls: 'x-plain',
border: false,
labelSeparator: '',
defaults: {
anchor: '100%',
border: false
},
items: [{
xtype: 'textarea',
height: 140,
hideLabel: true,
name: 'reason'
}]
},
buttonAlign: 'center',
buttons: [{
text: name+' 요청',
scale: 'medium',
handler: function(btn, e){
var reason = btn.ownerCt.ownerCt.get(0).get(0).getValue();
that.request(name,action, id, that, reason);

btn.ownerCt.ownerCt.close();
}
},{
text: '작성 취소',
scale: 'medium',
handler: function(btn, e){
btn.ownerCt.ownerCt.close();
}
}]
}).show();
},

buildTBarCancel: function(that){
return {
//>>text: '취소',
text: _text('MN00004'),
tooltip: '대기중인 작업을 취소합니다.<br />'
+'Tape아카이브 작업은 대기상태로,<br />'
+'Archive스토리지 삭제 작업은 삭제대기상태로 변경됩니다.',
icon: '/led-icons/cancel.png',
handler: function(){
var sm = that.getSelectionModel();
if(!sm.hasSelection()){
//>>Ext.Msg.alert('정보', '취소 하실 항목을 선택해주세요');
Ext.Msg.alert(_text('MN00023'), _text('MSG00118'));
return;
}

var id = [];
var status_error = false;
var status = '';
Ext.each(sm.getSelections(), function (r) {
id.push(r.get('id'));
status = r.get('status');
if( status != 'queue' && status != 'cancel' && status != 'error' )
{
status_error = true;
}
});
if( status_error )
{
Ext.Msg.alert('정보', '대기중인 작업만 취소 가능합니다.');
return;
}

//var id = sm.getSelected().get('id');
//console.log(id);return;
var name = _text('MN00004');
var action= 'cancel';
var store = that.getStore();

that.buildReason(name,action, id, that);
}
}
},

buildChangePriority: function(that){
return {
text: '우선순위 변경',
icon: '/led-icons/text_padding_top.png',
handler: function(btn, e){
if(!this.getSelectionModel().hasSelection()){
//>>Ext.Msg.alert('정보', '재시작 하실 항목을 선택해주세요');
Ext.Msg.alert(_text('MN00023'), '우선순위를 지정할 항목을 선택 해 주세요.');
return;
}

var sm = this.getSelectionModel(),
task_id_list = [];

var stop_flag = false;
var stop_flag2 = false;
var this_type = sm.getSelections()[0].get('type');
Ext.each(sm.getSelections(), function (r) {
task_id_list.push(r.get('id'));
if( r.get('type') != this_type )
{
stop_flag = true;
}
if( r.get('status') != 'queue' )
{
stop_flag2 = true;
}
});

if(stop_flag) {
Ext.Msg.alert('알림', 'Type값이 같은 작업끼리 선택 해 주세요.');
return;
}

if(stop_flag2) {
Ext.Msg.alert('알림', '대기중인 자료만 변경할 수 있습니다.');
return;
}

var type_msg, type_min, type_max;
switch(this_type) {
case 'archive':
type_min = 21;
type_max = 100;
type_msg = ' - Tape아카이브는 기본 40, 최소 '+type_min+', 최대 '+type_max+'<br />';
break;
case 'restore':
case 'pfr_restore':
type_min = 51;
type_max = 100;
type_msg = ' - 리스토어, Partial리스토어는 기본 60, 최소 '+type_min+', 최대 '+type_max+'<br />';
break;
case 'delete':
type_min = 1;
type_max = 100;
type_msg = ' - Tape아카이브 삭제는 기본 50, 최소 '+type_min+', 최대 '+type_max+'<br />';
break;

default:
Ext.Msg.alert('알림', '선택하신 작업은 Tape아카이브 관련 작업이 아닙니다.');
return;
break;
}

var name = '우선순위 변경';
var action = 'change_priority';

var pri_win = new Ext.Window({
title: name,
width: 350,
modal: true,
height: 200,
layout: 'fit',
items: [{
xtype: 'form',
padding: 15,
items: [{
xtype: 'displayfield',
hideLabel: true,
value: '# 우선순위 값이 높을수록 작업이 먼저 수행됩니다.<br />'+type_msg+'<br />&nbsp'
},{
xtype: 'numberfield',
id: 'pri_val',
fieldLabel: '우선순위',
value: ''
}]
}],
buttons: [{
text: '변경',
icon: '/led-icons/accept.png',
handler: function(b, e){
var pri_val = Ext.getCmp('pri_val').getValue();

if(pri_val < type_min) { Ext.Msg.alert('알림', '우선순위 설정값이 최저값(' +type_min+') 이하 입니다.'); return; } if(pri_val> type_max)
	{
	Ext.Msg.alert('알림', '우선순위 설정값이 최대값('+type_max+') 이상 입니다.');
	return;
	}

	Ext.Msg.show({
	title: name,
	msg: name+' 하시겠습니까?',
	icon: Ext.Msg.WARNING,
	buttons: Ext.Msg.OKCANCEL,
	fn: function(btnId){
	if (btnId == 'ok')
	{
	Ext.Ajax.request({
	url: '/store/send_task_action.php',
	params: {
	'task_id_list[]': task_id_list,
	action: action,
	pri_val: pri_val
	},
	callback: function(options, success, response){
	if(success)
	{
	try
	{
	var r = Ext.decode(response.responseText);
	if(!r.success)
	{
	//>>Ext.Msg.alert('오류', r.msg);
	Ext.Msg.alert(_text('MN00022'), r.msg);
	}
	else
	{
	Ext.Msg.alert(_text('MN00024'), name+ ' '+ _text('MN00011'));

	if(!Ext.isEmpty(that.getStore())) that.getStore().reload();
	}
	}
	catch (e)
	{
	Ext.Msg.alert(e['name'], e['message']);
	}
	}
	else
	{
	Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'( '+response.status+' )');
	}

	pri_win.close();
	}
	})
	}
	}
	});
	}
	},{
	text: '취소',
	icon: '/led-icons/cross.png',
	handler: function(b, e){
	pri_win.close();
	}
	}]
	});
	pri_win.show();
	},
	scope: this
	}
	},
	selModel: new Ext.grid.RowSelectionModel({
	listeners: {
	rowselect: function(self){
	Ext.getCmp('log_wm').getStore().load();
	},
	rowdeselect: function(self){
	Ext.getCmp('log_wm').getStore().removeAll();
	}
	}
	}),
	cm: new Ext.grid.ColumnModel({
	columns: [
	{header: 'ID', dataIndex: 'id', width: 60, hidden: true},
	{header: '작업 흐름명', dataIndex: 'user_task_name', width: 120},
	{header: '작업 모듈명', dataIndex: 't_name', width: 170},
	{header: _text('MN00287'), dataIndex: 'content_id', width: 100, hidden: true },
	{header: _text('MN00171'), dataIndex: 'media_id', width: 100 , hidden: true },
	{header: _text('MN00249'), dataIndex: 'title', width: 250},
	{header: _text('MN00120'), dataIndex: 'register', width: 130},
	{header: _text('MN00138'), dataIndex: 'status', align: 'center', width: 80, renderer: renderTaskMonitorStatus},
	new Ext.ux.ProgressColumn({
	header: _text('MN00261'),
	width: 105,
	dataIndex: 'progress',
	//divisor: 'price',
	align: 'center',
	renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
	return Ext.util.Format.number(pct, "0%");
	}
	}),
	{header: '<center>'+_text('MN00220')+'</center>', dataIndex: 'source', width: 200, menuDisabled: true, editor: new Ext.form.TextField({
	allowBlank: true,
	readOnly: true
	})},
	{header: '<center>대상</center>', dataIndex: 'target', width: 200, menuDisabled: true, editor: new Ext.form.TextField({
	allowBlank: true,
	readOnly: true
	})},
	{header: _text('MN00299'), dataIndex: 'parameter', align: 'center', width: 150 , hidden: true },
	{header: _text('MN00102'), dataIndex: 'creation_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
	{header: _text('MN00233'), dataIndex: 'start_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
	{header: _text('MN00234'), dataIndex: 'complete_datetime', align: 'center', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130},
	{header: '할당시스템', dataIndex: 'assign_system', align: 'center', hidden: false}
	]
	}),
	viewConfig: {
	//>>emptyText: '등록된 작업이 없습니다.',
	emptyText: _text('MSG00157'),
	//forceFit: true,
	listeners: {
	refresh: function(self) {
	Ext.getCmp('log_wm').getStore().removeAll();
	}
	}
	}
	});

	Ariel.Task.monitor.TaskPanelWM.superclass.initComponent.call(this);
	},

	getChecked: function(toolbar){

	var status_checkbox,
	tmp,
	status_group = new Array();

	status_checkbox = toolbar.find('group', 'toggle');

	Ext.each(status_checkbox, function (checkbox) {
	if (checkbox.checked) {
	status_group.push(checkbox.status);
	}
	});

	return "'"+status_group.join("','")+"'";
	},
	buildTBarStatusCheck: function(){
	var owner = this;

	return ['-',{
	xtype: 'checkbox',
	//>>boxLabel: '전체',
	group: 'toggle_all',
	boxLabel: _text('MN00246'),
	listeners: {
	check: function(self, checked){
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});

	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},'-',{
	xtype: 'checkbox',
	checked: true,
	//>>boxLabel: '처리 중',
	boxLabel: _text('MN00262'),
	status: 'processing',
	group: 'toggle',
	listeners: {
	check: function(self, checked){
	if(!checked) {
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle_all'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});
	}
	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},'-',{
	xtype: 'checkbox',
	//>>boxLabel: '대기 중',
	boxLabel: _text('MN00160'),
	status: 'queue',
	group: 'toggle',
	listeners: {
	check: function(self, checked){
	if(!checked){
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle_all'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});
	}
	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},'-',{
	xtype: 'checkbox',
	//>>boxLabel: '성공',
	boxLabel: _text('MN00015'),
	status: 'complete',
	group: 'toggle',
	listeners: {
	check: function(self, checked){
	if(!checked){
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle_all'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});
	}
	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},'-',{
	xtype: 'checkbox',
	//>>boxLabel: '실패',
	boxLabel: _text('MN00016'),
	status: 'error',
	group: 'toggle',
	listeners: {
	check: function(self, checked){
	if(!checked){
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle_all'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});
	}
	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},'-',{
	xtype: 'checkbox',
	boxLabel: '취소',
	//>>boxLabel: _text('MN00016'),
	status: 'cancel',
	group: 'toggle',
	listeners: {
	check: function(self, checked){
	if(!checked){
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle_all'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});
	}
	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},{
	xtype: 'checkbox',
	boxLabel: '삭제',
	hidden: true,
	//>>boxLabel: _text('MN00016'),
	status: 'delete',
	group: 'toggle',
	listeners: {
	check: function(self, checked){
	if(!checked){
	var toolbar = self.ownerCt;
	Ext.each(toolbar.find('group', 'toggle_all'), function(i){
	i.suspendEvents();
	i.setValue(checked);
	i.resumeEvents();
	});
	}
	Ext.getCmp('task_tab_wm').getActiveTab().get(0).getStore().load();
	}
	}
	},'-']
	}
	});


	Ariel.Nps.TaskMonitor = Ext.extend(Ext.Panel, {
	layout: 'fit',
	autoScroll: true,
	initComponent: function(config) {
	Ext.apply(this, config || {});
	var that = this;

	Ext.BLANK_IMAGE_URL = '/ext/resources/images/default/s.gif';

	this.items = [{
	region: 'center',
	layout: 'fit',
	items: [{
	layout: 'border',
	border: false,
	items: [{
	id: 'task_tab_wm',
	xtype: 'tabpanel',
	region: 'center',
	activeTab: 0,
	plain: true,
	intervalID: null,
	defaults: {
	autoScroll: true
	},

	runAutoReload: function(thisRef){
	this.stopAutoReload();

	this.intervalID = setInterval(function (e) {
	if (thisRef && thisRef.getActiveTab()) {
	thisRef.getActiveTab().get(0).getStore().reload();
	}
	}, <?= AUTO_REFRESH_TIME ?>);
	},

	stopAutoReload: function(){
	if (this.intervalID) {
	clearInterval(this.intervalID);
	}
	},

	listeners: {
	afterrender: function(self){
	},
	tabchange: function(self, p){
	var g = Ext.getCmp('task_tab_wm');
	var x1 = Ext.get(g.getTopToolbar().get(1).wrap.dom);
	var x3 = Ext.get(g.getTopToolbar().get(3).wrap.dom);

	x1.setStyle('width', '90px');
	x3.setStyle('width', '90px');
	g.getTopToolbar().doLayout();

	p.get(0).getStore().reload();
	},
	beforetabchange: function(self, newTab, currentTab){
	}
	},
	tbar:['작업등록일자',{
	xtype: 'datefield',
	editable: true,
	format: 'Y-m-d',
	name: 'sdate',
	width: 90,
	altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
	listeners: {
	render: function(self){
	var d = new Date();
	self.setMaxValue(d.format('Y-m-d'));
	self.setValue(d.format('Y-m-d'));
	},
	select: function(self, date) {
	var edate = self.ownerCt.find('name', 'edate')[0];
	edate.setMinValue(date);
	}
	}
	},'~',{
	xtype: 'datefield',
	editable: true,
	name: 'edate',
	width: 90,
	format: 'Y-m-d',
	altFormats: 'Ymd|ymd|Y-m-d|y-m-d|Y/m/d|Y/m/d',
	listeners: {
	render: function(self){
	var d = new Date();

	self.setMinValue(d.format('Y-m-d'));
	self.setValue(d.format('Y-m-d'));
	},
	select: function(self, date) {
	var sdate = self.ownerCt.find('name', 'sdate')[0];
	sdate.setMaxValue(date);
	}
	}
	},'-',{
	xtype: 'textfield',
	width: 110,
	name: 'search_value',
	listeners:{
	specialkey : function(self, e){
	var g = Ext.getCmp('task_tab_wm').getActiveTab().get(0);
	if (e.getKey() == e.ENTER ) {
	e.stopEvent();
	g.getStore().load();
	}
	}
	}

	},'-',{
	//>>text: '새로고침',
	text: '검색',
	icon: '/led-icons/arrow_refresh.png',
	handler: function(){
	var g = Ext.getCmp('task_tab_wm').getActiveTab().get(0);
	g.getStore().reload();
	},
	scope: this
	},
	buildTBarStatusCheck2(),
	buildTBarRetry(),
	'->', {
	text: '자동 새로고침 실행중',
	scale: 'small',
	pressed: true,
	id: 'run_monitor_autoload',
	icon: '/led-icons/accept.png',
	handler: function(b, e){
	var TaskListTabPanel = b.ownerCt.ownerCt;
	TaskListTabPanel.stopAutoReload();
	Ext.getCmp('run_monitor_autoload').hide();
	Ext.getCmp('stop_monitor_autoload').show();
	Ext.getCmp('run_monitor_autoload').pressed = false;
	}
	},{
	text: '자동 새로고침 중지됨',
	scale: 'small',
	id: 'stop_monitor_autoload',
	hidden: true,
	icon: '/led-icons/cross.png',
	handler: function(b, e){
	var TaskListTabPanel = b.ownerCt.ownerCt;
	TaskListTabPanel.runAutoReload(TaskListTabPanel);
	Ext.getCmp('stop_monitor_autoload').hide();
	Ext.getCmp('run_monitor_autoload').show();
	Ext.getCmp('run_monitor_autoload').pressed = true;
	}
	}]
	,items: [{
	title: '전체',
	layout: 'fit',
	items: new Ariel.Task.monitor.TaskPanelWM({taskType: 'all'})
	}
	<?= ',' . join(',', $comp_array) ?>
	]
	}, {
	id: 'log_wm',
	//>>title: "로그",
	title: _text('MN00048'),
	xtype: 'grid',
	region: 'south',
	split: true,
	collapsible: true,
	height: 200,
	loadMask: true,
	autoExpandColumn: 'description',
	store: new Ext.data.JsonStore({
	id: 'log_wm_store',
	url: '/store/get_task_log.php',
	totalProperty: 'total',
	idProperty: 'id',
	root: 'data',
	fields: [
	{name: 'task_log_id'},
	{name: 'task_id'},
	{name: 'description'},
	{name: 'creation_date', type: 'date', dateFormat: 'YmdHis'}
	],
	listeners: {
	beforeload: function(self, opts){
	var sel = Ext.getCmp('task_tab_wm').getActiveTab().get(0).getSelectionModel().getSelected();
	if (sel) {
	self.baseParams.task_id = sel.get('id');
	}
	}
	}
	}),
	columns: [
	{header: 'ID', dataIndex: 'task_log_id', width: 70},
	//>>{header: '생성일', dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'},
	//>>{header: '내용', dataIndex: 'description', id: 'description'}
	{header: _text('MN00107'), dataIndex: 'creation_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 130, align: 'center'},
	{header: _text('MN00156'), dataIndex: 'description', id: 'description'}
	],
	selModel: new Ext.grid.RowSelectionModel({
	singleSelect: true
	}),
	viewConfig: {
	//>>emptyText: '기록된 작업 내용이 없습니다.'
	emptyText: _text('MSG00166')
	},
	tbar: [{
	//text: '새로고침',
	text: _text('MN00139'),
	icon: '/led-icons/arrow_refresh.png',
	handler: function(){
	Ext.getCmp('log_wm').getStore().reload();
	}
	}]
	}
	]
	}]
	}];

	this.listeners = {
	activate: function(self){
	var auto_reload = Ext.getCmp('run_monitor_autoload').pressed;
	if(auto_reload){
	Ext.getCmp('task_tab_wm').runAutoReload(Ext.getCmp('task_tab_wm'));
	}
	},
	deactivate: function(self){
	Ext.getCmp('task_tab_wm').stopAutoReload();
	}
	};

	Ariel.Nps.TaskMonitor.superclass.initComponent.call(this);
	}
	});
	*/
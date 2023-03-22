<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/lib/config.php');
session_start();

$query = "
    SELECT  *
    FROM    BC_SYS_CODE
    WHERE   CODE = 'AUTOMATIC_REFRESH_TIME'
";
$auto_refresh = $db->queryRow($query);
$interval_time = empty($auto_refresh['ref1']) ? 0 : $auto_refresh['ref1'];

?>

/**
* Created by cerori on 2015-04-01.
*/
Ext.ns('Ariel.Panel.Monitor');
(function() {

function showTaskDetail(workflow_id, content_id, root_task) {

Ext.Ajax.request({
url: '/javascript/ext.ux/viewInterfaceWorkFlow.php',
params: {
workflow_id: workflow_id,
content_id: content_id,
root_task: root_task,
screen_width : window.innerWidth,
screen_height : window.innerHeight
},
callback: function (options, success, response) {
if (success) {
try {
var r = Ext.decode(response.responseText);
r.show();
} catch (e) {
Ext.Msg.alert(e.name, e.message);
}
} else {
Ext.Msg.alert( _text('MN01098'), response.statusText);//'서버 오류'
}
}
});
}
var btnWidth = 30;
var onSearch = function () {
var task_job = Ext.getCmp('task_list');

var key = Ext.getCmp('task_search_key').getValue();
var value = Ext.getCmp('task_search_value').getValue();
var filter = task_job.getTopToolbar().getComponent('filter').getValue();
var stdt = Ext.getCmp('task_grid_search_st_dt').getValue().format('Ymd');
var endt = Ext.getCmp('task_grid_search_en_dt').getValue().format('Ymd');

task_job.getStore().load({
params: {
key: key,
value: value,
filter: filter,
stdt: stdt,
endt: endt
}
});
};
Ariel.Panel.Monitor = Ext.extend(Ext.Panel, {
defaults: {
border: false,
margins: '10 10 10 10'
},
width: 700,
frame: false,
//Taks/Job
title : '<span class="user_span"><span class="icon_title"><i class="fa fa-list"></i></span><span class="main_title_header">'+_text('MN02128')+'</span></span>',
id: 'task_grid',
border: false,
bodyStyle: 'border-left: 1px solid #d0d0d0',
stripeRows: true,
loadMask: true,
layout: 'fit',
initComponent: function(config){
// -------------- SUBGRID START --------------
var expander = new Ext.grid.RowExpander({
tpl : new Ext.Template('<div id="myrow-{task_id}"></div>')
});
expander.on('expand', expandedRow);

function expandedRow(obj, record, body, rowIndex){
id = "myrow-" + record.data.task_id;
id2 = "mygrid-" + record.data.task_id;

var workfolow_detail_data = [];
if(record.data.details.length > 0) {
for(var i=0; i < record.data.details.length; i++ ){ var temp_arr=[]; temp_arr.push(record.data.details[i].job_name); temp_arr.push(record.data.details[i].task_id); temp_arr.push(record.data.details[i].target); temp_arr.push(record.data.details[i].status); temp_arr.push(record.data.details[i].type); temp_arr.push(record.data.details[i].source); temp_arr.push(record.data.details[i].progress); temp_arr.push(record.data.details[i].start_datetime); temp_arr.push(record.data.details[i].creation_datetime); temp_arr.push(record.data.details[i].complete_datetime); temp_arr.push(record.data.content_id); workfolow_detail_data.push(temp_arr); } } var gridX=new Ext.grid.GridPanel({ stripeRows: true, border: false, cls: 'proxima_customize_progress' , store: new Ext.data.Store({ reader : new Ext.data.ArrayReader({}, [ 'job_name' , 'task_id' , 'target' , 'status' , 'type' , 'source' , 'progress' , {name: 'start_datetime' , type: 'date' , dateFormat: 'YmdHis' }, {name: 'creation_datetime' , type: 'date' , dateFormat: 'YmdHis' }, {name: 'complete_datetime' , type: 'date' , dateFormat: 'YmdHis' }, 'content_id' , {name: 'task_result_icon' , convert: function(v, record) { var task_result; if(record[4]==15){ //QC check task_result='<span class="fa-stack " title="' +_text('MN02294')+'" onclick="show_qc_log('+record[10]+')" style="position:relative;height: 17px;margin-top: -3px;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i><strong class="fa fa-inverse fa-text fa-stack-1x" style="position:relative;font-size:10px;font-weight:bold;">QC</strong></span>';
    }else if(record[4] == 200){
    //Loundness Meansurement
    task_result = '<span class="fa-stack " title="'+_text('MN02243')+'" onclick="show_loudness_log('+record[10]+')" style="position:relative;width: 5px !important;height: 12px;"><i class="fa fa-align-right fa-rotate-90 fa-stack-1x" style="position:relative;top:-10px;font-size:14px;"></i></span>';
    }else if(record[4] == 210){
    //Loundness Correction
    task_result = '<span class="fa-stack " title="'+_text('MN02243')+'" onclick="show_loudness_log('+record[10]+')" style="position:relative;width: 5px !important;height: 12px;"><i class="fa fa-align-right fa-rotate-90 fa-stack-1x" style="position:relative;top:-10px;font-size:14px;"></i></span>';
    }else{
    task_result = '';
    }
    return task_result;
    }}
    ]),
    data: workfolow_detail_data
    }),
    cm: new Ext.grid.ColumnModel({
    defaults: {
    sortable: true
    },
    columns: [
    {header: _text('MN02138'), dataIndex: 'job_name',align:'center'},
    new Ext.ux.ProgressColumn({
    header: _text('MN00261'),
    dataIndex: 'progress',
    align: 'center',
    renderer: function(value, meta, record, rowIndex, colIndex, store, pct) {
    return Ext.util.Format.number(pct, "0%");
    }
    }),
    {header: _text('MN00138'), dataIndex: 'status',align:'center'},
    {header: _text('MN02477'), dataIndex: 'task_result_icon',align:'center'},
    {header: _text('MN00102'), dataIndex: 'creation_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),align:'center'},
    {header: _text('MN00233'), dataIndex: 'start_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),align:'center'},
    {header: _text('MN00234'), dataIndex: 'complete_datetime', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),align:'center'},
    {header: _text('MN00242'), dataIndex: 'target',align:'center'}
    ]
    }),
    viewConfig: {
    forceFit:true
    },
    //width: 800,
    autoHeight: true,
    id: id2,
    //frame: true
    });

    //gridX.render(id);
    //gridX.getEl().swallowEvent([ 'mouseover', 'mousedown', 'click', 'dblclick' ]);
    }
    // -------------- SUBGRID END --------------

    Ext.apply(this, config || {});

    this.store = new Ext.data.JsonStore({
    id: 'store_task_detail',
    url: '/store/get_personal_task.php',
    root: 'data',
    fields: [
    'workflow_id', 'workflow_name', 'content_title', 'content_id','type_job','task_id',
    'count_complete', 'count_error', 'count_processing', 'count_queue', 'total', 'total_progress',
    'user_id', 'user_name', 'status', 'root_task','details',
    {name: 'register', convert: function(v, record) {
    //console.log(record);
    return record.user_id + '(' + record.user_name + ')';
    }},
    {name: 'start_datetime', type: 'date', dateFormat: 'YmdHis'},
    {name: 'complete_datetime', type: 'date', dateFormat: 'YmdHis'},
    {name: 'creation_datetime', type: 'date', dateFormat: 'YmdHis'},
    {name: 'task_result_icon', convert: function(v, record) {
    var task_result;
    if(record.type_job == 15){
    //QC check
    task_result = '<span class="fa-stack " title="'+_text('MN02294')+'" onclick="show_qc_log('+record.content_id+')" style="position:relative;height: 17px;margin-top: -3px;"><i class="fa fa-square fa-stack-1x" style="font-size:17px;"></i><strong class="fa fa-inverse fa-text fa-stack-1x" style="position:relative;font-size:10px;font-weight:bold;">QC</strong></span>';
    }else if(record.type_job == 200){
    //Loudness Meansurement
    task_result = '<span class="fa-stack " title="'+_text('MN02243')+'" onclick="show_loudness_log('+record.content_id+')" style="position:relative;width: 5px !important;height: 12px;"><i class="fa fa-align-right fa-rotate-90 fa-stack-1x" style="position:relative;top:-10px;font-size:14px;"></i></span>';
    }else if(record.type_job == 210){
    //Loudness Correction
    task_result = '<span class="fa-stack " title="'+_text('MN02243')+'" onclick="show_loudness_log('+record.content_id+')" style="position:relative;width: 5px !important;height: 12px;"><i class="fa fa-align-right fa-rotate-90 fa-stack-1x" style="position:relative;top:-10px;font-size:14px;"></i></span>';
    }else{
    task_result = '';
    }
    return task_result;

    }}
    ],
    listeners: {
    exception: function(self, type, action, opts, response, args){
    try {
    var r = Ext.decode(response.responseText, true);
    if(!r.success) {
    Ext.Msg.alert(_text('MN00023'), response.responseText);
    }
    }
    catch(e) {
    Ext.Msg.alert(_text('MN00023'), response.responseText);
    }
    }
    }
    });

    this.items = new Ext.grid.GridPanel({
    id: 'task_list',
    cls: 'proxima_customize',
    border: false,
    store: this.store,
    stripeRows: true,
    loadMask: true,
    plugins: expander,
    listeners: {
    viewready: function(self){
    onSearch();
    },
    rowcontextmenu: function (self, row_index, e) {
    e.stopEvent();

    self.getSelectionModel().selectRow(row_index);

    var rowRecord = self.getSelectionModel().getSelected();
    var workflow_id = rowRecord.get('workflow_id');
    var content_id = rowRecord.get('content_id');
    var root_task = rowRecord.get('root_task');


    var menu = new Ext.menu.Menu({
    items: [{
    text: '<span style="position:relative;top:1px;"><i class="fa fa-list" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00241'),//'작업흐름보기'
    //icon: '/led-icons/chart_organisation.png',
    handler: function (btn, e) {
    showTaskDetail(workflow_id, content_id, root_task);
    menu.hide();
    }
    }, {
    hidden: true,
    text: '<span style="position:relative;top:1px;"><i class="fa fa-check-square-o" style="font-size:13px;"></i></span>&nbsp;'+_text('MN02129'),//'완료 처리'
    icon: '/led-icons/chart_organisation.png',
    handler: function (btn, e) {

    // console.log(workflow_id, content_id);

    // manualTaskComplete(workflow_id, content_id);
    menu.hide();
    }
    }]
    });
    menu.showAt(e.getXY());
    },
    },
    viewConfig:{
    forceFit: true,
    emptyText: _text('MSG00148')
    },
    colModel: new Ext.grid.ColumnModel({
    defaults: {
    sortable: true
    },
    columns: [
    //expander,
    {header: _text('MN01028'), dataIndex: 'workflow_name',align:'center'},
    {header:_text('MN00249'), dataIndex: 'content_title',align:'center'},
    {header: _text('MN00120'), dataIndex: 'register',align:'center'},
    {header: _text('MN00138'), dataIndex: 'status', align:'center'},

    {header: _text('MN02477'), dataIndex: 'task_result_icon',align:'center'},
    {
    header: _text('MN02130'),
    dataIndex: 'count_complete',
    align: 'center',
    renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
    return value + ' / ' + record.get('total');
    }
    },
    new Ext.ux.ProgressColumn({
    header: _text('MN00261'),
    dataIndex: 'total_progress',
    align: 'center',
    renderer: function (value, meta, record, rowIndex, colIndex, store, pct) {
    return Ext.util.Format.number(pct, "0%");
    }
    }),
    {
    header: _text('MN01023'),
    dataIndex: 'creation_datetime',
    align: 'center',
    renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
    }
    ]
    }),
    tbar: [{
    hidden: true,
    //icon: '/led-icons/arrow_refresh.png',
    text: '<span style="position:relative;top:1px;"><i class="fa fa-refresh" style="font-size:13px;"></i></span>&nbsp;'+_text('MN00390'),//'새로고침'
    handler: function (btn, e) {
    Ext.getCmp('task_grid').getStore().reload();
    }
    }, {
    hidden: true,
    xtype: 'combo',
    id: 'task_type',
    typeAhead: true,
    triggerAction: 'all',
    mode: 'local',
    width: 120,
    editable: false,
    value: 'all',
    emptyText: '작업구분',
    store: new Ext.data.SimpleStore({
    fields: [
    'type_id',
    'type_nm'
    ],
    data: [
    ['all', '전체'],
    ['regist', '등록'],
    ['transfer', '전송']
    ]
    }),
    valueField: 'type_id',
    displayField: 'type_nm',
    listeners: {
    select: function () {
    }
    }
    }, {
    hidden: true,
    xtype: 'tbspacer',
    width: '20'
    }, {
    hidden: true,
    xtype: 'combo',
    id: 'task_status',
    typeAhead: true,
    triggerAction: 'all',
    mode: 'local',
    width: 120,
    editable: false,
    value: 'all',
    emptyText: '상태구분',
    store: new Ext.data.SimpleStore({
    fields: [
    'status_id',
    'status_nm'
    ],
    data: [['all', '전체'],

    ['pending', '작업대기중'],
    ['queue', '작업대기'],
    ['assigning', '작업할당중'],
    ['processing', '작업중'],
    ['complete', '작업완료'],
    ['cancel', '작업취소'],
    ['canceled', '작업취소'],
    ['error', '실패']
    ]
    }),
    valueField: 'status_id',
    displayField: 'status_nm'
    }, {
    hidden: true,
    xtype: 'tbspacer',
    width: '20'
    }, {
    xtype: 'combo',
    id: 'task_filter_combo',
    itemId: 'filter',
    mode: 'local',
    store: [
    [1, _text('MN02131')],//'전체 자료'
    [2, _text('MN02132')]//'내 자료'
    ],
    value: 2,
    width: 90,
    triggerAction: 'all',
    typeAhead: true,
    editable: false,
    listeners: {
    select: function () {
    _this.onSearch();
    }
    }
    },{
    xtype: 'tbspacer',
    width: 10
    }, {
    hidden : true,
    xtype: 'combo',
    id: 'task_search_key',
    typeAhead: true,
    triggerAction: 'all',
    mode: 'local',
    width: 80,
    editable: false,
    value: 'content_title',
    store: [
    ['content_title', '제목']//,
    //['filename', '파일명']
    ]
    },' ',{
    xtype: 'datefield',
    id: 'task_grid_search_st_dt',
    format: 'Y-m-d',
    width: 90,
    value: new Date().add(Date.DAY, -5).format('Y-m-d')
    }, _text('MN00183'),//' 부터 '
    {
    id: 'task_grid_search_en_dt',
    xtype: 'datefield',
    format: 'Y-m-d',
    width: 90,
    value: new Date()
    },'-',{
    xtype : 'displayfield',
    value : _text('MN00249'),//'제목'
    hidden: true
    },{
    xtype: 'tbspacer',
    width: 5,
    hidden: true
    }, {
    id: 'task_search_value',
    xtype: 'textfield',
    width: 300,
    emptyText: _text('MN00249'),
    listeners: {
    specialkey: function (field, e) {
    if (e.getKey() == e.ENTER) {
    onSearch();
    }
    }
    }
    }, {
    xtype: 'button',
    //icon: '/led-icons/find.png',
    id: 'task_grid_btn_search',
    text: '<span style="position:relative;" title="'+_text('MN00037')+'"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',//'조회'
    handler: function(){
    onSearch();
    }
    },'->'
    <?php
    if ($auto_refresh['use_yn'] == 'Y') {
        ?>
        ,{
        xtype: 'checkbox',
        //boxLabel: '자동 새로고침',
        boxLabel: _text('MN00229'),
        checked: false,
        listeners: {
        check: function (self, checked) {
        var TaskListTabPanel = self.ownerCt.ownerCt;
        if (checked) {
        TaskListTabPanel.runAutoReload(TaskListTabPanel);
        }
        else {
        TaskListTabPanel.stopAutoReload();
        }
        },
        render : function(self){
        var TaskListTabPanel = self.ownerCt.ownerCt;
        TaskListTabPanel.stopAutoReload();
        }
        }
        }
    <?php
    }
    ?>
    ],
    runAutoReload: function(thisRef){
    this.stopAutoReload();

    var time_interval = <?= $interval_time ?> * 1000;

    this.intervalID = setInterval(function (e) {
    if (thisRef ) {
    thisRef.getStore().reload();
    }
    }, time_interval);
    },

    stopAutoReload: function(){
    if (this.intervalID) {
    clearInterval(this.intervalID);
    }
    },
    bbar: {
    xtype: 'paging',
    pageSize: 15,
    displayInfo: true,
    store: this.store
    }
    });
    Ariel.Panel.Monitor.superclass.initComponent.call(this);
    }





    })
    Ext.reg('mainmonitor', Ariel.Panel.Monitor);

    })();
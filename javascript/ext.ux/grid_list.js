function renderTitle(value, p, record){	
	var img_src = '/led-icons/';	
	switch(record.data.type){
		case 'movie':
			img_src += 'film.png';
		break;
		case 'sound':
			img_src += 'picture.png';
		break;
		case 'image':
			img_src += 'music.png';
		break;
		case 'document':
			img_src += 'book.png';
		break;
	
	}
	
	return String.format(
		'<table><tr><td rowspan="3" width="25"><img src="{2}" style="float: left;" /></td><td></tr><tr><td align="left"><b><a href="detail.php?id={0}" target="_blank">{1}</a></b></td></tr>'+
		'<tr><td>{3}</td></tr></table>',
		record.data.id, record.data.title, img_src, record.data.registered.format('Y-m-d H:i:s')
	);
}

function renderFileInfo(value, p, r){
	return String.format(
		'{2}<br />{0}<br />{1} MB',
		r.data.filename, r.data.filesize, r.data.duration
	);
}

var thumbnailTpl = new Ext.XTemplate(
	'<tpl for=".">',
	'<div id="{id}" class="prevbuck">',
	'<dl>',
	'<dt><a href="detail.php?id={id}" onclick="gotoDetails(\'detail.php?id={id}\'); return false;" title="{title}"><strong>{title}</strong></a></dt>',
	'<dd class="gmi" onclick="gotoDetails(\'detail.php?id={id}\');	return false;" onmouseout="azContent.showDetails(\'{id}\', this);" onmouseover="azContent.showDetails(\'{id}\',this);"  style="overflow: hidden">',
	'<a title="{title}" rev="0" name="previmg"	onclick="return	false;"	class="previmg"	rel="buck_{id}">',
	'<img src="{previmg}" class="bucket_thumb" border="0" alt="{title}" width="228" height="128"/>',
	'</a>',
	'<div id="opq{id}"	class="bucket_opaque"></div>',
	'<div id="pd{id}" class="prevdets">',
	'<strong>{filename} - {dORr} - {filesize} MB</strong>',
	'<p>{summary}</p>',
	'<div class="axn clr">',
	'<strong class="common"><em class="gobtn"><a name="detailbtn">상세 내용 보기</a></em></strong>',
	'</div>',
	'</div>',
	'</dd>',
	'<dd id="buck_rate_ZJ76AC7E5UHDDC25AHUQOYOAT2KXTIS7" class="prevft clr">',
	'<ul>',
	'<li class="rt"></li>',
	'<li class="tq">',
	'<span class="quality {type}" title="{type}"><img src="img/thumbnail_small_{type}.png" alt="{type}" /></span>', 
	'</li></ul></dd></dl></div>',
	'</tpl>'
);

function changeView(btn, state){
	var view = Ext.getCmp('grid_list').getView();
	var columnModel = Ext.getCmp('grid_list').getColumnModel();
	if(btn.tpl == 'thumbnailTpl'){
		columnModel.setRenderer(columnModel.findColumnIndex('title'), null);
		columnModel.setRenderer(columnModel.findColumnIndex('filename'), null);
		view.changeTemplate(thumbnailTpl);
	}else{
		columnModel.setRenderer(columnModel.findColumnIndex('title'), renderTitle);
		columnModel.setRenderer(columnModel.findColumnIndex('filename'), renderFileInfo);
		view.changeTemplate(null);		
	}
}

function changeType(item, checked){
	if(checked){
		var store = Ext.getCmp('grid_list').getStore();
		
		store.load({
			params: {
				type: item.type
			}
		});
		
	}
}


var grid_store = new Ext.data.JsonStore({
	id: 'grid-store',
	url: 'php/database.php',
	root: 'results',
	idProperty: 'id',
	totalProperty: 'total',
	remoteSort: true,
	fields: [
		{name: 'id'},
		{name: 'registered', type:'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'type'},
		{name: 'category'},
		{name: 'actor'},
		{name: 'shooter'},
		{name: 'editor'},
		{name: 'scriptor'},
		{name: 'shooting_place'},
		{name: 'title'},
		{name: 'summary'},
		{name: 'creation_date', type:'date', dateFormat: 'Y-m-d H:i:s'},
		{name: 'broadcasting_date', type:'date', dateFormat: 'Y-m-d H:i:s'},		
		{name: 'filename'},
		{name: 'filesize',   type:'int'},
		{name: 'duration'},
		{name: 'previmg'}
	],
	listeners: {
		beforeload: function(self, opts){
			self.baseParams.task = 'LISTING';
			self.baseParams.start = 0;
			self.baseParams.limit = 50;
		}
	}
});
grid_store.setDefaultSort('id', 'DESC');

var grid_list = new Ext.grid.GridPanel({
	id: 'grid_list',
	region: 'center',
	loadMask: true,
	store: grid_store,
    //trackMouseOver:false,
    //dsableSelection:true,\
	selModel: new Ext.grid.RowSelectionModel({
		singleSelect: true
	}),
	colModel: new Ext.grid.ColumnModel({
		defaults: {
			align: 'center',
			sortable: true
		},
		columns: [
			{header: 'ID',           dataIndex: 'id', hidden: true},
			{header: '제목',         dataIndex: 'title', renderer: renderTitle, id: 'title', width: 200},
			{header: '종류',         dataIndex: 'type', hidden: true},
			{header: '분류',    	dataIndex: 'category'},
			{header: '출연자',       dataIndex: 'actor'},
			{header: '취재자',       dataIndex: 'shooter'},
			{header: '편집자',       dataIndex: 'editor'},
			{header: '촬영 장소',    dataIndex: 'shooting_place'},
			{header: '방송 예정일',    dataIndex: 'broadcasting_date', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s')},
			{header: '파일 정보', dataIndex: 'filename', renderer: renderFileInfo, id: 'fileinfo'}
		]
	}),
//	view: new Ext.ux.grid.ExplorerView({
//		rowTemplate: thumbnailTpl
//	}),
	viewConfig: {
		enableRowBody: true,
		showPreview: true,
		forceFit: true,
		getRowClass: function(record, rowIndex, p, store){
			if(this.showPreview){
				p.body = '<p>'+record.data.summary+'</p>';
				return 'x-grid3-row-expanded'
			}
			return 'x-grid3-row-collapsed';
		}
	},
	tbar: [{
		tpl: 'thumbnailTpl',
		tooltip: '썸네일',
		icon: '/led-icons/application_view_tile.png',
		toggleGroup: 'view',
		//enableToggle: true,
		toggleHandler: changeView
	},{
		tpl: null,
		tooltip: '자세히',
		icon: '/led-icons/application_view_detail.png',
		toggleGroup: 'view',
		pressed: true,
		//enableToggle: true,
		toggleHandler: changeView
	}
	,'-',
	{	
		type: null,
		tooltip: '전체',
		icon: '/led-icons/asterisk_orange.png',
		toggleGroup: 'type',
//			enableToggle: true,
		pressed: true,
		toggleHandler: changeType
	},{	
		type: 'movie',
		tooltip: '영상',
		icon: '/led-icons/film.png',
		toggleGroup: 'type',
//			enableToggle: true,
		toggleHandler: changeType
	},{
		type: 'sound',
		tooltip: '음향',
		icon: '/led-icons/picture.png',
		toggleGroup: 'type',
		toggleHandler: changeType
	},{
		type: 'image',
		tooltip: '사진',
		icon: '/led-icons/music.png',
		toggleGroup: 'type',
		toggleHandler: changeType
	},{
		type: 'document',
		tooltip: '문서',
		icon: '/led-icons/book.png',
		toggleGroup: 'type',
		toggleHandler: changeType
	}
	,'-',
	{
		text: '상세 검색',
		icon: '/led-icons/find.png',
		handler: function(){
			search_win.show();
		}
	},'->',{
		xtype: 'textfield'	
	},{
		text: '검색',
		handler: function(){

		}		
	}],
	bbar: new Ext.PagingToolbar({
		pageSize: 50,
		store: grid_store,
		displayInfo: true,
		items: [
		'-',
		{
			pressed: true,
			enableToggle: true,
			text: '요약 보기',
			cls: 'x-btn-text-icon details',
			toggleHandler: function(btn, pressed){
				var view = grid_list.getView();
				view.showPreview = pressed;
				view.refresh();
			}
		}]
	})                  
});


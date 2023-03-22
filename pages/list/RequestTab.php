Ext.ns('Ariel');//신청관리 아이콘 클릭시 나오는 탭메뉴
Ariel.RequestTab = Ext.extend(Ext.TabPanel,{
	region: 'center',
	initComponent: function(){
	this.items=[
	{
		layout:'card',
		title: '작업요청'
	},{
		layout: 'card',
		title:'NPS등록대기'
	},{
		layout: 'card',
		title:'지상파주조등록대기'
	},{
		layout: 'card',
		title:'위성멀티주조등록대기'
	}];
		Ariel.RequestTab.superclass.initComponent.call(this);
	}
});
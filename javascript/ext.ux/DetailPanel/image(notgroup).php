<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');

$mode = $_REQUEST['mode'];

$record = json_decode( $_POST['record'], true);

if(is_array($record))
{
	$highres_web_root = $record['highres_web_root'];
	$lowres_web_root = 	$record['lowres_web_root'];
	$ori_path =  $record['ori_path'];
	$proxy_path =  $record['proxy_path'];

	if( !is_null($lowres_web_root) && !is_null($proxy_path)  && $record['status'] == 2/*&& ( $record['proxy_task_status'] =='complete' )*/ )
	{
		$image_path = $lowres_web_root.'/'.$proxy_path;
	}

	if( empty($image_path) && !is_null($highres_web_root) && !is_null($ori_path) && ( $record['ori_task_status'] =='complete' ) )
	{
		$image_path = $highres_web_root.'/'.$ori_path;
	}
}
else
{

}

if(empty($image_path ))
{
	$image_path = '/img/incoming.jpg';
}

list($width, $height) = @getimagesize('http://'.convertIP('').'/'.$image_path);

//if (($width/300) > ($height/500))
//{
//	$_width = $width;
//	$_height = 500;
//}
//else
//{
//	$_width = 300;
//	$_height = $height;
//}
if($width > $height)
{
	$_width = '100%';
	$_height = '0%';
	$_autoW = 'false';
	$_autoH = 'true';
}
else if($width < $height)
{
	$_width = '0%';
	$_height = '100%';
	$_autoW = 'true';
	$_autoH = 'false';
}
else
{
	$_width = '500';
	$_height = '0%';
	$_autoW = 'false';
	$_autoH = 'true';
}

?>

(function(){
	Ext.ns('Ariel');

	Ariel.DetailWindow = Ext.extend(Ext.Window, {
		id: 'winDetail',
		title: '<?=$record['ud_content_title']?> 상세보기 [<?=addslashes($record['title'])?>]',
		editing: <?=$editing ? 'true,' : 'false,'?>
		width: 1000,
		height: 670,
		minHeight: 500,
		minWidth: 800,
		modal: true,
		layout: 'fit',
		maximizable: true,
		listeners: {
			render: function(self){
				self.mask.applyStyles({
					"opacity": "0.5",
					"background-color": "#000000"
				});
				self.setSize(1000,680);
			}
		},
		initComponent: function(config){
			Ext.apply(this, config || {});

			this.items = [{
				border: false,
				layout: 'border',
				split: true,

				items: [{
					layout: 'border',
					region: 'center',
					border: false,
					//autoScroll: true,

					items: [{
						layout: 'hbox',
						layoutConfig: {
							align: 'middle',
							pack: 'center'
						},
						region: 'center',
						//autoScroll: true,
						bodyStyle:'background-color:black ;',
						items: [{
							id: 'preview',
							xtype: 'box',
							region: 'center',
							width: '<?=$_width ?>',
							height: '<?=$_height ?>',
							autoWidth: <?=$_autoW ?>,
							autoHeight: <?=$_autoH ?>,
							//align: 'center',
							//anchor: '97%',
							autoEl: {
								tag: 'img',
								src: '<?=addslashes($image_path)?>'
							}
						}]

					},{
						hidden: true,
						region: 'south',
						xtype: 'grid',
						title: '미디어파일 리스트',
						collapsed: true,
						collapsible: true,
						split: true,
						height: 120,
						store: new Ext.data.JsonStore({
							id: 'detail_media_grid',
							url: '/store/get_media.php',
							root: 'data',
							fields: [
								'content_id',
								'media_id',
								'storage_id',
								'type',
								'path',
								'filesize',
								{name: 'created_time', type: 'date', dateFormat: 'YmdHis'}
							],
							listeners: {
								exception: function(self, type, action, opts, response, args){
									Ext.Msg.alert('오류', response.responseText);
								}
							}

						}),
						columns: [
							{header: '파일용도', dataIndex: 'type', width: 65, renderer: function(value, metaData, record, rowIndex, colIndex, store){
								switch(value){
									case 'original':
										var tip = '원본 자료입니다';
										var value = '원본';
									break;

									case 'thumb':
										var tip = '리스트 썸네일 이미지입니다.';
										var value = '대표이미지';
									break;

									case 'proxy':
										var tip = '미리보기용 프록시 파일입니다.';
										var value = '프록시 파일';
									break;

									case 'download':
										var tip = '사용자 다운로드 자료입니다.';
										var value = '다운로드';
									break;
								}

								metaData.attr = 'ext:qtip="'+tip+'"';
								return value;
							}},
							{header: '저장경로', dataIndex: 'path', width: 400},
							{header: '파일용량', dataIndex: 'filesize', width: 70, align: 'center'},
							{header: '생성일', dataIndex: 'created_time', renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'), width: 120, align: 'center'}
						],
						sm: new Ext.grid.RowSelectionModel({
							singleSelect: true
						}),
						listeners: {
							rowcontextmenu: function(self, idx, e){
										var r = self.getSelectionModel().selectRow(idx);
										e.stopEvent();

										var menu = new Ext.menu.Menu({
											items: [{
												icon: '/led-icons/disk.png',
												text: '다운로드',
												handler: function(b, e) {
													new Ext.Window({
														title: '다운로드 사유 기입',
														width: 300,
														height: 200,
														modal: true,
														border: false,
														layout: 'fit',

														items: {
															xtype: 'textarea',
															name: 'download_summary'
														},

														buttons: [{
															text: '확인',
															handler: function(b, e){
																b.ownerCt.ownerCt.close();
															}
														},{
															text: '취소',
															handler: function(b, e){
																b.ownerCt.ownerCt.close();
															}
														}]
													}).show();
												}
											}]
										});
										menu.showAt(e.getXY());
									},
							viewready: function(self){
								self.getStore().load({
									params: {
										content_id: <?=$content_id?>
									}
								});
							}
						}
					}]
				},{
					region: 'east',
					id: 'detail_panel',
					xtype: 'tabpanel',
					title: '메타데이터',
					border: false,
					split: false,
					width: '50%',

					listeners: {
						afterrender: function(self){
							Ext.Ajax.request({
								url: '/store/get_detail_metadata.php',
								params: {
									mode: '<?=$mode?>',
									content_id: <?=$content_id?>
								},
								callback: function(opts, success, response){
									if(success){
										try {
											var r = Ext.decode(response.responseText);
											self.add(r)
											self.doLayout();
											self.activate(0);
										}catch(e){
											Ext.Msg.alert('오류', e+'<br />'+response.responseText);
										}
									}else{
										Ext.Msg.alert('오류', opts.url+'<br />'+response.statusText+'('+response.status+')');
									}
								}
							})
						}
					}
				}]
			}];

			Ariel.DetailWindow.superclass.initComponent.call(this);
		},

		loadForm: function(content_id){
			Ext.Ajax.request({
				url: '/store/get_detail_form.php',
				callback: function(self, type, action, response, arg){

				}
			})
		}
	});

	new Ariel.DetailWindow().show();
})()
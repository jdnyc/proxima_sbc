(function() {

	var store = new Ext.data.JsonStore({
		root: 'data',
		url: '/store/statistics/content/content_type_pie_regist_tmp.php',
		autoLoad: true,
		fields: [{
			name: 'name'
		},{
			name: 'count',
			type: 'int'
		}],
		listeners: {
			load:function(self, records, opts){
				var target_panel = Ext.getCmp('statistics_content_type_chart');
				var pieColor = ["#00b8bf","#8dd5e7","#edff9f",'#ffa928','#c0fff6'];
				for(var i=0; i<records.length; i++){
					records[i].data.color = pieColor[i];
				
				}
		
				target_panel.setData(target_panel,records);
			
			}
		}
	});
	return {
		layout: 'vbox',
		frame: false,
		border: false,
		layoutConfig: {
			align: 'stretch',
			pack: 'start'
		},

		items: [{	
					xtype:'dataview',
					flex: 2,
					id: 'statistics_content_type_chart',
					dataField: 'count',
					categoryField: 'name',
					listeners: {
								render: function(self){
									self.store.load();
								}
					},		
					store: store,
					tpl:new Ext.XTemplate(
						"<div class='chart-pie-style'>",
						"<span class='content'>",
						"<canvas id='chart-pie' style='transform: rotate(-90deg);-moz-transform: rotate(-90deg);-webkit-transform: rotate(-90deg);-o-transform: rotate(-90deg);'></canvas>",
						"</br>",
						"<canvas id='chart-pie-name-color' width='700px' height='50'></canvas>",
						"</span>",
						"<span class='blank'></span>",
						'</div>'
					),
					setData:function(self,data){
						self.drawChart(data);
					},
					drawChart:function(data){
						var canvas;
						var ctx;
						var lastend = 0;
						var pieTotal = 0;
					
						for(var i=0; i<data.length; i++){
							pieTotal = pieTotal + data[i].data.count;
						}
				
						canvas = document.getElementById('chart-pie');
						canvas2 = document.getElementById('chart-pie-name-color');
					
						if (canvas.getContext){
							ctx = canvas.getContext("2d");
							ctx2 = canvas2.getContext("2d");
							ctx2.font = "15px Arial";		
							ctx.clearRect(0, 0, canvas.width, canvas.height); 

							canvas.width = (Ext.getCmp('statistics_content_type_chart').lastSize.width)/1.2;
							canvas.height = (Ext.getCmp('statistics_content_type_chart').lastSize.height)/1.2;
		
							var hwidth = ctx.canvas.width/2;
							var hheight = ctx.canvas.height/2;
							
							for (var i = 0,x = 100; i < data.length; i++) {
								ctx.fillStyle = data[i].data.color;
								ctx.beginPath();
								ctx.moveTo(hwidth,hheight);
								ctx.arc(hwidth,hheight,hheight,lastend,lastend+
								(Math.PI*2*(data[i].data.count/pieTotal)),false);
								
								ctx.lineTo(hwidth,hheight);
								ctx.fill();
		
								var radius = hheight/1.5;
								var endAngle = lastend + (Math.PI*(data[i].data.count/pieTotal));
								
								var setX = hwidth + Math.cos(endAngle) * radius;
								var setY = hheight + Math.sin(endAngle) * radius;
		
								// if(!(data[i].data.count == 0)){
								// ctx.fillStyle = "#ffffff";
								// ctx.font = '14px Calibri';
								// ctx.fillText(data[i].data.count,setX,setY);
								// }
						
								ctx2.fillStyle = data[i].data.color;
								ctx2.fillRect (x-15, 20, 12, 12);
								ctx2.fillStyle = "#1f1f23";
								ctx2.fillText(data[i].data.name,x,30);
								x=x+110;
								
								lastend += Math.PI*2*(data[i].data.count/pieTotal);
							}
						}else{
							// 지원하지 않음
						}
					}
				},	
		// 	{
		// 	xtype: 'piechart',
		// 	flex: 2,
		// 	store: store,
		// 	dataField: 'count',
		// 	categoryField: 'name',

		// 	extraStyle:
		// 	{
		// 		legend:
		// 		{
		// 			display: 'bottom',
		// 			padding: 5,
		// 			font:
		// 			{
		// 				family: 'Tahoma',
		// 				size: 13
		// 			}
		// 		}
		// 	}
		// },
		{
			xtype: 'grid',
			//!!title: '콘텐츠 타입별 등록 횟수',
			//title: _text('MN00284'),
			//cls: 'proxima_customize',
			title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">'+_text('MN00284')+'</span></span>',
			cls: 'grid_title_customize proxima_customize',
			stripeRows: true,
			border: false,
			store: store,
			tbar:	[{
				//xtype: 'toolbar',
				//height: 35,
				//items: [{
						xtype: 'button',
						//icon: '/led-icons/disk.png',
						//icon: '/led-icons/doc_excel_table.png',
						//style: 'border-style:outset;',
						//!!text: '엑셀로 저장',
						//text: _text('MN00212'),
						cls: 'proxima_button_customize',
						width: 30,
						text: '<span style="position:relative;top:1px;" title="'+_text('MN00212')+'"><i class="fa fa-file-excel-o" style="font-size:13px;color:white;"></i></span>',
						ref: '../button',
						id: 'btn',
						handler: function(btn, e){
							window.location="/store/statistics/content/content_type_pie_regist_excel.php";
						}
				//	}]
			}],
			flex: 1,
			border: false,
			autoShow: true,
			ref: 'grid',
			id: 'grid1',
			columns: [{
				align: 'center',
				xtype: 'gridcolumn',
				//!!header: '콘텐츠 타입',
				header: _text('MN00276'),
				sortable: true
			},{
				align: 'center',
				xtype: 'gridcolumn',
				//!!header: '등록 수',
				header: _text('MN00284'),
				sortable: true
			}],
			viewConfig: {
				forceFit: true
			}
		}]
	};
})()
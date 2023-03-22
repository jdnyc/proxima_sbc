{
	border: false,
		layout: 'fit',
			title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + _text('MN00282') + '</span></span>',
				cls: 'grid_title_customize',

					//!!'통계 종류:'
					tbar: [_text('MN00294'), {
						xtype: 'combo',
						id: 'statistics_content_type',
						width: 100,
						triggerAction: 'all',
						editable: false,
						mode: 'local',
						displayField: 'd',
						valueField: 'v',
						value: 'reg',
						store: new Ext.data.ArrayStore({
							fields: [
								'v', 'd'
							],
							data: [
								//!!['reg', '등록'],
								//!!['read', '조회'],
								//!!['download', '다운로드']
								['reg', _text('MN00038')],
								['read', _text('MN00047')],
								['download', _text('MN00050')]
							]
						})
					}, _text('MN00150')//!!기간
						, {
						xtype: 'datefield',
						id: 'start_date',
						editable: false,
						format: 'Y-m-d',
						width: 100,
						listeners: {
							render: function (self) {
								var d = new Date();

								self.setMaxValue(d.format('Y-m-d'));
								self.setValue(d.add(Date.MONTH, -1).format('Y-m-d'));
							}
						}
					},
					_text('MN00183')//!!'부터'
						, {
						xtype: 'datefield',
						id: 'end_date',
						editable: false,
						format: 'Y-m-d',
						width: 100,
						listeners: {
							render: function (self) {
								var d = new Date();

								self.setMaxValue(d.format('Y-m-d'));
								self.setValue(d.format('Y-m-d'));
							}
						}
					}, {
						//icon: '/led-icons/find.png',
						//text: '조회',
						//text: _text('MN00059'),
						cls: 'proxima_button_customize',
						width: 30,
						text: '<span style="position:relative;top:1px;" title="' + _text('MN00059') + '"><i class="fa fa-search" style="font-size:13px;color:white;"></i></span>',
						handler: function (btn, e) {
							Ext.getCmp('statistics_content_type_chart').store.reload();
						}
					}],

						items: {
		xtype: 'dataview',
			id: 'statistics_content_type_chart',
				dataField: ['count', 'color'],
					categoryField: 'name',
						listeners: {
			render: function(self) {
				self.store.load();

			}
		},
		store: new Ext.data.JsonStore({
			url: '/store/get_statistics_content_type.php',
			root: 'data',
			fields: [
				'name',
				'count',
				'color'
			],
			listeners: {
				beforeload: function (self, opts) {
					opts.params = opts.params || {};

					Ext.apply(opts.params, {
						type: Ext.getCmp('statistics_content_type').getValue(),
						start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
						end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
					});


				},
				load: function (self, records, opts) {
					var target_panel = Ext.getCmp('statistics_content_type_chart');
					var pieColor = ["#00b8bf", "#8dd5e7", "#edff9f", '#ffa928', '#c0fff6'];
					for (var i = 0; i < records.length; i++) {
						records[i].data.color = pieColor[i];
					}
					target_panel.setData(target_panel, records);
					// Ext.getCmp('statistics_content_type_chart').store.reload();


				}
			}
		}),

			tpl: new Ext.XTemplate(

				"<div class='chart-pie-style'>",
				"<span class='content'>",
				"<canvas id='chart-pie' style='transform: rotate(-90deg);-moz-transform: rotate(-90deg);-webkit-transform: rotate(-90deg);-o-transform: rotate(-90deg);'></canvas>",
				"</br>",
				"<canvas id='chart-pie-name-color' width='800px' height='50'></canvas>",
				"</span>",
				"<span class='blank'></span>",
				'</div>'

			),
				prepareData: function(data, recordIndex, record) {
					return data;
				},
		setData: function(self, data) {
			self.drawChart(data);

		},
		drawChart: function(data) {
			var canvas;
			var ctx;
			var lastend = 0;
			var pieTotal = 0;

			for (var i = 0; i < data.length; i++) {
				pieTotal = pieTotal + data[i].data.count;
			}

			canvas = document.getElementById('chart-pie');
			canvas2 = document.getElementById('chart-pie-name-color');





			if (canvas.getContext) {
				ctx = canvas.getContext("2d");
				ctx2 = canvas2.getContext("2d");
				ctx2.font = "15px Arial";

				ctx.clearRect(0, 0, canvas.width, canvas.height);

				canvas.width = (Ext.getCmp('statistics_content_type_chart').lastSize.width) / 1.2;
				canvas.height = (Ext.getCmp('statistics_content_type_chart').lastSize.height) / 1.2;


				var hwidth = ctx.canvas.width / 2;
				var hheight = ctx.canvas.height / 2;


				for (var i = 0, x = 150; i < data.length; i++) {
					ctx.fillStyle = data[i].data.color;
					ctx.beginPath();
					ctx.moveTo(hwidth, hheight);
					ctx.arc(hwidth, hheight, hheight, lastend, lastend +
						(Math.PI * 2 * (data[i].data.count / pieTotal)), false);
					ctx.lineTo(hwidth, hheight);
					ctx.fill();

					var radius = hheight / 1.5;
					var endAngle = lastend + (Math.PI * (data[i].data.count / pieTotal));
					var setX = hwidth + Math.cos(endAngle) * radius;
					var setY = hheight + Math.sin(endAngle) * radius;

					// if(!(data[i].data.count == 0)){
					// ctx.fillStyle = "#ffffff";
					// ctx.font = '14px Calibri';
					// ctx.fillText(data[i].data.count,setX,setY);
					// }

					ctx2.fillStyle = data[i].data.color;
					ctx2.fillRect(x - 15, 20, 10, 10);
					ctx2.fillStyle = "#1f1f23";
					ctx2.fillText(data[i].data.name, x, 30);
					x = x + 110;

					lastend += Math.PI * 2 * (data[i].data.count / pieTotal);


				}
			} else {
				// 지원하지 않음
			}

		}
	}

	// {
	// 	xtype: 'piechart',
	// 	id: 'statistics_content_type_chart',
	// 	dataField: 'count',
	// 	categoryField: 'name',
	// 	listeners: {
	// 		render: function(self){
	// 			self.store.load();
	// 		}
	// 	},		
	// 	store: new Ext.data.JsonStore({
	// 		url: '/store/get_statistics_content_type.php',
	// 		root: 'data',
	// 		fields:[
	// 			'name', 
	// 			'count'
	// 		],
	// 		listeners: {
	// 			beforeload: function(self, opts){
	// 				opts.params = opts.params || {};

	// 				Ext.apply(opts.params, {
	// 					type: Ext.getCmp('statistics_content_type').getValue(),
	// 					start_date: Ext.getCmp('start_date').getValue().format('Ymd000000'),
	// 					end_date: Ext.getCmp('end_date').getValue().format('Ymd240000')
	// 				});
	// 			}
	// 		}
	// 	}),
	// 	extraStyle: {
	// 		legend: {
	// 			display: 'bottom',
	// 			padding: 5,
	// 			font: {
	// 				family: 'Tahoma',
	// 				size: 13
	// 			}
	// 		}
	// 	}
	// }
}
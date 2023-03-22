(function () {
    Ext.ns("Ariel.archiveManagement");

    Ariel.archiveManagement.originalSearchGrid = Ext.extend(Ext.Panel, {
        title: '<span class="user_span"><span class="icon_title"><i class=""></i></span><span class="main_title_header">' + '방송원본 검색' + '</span></span>',
        layout: 'border',
        autoScroll: true,
        border:false,
        cls: 'grid_title_customize proxima_customize',
        initComponent: function () {
            this._initialize();
            Ariel.archiveManagement.originalSearchGrid.superclass.initComponent.call(this);
        },
        _initialize: function () {
            var that = this;
    
            this.items= [{
                    region: 'north',
                    xtype: 'fieldset',
                    width: '100%',
                    frame: false,
                    height: 200,
                    split: true,
                    layout:	'border',
                    flex:1,
                    items: [{
                        xtype:'form',
                        id: 'serchForm',
                        region: 'center',
                        border: false,
                        width: '70%',
                        labelWidth: 1,
                        defaults: {
                            anchor: '100%',
                            style: {
                                'padding-top': '5px',
                            }
                        },
                        items: [{
                            xtype: "compositefield",
                            items: [
                            {
                                xtype: "displayfield",
                                value: "프로그램",
                                width: 50
                            },
                            {
                                xtype : 'combo',
                                width: 115,
                                hiddenName: 'category',
                                hiddenValue: 'value',
                                displayField:'name',
                                valueField: 'value',
                                typeAhead: true,
                                triggerAction: 'all',
                                lazyRender:true,
                                mode: 'local',
                                editable : false,
                                store: new Ext.data.ArrayStore({
                                        fields: ['name','value'],
                                        data: [['방송일반', 'general'], ['국회', 'congress'], ['구매', 'buy'], ['방송DCT', 'DCT'], ['기타', 'etc']]
                                }),
                            },
                            {
                                xtype: 'displayfield',
                                value: '횟수',
                                width: 50
                            },
                            {
                                xtype: "textfield",
                                name: "tape_no",
                                width: 50
                            },
                            {
                                xtype: "displayfield",
                                value: '~'
                            },
                            {
                                xtype: "textfield",
                                name: "tape_no",
                                width: 50
                            },
                            {
                                xtype: "displayfield",
                                value: '부제',
                                width: 50
                            },
                            {
                                xtype: "textfield",
                                name: "tape_no",
                            },{
                                xtype : 'combo',
                                width: 50,
                                hiddenName: 'category',
                                hiddenValue: 'value',
                                displayField:'name',
                                valueField: 'value',
                                typeAhead: true,
                                triggerAction: 'all',
                                lazyRender:true,
                                mode: 'local',
                                value: 'AND',
                                editable : false,
                                store: new Ext.data.ArrayStore({
                                        fields: ['name','value'],
                                        data: [['방송일반', 'general'], ['국회', 'congress'], ['구매', 'buy'], ['방송DCT', 'DCT'], ['기타', 'etc']]
                                }),
                            },{
                                xtype: "textfield",
                                name: "tape_no",
                            }]
                        },{
                            xtype: "compositefield",
                            items: [
                            {
                                xtype: "displayfield",
                                value: "검색기간",
                                width: 50
                            },
                            {
                                xtype: "radiogroup",
                                name: "date",
                                width: 310,
                                items: [
                                    { boxLabel: "최근3개월", name: "date", inputValue: "1", checked: true },
                                    { boxLabel: "6개월", name: "date", inputValue: "2" },
                                    { boxLabel: "1년", name: "date", inputValue: "3" },
                                    { boxLabel: "전체", name: "date", inputValue: "4" }
                                ]
                            },
                            {
                                xtype: 'displayfield',
                                value: '본방일',
                            },
                            {
                                xtype: 'datefield',
                                format: 'Y-m-d'
                            },
                            {
                                xtype: 'displayfield',
                                value: '~',
                            },
                            {
                                xtype: 'datefield',
                                format: 'Y-m-d'
                            }]
                        },{
                            xtype: "compositefield",
                            items: [
                            {
                                xtype: "displayfield",
                                value: "제작구분",
                                width: 50
                            },
                            {
                                xtype : 'combo',
                                width: 115,
                                hiddenName: 'category',
                                hiddenValue: 'value',
                                displayField:'name',
                                valueField: 'value',
                                typeAhead: true,
                                triggerAction: 'all',
                                lazyRender:true,
                                mode: 'local',
                                editable : false,
                                store: new Ext.data.ArrayStore({
                                        fields: ['name','value'],
                                        data: [['방송일반', 'general'], ['국회', 'congress'], ['구매', 'buy'], ['방송DCT', 'DCT'], ['기타', 'etc']]
                                }),
                            },
                            {
                                xtype: 'displayfield',
                                value: '방송구분',
                                width: 50
                            },
                            {
                                xtype : 'combo',
                                width: 115,
                                hiddenName: 'category',
                                hiddenValue: 'value',
                                displayField:'name',
                                valueField: 'value',
                                typeAhead: true,
                                triggerAction: 'all',
                                lazyRender:true,
                                mode: 'local',
                                editable : false,
                                store: new Ext.data.ArrayStore({
                                        fields: ['name','value'],
                                        data: [['방송일반', 'general'], ['국회', 'congress'], ['구매', 'buy'], ['방송DCT', 'DCT'], ['기타', 'etc']]
                                }),
                            },{
                                xtype: 'displayfield',
                                value: 'PD',
                                width: 50
                            },{
                                xtype: "textfield",
                                name: "tape_no",
                            },{
                                xtype: 'displayfield',
                                value: '테잎번호',
                                width: 50
                            },{
                                xtype: "textfield",
                                name: "tape_no",
                                width: 50
                            },
                            {
                                xtype: "displayfield",
                                value: '~'
                            },
                            {
                                xtype: "textfield",
                                name: "tape_no",
                                width: 50
                            }]
                        },
                        {
                            xtype: "compositefield",
                            items: [
                            {
                                xtype: "displayfield",
                                value: "장면내용",
                                width: 50,
                            },
                            {
                                xtype: "textfield",
                                name: "program",
                                id: "program",
                                width: 115
                            },
                            {
                                xtype : 'combo',
                                width: 50,
                                hiddenName: 'category',
                                hiddenValue: 'value',
                                displayField:'name',
                                valueField: 'value',
                                typeAhead: true,
                                triggerAction: 'all',
                                lazyRender:true,
                                mode: 'local',
                                editable : false,
                                value: 'AND',
                                store: new Ext.data.ArrayStore({
                                        fields: ['name','value'],
                                        data: [['방송일반', 'general'], ['국회', 'congress'], ['구매', 'buy'], ['방송DCT', 'DCT'], ['기타', 'etc']]
                                }),
                            },{
                                xtype: "textfield",
                                name: "subject",
                                id: "subject",
                                width: 215
                            }, {
                                xtype: "displayfield",
                                width: 20,
                            },{
                                xtype: "radiogroup",
                                name: "order",
                                width: 200,
                                items: [
                                    { boxLabel: "ID_NO순", name: "order", inputValue: "1", checked: true },
                                    { boxLabel: "본방일순", name: "order", inputValue: "2" }
                                ]
                            },]
                        }]
                    },{
                        xtype: "panel",
                        region: 'east',
                        border: false,
                        width: '30%',
                        items: [
                            {
                                xtype: "a-iconbutton",
                                text: "초기화",
                                handler: function (self) {
                                    
                                }
                            },
                        ]
                    }] 
                },{
                    xtype: "fieldset",
                    region: "center",
                    flex: 1,
                    border: false,
                    height: 200,
                    items: [{
                        xtype: 'grid',
                        height: 200,
                        frame: false,
                        clicksToEdit: 2,
                        viewConfig: {
                            emptyText: "목록이 없습니다.",
                            border: false
                        },
                        store: new Ext.data.SimpleStore({
                            fields: [
                              'idx',
                              'times',
                              'content',
                              'start_time',
                              'end_time',
                              'preview'
                            ],
                            data: [
                                ['1', '230', '전체', '00:00', '00:00', '2200_00.wmv'],
                                ['2', '230', '주제:금융빅뱅!자본시장의 세계화', '00:00', '00:00', ' ']
                            ]
                        }),
                        cm: new Ext.grid.ColumnModel({
                            defaults: {
                                align: "center",
                                menuDisabled: true,
                                sortable: false
                            },
                            columns: [
                                { header: 'NO', dataIndex: 'idx', sortable: true ,  width: 40 },
                                { header: '수록횟수', dataIndex: 'times', sortable: true , width: 60 },
                                { header: '테잎번호', dataIndex: 'start_time', sortable: true , width: 60 },
                                { header: '프로그램', dataIndex: 'end_time', sortable: true,width: 100 },
                                { header: '프로그램명', dataIndex: 'preview', sortable: true , width: 150, align: "left" },
                                { header: '부제', dataIndex: 'preview', sortable: true , width: 150, align: "left" },
                                { header: '본방일', dataIndex: 'preview', sortable: true , width: 100},
                                { header: '장면내용', dataIndex: 'preview', sortable: true , width: 200, align: "left" },
                                { header: '구분', dataIndex: 'preview', sortable: true , width: 100, align: "left" },
                                { header: '매체', dataIndex: 'preview', sortable: true , width: 100, align: "left" },
                                { header: '규격', dataIndex: 'preview', sortable: true , width: 50, align: "left" },
                                { header: '제작', dataIndex: 'preview', sortable: true , width: 50, align: "left" },
                                { header: '길이', dataIndex: 'preview', sortable: true , width: 50 },
                            ]
                        }),
                        sm : new Ext.grid.RowSelectionModel({
                            singleSelction: true
                        }),
                        listeners: {
                            rowdblclick: function (self, rowIndex, e) {
                                 var _this = this;
                                 var sm = _this.getSelectionModel();
                                 var selectRecord = sm.getSelected();
                                 that.buildEditTableWin(_this, selectRecord);
                            }
                        }
                    }] 
                }
            ],

            this.tbar = [
                {
                    xtype: "radiogroup",
                    name: "search",
                    width: 180,
                    checked: true,
                    items: [
                        { boxLabel: "단순검색", name: "search", inputValue: "1" },
                        { boxLabel: "상세검색", name: "search", inputValue: "2" }
                    ]
                }
            ]

        }, buildEditTableWin: function(_this, originRecord){//추후 데이터로 상세 팝업창 띄울 예정
            var win = new Ext.Window({
                cls: 'change_background_panel',
                title: '방송원본등록',
                width: 1200,
                height: 700,
                modal: true,
                layout: 'border',
                border: false,
                items: [
                    {
                        region: 'north',
                        xtype: 'fieldset',
                        width: '100%',
                        frame: false,
                        height: 300,
                        split: true,
                        layout:	'border',
                        flex:1,
                        items: [{
                            xtype:'form',
                            id: 'serchForm',
                            region: 'center',
                            border: false,
                            width: '60%',
                            labelWidth: 1,
                            defaults: {
                                anchor: '100%',
                                width: 115,
                                style: {
                                    'padding-top': '5px',
                                }
                            },
                            items: [{
                                xtype: "compositefield",
                                style: {
                                    'padding-top': '15px',
                                },
                                items: [{
                                    xtype: "displayfield",
                                    value: "프로그램",
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 90
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 230
                                },
                                {
                                    xtype: 'displayfield',
                                    width: 10
                                },
                                {
                                    xtype: "displayfield",
                                    value: '횟수',
                                    width: 80
                                },
                                {
                                    xtype: "textfield",
                                    name: "tape_no",
                                    width: 115
                                }],
                            },{
                                xtype: "compositefield",
                                items: [{
                                    xtype: "displayfield",
                                    value: "부제목",
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 325
                                },
                                {
                                    xtype: 'displayfield',
                                    width: 10
                                },
                                {
                                    xtype: 'displayfield',
                                    value: '테잎번호',
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 115
                                }]
                            },{
                                xtype: "compositefield",
                                items: [{
                                    xtype: "displayfield",
                                    value: "감독",
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 115
                                },
                                {
                                    xtype: 'displayfield',
                                    width: 220
                                },
                                {
                                    xtype: 'displayfield',
                                    value: '본방일',
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 115
                                }]
                            },
                            {
                                xtype: "compositefield",
                                items: [{
                                    xtype: "displayfield",
                                    value: "제작구분",
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 115
                                },
                                {
                                    xtype: 'displayfield',
                                    width: 220
                                },
                                {
                                    xtype: 'displayfield',
                                    value: '방송구분',
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 115
                                }]
                            },{
                                xtype: "compositefield",
                                items: [{
                                    xtype: "displayfield",
                                    value: "길이",
                                    width: 80,
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 115
                                },
                                {
                                    xtype: "textfield",
                                    name: "serial_num",
                                    width: 50
                                }],
                            }],
                            tbar: [
                                {
                                    xtype: "a-iconbutton",
                                    text: "저장",
                                    handler: function (self) {
                                       
                                    }
                                },{
                                    xtype: 'tbfill'
                                },
                                {
                                    xtype: "a-iconbutton",
                                    text: "미리보기",
                                    cls: "x-toolbar-cell-right",
                                    handler: function (self) {
                                       
                                    }
                                }
                            ]
                        },{
                            xtype: "fieldset",
                            region: 'east',
                            border: false,
                            width: '40%',
                            id: 'reserve',
                            items: [{
                                xtype: 'grid',
                                height: 200,
                                frame: false,
                                clicksToEdit: 2,//더블클릭으로 변경
                                viewConfig: {
                                    emptyText: "목록이 없습니다.",
                                    border: false
                                },
                                store: new Ext.data.SimpleStore({
                                    fields: [
                                      'tape_no',
                                      'state',
                                      'rent_nm',
                                    ],
                                    data: [
                                        ['213B10875', '대출중', '김도형']
                                    ]
                                }),
                                sm: new Ext.grid.CheckboxSelectionModel(),
                                cm: new Ext.grid.ColumnModel({
                                    defaults: {
                                        align: "center",
                                        menuDisabled: true,
                                        sortable: false
                                    },
                                    columns: [
                                        new Ext.grid.CheckboxSelectionModel(), 
                                        { header: '테잎번호', dataIndex: 'tape_no', sortable: true, width: 100 },
                                        { header: '테잎상태', dataIndex: 'state', sortable: true,  width: 100 },
                                        { header: '대출(예약)자명', dataIndex: 'rent_nm', sortable: true, width: 100 }
                                    ]
                                }),
                                tbar: [
                                    {
                                        xtype: "a-iconbutton",
                                        text: "예약하기",
                                        handler: function (self) {
                                            
                                        }
                                    },{
                                        xtype: "displayfield",
                                        value: "*예약완료 후 대출신청서를 작성하세요."
                                    },{
                                        xtype: 'tbfill'
                                    },
                                    {
                                        xtype: "a-iconbutton",
                                        text: "닫기",
                                        cls: "x-toolbar-right",
                                        handler: function (self) {
                                           
                                        }
                                    }
                                ]
                            }]
                        }] 
                    },{
                        xtype: "fieldset",
                        region: "center",
                        flex: 1,
                        border: false,
                        height: 200,
                        items: [{
                            xtype: 'grid',
                            height: 200,
                            frame: false,
                            clicksToEdit: 2,
                            border: true,
                            hideHeaders:true,
                            viewConfig: {
                                emptyText: "목록이 없습니다.",
                                border: false
                            },
                            store: new Ext.data.SimpleStore({
                                fields: [
                                  'times',
                                  'content',
                                  'start_time',
                                ],
                                data: [
                                    ['3582324', '@', '-대통령 원자력 전시회 참석(56)', '00:00', 'kangdate/kangdate_20070725_2200_00.wmv'],
                                    ['3582324', '@', '-한국철도 57주년(56)', '00:00', ' ']
                                ]
                            }),
                            cm: new Ext.grid.ColumnModel({
                                defaults: {
                                    align: "center",
                                    menuDisabled: true,
                                    sortable: false,
                                },
                                columns: [
                                    { header: '테잎번호', dataIndex: 'times' , width: 100 },
                                    { header: '테잎번호', dataIndex: 'content', width: 50, align: "left" },
                                    { header: '테잎번호', dataIndex: 'start_time', width: 200, align: "left" },
                                ]
                            }),
                            sm : new Ext.grid.RowSelectionModel({
                                singleSelction: true
                            }),
                            tbar: [{
                                    xtype: 'tbtext',
                                    text: '장면내용',
                                },{
                                    xtype: 'displayfield',
                                    width: 10
                                },
                                {
                                    xtype: 'checkbox',
                                    boxLabel: 'TEXT 보기',
                                    name: 'video_codec_value',
                                    inputValue: 'show',
                                }
                            ]
                        }] 
                    }
                ],tbar: [
                    {
                        xtype: 'tbtext',
                        text: '테잎번호',
                    },{
                        xtype: 'textfield',
                        width: 100
                    },{
                        xtype: "displayfield",
                        width: 95,
                    },{
                        xtype: 'tbtext',
                        text: '횟수',
                    },{
                        xtype: 'textfield',
                        width: 100
                    }
                ]
            }).show(); 
        }
    });
    return new Ariel.archiveManagement.originalSearchGrid();
})()

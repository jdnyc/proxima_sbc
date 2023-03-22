(function(){
    Ext.ns('Ext.ux');

    Ext.ux.p = Ext.extend(Ext.util.Observable, {
        init: function(checkbox) {
        },
        contructor: function() {
        }
    });


    function onSave(btn, e) {
    }

    // var form = new Ext.form.FormPanel();

    return {
        xtype: 'form',
        frame: true,
        padding: 10,

        defaultType: 'textfield',

        url: 'save.php',

        items: [{
            allowBlank: false,
            fieldLabel: '카테고리 명칭',
            labelWidth: 50,
            width: 250
        }, {
            xtype: 'checkbox',
            hideLabel: true,
            boxLabel: '외부 BIS 제작 프로그램 연동 지원',
        }, {
            xtype: 'checkbox',
            // hideLabel: true,
            fieldClass: 'width: 25px;',
            boxLabel: 'BIS 제작 프로그램 외 폴더 생성 지원',
            plugins: new Ext.ux.p()
        }, {
            xtype: 'checkbox',
            hideLabel: true,
            boxLabel: '미디어 검색에서 변경 지원',
        }, {
            xtype: 'checkbox',
            hideLabel: true,
            boxLabel: '스토리지 폴더 구조와 카테고리 연동'
        }],

        buttons: [{
            text: '저장',
            handler: onSave
        }]
    };
})()
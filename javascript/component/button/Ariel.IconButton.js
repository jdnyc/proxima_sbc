(function(){
    Ext.ns('Ariel');
    Ariel.IconButton = Ext.extend(Ext.Button, { 
        constructor: function(config) {
            config.cls = 'proxima_button_customize';

            var content = '';
            if (Ext.isEmpty(config.icon)) {
                content = config.text;
            } else {
                if(Ext.isEmpty(config.iconSize)) {
                    config.iconSize = 13;
                }
                content = '<i class="' + config.icon + '"></i>';
            }

            config.text = '<span style="position:relative;top:1px;font-size:13px;color:white;padding:0px 7px 0px 7px;" title="' + config.text + '">' + content + '</span>';

            config.icon = undefined;
            Ext.apply(this, config || {});
            Ariel.IconButton.superclass.constructor.call(this);
        }   
    });
    Ext.reg('a-iconbutton', Ariel.IconButton);

    Ariel.AwIconButton = Ext.extend(Ext.Button, {
        scale: 'medium',
        constructor: function(config) {
            var iClsText = '';
            var iClsTag = '';
            var iText = '&nbsp;';
            var iColor = '';
            if( !Ext.isEmpty(config.iColor) ){
                iColor = 'color:'+config.iColor+';';
            }
            if( Ext.isEmpty(config.iFontSize) ){
                config.iFontSize = 13;
            }
            if( !Ext.isEmpty(config.text) ){
                iText = '&nbsp;' + config.text ;
                iTextTitle= 'title="'+ config.text+'"';
            }
                 
            if( !Ext.isEmpty(config.iCls) ){
                iClsText = 'class="'+config.iCls+'" ';
                iClsTag = '<i '+iClsText+' style="font-size:'+config.iFontSize+'px;'+iColor+'">'+iText+'</i>';
            }else{
                iClsTag = '<div style="font-size:'+config.iFontSize+'px;'+iColor+'">'+iText+'</div>';
            }

            config.text = '<span style="position:relative;" '+iTextTitle+' >'+ iClsTag +'</span>';

            config.icon = undefined;
            Ext.apply(this, config || {});
            Ariel.AwIconButton.superclass.constructor.call(this);
        }   
    });
    Ext.reg('aw-button', Ariel.AwIconButton);

    /*
    fa fa-check
    fa-times
    fa-ban
    fa-user-plus
    fa-refresh
    fa-search
    fa-file-excel-o
    */
    Ariel.AwIconButton = Ext.extend(Ext.Button, {
        scale: 'medium',
        constructor: function(config) {
            var iClsText = '';
            var iClsTag = '';
            var iText = '&nbsp;';
            var iColor = '';
            if( !Ext.isEmpty(config.iColor) ){
                iColor = 'color:'+config.iColor+';';
            }
            if( Ext.isEmpty(config.iFontSize) ){
                config.iFontSize = 13;
            }
            if( !Ext.isEmpty(config.text) ){
                iText = '&nbsp;' + config.text ;
                iTextTitle= 'title="'+ config.text+'"';
            }
                 
            if( !Ext.isEmpty(config.iCls) ){
                iClsText = 'class="'+config.iCls+'" ';
                iClsTag = '<i '+iClsText+' style="font-size:'+config.iFontSize+'px;'+iColor+'">'+iText+'</i>';
            }else{
                iClsTag = '<div style="font-size:'+config.iFontSize+'px;'+iColor+'">'+iText+'</div>';
            }

            config.text = '<span style="position:relative;" '+iTextTitle+' >'+ iClsTag +'</span>';

            config.icon = undefined;
            Ext.apply(this, config || {});
            Ariel.AwIconButton.superclass.constructor.call(this);
        }   
    });
    Ext.reg('aw-button', Ariel.AwIconButton);

    
    Ariel.AwIconNode = Ext.extend(Ext.tree.TreeNode, {

        constructor: function(config) {
            var iClsText = '';
            var iClsTag = '';
            var iText = '&nbsp;';
            if( Ext.isEmpty(config.attributes.iColor) ){
                config.attributes.iColor = 'black';
            }
            if( Ext.isEmpty(config.attributes.iFontSize) ){
                config.attributes.iFontSize = 13;
            }
            if( !Ext.isEmpty(config.text) ){
                iText = '&nbsp;' + config.text ;
                iTextTitle= 'title="'+ config.text+'"';
            }
                 
            if( !Ext.isEmpty(config.attributes.iCls) ){
                iClsText = 'class="'+config.attributes.iCls+'" ';
                iClsTag = '<i '+iClsText+' style="font-size:'+config.attributes.iFontSize+'px;color:'+config.attributes.iColor+';">'+iText+'</i>';
            }else{
                iClsTag = '<div style="font-size:'+config.attributes.iFontSize+'px;color:'+config.attributes.iColor+';">'+iText+'</div>';
            }

            config.text = '<span style="position:relative;" '+iTextTitle+' >'+ iClsTag +'</span>';

            config.icon = undefined;
            Ext.apply(this, config || {});
            Ariel.AwIconNode.superclass.constructor.call(this);
        }   
    });
    Ext.reg('aw-node', Ariel.AwIconNode);
})()
$(function(){
    $('#arc_config_tree').tree({
        onSelect : function(node)
        {         
            if(node.text == 'Archive 설정 관리')
            {
                $('#main_layout').layout('panel','center').panel('refresh', '/pages/menu/config/archive/php/archive_config.html');
            }
            else if(node.text == 'Archive 모니터링')
            {
                $('#main_layout').layout('panel','center').panel('refresh', '/pages/menu/config/archive/php/archive_monitor.html');
            }
        }
    }); 
});

function arc_dlg(){
    $('#arc_dlg').dialog('open');
    var arc_treegrid = $('#arc_treegrid').treegrid('getSelected');
    if(arc_treegrid)
    {
        var check_row = $('#arc_treegrid').treegrid('getSelected');
        $('#arc_form').form('load',{
            c_category : check_row.category_id 
        }); 
    }
}

function del_dlg(){
    $('#del_dlg').dialog('open');
    var arc_treegrid = $('#arc_treegrid').treegrid('getSelected');
    if(arc_treegrid)
    {
        var check_row = $('#arc_treegrid').treegrid('getSelected');
        $('#del_form').form('load',{
            c_category : check_row.category_id
        }); 

    }
}

function abrogate_dlg(){
    $('#abrogate_dlg').dialog('open');
    var arc_treegrid = $('#arc_treegrid').treegrid('getSelected');
    if(arc_treegrid)
    {
        var check_row = $('#arc_treegrid').treegrid('getSelected');
        $('#abr_category').val(check_row.category_title); 
    }
}

function arc_submit(){
    $('#arc_form').form('submit',{
        url : "/pages/menu/config/archive/php/update_arc_info.php",
        onSubmit: function(){          
            var isValid = $(this).form('validate');
            if(!isValid)
            {
                $.messager.alert('Warning', '값을 모두 입력하세요');
            }
        },
        success:function(data){
            $('#arc_dlg').dialog('close');
            $.messager.alert('변경완료', data, 'info');
            $('#arc_treegrid').treegrid('reload');
        }
    })
}

function del_submit(){
    var grid_data = $('#arc_treegrid').treegrid('getSelected');
    var row = $('#arc_treegrid').treegrid('getRowIndex',grid_data);
    var category_id = grid_data.category_id;
    $('#del_form').form('submit',{
        url : "/pages/menu/config/archive/php/update_del_info.php",
        onSubmit: function(){          
            var isValid = $(this).form('validate');
            if(!isValid)
            {
                $.messager.alert('Warning', '값을 모두 입력하세요');
            }
     
        },
        success:function(data){
            $('#del_dlg').dialog('close');
            $.messager.alert('변경완료', data, 'info');
            $('#arc_treegrid').treegrid('reload');            
        }
    })
}

function abrogate_submit(){
    var grid_data = $('#arc_treegrid').treegrid('getSelected');
    var category_id = grid_data.category_id;
    
    $('#abr_form').form('submit',{
        url : "/pages/menu/config/archive/php/update_abrogate_info.php",
        onSubmit: function(param){          
            var isValid = $(this).form('validate');
            var abr_period = $('#abr_period').numberbox('getValue');
 
            if(abr_period == '0')
            {
                    $.messager.alert('Warning', '자동폐기는 최소 1일 후부터 가능합니다');
                    
            }else{
            if(!isValid)
            {
                $.messager.alert('Warning', '값을 모두 입력하세요');
            }
            param.category_id = category_id;
            }
        },
        success:function(data){
            $('#abrogate_dlg').dialog('close');
            $.messager.alert('변경완료', data, 'info');
            $('#arc_treegrid').treegrid('reload');
        }
    })
}
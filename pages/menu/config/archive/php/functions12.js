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
                alert("오픈 준비중입니다");
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
            $('#del_treegrid').treegrid('reload');
        }
    })
}
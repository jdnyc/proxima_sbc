$(function(){
    $('#category_tree').tree({
        onSelect : function(node)
        {
            var get_node = $(this).tree('getSelected');
            $('#archive_grid').datagrid('load',{
                id : get_node.id
            });
        }                                   
    });
    
    $('#view_opt1').combobox({
         onHidePanel : function()
        {
            var view_opt = $('#view_opt1').combobox('getValue');
            if(view_opt)
                {
                    var opt = $('#view_opt1').combobox('getValue');
                    $('#monitor_all').datagrid('load',
                    {
                        view_opt : opt
                    });
                }
        } 
    });

    $('#archive_grid').datagrid({
        onDblClickRow : function(index,data){
            var check_id =  data.category_id;
            if(!check_id)
            {
                $.messager.alert('Warning', '설정값이 없습니다. 신규 등록해주십시오');
            }
            else
            {
                $('#arc_edit_dlg').dialog('open');
                $('#arc_edit_form').form('load',{
                    c_category : data.category_id, 
                    a_method : data.arc_method,
                    a_period : data.arc_period
                });
                if(data.arc_method == '수동')
                {
                    $('#edit_period').numberbox('disable');
            }    
        }
            
        },
        onClickRow : function(rowIndex, rowData) {
            if(rowData.arc_method == '수동')
            {
                $('#man_arc').linkbutton('enable');
            }
        } 
                    
    });
                
    $('#edit_method').combobox({
        onHidePanel:function(){
            var method = $('#edit_method').combobox('getValue');
            if(method == 'A')
            {
                $('#edit_period').numberbox('enable');
            }
            else
            {
                $('#edit_period').numberbox('disable');
            }
        }
    });
                     
    $('#add_method').combobox({
        onHidePanel:function(){
            var method = $('#add_method').combobox('getValue');
            if(method == 'A')
            {
                $('#add_period').numberbox('enable');
            }
            else
            {
                $('#add_period').numberbox('disable');
            }
        }
    });
    
    $('#contents_tab').tabs({
        onSelect :function(index){
           var tab = $('#contents_tab').tabs('getSelected');
                $('#contents_tab').tabs('update',{
                    tab : tab,
                    options : {
                        href : '/pages/menu/config/archive/archive_content.html',
                        ud_content_id : index
                    }
                })               
            } 
    })
    
});
              
          
function add_dlg(){
    $('#arc_add_dlg').dialog('open');
    var arc_grid = $('#archive_grid').datagrid('getSelected');
    if(arc_grid)
    {
        var check_row = $('#archive_grid').datagrid('getSelected');
        $('#arc_add_form').form('load',{
            c_category : check_row.category_title 
        }); 
    }
    else
    {
       var archive = $('#category_tree').tree('getSelected');
        if(archive)
        {
       var arc = $('#category_tree').tree('getSelected');
            $('#arc_add_form').form('load',{
                c_category : arc.text 
            });
        }
    }
}        
            
function edit_dlg(){
    var archive =  $('#archive_grid').datagrid('getSelected');
    if(archive)
    {
        var check_id =  archive.category_id;
        if(!check_id)
        {
            $.messager.alert('Warning', '설정값이 없습니다. 신규 등록해주십시오');
        }
        else
        {
            $('#arc_edit_dlg').dialog('open');
            var arc =  $('#archive_grid').datagrid('getSelected');
            $('#arc_edit_form').form('load',{
                c_category : arc.category_title,
                a_method : arc.arc_method,
                a_period : arc.arc_period
            });
        }
    }else                   
    {
        $.messager.alert('Warning','수정하실 항목을 선택해주세요');   
    }
                
}
function del_dlg(){
    var archive =  $('#archive_grid').datagrid('getSelected');
    if(archive)
    {
        $.messager.alert('Info', '준비 중 입니다');
    }else
    {
        $.messager.alert('Warning','삭제하실 항목을 선택해주세요');  
    }
}
function add_submit(){
    $('#arc_add_form').form('submit',{
        url : "/pages/menu/config/archive/php/update_arc_info.php",
        onSubmit: function(){          
            var isValid = $(this).form('validate');
            if(!isValid)
            {
                $.messager.alert('Warning', '값을 모두 입력하세요');
            }
        },
        success:function(data){
            $('#arc_add_dlg').dialog('close');
            $.messager.alert('등록완료', data, 'info');
            $('#archive_grid').datagrid('reload');
        }
    })
}
function edit_submit(){
    $('#arc_edit_form').form('submit',{
        url : "/pages/menu/config/archive/php/update_arc_info.php",
        onSubmit: function(){
            var isValid = $(this).form('validate');
            if(!isValid)
            {
                $.messager.alert('Warning', '값을 모두 입력하세요');
            }
            
        },
        success:function(data){
            $('#arc_edit_dlg').dialog('close');
            $.messager.alert('수정완료',data, 'info');
            $('#archive_grid').datagrid('reload');
        }
    })
}
            
function manual_arc(){
    $('#con_man_arc').dialog('open');
    var arc =  $('#archive_grid').datagrid('getSelected');
    $('#m_arc_grid').datagrid('load',{
        category_id : arc.category_id
    });
}
            
function monitor_arc(){
    $('#monitor_archive').dialog('open');
    var arc =  $('#archive_grid').datagrid('getSelected');
    $('#man_arc_grid').datagrid('load',{
        index : 'all'
    });
}
            
function doSearch(){
    var ss_date = $('#start_date').datebox('getValue');
    var se_date = $('#end_date').datebox('getValue');
    $('#m_arc_grid').datagrid('load',{
        //      category_id : category_id,
        start_date : ss_date.replace(/-/g,''),
        end_date : se_date.replace(/-/g,'') 
    });
}
function select_all(){
    $('#m_arc_grid').datagrid('checkAll');
}
$.fn.datebox.defaults.formatter = function(date){
    var y = date.getFullYear();
    var m = ('0' + (date.getMonth()+1)).slice( -2 );
    var d = ('0' + date.getDate()).slice( -2 );
    return y+'-'+m+'-'+d;
}


		
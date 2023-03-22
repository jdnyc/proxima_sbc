<?php

$ud_content_id = $_GET['ud_content_id'];

if(empty($ud_content_id))
{
    $ud_content_id = '0';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
        <meta charset="UTF-8"></meta>
        <title>Archive 관리</title>
        <link rel="stylesheet" type="text/css" href="/jquery/themes/default/easyui.css"></link>
        <link rel="stylesheet" type="text/css" href="/jquery/themes/icon.css"></link>

        <script src="http://code.jquery.com/jquery-1.8.1.min.js"></script>
        <script type="text/javascript" src="/jquery/jquery.easyui.min.js"></script>
        <script src="/javascript/URI.js"></script>
    </head>
    <body>
        <table id="monitor_grid" class="easyui-datagrid" singleSelect="true" name="arc_monitor" 
                   url = "/pages/menu/config/archive/php/get_arc_monitor.php?ud_content_id=<?=$ud_content_id?>"
                   rownumbers ="true" toolbar="#monitor_tb" pagination="true" pageSize = "20" pageList="[10,20,50,100,200]">
                <thead>
                    <tr>
                        <th field="task_id" width="50"><center>ID</center></th>
                        <th field="title" width="200"><center>제 목</center></th>
                        <th field="created_datetime" width="100" align="center">등록일자</th>
                   </tr>
                </thead> 
        </table>
        <div id="monitor_tb" style="padding:3px" >
            <td>
                <select id="arc_type" class="easyui-combobox" name="arc_type" style="width:80px;" data-options="panelHeight:'auto'">
                    <option value ="all">전체보기</option>
                    <option value ="complete">아카이브성공</option>
                    <option value ="wait">아카이브대기</option>
                    <option value ="delete">아카이브삭제</option>
                </select>
            </td>
            <td>
                <select id="arc_status" class="easyui-combobox" name="arc_status" style="width:80px;" data-options="panelHeight:'auto'">
                    <option value ="all">전체보기</option>
                    <option value ="complete">아카이브성공</option>
                    <option value ="error">아카이브실패</option>
                    <option value ="delete">아카이브삭제</option>
                </select>
            </td>
            <td>
                <span> 아카이브 날짜 : </span>  
                <input id="arc_start_date" type="text" class="easyui-datebox" style="width:90px;"></input>  
                <span> ~ </span>  
                <input id="arc_end_date" type="text" class="easyui-datebox" style="width:90px;"></input>  
                <a href="#" class="easyui-linkbutton" iconCls="icon-search" plain="true" onclick="doSearch()">검색</a>
            </td>
            <td>
                <a href=#" class="easyui-linkbutton" iconCls="icon-arrow-redo" plain="true" onclick="jobRetry()">재요청</a>
            </td>
        </div>  
    </body>
</html>

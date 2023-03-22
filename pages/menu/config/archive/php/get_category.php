<?php
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
    require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
    
    global $db;
    
    if(!isset($_POST['id']))
    {
        $id = 0;
    }
    else
    {
        $id = intval($_POST['id']);
    }
    $result = array();
    $query = "select * from bc_category where parent_id = $id order by show_order";
    $categories = $db->queryAll($query);
    foreach($categories as $category)
    {
        $node = array();
        $node['id'] = $category['category_id'];
        $node['text'] = $category['category_title'];
        if($category['no_children']== 0)
        {
            $leaf = 'closed';
        }
        else
        {
            $leaf = 'open';
        }
        $node['state'] = $leaf;
        array_push($result, $node);
    }
    
    echo json_encode($result);
    
?>

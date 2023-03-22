<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lang.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/out.php');
session_start();
$user_id = $_SESSION['user']['user_id'];

try
{

   $query_m = "SELECT  top_menu_mode, slide_thumbnail_size, first_page, slide_summary_size, show_content_subcat_yn
    FROM        bc_member_option
    WHERE   member_id = (
        SELECT  member_id
        FROM        bc_member
        WHERE   user_id =  '".$user_id."'
    )
   ";
   $results = $db->queryAll($query_m);
   echo json_encode($results);

}
catch (Exception $e)
{
   echo _text('MN01039').' : '.$e->getMessage();//'오류 : '
}
?>
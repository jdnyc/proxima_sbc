<?php
$content_id = $_POST['content_id'];
$allow = $db->queryOne("select count(*) 
								from bc_media
								where content_id = $content_id
								and media_type = 'proxy'
								and path != 'Temp'");
if ($allow > 0)
{
	HandleSuccess(array('allow'=>false));
}
else
{
	HandleSuccess(array('allow'=>true));
}
?>
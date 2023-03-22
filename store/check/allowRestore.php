<?php
$content_id = $_POST['content_id'];
$allowRestore = $db->queryOne("select count(*) 
								from alto_archive a, bc_media m 
								where m.content_id=$content_id
								and m.media_type='original'
								and m.media_id=a.media_id
								and a.uuid is not null");
if ($allowRestore == 0)
{
	HandleSuccess(array('allowRestore'=>false));
}
else
{
	HandleSuccess(array('allowRestore'=>true));
}
?>
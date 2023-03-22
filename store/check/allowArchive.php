<?php
$content_id = $_POST['content_id'];
$allowArchive = $db->queryOne("select count(*) 
								from alto_archive a, bc_media m 
								where m.content_id=$content_id
								and m.media_type='original'
								and m.media_id=a.media_id
								and a.uuid is not null");
if ($allowArchive > 0)
{
	HandleSuccess(array('allowArchive'=>false));
}
else
{
	HandleSuccess(array('allowArchive'=>true));
}
?>
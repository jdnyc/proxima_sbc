<?php
function getPrefixPath($media_id) {
	global $db;

	$root_path = $db->queryOne("SELECT S.PATH 
								FROM BC_MEDIA M, BC_TASK T, BC_TASK_RULE TR, BC_STORAGE S
								WHERE M.MEDIA_ID=$media_id
								AND M.MEDIA_ID=T.MEDIA_ID
								AND T.TASK_RULE_ID=TR.TASK_RULE_ID
								AND TR.TARGET_PATH=S.STORAGE_ID");

	$root_path = explode('/', str_replace('\\', '/', $root_path), 5);

	return rtrim($root_path[4], '/');
}
?>
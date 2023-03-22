<?php
function queryOne($sql)
{
	global $db;

	$r = $db->queryOne($sql);

	return $r;
}

function queryRow($sql)
{
	global $db;

	$r = $db->queryRow($sql);

	return $r;
}

function queryAll($sql)
{
	global $db;

	$r = $db->queryAll($sql);

	return $r;
}

function queryExec($sql)
{
	global $db;

	$r = $db->exec($sql);

	return $r;
}
?>
<?php
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/lib.php');

$type = $_POST['type'];

$page = $_SERVER['DOCUMENT_ROOT'].'/store/check/'.$type.'.php';
if ( file_exists($page) )
{
	include($_SERVER['DOCUMENT_ROOT'].'/store/check/'.$type.'.php');
}
else
{
	handleError('page not found.');
}
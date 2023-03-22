<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require __DIR__ . '/program.manager.class.php';

$PM = new ProgramManager($db);

$node = $_POST['node'];

if (is_numeric($node)) {
    $data = $PM->getPrograms($node);
} else {
    $data = $PM->getPrograms(0);
}



echo json_encode($data);
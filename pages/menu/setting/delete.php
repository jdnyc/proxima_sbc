<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require __DIR__ . '/program.manager.class.php';

$user_id = $_SESSION['user']['user_id'];

$data = json_decode(file_get_contents('php://input'));

$PM = new ProgramManager($db);

$PM->delete($data->id);

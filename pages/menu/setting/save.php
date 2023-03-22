<?php
session_start();
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/config.php');
require_once($_SERVER['DOCUMENT_ROOT'].'/lib/functions.php');
require __DIR__ . '/program.manager.class.php';

$user_id = $_SESSION['user']['user_id'];

$data = json_decode(file_get_contents('php://input'));

$PM = new ProgramManager($db);

$category_id = $PM->insertEpisode(0, $data->pgm_id, $data->pgm_nm);
foreach ($data->episode as $episode) {
    $PM->insertEpisode($category_id, $episode->pgm_id, $episode->epsd_nm, $episode->epsd_no);
}
<?php
session_start();
require_once '../lib/config.php';
require_once '../workflow/lib/task_manager.php';

$user_id = $_SESSION['user']['user_id'];
$content_list = json_decode($_POST['content_list']);
$channel = $_POST['channel'];

$task = new TaskManager($db);

foreach ($content_list as $content_id) {

    $task->start_task_workflow($content_id, $channel, $user_id);
}


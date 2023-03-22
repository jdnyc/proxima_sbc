<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan sync', ['--type' => 'code']);
$task->everyHour()
    ->description('Code Sync');


return $scheduler;
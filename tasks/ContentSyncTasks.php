<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan sync', ['--type' => 'content','--limit' => '50']);
$task->everyMinute()
    ->description('Portal Content Sync');

return $scheduler;
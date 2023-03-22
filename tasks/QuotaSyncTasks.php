<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan quota:sync');
$task->everyHour()
    ->description('Scratch Quota Sync');


return $scheduler;
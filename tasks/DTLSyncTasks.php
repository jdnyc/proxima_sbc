<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan schedule:dtl_sync');
$task->everyDay()
    ->description('DTL Sync');

return $scheduler;
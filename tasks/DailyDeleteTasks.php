<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan schedule:delete -t restore');
$task->daily()
->at('01:00')
    ->description('Daily Restore Delete');
//->at('01:20')
$task = $scheduler->run(PHP_BINARY . ' artisan schedule:delete -t original');
$task->daily()
->hour(3, 9, 15, 21)
    ->description('Daily Archive after Original Delete');

$task = $scheduler->run(PHP_BINARY . ' artisan schedule:delete -t ud_origin');
$task->daily()
->at('01:40')
    ->description('Daily 14 days after UD origin');

$task = $scheduler->run(PHP_BINARY . ' artisan schedule:delete_media -t archive');
$task->everyHour()
    ->description('Daily Nearline Delete');

    $task = $scheduler->run(PHP_BINARY . ' artisan schedule:delete_media -t archive_percent');
$task->everyHour()
    ->description('Daily Nearline Delete');


return $scheduler;
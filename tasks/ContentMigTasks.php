<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan mig:task',['--code' => 'thumb','--limit' => '500'] );
$task->everyHour()
    ->description('Content thumb Mig');
$task = $scheduler->run(PHP_BINARY . ' artisan mig:task',['--code' => 'info','--limit' => '200'] );
$task->everyHour()
    ->description('Content media info Mig');

return $scheduler;
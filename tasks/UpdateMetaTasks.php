<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$task = $scheduler->run(PHP_BINARY . ' artisan schedule:update_meta', ['--type' => 'embargo']);
$task->everyThirtyMinutes()
    ->description('Update Meta embargo');

return $scheduler;
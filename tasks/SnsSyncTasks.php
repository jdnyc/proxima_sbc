<?php

use Crunz\Schedule;

$schedule = new Schedule();
$task = $schedule->run(PHP_BINARY . ' artisan job:social', ['--job' => 'sync']);
$task->everyMinute()
    ->description('Sync social network.');

return $schedule; 
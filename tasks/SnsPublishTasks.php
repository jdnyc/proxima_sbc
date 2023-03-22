<?php

use Crunz\Schedule;

$schedule = new Schedule();
$task = $schedule->run(PHP_BINARY . ' artisan job:social', ['--job' => 'publish']);
$task->everyMinute()
    ->description('Publish social network.');

return $schedule; 
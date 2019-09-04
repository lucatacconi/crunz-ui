<?php
// tasks/FirstTasks.php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run('/usr/bin/php email0.php');
$task
    ->saturdays()
    ->on('13:30')
    ->description('Test Mail 0');

// ...

// IMPORTANT: You must return the schedule object
return $schedule;

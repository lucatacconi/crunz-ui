<?php
// tasks/FirstTasks.php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run('/usr/bin/php email3.php');
$task
    ->mondays()
    ->description('Test Mail 3');

// ...

// IMPORTANT: You must return the schedule object
return $schedule;

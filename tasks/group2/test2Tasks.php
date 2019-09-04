<?php
// tasks/FirstTasks.php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run('/usr/bin/php email2.php');
$task
    ->yearly()
    ->description('Test Mail 2');

// ...

// IMPORTANT: You must return the schedule object
return $schedule;

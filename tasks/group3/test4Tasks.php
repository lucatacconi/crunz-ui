<?php
// tasks/FirstTasks.php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run('/usr/bin/php email4.php');
$task
    ->monthly()
    ->description('Test Mail 4');

// ...

// IMPORTANT: You must return the schedule object
return $schedule;

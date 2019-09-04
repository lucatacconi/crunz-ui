<?php
// tasks/FirstTasks.php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run('/usr/bin/php email1.php');
$task
    ->daily()
    ->description('Test Mail 1');

// ...

// IMPORTANT: You must return the schedule object
return $schedule;

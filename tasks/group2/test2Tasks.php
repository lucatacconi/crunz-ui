<?php
// tasks/FirstTasks.php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run('cp project project-bk');
$task
    ->daily()
    ->description('Create a backup of the project directory.');

// ...

// IMPORTANT: You must return the schedule object
return $schedule;

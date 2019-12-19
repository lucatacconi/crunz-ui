<?php

use Crunz\Schedule;

$schedule = new Schedule();

$task = $schedule->run(function() {
    echo "Running a test...";
    sleep(5);
});

$task
->description('Task file '.__FILE__)
->daily();

return $schedule;



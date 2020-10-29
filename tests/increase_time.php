<?php

function increaseTime($_time, $_interval) {
    $hours   = ($_time / 100);
    $minutes = ($_time % 100);
    $hours   += intval($_interval / 60);
    $minutes += $_interval % 60;
    if ($minutes >= 60) {
        $minutes = $minutes % 60;
        $hours++;
    }
    $hours = $hours % 24;
    return ($hours * 100) + $minutes;
}

for ($h = 0; $h < 24; $h++) {
   for ($m = 0; $m < 60; $m++) {
       print "$h:" . str_pad($m, 2, '0', STR_PAD_LEFT) . " +15 ==> " .increaseTime(($h * 100) + $m, 15) . "\n";
   }
}


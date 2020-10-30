<?php

$times       = array();
$interval    = 5; 

/**
$hours   = 0;
$minutes = 0;
$end     = 24 * 60 / $interval;
for($i = 0; $i < $end; $i++) {
    $next_mins  = ($minutes + $interval - 1);
    $next_hours = ($hours);
    array_push($times, ($hours * 100 + $minutes));
    print "time-interval [" . $hours . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT) 
		. " .. " . ($next_hours % 24) . ":" . str_pad($next_mins, 2, '0', STR_PAD_LEFT)  . "]\n";
    $minutes  += $interval;
    if ($minutes == 60) {
	$minutes = 0;
        $hours++;
    }
}
*/

$minutes = 0;
$hours   = 0;
$count   = 24 * 60 / $interval;
for($i = 0; $i < $count; $i++) {
   array_push($times, ($hours * 100 + $minutes));
   $hours   += intval(($minutes + $interval) / 60);
   $minutes  = ($minutes + $interval) % 60;
}

print_r ($times);

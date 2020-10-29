<?php

$times     = array(1600, 1615, 1630, 1645);
$intervals = array(5, 10, 15, 30, 60);

print "start time\n";
foreach ($times as $time) {
    print "TIME INITIAL = $time\n";
    foreach ($intervals as $interval) {
        print "INTERVAL = $interval --> ";
        //print "DELTA = " . (($time % 100 ) % $interval) . "\n"; 
        $hours    = intval($time / 100);
        $minutes  = ($time % 100);
        $minutes -= ($minutes % $interval);
        print "$hours:" . str_pad($minutes, 2, '0', STR_PAD_LEFT) ."\n";
    }
}

print "end time\n";
foreach ($times as $time) {
    print "TIME INITIAL = $time\n";
    foreach ($intervals as $interval) {
        print "INTERVAL = $interval --> ";
        //print "DELTA = " . ((($time % 100 ) % $interval)) . "\n"; 
        $hours   = intval($time / 100);
        $minutes = ($time % 100);
	if (($interval % 60) == 0) { 
            $minutes -= ($minutes % $interval);
            $hours   ++;
        } else {
            $minutes += ($minutes % $interval);
        }
        $hours   += intval($minutes / 60);
        $minutes  = ($minutes % 60);
        print "$hours:" . str_pad($minutes, 2, '0', STR_PAD_LEFT) ."\n";
    }
}

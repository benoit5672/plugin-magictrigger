<?php

function getIndex($_time, $_interval) {
    $hours   = intval($_time / 100);
    $minutes = ($_time % 100);
    print " ($_time === $hours:$minutes) ";
    return (((60 / $_interval) * $hours) + intval($minutes / $_interval));
}


for ($h = 0; $h < 24; $h++) {
   for ($m = 0; $m < 60; $m++) {
       print "$h:" . str_pad($m, 2, '0', STR_PAD_LEFT) . " ==> " . getIndex(($h * 100) + $m, 15) . "\n";
   }
}


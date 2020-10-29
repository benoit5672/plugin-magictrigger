<?php
function getTime($_index, $_period) {
   $time    = $_index * $_period;
   $hours   = intval($time / 60);
   $minutes = ($time % 60);
   return (str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT));
}

$period = 5;
$count  = (24 * intval(60 / $period));
for ($i; $i < $count; $i++) {
    print str_pad($i, 3, ' ') . " = " . getTime($i, $period) . "\n";
}
 


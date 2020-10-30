<?php

$timestamp = time();
print "TIMESTAMP: $timestamp\n";

$adjust = $timestamp % 86400;
$begin = $timestamp - $adjust;
$date  = date("d/m/Y H:i", $begin);
print "BEGIN: $begin ($date), ADJUST=$adjust\n";

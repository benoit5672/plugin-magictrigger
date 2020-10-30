<?php

$before = date("m/d/Y", mktime(0,0,0,date("m")-24, date("d"), date("y")));

print $before . " ===> " . strtotime($before) . "\n";

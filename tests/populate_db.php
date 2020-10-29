<?php

$magicId = 12;

$tests = array (
/*
	'dummy' => array(
	    1 => array('cnt' => 150, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	    2 => array('cnt' => 200, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	    3 => array('cnt' => 500, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	    4 => array('cnt' => 100, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	    5 => array('cnt' => 750, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	    6 => array('cnt' => 50, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	    0 => array('cnt' => 300, 'bh' => 0, 'bm' => 0, 'eh' => 23, 'em' => 59),
	),
*/
        'entries' => array (
 	    // target data
	    1 => array('cnt' => 200, 'bh' => 16, 'bm' => 30, 'eh' => 17, 'em' => 59),
	    2 => array('cnt' => 300, 'bh' => 14, 'bm' => 00, 'eh' => 15, 'em' => 25),
	    3 => array('cnt' => 250, 'bh' => 12, 'bm' => 20, 'eh' => 12, 'em' => 40),
	    4 => array('cnt' => 120, 'bh' => 15, 'bm' => 00, 'eh' => 15, 'em' => 59),
	    5 => array('cnt' => 175, 'bh' => 19, 'bm' => 30, 'eh' => 19, 'em' => 50),
	    6 => array('cnt' => 250, 'bh' => 11, 'bm' => 00, 'eh' => 11, 'em' => 59),
	    6 => array('cnt' => 150, 'bh' => 16, 'bm' => 00, 'eh' => 19, 'em' => 59),
	)
   );
	
print "USE jeedom;\n";
print "CREATE TABLE IF NOT EXISTS `magictriggerDB` (\n";
print "`added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n";
print "`magicId` INT(11) NOT NULL,\n";
print "`dow` TINYINT(1) UNSIGNED NOT NULL,\n";
print "`time` MEDIUMINT(4) UNSIGNED NOT NULL\n";
print ") ENGINE=InnoDB DEFAULT CHARSET=utf8;\n";


print "INSERT INTO magictriggerDB (magicId, dow, time) VALUES\n";
foreach ($tests as $k =>$v) {
    foreach ($v as $key =>$values) {
       $count = $values['cnt']; 
       for ($i = 0; $i < $count; ++$i) {
           $time = random_int($values['bh'],$values['eh']).str_pad(rand($values['bm'],$values['em']), 2, "0", STR_PAD_LEFT);
           print "(" . $magicId . ", " . $key . ", " . $time . "),\n";
       }
    }
}
print ";\n";
?>

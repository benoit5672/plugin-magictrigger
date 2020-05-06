<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */


class magictriggerEvent {

	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

    /**
     * load the events for the specified day, and between start and date. 
     * It sum all the elements in the same period. 
     * It also create elements for non present points in the period
     * It only return the total of events in the period (array of integers)
     */
    public static function getEvents($_magicId, $_dow, $_start, $_end, $_period) {

        $values = magictriggerDB::byIdDowTime($_magicId, $_dow, $_start, $_end);
        if (!is_array($values) || (count($values) > 0 && !is_object($values[0]))) {
            log::add('magictrigger', 'error', __('Erreur dans getEvents', __FILE__));
            return array();
        }

        // initialize the result tabs with all values to 0 
        $count = 24 * intval(60 / $_period);
        $res   = array_fill(0, $count, 0);
        log::add('magictrigger', 'debug', 
                 'getEvents will return an array of ' . $count . ' elements.');
        
        // Now addition the values returned from the DB, into a single period
        foreach ($values as $value) {
            $inter = self::getIndex($value->getTime(), $_period);
            log::add('magictrigger', 'debug', 'time=' . $value->getTime() . ' Add mte[' . $inter . ']=' . $value->getCount());
            $res[$inter] += $value->getCount();
        }
        return $res;
    }


    /**
     * Calculate the statistics for the specified day, and between start and date. 
     *
     * To do that, it sums all the elements in the same period. For the first
     * entries, it loads the entries for the day before 
     */
    public static function getStats($_magicId, $_dow, $_start, $_end, $_period, $_interval) {

        // Check if we need data from tomorrow, that is to say that $_end + $_interval > 2400
        $isTomorrowNeeded = (($_end + $_interval) >= 2400);

        // Fetch the data for today and tomorrow if needed
        if (isTomorrowNeeded == true) {
            $t             = (($_dow + 1) % 7);
            $tomorrow      = self::getEvents($_magicId, $t, 0, $_interval, $_period);
            $totalTomorrow = magictriggerDB::getTotalPerDowTime($_magicId, $t, 0, $_interval);
        } else {
            $tomorrow      = array_fill(0, intval($_interval / $_period), 0);
            $totalTomorrow = 0;
        } 
        // Events for today
        $today      = self::getEvents($_magicId, $_dow, $_start, $_end, $_period);
        $totalToday = magictriggerDB::getTotalPerDowTime($_magicId, $_dow, $_start, $_end);
        $count      = count($today);

        //  the total for today
        $total = $totalToday + $totalTomorrow;

        log::add('magictrigger', 'debug', 'getStats: total=' . $total . '(' . $totalToday . ' + ' . $totalTomorrow . ')');

        if ($total != 0) {

            // First, add the tomorrow entries to the today array
            $p = intval($_interval / $_period);
            for($i = 0; $i < $p; $i++) {
                array_push($today, $tomorrow[$i]);
            }

            // Build the statistics
            $res  = array();
            $max  = 0;
            for ($i = 0; $i < $count; $i++) {
                $sum = 0;
                for ($j = 0; $j < $p; $j++) {
                    $sum += $today[$i+$j];
                }
                $stat = round(($sum / $total) * 100);
                if ($stat != 0) log::add('magictrigger', 'debug', 'getStats == stats[' . $i . '] = ' . $stat . '(' . $sum . '/' . $total . ')');
                
                array_push($res, $stat);
                if ($stat >= $max) {
                    $max  = $stat;
                }
            }
            log::add('magictrigger', 'info', __('Maximum du jour ', __FILE__) . $max . '%');
        } else {
            // today is an array of 0 values !
            $res = $today;
        }
        return $res;
    }


    /**
     * Return the index in the array for the specified time and period
     */
    public static function getIndex($_time, $_period) {
        $hours   = intval($_time / 100);
        $minutes = ($_time % 100);
        return (((60 / $_period) * $hours) + intval($minutes / $_period));
    }


	/*     * *********************Methode d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */
}


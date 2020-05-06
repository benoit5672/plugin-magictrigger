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
     * Return the index in the array for the specified time and period
     */
    public static function getIndex($_time, $_period) {
        $hours   = intval($_time / 100);
        $minutes = ($_time % 100);
        return (((60 / $_period) * $hours) + intval($minutes / $_period));
    }


    /**
     * Return the time (hours:minutes) specified index and period
     */
    public static function getTime($_index, $_period) {
        $time    = $_index * $_period;
        $hours   = intval($time / 60);
        $minutes = ($time % 60);
        return (str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT));
    }


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

        log::add('magictrigger', 'debug', 'getEvents will return an array of ' . $count . ' elements.');
        
        // Now addition the values returned from the DB, into a single period
        foreach ($values as $value) {
            $inter = self::getIndex($value->getTime(), $_period);
            //log::add('magictrigger', 'debug', 'time=' . $value->getTime() . ' Add mte[' . $inter . ']=' . $value->getCount());
            $res[$inter] += $value->getCount();
        }
        return $res;
    }


    /**
     * Calculate the statistics for the day.
     */
    public static function getStats($_magicId, $_today, $_todayStart, $_todayEnd,
            $_tomorrow, $_tomorrowStart, $_tomorrowEnd, $_period, $_interval) {

        log::add('magictrigger', 'debug', 'Entering getStats(magicId=' . $_magicId . ', today=' . $_today 
            . ', todayStart=' . $_todayStart . ', todayEnd=' . $_todayEnd 
            . ', tomorrow=' . $_tomorrow . ', tomorrowStart=' .  $_tomorrowStart 
            . ', tomorrowEnd=' . $_tomorrowEnd . ', period=' . $_period . ', interval=' . $_interval . ')');

        $steps = intval($_interval / $_period);
        
        // Fetch the data for today
        $todayEvents = self::getEvents($_magicId, $_today, $_todayStart, $_todayEnd, $_period);
        $todayTotal  = magictriggerDB::getTotalPerDow($_magicId, $_today, $_todayStart, $_todayEnd);

        // Fetch the data tomorrow if needed, otherwise use a 0 filled array
        if ($_tomorrow != -1 && $_tomorrowStart < $_interval && $_tomorrowEnd != 0) {
            //log::add('magictrigger', 'debug', 'getStats: fetch tomorrow events and total');
            $end            = min($_interval, $_tomorrowEnd);
            $tomorrowEvents = self::getEvents($_magicId, $_tomorrow, $_tomorrowStart, $end, $_period);
            $tomorrowTotal  = magictriggerDB::getTotalPerDow($_magicId, $_tomorrow, $_tomorrowStart, $end);
        } else {
            //log::add('magictrigger', 'debug', 'getStats: tomorrow is 0');
            $tomorrowEvents = array_fill(0, intval($_interval / $_period), 0);
            $tomorrowTotal  = 0;
        } 

        // We have to adds the values collected in tomorrow [0..interval]
        // at the end, we will only return the values for the day ;)
        // I know it's a bit tricky, but needed if we want accurate statistics


        //  the total for today
        $total = $todayTotal + $tomorrowTotal;

        //log::add('magictrigger', 'debug', 'getStats: total=' . $total . '(' . $todayTotal . ' + ' . $tomorrowTotal . ')');

        if ($total != 0) {

            // Store the initial length of $today
            $count = count($todayEvents);

            // Add the tomorrow entries to the today array (only $interval / $period) entries
            for($i = 0; $i < $steps; $i++) {
                array_push($todayEvents, $tomorrowEvents[$i]);
            }

            // and then build the statistics
            $res  = array();
            $max  = 0;
            for ($i = 0; $i < $count; $i++) {
                $sum = 0;
                for ($j = 0; $j < $steps; $j++) {
                    $sum += $todayEvents[$i + $j];
                }
                $stat = round(($sum / $total) * 100);
                if ($stat != 0) log::add('magictrigger', 'debug', 'getStats == ' . $_today
                    . ' @ ' . self::getTime($i, $_period) . ' = ' . $stat . '% (' . $sum . '/' . $total . ')');
                
                array_push($res, $stat);
                if ($stat >= $max) {
                    $max  = $stat;
                }
            }
            log::add('magictrigger', 'info', __('Maximum du jour ', __FILE__) . $_today
                . ' = '. $max . '%');
        } else {
            // today is an array of 0 values !
            $res = $todayEvents;
        }
        return $res;
    }

	/*     * *********************Methode d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */
}


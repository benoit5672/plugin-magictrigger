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
     * It sum all the elements in the same interval. 
     * It also create elements for non present points in the interval
     * It only return the total of events in the interval (array of integers)
     */
    public static function getEvents($_magicId, $_dow, $_start, $_end, $_interval) {

        $values = magictriggerDB::byIdDowTime($_magicId, $_dow, $_start, $_end);
        if (!is_array($values) || (count($values) > 0 && !is_object($values[0]))) {
            log::add('magictrigger', 'error', __('Erreur dans getEvents', __FILE__));
            return array();
        }

        // initialize the result tabs with all values to 0 
        $res = array();
        $count   = 24 * intval(60 / $_interval);
        //log::add('magictrigger', 'debug', 
        //         'getEvents will return an array of ' . $count . ' elements.');
        //$minutes = 0;
        //$hours   = 0;
        for($i = 0; $i < $count; $i++) {
            //log::add('magictrigger', 'debug', 'Add mte[' . $i . ']=' . ($hours * 100 + $minutes));
            array_push($res, 0);
            //$hours   += intval(($minutes + $_interval) / 60);
            //$minutes  = ($minutes + $_interval) % 60;
        }
        // Now addition the values returned from the DB, into a single interval
        foreach ($values as $value) {
            $inter = self::getIndex($value->getTime(), $_interval);
            //log::add('magictrigger', 'debug', 'time=' . $value->getTime() . ' Add mte[' . $inter . ']=' . $value->getCount());
            $res[$inter] += $value->getCount();
        }
        return $res;
    }

    /**
     * Return the index in the array for the specified time and interval
     */
    public static function getIndex($_time, $_interval) {
        $hours   = intval($_time / 100);
        $minutes = ($_time % 100);
        return (((60 / $_interval) * $hours) + intval($minutes / $_interval));
    }


	/*     * *********************Methode d'instance************************* */

	/*     * **********************Getteur Setteur*************************** */
}


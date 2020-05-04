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


class magictriggerDB {

	/*     * *************************Attributs****************************** */
	private $magicId;
	private $dow;
	private $time;
    private $count = 1;

	/*     * ***********************Methode static*************************** */

    /**
     * Return an array of elements for the specified magicId, doy of week (dow)
     * and between start and end 
     */
    public function byIdDowTime($_magicId, $_dow, $_start, $_end) {

        if ($end == 2400) {
            $end = 2359;
        } 
		$parameters = array(
			'magicId' => $_magicId,
			'dow'     => $_dow,
            'start'   => $_start,
            'end'     => $_end,
		);
        $sql = 'SELECT magicId, dow, time, COUNT(*) AS count
                FROM `magictriggerEvent`
                WHERE `magicId` = :magicId AND `dow` = :dow AND `time` >= :start AND `time` <= :end
                GROUP BY time
                ORDER BY magicId, dow, time;';
		return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Return an array tuple (magicId, dow, total for the dow)
     */
    public static function getTotalPerDow($_magicId) {

		$parameters = array(
			'magicId' => $_magicId,
		);
        $sql = 'SELECT magicId, dow, 0 AS time, COUNT(*) AS count
                FROM `magictriggerEvent`
                WHERE `magicId` = :magicId
                GROUP BY dow
                ORDER BY magicId, dow, time;';
		return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
    }

    /**
     * Return the total for the tuple (magicId, dow, start, end)
     */
    public static function getTotalPerDowTime($_magicId, $_dow, $_start, $_end) {
		$parameters = array(
			'magicId' => $_magicId,
			'dow'     => $_dow,
			'start'   => $_start,
			'end'     => $_end,
		);
        $sql = 'SELECT magicId, dow, 0 AS time, COUNT(*) AS count
                FROM `magictriggerEvent`
                WHERE `magicId` = :magicId AND `dow` = :dow AND `time` >= :start AND `time` <= :end
                GROUP BY dow
                ORDER BY magicId, dow, time;';

		$mte = DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
        if (!is_array($mte) || (count($mte) > 0 && !is_object($mte[0]))) {
            log::add('magictrigger', 'error', __('Erreur dans la fonction getTotalPerDowTime', __FILE__));
        }
        return ((count($mte) == 0) ? 0 : $mte[0]->getCount());
    }

    /**
     * Remove all the entries associated to a magicTrigger object, for example
     * when the object is deleted
     */
	public static function removeAllbyId($_magicId) {

        $parameters = array ( 
            'magicId' => $_magicId,
        );

        $sql = 'DELETE FROM `magictriggerEvent` 
                WHERE `magicId` = :magicId;';
		return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ROW);
	}

    /**
     * Remove all the entries older than the specified date for 
     * magicId specified
     */
    public static function removeAllByIdTimestamp($_magicId, $_timestamp) {

        $parameters = array ( 
            'magicId' => $_magicId,
            'added'   => $_timestamp,
        );
        $sql = 'DELETE FROM `magictriggerEvent` 
                WHERE `magicId` = :magicId AND `added` < :timestamp;';
        return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ROW);
    }


    /**
     * Remove all the entries that don't have pending eqLogic object
     */
    public static function removeDeadEvents() {
        $sql = 'SELECT magicId, dow, time, 0 AS count FROM `magictriggerEvent` 
            WHERE `magicId` NOT IN 
            (SELECT id FROM `eqLogic` WHERE magicId = id and `eqType_name` = "magictrigger");';
		$values =  DB::Prepare($sql, array(), DB::FETCH_TYPE_ALL, PDO::FETCH_CLASS, __CLASS__);
        foreach ($values as $value) {
            log::add('magictrigger', 'info', 
                __('Suppression des evenements "dead" pour l\'id', __FILE__)
                . $value->getMagicId);
            self::removeAllbyId($value->getMagicId);
        }
    }


    /** 
     * Create a new MagicTriggerEvent according to the specified parameters
     */
    public static function create($_magicId, $_dow, $_time, $_count=1) {

        return new magictriggerEvent(array('magicId' => $_magicId, 
                                           'dow' => $_dow, 
                                           'time' => $_time,
                                           'count' => $_count));
    }

	/*     * *********************Methode d'instance************************* */

    public function __construct($obj = null){
        if ($obj && is_array($obj)) {
            foreach (((object)$obj) as $key => $value) {
                if(isset($value) && in_array($key, array_keys(get_object_vars($this)))){
                    $this->$key = $value;
                }
            }
        }
    }

	public function save() {

        $parameters = array ( 
            'magicId' => $this->magicId,
            'dow'     => $this->dow,
            'time'    => $this->time
        );

        $sql = 'INSERT INTO `magictriggerEvent` SET
                `magicId` = :magicId, `dow` = :dow, `time` = :time;';
		return DB::Prepare($sql, $parameters, DB::FETCH_TYPE_ROW);
	}

	/*     * **********************Getteur Setteur*************************** */


	public function getMagicId() {
		return $this->magicId;
	}

    public function getDayOfWeek() {
        return $this->dow;
    }

    public function getTime() {
        return $this->time;
    }

    public function getCount() {
       return $this->count; 
    }

}


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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';
require_once __DIR__  . '/magictriggerEvent.class.php';
require_once __DIR__  . '/magictriggerDB.class.php';

class magictrigger extends eqLogic {

	/*     * *************************Attributs****************************** */
    private static $_days = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 
        3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday');
	
	/*     * ***********************Methode static*************************** */
	

    /*************************************************************************
     * Cron functions, called automatically by Jeedom Core 
     * We only implement cronDaily() functions
     *************************************************************************/

    /**
     * Function executed every 5 minutes by jeedom (plugin)
     */
    public static function cron5() {
        
        //log::add('magictrigger' , 'debug', 'cron5');
        // If we are running between 0:00 and 0:04, then ignore, we will
        // use the cronDaily instead
        if (date('H') == 0 && date('i') < 5) {
            return;
        }

        // Iterate, and process all magic trigger objects
    	foreach (self::byType(__CLASS__) as $magic) {
            if (is_object($magic) && $magic->getIsEnable() == 1) {
                try {
                    $cr = $magic->getCache('cron', '');
                    if ($cr === '') {
                        // cache has been cleared, rebuild the cache
                        $magic->populateCache();
                        $cr = $magic->getCache('cron', '');
                    }
                    $cron = new Cron\CronExpression($cr, new Cron\FieldFactory);
                    log::add('magictrigger', 'debug', $magic->getHumanName() 
                        . ' isDue(' . $cr . ') = ' 
                        . (($cron->isDue() == 1) ? 'true' : 'false'));
				    if ($cron->isDue()) {
				        try {
							$magic->cronNotification($magic);
						} catch (Exception $e) {
                            log::add('magictrigger', 'error', $magic->getHumanName() 
                                . __('Erreur ', __FILE__) . ': ' . $e->getMessage());
						}
					}
				} catch (Exception $e) {
                    log::add('magictrigger', 'error', $magic->getHumanName() . 
                        __('Expression cron non valide pour ', __FILE__) . ': ' 
                        . $magic->getCache('cron'));
				}
			}
		}
    }

    /**
     * Function executed every day by jeedom (plugin)
     */
     public static function cronDaily() {

         // remove dead events (events not associated to any eqLogic (magictrigger) object)
         magictriggerDB::removeDeadEvents();
         
         foreach (eqLogic::byType(__CLASS__, true) as $magic) {
            // remove events based on the retention parameter
            $magic->removeOldEvents();
            
            // Refresh the data
            $magic->getInformation(); 

            // once the information has been fetched, process the data !
            $magic->cronNotification($magic);
        }
     }

    /**
     * Function call by the plugin core object when the plugin is started
     */
    public static function start() {
        foreach (eqLogic::byType(__CLASS__, true) as $magic) {
            $magic->getInformation();
        }
    }

    /**
     * function that increase the time by the specified interval
     * if time + interval is superior to 23:59, then we return
     * the time for the next day (00:30 for example)
     */
    private static function increaseTime($_time, $_interval) {
        $hours   = ($_time / 100);
        $minutes = ($_time % 100);
        $hours   += intval($_interval / 60);
        $minutes += $_interval % 60;
        if ($minutes >= 60) {
            $minutes = $minutes % 60;
            $hours++;
        }
        $hours = $hours % 24;
        return ($hours * 100) + $minutes;
    }


    /**
     * Convert common time format to a numerical format
     * 16:05 --> 1605
     */
    private static function timeToJeeTime($_time) {
        return intval(ltrim(str_replace(':', '', $_time), '0'));
    }

    /**
     * Convert a time in minute to a time in jeedom time (numerical hourminute)
     */
    private static function minutesToJeeTime($_time) {
        return ((intval($_time / 60) * 100) + ($_time % 60));
    }


  	/*     * *********************Methode d'instance************************* */

    /** PRIVATE FUNCTIONS **/

    /** 
     * Function to manage the listener associated with the triggers
     * - getListener
     * - removeListener
     * - addListener
     */
    private function getListener() {
        return listener::byClassAndFunction(__CLASS__, 'triggerCallback', 
                                            array('id' => $this->getId()));
	}

	private function removeListener() {
		log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering removeListener');

        $listener = $this->getListener();
		if (is_object($listener)) {
		   $listener->remove();
        }
	}

	private function addListener() {
		log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering addListener');

        // If the object is disabled, then remove the listener
		if ($this->getIsEnable() == 0) {
			$this->removeListener();
			return;
		}

        // Otherwise, add all the triggers to the event list
		$listener = $this->getListener();
		if (!is_object($listener)) {
			$listener = new listener();
			$listener->setClass(__CLASS__);
			$listener->setFunction('triggerCallback');
			$listener->setOption(array('id' => $this->getId()));
		}
		$listener->emptyEvent();

        $triggers = $this->getConfiguration('triggers');
        foreach ($triggers as $trigger) {
            $cmd = cmd::byId(str_replace('#', '', $trigger['cmd']));
            if (!is_object($cmd)) {
                continue;
            }
			$listener->addEvent($cmd->getId());
        }
        $listener->save();
    }


    /**
     * Function used to build the cron arguments, based on the period configuration
     * parameters
     * set the result in cache
     */
    private function setCron() {

        // Minutes
        $refresh  = $this->getConfiguration('period');
        $minutes  = $refresh % 60;
        $cronStr  = ($minutes == 0) ? '0 ' : '*/' . $minutes . ' ';

        // Hours
        $hours    = intdiv($refresh, 60);
        $cronStr .= ($hours == 0) ? '* ' : '*/' . $hours . ' ';
        $cronStr .= '* * ';
        
        //  Days of week
        $dow  = array();
        foreach (self::$_days as $d => $day) {
            if ($this->getConfiguration($day) == 1) {
                array_push($dow, $d);
            }
        }
        //$dow = array_uniq($dow);
        $cronStr .= implode(',', $dow);

        $cronStr = checkAndFixCron($cronStr);
        log::add('magictrigger', 'debug', $this->getHumanName() . ' cron string = ' . $cronStr);
        $this->setCache('cron', $cronStr);
    }

	
    /** 
     * Build the super condition
     * The super condition is build from the 'Monitoring' information tab
     * - holiday
     * - vacation
     * - day of week + hours if any
     * - condition specified by the user.
     * At the end, we store this information in cache, and we will use it when the 
     * trigger is raised
     */
    private function setSuperCondition() {

        // Build superCondition and store it in cache
        $condition = '';
        if ($this->getConfiguration('holiday') == 1) {
            $condition .= '(' . $this->getConfiguration('holidayInfo') . ' == 0)';
        }
        if ($this->getConfiguration('vacation') == 1) {
            if ($condition !== '') {
                $condition .= ' && ';
            }
            $condition .= '(' . $this->getConfiguration('vacationInfo') . ' == 0)';
        }
        if ($condition !== '') {
            $condition .= ' && ';
        }
        $daysCond = '';
        foreach (self::$_days as $d => $day) {
            if ($this->getConfiguration($day) == 1) {
                if ($daysCond !== '') {
                    $daysCond .= ' || ';
                }
                $daysCond .= '(#njour# == ' . $d;
                if ($this->getConfiguration($day . 'Full') == 0) {
                    $start = self::timeToJeeTime($this->getConfiguration($day . 'Start'));
                    $end   = self::timeToJeeTime($this->getConfiguration($day . 'End'));
                    $daysCond .= ' && #time# > ' . $start . ' && #time# < ' . $end;
                }
                $daysCond .= ')';
            }
        }
        $condition .= '(' . $daysCond . ')';
        if ($this->getConfiguration('condition')) {
           if ($condition !== '') {
               $condition .= ' && ';
           } 
           $condition .= '(' . $this->getConfiguration('condition') . ')';
        }

        log::add('magictrigger', 'info', $this->getHumanName() . ' superCondition = ' . $condition);
        // Store the superCondition in cache
        $this->setCache('superCondition', $condition);
    }

    /**
     * Function used to check the first tab (Equipement)
     * We check the value range, and that the mandatory fields are filled
     * It prevents errors at runtime of the object
     *
     * In case of errors, an exception is raised, that will be displayed in 
     * the upper layer of the configuration window
     */
    private function checkEquipement() {
        
        // interval cannot be less than period
        $interval = $this->getConfiguration('interval');
        $period   = $this->getConfiguration('period');
        if ($interval < $period) {
            throw new Exception(__('L\'intervalle ne peut pas etre inferieur a la periode.',__FILE__));
        }

        // timeOffset [0..120]
        $timeOffset = $this->getConfiguration('timeOffset');
        if ($timeOffset === '' || !is_numeric($timeOffset) || $timeOffset < 0 || $timeOffset > 120) {
            throw new Exception(__('Merci de specifier le decalage temporaire entre 0 et 120.',__FILE__));
        }
        // learning [0..12]
        $learning = $this->getConfiguration('learning');
        if ($learning === '' || !is_numeric($learning) || $learning < 0 || $learning > 12) {
            throw new Exception(__('Merci de specifier la periode d\'apprentissage entre 0 et 12.',__FILE__));
        }
    }

    /**
     * Function used to check the second tab (Monitoring)
     * We check the value range, and that the mandatory fields are filled
     * It prevents errors at runtime of the object
     *
     * In case of errors, an exception is raised, that will be displayed in 
     * the upper layer of the configuration window
     */
    private function checkMonitoring() { 

        // We check that at least one day is checked, and that either Full day, 
        // or Start and End time are filled.
        $nbDays = 0;
        foreach (self::$_days as $k => $day) {
            if ($this->getConfiguration($day) == 1) {
                $nbDays += 1;
                if ($this->getConfiguration($day . 'Full') == 0
                    && ($this->getConfiguration($day . 'Start') === ''
                    || $this->getConfiguration($day . 'End') === ''
                    || self::timeToJeeTime($this->getConfiguration($day . 'Start')) >= self::timeToJeeTime($this->getConfiguration($day . 'End')))) {
                    throw new Exception(__($day . ' est coche. Merci de renseigner soit le champ \'Toute la journee\' ou les champs \'debut\' et \'fin\' (l\'heure de fin doit etre apres l\'heure de debut).',__FILE__));
                }
            }
        }
        if ($nbDays == 0) {
            throw new Exception(__('Merci de selectionner au moins un jour de la semaine.',__FILE__));
        }

        // holiday checked -> holidayInfo is set
        if ($this->getConfiguration('holiday') == 1 
            && $this->getConfiguration('holidayInfo') === '') { 
            throw new Exception(__('Merci de renseigner la commande permettant de connaitre les jours feries.',__FILE__));
        }

        // vacation checked -> vacationInfo is set
        if ($this->getConfiguration('vacation') == 1
            && $this->getConfiguration('vacationInfo') === '') {
            throw new Exception(__('Merci de renseigner la commande permettant de connaitre les periodes de vacances scolaires.',__FILE__));
        }
    }

    /**
     * Function used to check the third tab (Configuration)
     * We check the value range, and that the mandatory fields are filled
     * It prevents errors at runtime of the object
     *
     * In case of errors, an exception is raised, that will be displayed in 
     * the upper layer of the configuration window
     */
    private function checkConfiguration() {

        // We should have at least one trigger and one action, condition is optional
        $triggers = $this->getConfiguration('triggers');
        if (!is_array($triggers) || count($triggers) == 0) {
            throw new Exception(__('Merci de renseigner au moins un declencheur.',__FILE__));
        }
        foreach ($triggers as $trigger) {
            if ($trigger['cmd'] === '') {
                throw new Exception(__('Le declencheur ne peut pas etre vide.',__FILE__));
            }
        }

        if ($this->getConfiguration('condition') !== '') {
            $result = jeedom::evaluateExpression($this->getConfiguration('condition'));
            if (!is_bool($result)) {
                throw new Exception(__('La condition specifiee est invalide. Le resultat doit etre une valeur booleenne.',__FILE__));
            }
        }

        $actions = $this->getConfiguration('actions');
        if (!is_array($actions) || count($actions) == 0) {
            throw new Exception(__('Merci de renseigner au moins une action et un seuil associe.',__FILE__));
        }
        foreach ($actions as $action) {
            $threshold = $action['threshold'];
            if ($threshold === '' || !is_numeric($threshold) || $threshold < 0 || $threshold > 100) {
                throw new Exception(__('Le seuil ne peut pas etre vide et doit etre compris entre 0 et 100.',__FILE__));
            }
            if ($action['cmd'] === '') {
                throw new Exception(__('L\'action ne peut pas etre vide.',__FILE__));
            }
        }
    }


    /** 
     * Function used to create all the commands in the eqLogic object
     * We create one command per day of week, which will contain the total
     * of events for the day
     */
	private function createCommands() {

        $order = 1;
		$cmd = $this->getCmd(null, 'refresh');
		if (!is_object($cmd)) {
			$cmd = new magictriggerCmd();
			$cmd->setLogicalId('refresh');
			$cmd->setIsVisible(1);
			$cmd->setName(__('Rafraichir', __FILE__));
            $cmd->setOrder($order++);
        }
		$cmd->setEqLogic_id($this->getId());
		$cmd->setType('action');
		$cmd->setSubType('other');
        $cmd->save();

        // Create the commands for the total per day
        foreach (self::$_days as $k => $day) {
		    $cmd = $this->getCmd(null, 'total' . $k);
		    if (!is_object($cmd)) {
			    $cmd = new magictriggerCmd();
			    $cmd->setLogicalId('total' . $k);
			    $cmd->setIsVisible(1);
			    $cmd->setName(__('total ' . ucfirst($day), __FILE__));
			    $cmd->setType('info');
			    $cmd->setSubType('numeric');
                $cmd->setTemplate('dashboard', 'magictrigger');
			    $cmd->setEqLogic_id($this->getId());
                $cmd->setOrder($order++);
			    $cmd->save();
			    $this->checkAndUpdateCmd('total' . $k, 0);
		    }
        }
    }
  

    /**
     * For each action defined, check if we are reaching one of the threshold
     * if this is the case, then we run the action
     */
    private function executeActions($_value) {

        $actions   = $this->getConfiguration('actions');
        $action    = NULL;
        $threshold = 0;
        foreach ($actions as $a) {
            if ($a['threshold'] <= $_value && $a['threshold'] > $threshold) {
                $action    = $a;
                $threshold = $a['threshold'];
            }
        }
        if (isset($action)) {
            try {
		    	$options = [];
                if (isset($action['options'])) {
                    $options = $action['options'];
                }
				//if ($options['enable'] === '1'){
                // Replace #threshold# with the threshold selected
                // #value# with the current value
                // #title# with the name of the equipment
			    foreach ($options as $key => $option) {
                    $opt = str_replace('#threshold#', $action['threshold'], $option);
					$opt = str_replace('#value#', $_value, $opt);
					$opt = str_replace('#title#', $this->getName(), $opt);
                    $options[$key] = $opt;
				}
                log::add('magictrigger', 'info', $this->getHumanName() 
                       . __(': Execution de la commande ' . $action['cmd'] . ' avec les options ' 
                       . json_encode($options), __FILE));
				scenarioExpression::createAndExec('action', $action['cmd'], $options);
				//}					
			} catch (Exception $e) {
                log::add('magictrigger', 'error', $this->getHumanName() 
                    . __('executeAction: failure in ', __FILE__) . $action['cmd'] 
                    . __('. Details : ', __FILE__) . $e->getMessage());
			}
        } else {
            log::add('magictrigger', 'debug', $this->getHumanName() 
                . __('Pas d\'action a executer pour la valeur ', __FILE__) . $_value);
        }
    }

    /**
     * triggerNotification
     * Used to process the incoming trigger, that has been raised through the
     * listener class
     */
    private function triggerNotification() {
	    //log::add('magictrigger', 'debug', $this.getHumanName() . ' triggerNotification');

        $superCondition = $this->getCache('superCondition', '');
        if ($superCondition === '') {
            // Cache has been cleared, rebuild the superCondition
            $this->populateCache();
            $superCondition = $this->getCache('superCondition', '');
        }
        if (jeedom::evaluateExpression($superCondition) == 0) {

            log::add('magictrigger', 'debug', $this->getHumanName() . ' triggerNotification = '
                .$superCondition . ' returns false');
            return;
        }
        log::add('magictrigger', 'debug', $this->getHumanName() . ' triggerNotification = ' 
                . $superCondition . ' returns true');

        // Insert a new event in the database
        $dow = date('w');
        $mte = magictriggerDB::create($this->getId(), $dow, intval(date('Hi')), 1);
        log::add('magictrigger', 'debug', $this->getHumanName() . ' (DB) MTE = ' 
            . $mte->getMagicId() . ', dow=' . $mte->getDayOfWeek() 
            . ', time=' . $mte->getTime());
        $mte->save();
        log::add('magictrigger', 'info', $this->getHumanName() 
            . __('triggerNotification = un nouvel evenement a ete ajoute', __FILE__));

        // @todo Reload the information or assume in will be used on next week ??? 
        //$this->getInformation();

        // Increase the total of events for the day
        $cmd = $this->getCmd(null, 'total' . $dow);
        if (!is_object($cmd)) {
            log:add('magictrigger', 'error', $this->getHumanName() 
                . __('Erreur jour: ' . $dow . ' n\'a pas de commande.', __FILE__));
        }
        $this->checkAndUpdateCmd('total' . $dow, ($cmd->execCmd() + 1));
    } 


    /**
     * cronNotification
     * Used to process incoming cron task, that have been raised depending on the 'interval'
     * parameter 
     */
    public function cronNotification($magic) {

	    //log::add('magictrigger', 'debug', $magic->getHumanName() . ' cronNotification');

        // check remaining days for learning
        $learningStartDate = $magic->getConfiguration('learningStartDate', 0);
        $learning          = $magic->getConfiguration('learning', 0);
        if ((time() - $learningStartDate) < ($learning * 604800)) {
            log::add('magictrigger', 'info', $magic->getHumanName() 
                . __(' la periode d\'apprentissage n\'est pas terminee', __FILE__));
            return;
        }

        // Get the information from the cache
        $stats = $magic->getCache('magicTriggerStats', array());
        if (count($stats) == 0) {
            // nothing to process, maybe in 'getInformation' mode
            return;
        }

        // load configuration parameters
        $interval = $magic->getConfiguration('interval');
        $period   = $magic->getConfiguration('period');
        $offset   = $magic->getConfiguration('timeOffset');
        
        // The tricks here is to manage the day change, in case (time + offset) goes to 
        // the next day.
        // 
        // We have loaded (day) and (day + 1) in cache, so we can manage the offset 
        // in case we go from (day) to (day + 1) without loading new data.
        // we just have to take care about the index to go to next day if the increaseTime 
        // is lower than the initial time
        // 
        $curTime       = date('Hi');
        $time          = self::increaseTime($curTime, $offset);
        $nextDay       = (($time < $curTime) ? 1 : 0);
        $nextDayOffset = 24 * intval(60 / $period);

        // Calculate the index for start and end, taking (day + 1) into account
        $index = ($nextDayOffset * $nextDay) + magictriggerEvent::getIndex($time, $period);
        log::add('magictrigger', 'info', $magic->getHumanName() . ': stat=' . $stats[$index]
            . '% (index=' . $index . ', interval=' . $interval . ', offset=' . $offset 
            . ', time+offset=' . $time . ')'); 

        $magic->executeActions($stats[$index]);
    }

    /**
     * Get the statistics for the day specified.
     */
    public function getStatistics($_today, $_period, $_interval) {

        // Load statistics from the database
        // Calculate the start and end date we are looking in the DB
        $stats       = array();
        $tomorrow    = (($_today + 1) % 7);
        $todayStr    = self::$_days[$_today];
        $tomorrowStr = self::$_days[$tomorrow];

        $todayStart    = self::timeToJeeTime($this->getConfiguration($todayStr . 'Start'));
        $todayEnd      = self::timeToJeeTime($this->getConfiguration($todayStr . 'End'));
        $tomorrowStart = self::timeToJeeTime($this->getConfiguration($tomorrowStr . 'Start'));
        $tomorrowEnd   = self::timeToJeeTime($this->getConfiguration($tomorrowStr . 'End'));
        return magictriggerEvent::getStats($this->getId(), $_today, $todayStart, $todayEnd,
                                           $tomorrow, $tomorrowStart, $tomorrowEnd, 
                                           $_period, $_interval);
    }


    /**
     * Get min threshold.
     */
    public function getMinThreshold() {
        $actions   = $this->getConfiguration('actions');
        $threshold = 100;
        foreach ($actions as $a) {
            if ($a['threshold'] < $threshold) {
                $threshold = $a['threshold'];
            }
        }
        return $threshold;
    } 

    /**
     * Get max threshold.
     */
    public function getMaxThreshold() {
        $actions   = $this->getConfiguration('actions');
        $threshold = 0; 
        foreach ($actions as $a) {
            if ($a['threshold'] > $threshold) {
                $threshold = $a['threshold'];
            }
        }
        return $threshold;
    }


    /**
     * Function used to populate the cache
     */
    private function populateCache() {

        // Build superCondition and store it in cache
        $this->setSuperCondition();

        // Set the cron
        $this->setCron();

        // rebuild the information
        $this->getInformation();
    }

    /**
     * Get total for the day, including the extra entries from tomorrow, between 0 and interval
     */
    private function getTotal($_today, $_interval) {

        // We need to compute extra entries for tomorrow, from 0 to 'interval'
        $todayStr    = self::$_days[$_today];
        $tomorrow    = (($_today + 1) % 7);
        $tomorrowStr = self::$_days[$tomorrow];
          
        $todayStart    = self::timeToJeeTime($this->getConfiguration($todayStr . 'Start'));
        $todayEnd      = self::timeToJeeTime($this->getConfiguration($todayStr . 'End'));
        $tomorrowStart = self::timeToJeeTime($this->getConfiguration($tomorrowStr . 'Start'));
        $tomorrowEnd   = self::timeToJeeTime($this->getConfiguration($tomorrowStr . 'End'));
        $todayTotal    = magictriggerDB::getTotalPerDow($this->getId(), $_today, $todayStart, $todayEnd);
        $tomorrowTotal = 0;
        if ($tomorrowStart < $_interval) {                   
            $tomorrowTotal = magictriggerDB::getTotalPerDow($this->getId(), $tomorrow,
                                                            $tomorrowStart, min($interval, $tomorrowEnd));
        }
        return ($todayTotal + $tomorrowTotal);
    }


    /**
     * Get the information from the database, and populate the different fields 
     */
    private function getInformation() {
		log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering getInformation');

        // Clear cache first
        $this->setCache('magicTriggerStats', array());

        $interval    = $this->getConfiguration('interval');
        $period      = $this->getConfiguration('period');
        $offset      = $this->getConfiguration('timeOffset');

        // holiday and vacation
        $holiday = false;
        if ($this->getConfiguration('holiday') == 1) {
            $holiday = jeedom::evaluateExpression($this->getConfiguration('holidayInfo'));
            if (!is_bool($holiday) && !is_numeric($holiday)) {
                log::add('magictrigger', 'error', 
                    __('La commande \'jour ferie\' est invalide', __FILE__));
                $holiday = false;
            } 
        }
        $vacation = false;
        if ($this->getConfiguration('vacation') == 1) {
            $vacation = jeedom::evaluateExpression($this->getConfiguration('vacationInfo'));
            if (!is_bool($vacation) && !is_numeric($vacation)) {
                log::add('magictrigger', 'error', 
                    __('La commande \'vacances\' est invalide', __FILE__));
                $vacation = false;
            } 
        }
        log::add('magictrigger', 'info', 'holiday=' . $holiday . ', vacation=' . $vacation);

        // Load the totals per day from the Database
        $dow = date('w');
        foreach (self::$_days as $today => $todayStr) {

            if ($today == $dow && ($holiday == true || $vacation == true)) { 
                $total = 0;
            } else {
                $total = $this->getTotal($today, $interval);
            }
            //log::add('magictrigger', 'debug', 'update total' . $today . ' to ' . $total);
            $this->checkAndUpdateCmd('total' . $today, $total);
        }

        // Load statistics from the database
        // Calculate the start and end date we are looking in the DB
        $stats         = array();
        $today         = date('w');
        $tomorrow      = (($today + 1) % 7);

        // Fetch statistics for today and tomorrow. Only store offset information for tomorrow
        if ($holiday == true || $vacation == true) { 
            $todayStats = array_fill(0, (24 * intval(60 / $period)), 0);
        } else {
            $todayStats = $this->getStatistics($today, $period, $interval);
        }
        $tomorrowStats = $this->getStatistics($tomorrow, $period, $interval); 
        $tomorrowStats = array_slice($tomorrowStats, 0, intval($offset / $period));

        // Merge both results into the array
        $stats = array_merge($todayStats, $tomorrowStats);
        
        log::add('magictrigger', 'debug', $this->getHumanName() . ' stats array size=' . count($stats));
        $this->setCache('magicTriggerStats', $stats);
    }

    /**
     * Remove the events that have been inserted before the retention
     * date
     */
    private function removeOldEvents() {
        $retention = $this->getConfiguration('retention');
        $after     = date("m/d/Y", mktime(0, 0, 0, date("m") - $retention, date("d"), date("y")));

        log::add('magictrigger', 'info', $this->getHumanName() 
            . __(': suppression des evenements anterieur a ', __FILE__) . $after);
       
        magictriggerDB::removeAllByIdTimestamp($this->getId(), strtotime($after));
    }


    /*************************************************************************

    /**
     * Function that is automatically called by the Listener class, when 
     * a monitored value has changed
     */
	public function triggerCallback($_option) {
        //log::add('magictrigger' , 'debug', 'triggerCallback');

        $magic = self::byId($_option['id']);
        if (is_object($magic) && $magic->getIsEnable() == 1) {
		    $magic->triggerNotification();
		}
	}

    /**
     * Function use by eqLogic class to refresh the data object
     * We reload:
     * - the total per dow of week
     * - the events for the day
     */
    public function refresh($_force = false) {

        $this->getInformation();
		$this->refreshWidget();
	}

    /**************************************************************************
     * Object management functions, used to validate, create, remove, and save
     * the object
     *************************************************************************/

    /**
     * Use to setup default values in the configuration tabs
     */
	public function preInsert() {
        log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering preInsert');

        $this->setConfiguration('period', 5);
        $this->setConfiguration('interval', 15);
        $this->setConfiguration('timeOffset', 30);
        $this->setConfiguration('learning', 4);
        $this->setConfiguration('remaining', '4s');
        $this->setConfiguration('remainingStartDate', time());
	}

	public function postInsert() {
        //log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering postInsert');

	}

	public function preSave() {
        log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering preSave');
	}

    /**
     * Use to create all the commands
     */
	public function postSave() {
        log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering postSave');

        $this->createCommands();
	}

    /** Used to validate all the configurations parameters
     * Check that all the mandatory parameters are set
     * Check that values are appropriate (ranges, syntax, ...)
     */
	public function preUpdate() {
        log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering preUpdate');

        $this->checkEquipement();
        $this->checkMonitoring();
        $this->checkConfiguration();
        
        // Set the Start end End value for each day depending on Full
        foreach (self::$_days as $d => $day) {
            if ($this->getConfiguration($day) == 1) { 
                if ($this->getConfiguration($day . 'Full') == 1) {
                    $this->setConfiguration($day . 'Start', '0:00');
                    $this->setConfiguration($day . 'End', '23:59');
                }
            } else {
               $this->setConfiguration($day . 'Start', '0:00');
               $this->setConfiguration($day . 'End', '0:00');
            }
        }
	}

    /**
     * Create and/or update the listeners, based on specified triggers
     * Create the 'superCondition', which is a combination of the condition specified
     * and the monitoring information (day of week, holiday and vacation)
     */
	public function postUpdate() {
        log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering postUpdate');

        // populate the cache
        $this->populateCache();

        // Register the listener !
        $this->addListener();
	}

    /**
     * Remove the listeners that have been created in postUpdate function
     */
	public function preRemove() {
        log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering preRemove');

        // Remove the listener, if any
        $this->removeListener();

        // We remove all the events associated to the ID from the DB
        magictriggerDB::removeAllbyId($this->getId());
	}

	public function postRemove() {
        //log::add('magictrigger', 'debug', $this->getHumanName() . ' Entering postRemove');

	}
}

class magictriggerCmd extends cmd {

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
        $magic = $this->getEqLogic(); 
		switch ($this->getLogicalId()) { 
			case 'refresh': 
                $magic->refresh();
                break;
        }
	}

    public function getStatistics($_options = array()) {

        $magic = $this->getEqLogic(); 
        $today = $_options['dow'];
        if ($today == -1) {
            $today = intval(str_replace('total', '', $this->getLogicalId()));
        } 
        $period = $_options['period'];
        if ($period == 0) {
            $period = $magic->getConfiguration('period');
        } 
        $interval = $_options['interval'];
        if ($interval == 0) {
            $interval   = $magic->getConfiguration('interval');
        } 
        $count      = 24 * intval(60 / $period);
        log::add('magictrigger', 'info', 'getStatistics on command ' . $this->getName() . ' (today='. $today . ', period=' . $period . ', interval=' . $interval . ')');
        $statistics = $magic->getStatistics($today, $period, $interval);
        $result     = array('dow' => $today, 'period' => $period, 
            'interval' => $interval, 'points' => array(),
            'minThreshold' => $magic->getMinThreshold(), 'maxThreshold' => $magic->getMaxThreshold());
        for ($i = 0; $i < $count; $i++) {
            array_push($result['points'], 
                       array('time'  => magictriggerEvent::getTime($i, $period),
                             'value' => $statistics[$i]));
        } 
        return $result;
    }

/**
    public function toHtml($_version = 'dashboard', $_options = '') {
                                $_version = jeedom::versionAlias($_version);
       $html = '';
       $replace = array(
            '#id#' => $this->getId(),
            '#name#' => $this->getName(),
            '#name_display#' => ($this->getDisplay('icon') != '') ? $this->getDisplay('icon') : $this->getName(),
            '#history#' => 'magictrigger-graph',
            '#hide_history#' => 'hidden',
            '#unite#' => $this->getUnite(),
            '#minValue#' => $this->getConfiguration('minValue', 0),
            '#maxValue#' => $this->getConfiguration('maxValue', 100),
            '#logicalId#' => $this->getLogicalId(),
            '#uid#' => 'cmd' . $this->getId() . eqLogic::UIDDELIMITER . mt_rand() . eqLogic::UIDDELIMITER,
            '#version#' => $_version,
            '#eqLogic_id#' => $this->getEqLogic_id(),
            '#generic_type#' => $this->getGeneric_type(),
            '#hide_name#' => '',
        );
        //$replace['<script>'] = str_replace('#uid#', $replace['#uid#'], "<script>\n$('.cmd[data-cmd_uid=#uid#] .magictrigger-graph').on('click', function() { bootbox.confirm('{{On va ouvrir une modale}}', function(result) {}); });");
        $replace['<script>'] = str_replace('#uid#', $replace['#uid#'], "<script>\n$('.cmd[data-cmd_uid=#uid#] .magictrigger-graph').on('click', function() { $('#md_modal').dialog({title: '{{Statistique}}'});\n$('#md_modal').load('index.php?v=d&plugin=magictrigger&modal=modal.magictrigger&cmd_id=' + $(this).closest('.cmd').attr('data-cmd_id')).dialog('open'); });");


        if ($this->getDisplay('showNameOn' . $_version, 1) == 0) {
            $replace['#hide_name#'] = 'hidden';
        }
        if ($this->getDisplay('showIconAndName' . $_version, 0) == 1) {
            $replace['#name_display#'] = $this->getDisplay('icon') . ' ' . $this->getName();
        }
        $template = $this->getWidgetTemplateCode($_version);
        if ($_options != '') {
            $options = jeedom::toHumanReadable($_options);
            $options = is_json($options, $options);
            if (is_array($options)) {
                foreach ($options as $key => $value) {
                    $replace['#' . $key . '#'] = $value;
                }
            }
        }
        if ($this->getType() == 'info') {
            $replace['#state#'] = '';
            $replace['#tendance#'] = '';
            if ($this->getEqLogic()->getIsEnable() == 0) {
                $template = getTemplate('core', $_version, 'cmd.error');
                $replace['#state#'] = 'N/A';
            } else {
                $replace['#state#'] = $this->execCmd();
                if (strpos($replace['#state#'], 'error::') !== false) {
                    $template = getTemplate('core', $_version, 'cmd.error');
                    $replace['#state#'] = str_replace('error::', '', $replace['#state#']);
                } else {
                    if ($this->getSubType() == 'numeric' && trim($replace['#state#']) === '') {
                        $replace['#state#'] = 0;
                    }
                }
            }

            $replace['#state#'] = str_replace(array("\'", "'","\n"), array("'", "\'",'<br/>'), $replace['#state#']);
            $replace['#collectDate#'] = $this->getCollectDate();
            $replace['#valueDate#'] = $this->getValueDate();
            $replace['#alertLevel#'] = $this->getCache('alertLevel', 'none');
            $parameters = $this->getDisplay('parameters');
            if (is_array($parameters)) {
                foreach ($parameters as $key => $value) {
                    $replace['#' . $key . '#'] = $value;
                }
            }
            return translate::exec(template_replace($replace, $template), 'core/template/widgets.html');
        } else {
            $template = getTemplate('core', $_version, 'cmd.error');
            $replace['#state#'] = 'N/A';
            return translate::exec(template_replace($replace, $template), 'core/template/widgets.html');
        }
    }
     */

}


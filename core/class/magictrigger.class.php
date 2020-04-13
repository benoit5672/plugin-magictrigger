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

class magictrigger extends eqLogic {

	/*     * *************************Attributs****************************** */
    public static $days = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 
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
                    log::add('magictrigger', 'debug', $magic->getHumanName() . ' isDue(' . $magic->getCache('cron') . ') = ' . $cron->isDue());
				    if ($cron->isDue()) {
				        try {
							$magic->cronNotification($magic);
						} catch (Exception $e) {
							log::add('magictrigger', 'error', __('Erreur pour ', __FILE__) . $magic->getHumanName() . ' : ' . $e->getMessage());
						}
					}
				} catch (Exception $e) {
					log::add('magictrigger', 'error', __('Expression cron non valide pour ', __FILE__) . $magic->getHumanName() . ' : ' . $magic->getCache('cron'));
				}
			}
		}
    }

    /**
     * Function executed every day by jeedom (plugin)
     */
     public static function cronDaily() {

        foreach (eqLogic::byType(__CLASS__, true) as $magic) {
            // @todo: remove entries based on the retention parameter
         
            // Refresh the data
            $magic->getInformation(); 
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

    public function postAjax() {
        //$this->getInformation();
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
		return listener::byClassAndFunction(__CLASS__, 'triggerCallback', array('id' => $this->getId()));
	}

	private function removeListener() {
		log::add('magictrigger', 'debug', 'Entering removeListener');

        $listener = $this->getListener();
		if (is_object($listener)) {
		   $listener->remove();
        }
	}

	private function addListener() {
		log::add('magictrigger', 'debug', 'Entering addListener');

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
     * Function used to build the cron arguments, based on the autorefresh configuration
     * parameters
     * set the result in cache
     */
    private function setCron() {

        // Minutes
        $refresh  = $this->getConfiguration('autorefresh');
        $minutes  = $refresh % 60;
        $cronStr  = ($minutes == 0) ? '0 ' : '*/' . $minutes . ' ';

        // Hours
        $hours    = intdiv($refresh, 60);
        $cronStr .= ($hours == 0) ? '* ' : '*/' . $hours . ' ';
        $cronStr .= '* * ';
        
        //  Days of week
        $dow  = '';
        foreach (magictrigger::$days as $d => $day) {
            if ($this->getConfiguration($day) == 1) {
                if ($dow !== '') {
                    $dow .= ',';
                }
                $dow .= $d;
            }
        }
        $cronStr .= $dow;

        $cronStr = checkAndFixCron($cronStr);
        log::add('magictrigger', 'debug', 'cron string = ' . $cronStr);
        $this->setCache('cron', $cronStr);
    }

	
    /** 
     * Build the super condition
     * The super condition is build from the 'Monitoring' information tab
     * - holiday
     * - vacation
     * - day of week + hours if any
     * - condition specified by the user.
     * At the end, we store this information in cache, and we will use it when the trigger is raised
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
        foreach (magictrigger::$days as $d => $day) {
            if ($this->getConfiguration($day) == 1) {
                if ($daysCond !== '') {
                    $daysCond .= ' || ';
                }
                $daysCond .= '(#njour# == ' . $d;
                if ($this->getConfiguration($day . 'Full') == 0) {
                    $start = intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'Start')), '0'));
                    $end   = intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'End')), '0'));
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

        log::add('magictrigger', 'debug', 'superCondition = ' . $condition);
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
        
        // Interval
        /**
        if ($this->getConfiguration('interval') !== '') {
		    try {
                new Cron\CronExpression($this->getConfiguration('interval'), 
                                        new Cron\FieldFactory);
			} catch (Exception $e) {
                throw new Exception(__('Merci d\'utiliser un format de cron valide.',__FILE__));
			}
        }
         */

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

        // We check that at least one day is checked, and that either Full day, or Start and End time are 
        // filled.
        $nbDays = 0;
        foreach (magictrigger::$days as $k => $day) {
            if ($this->getConfiguration($day) == 1) {
                $nbDays += 1;
                if ($this->getConfiguration($day . 'Full') == 0
                    && ($this->getConfiguration($day . 'Start') === ''
                    || $this->getConfiguration($day . 'End') === ''
                    || intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'Start')), '0')) >= intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'End')), '0')))) {
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

        foreach (magictrigger::$days as $k => $day) {
		    $cmd = $this->getCmd(null, 'total' . $k);
		    if (!is_object($cmd)) {
			    $cmd = new magictriggerCmd();
			    $cmd->setLogicalId('total' . $k);
			    $cmd->setIsVisible(1);
			    $cmd->setName(__('total ' . ucfirst($day), __FILE__));
			    $cmd->setType('info');
			    $cmd->setSubType('numeric');
			    $cmd->setEqLogic_id($this->getId());
                $cmd->setOrder($order++);
			    $cmd->save();
			    $this->checkAndUpdateCmd('total' . $k, 0);
		    }
        }
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
     * triggerNotification
     * Used to process the incoming trigger, that has been raised through the
     * listener class
     */
    public function triggerNotification() {
	    //log::add('magictrigger', 'debug', 'triggerNotification(' . $this->getHumanName() . ')');

        $superCondition = $this->getCache('superCondition', '');
        if ($superCondition === '') {
            // Cache has been cleared, rebuild the superCondition
            $this->populateCache();
            $superCondition = $this->getCache('superCondition', '');
        }
        if (jeedom::evaluateExpression($superCondition) == 0) {

            log::add('magictrigger', 'debug', 'triggerNotification(' . $this->getHumanName() . ') = '
                .$superCondition . ' returns false');
            return;
        }
        log::add('magictrigger', 'debug', 'triggerNotification(' . $this->getHumanName() . ') = ' 
                . $superCondition . ' returns true');

        // Insert a new event in the database
        $dow = date('w');
        $mte = new magictriggerEvent(array('magicId' => $this->getId(), 'dow' => $dow, 
                                            'time' => intval(date('Hi'))));
        log::add('magictrigger', 'debug', 'MTE = ' . $mte->getMagicId() . ', dow=' 
                    . $mte->getDayOfWeek() . ', time=' . $mte->getTime());
        $mte->save();

        // @todo: Update the list in memory, increase the total of event for the day
        $cmd = $this->getCmd(null, 'total' . $dow);
        if (!is_object($cmd)) {
            log:add('magictrigger', 'error', __('Erreur jour: ' . $dow . ' n\'a pas de commande.', __FILE__));
        }
        $this->checkAndUpdateCmd('total' . $dow, ($cmd->execCmd() + 1));
    } 


    /**
     * cronNotification
     * Used to process incoming cron task, that have been raised depending on the 'interval'
     * parameter 
     */
    public function cronNotification($magic) {

	    log::add('magictrigger', 'debug', 'cronNotification(' . $magic->getHumanName() . ')');

        // @todo: check remaining days for learning
        // @todo: check actionCondition 

        // Calculate the start and end date we are looking in the DB
        $start = time() + $magic->getConfiguration('timeOffset'); 
        $end   = $start + $magic->getConfiguration('interval');

        // @todo fetch from DB
        //
        // @todo: algorithm to evaluate the probability of the event to occur
        // - ppi = count(day) / 24 * (60/interval)
        // - ((count(interval+offset) / count(day)) / ppi) * 100
    }


    /**
     * Function used to populate the cache
     */
    public function populateCache() {

        // Build superCondition and store it in cache
        $this->setSuperCondition();

        // Set the cron
        $this->setCron();
    }


    /**
     * Get the information from the database, and populate the different fields
     */
    public function getInformation() {
		log::add('magictrigger', 'debug', 'Entering getInformation for ' . $this->getHumanName());

        // Load the totals per day from the Database
        $totals = magictriggerEvent::byIdTotalPerDow($this->getId());
        //log::add('magictrigger', 'debug', '$totals -> '. print_r($totals));
        if (!is_array($totals)) {
            log::add('magictrigger', 'error', __('getInformation: Erreur database requete 1', __FILE__));
            return;
        }

        // Update the total for each day of week, but first, reset all the values to 0
        // @todo
        //foreach (magictrigger::$days as $k => $day) {
        //}
        
        foreach ($totals as $total) {
            $dow = $total->getDayOfWeek();
            $cmd = $this->getCmd(null, 'total' . $dow);
            if (!is_object($cmd)) {
                continue;
            }
            log::add('magictrigger', 'debug', 'update total' . $dow . ' to ' . $total->getCount());
            $this->checkAndUpdateCmd('total' . $dow, $total->getCount());
        }

        // @toto: load the events for the current day
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
        log::add('magictrigger', 'debug', 'Entering preInsert');

        $this->setConfiguration('autorefresh', 5);
        $this->setConfiguration('interval', 15);
        $this->setConfiguration('timeOffset', 30);
        $this->setConfiguration('learning', 4);
        $this->setConfiguration('remaining', 4);
	}

	public function postInsert() {
        log::add('magictrigger', 'debug', 'Entering postInsert');

	}

	public function preSave() {
        log::add('magictrigger', 'debug', 'Entering preSave');
	}

    /**
     * Use to create all the commands
     */
	public function postSave() {
        log::add('magictrigger', 'debug', 'Entering postSave');

        $this->createCommands();
        $this->getInformation();
	}

    /** Used to validate all the configurations parameters
     * Check that all the mandatory parameters are set
     * Check that values are appropriate (ranges, syntax, ...(
     */
	public function preUpdate() {
        log::add('magictrigger', 'debug', 'Entering preUpdate');

        $this->checkEquipement();
        $this->checkMonitoring();
        $this->checkConfiguration();
	}

    /**
     * Create and/or update the listeners, based on specified triggers
     * Create the 'superCondition', which is a combination of the condition specified
     * and the monitoring information (day of week, holiday and vacation)
     */
	public function postUpdate() {
        log::add('magictrigger', 'debug', 'Entering postUpdate');
    
        // populate the cache
        $this->populateCache();

        // Register the listener !
        $this->addListener();
	}

    /**
     * Remove the listeners that have been created in postUpdate function
     */
	public function preRemove() {
        log::add('magictrigger', 'debug', 'Entering preRemove');

        // Remove the listener, if any
        $this->removeListener();

        // We remove all the events associated to the ID from the DB
        magictriggerEvent::removeAllbyId($this->getId());
	}

	public function postRemove() {

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
}


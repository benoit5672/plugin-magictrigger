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

class magictrigger extends eqLogic {

	/*     * *************************Attributs****************************** */
	
	/*     * ***********************Methode static*************************** */
	

    /*************************************************************************
     * Cron functions, called automatically by Jeedom Core 
     * We only implement cronDaily() functions
     *************************************************************************/

    /**
     * Fonction exécutée automatiquement tous les jours par Jeedom
     *
     public static function cronDaily() {
         // @todo: remove entries based on the retention parameter
         //
         // @todo: count the number of trigger in the period of the day
         // and keep it in cache
     }
     */

    

    /**
     * Function that is automatically called by the Listener class, when 
     * a monitored value has changed
     */
	public static function trigger($_option) {
        log::add('magictrigger' , 'debug', 'trigger');

		$eqLogic = self::byId($_option['id']);
		if (is_object($eqLogic) && $eqLogic->getIsEnable() == 1) {
			$eqLogic->triggerIndication();
		}
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
		return listener::byClassAndFunction(__CLASS__, 'trigger', array('id' => $this->getId()));
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
			$listener->setFunction('trigger');
			$listener->setOption(array('id' => $this->getId()));
		}
		$listener->emptyEvent();

        $triggers = $this->getConfiguration('triggers');
        foreach ($triggers as $trigger) {
            $cmd = cmd::byId($trigger['cmd']);
            if (!is_object($cmd)) {
                continue;
            }
			$listener->addEvent($cmd->getId());
        }
        $listener->save();
    }


    /**
     * Function used to build the cron arguments, based on the interval configuration
     * parameters
     * It also install the cron in jeedom
     */
    private function addCron() {

        // Minutes
        $interval = $this->getConfiguration('interval');
        $minutes  = $interval % 60;
        $cronStr  = ($minutes == 0) ? '0 ' : '*/' . $minutes . ' ';

        // Hours
        $hours    = intdiv($interval, 60);
        $cronStr .= ($hours == 0) ? '* ' : '*/' . $hours . ' ';
        $cronStr .= '* * ';
        
        //  Days of week
        $days = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday',
                        4 => 'thursday', 5 => 'friday', 6 => 'saturday');
        $dow  = '';
        foreach ($days as $d => $day) {
            if ($this->getConfiguration($day) == 1) {
                if ($dow !== '') {
                    $dow .= ',';
                }
                $dow .= $d;
            }
        }
        $cronStr .= $dow;

        log::add('magictrigger', 'debug', 'cron string = ' . $cronStr);

		try {
            new Cron\CronExpression($cronStr, new Cron\FieldFactory);
        } catch (Exception $e) {
            log:add('magictrigger', 'warning', 'Error creating cron');
		}
    }

    /**
     * Remove the cron installed for this instance
     */
    private function removeCron() {
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
        $days = array(0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 
                        4 => 'thursday', 5 => 'friday', 6 => 'saturday');
        if ($condition !== '') {
            $condition .= ' && ';
        }
        $daysCond = '';
        foreach ($days as $d => $day) {
            if ($this->getConfiguration($day) == 1) {
                if ($daysCond !== '') {
                    $daysCond .= ' || ';
                }
                $daysCond .= '(#njour# == ' . $d;
                if ($this->getConfiguration($day . 'Full') == 0) {
                    $begin = intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'Begin')), '0'));
                    $end   = intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'End')), '0'));
                    $daysCond .= ' && #time# > ' . $begin . ' && #time# < ' . $end;
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
        if ($this->getConfiguration('interval') !== '') {
		    try {
                new Cron\CronExpression($this->getConfiguration('autorefresh'), 
                                        new Cron\FieldFactory);
			} catch (Exception $e) {
                throw new Exception(__('Merci d\'utiliser un format de cron valide.',__FILE__));
			}
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

        // We check that at least one day is checked, and that either Full day, or Begin and End time are 
        // filled.
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $nbDays = 0;
        foreach ($days as $day) {
            if ($this->getConfiguration($day) == 1) {
                $nbDays += 1;
                if ($this->getConfiguration($day . 'Full') == 0
                    && ($this->getConfiguration($day . 'Begin') === ''
                    || $this->getConfiguration($day . 'End') === ''
                    || intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'Begin')), '0')) >= intval(ltrim(str_replace(':', '', $this->getConfiguration($day . 'End')), '0')))) {
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

    /*************************************************************************

    /**
     * triggerIndication
     * Used to process the incoming trigger, that has been raised through the
     * listener class
     */
    public function triggerIndication() {
	    log::add('magictrigger', 'debug', 'triggerIndication(' . $this->getName() . ')');

        $superCondition = $this->getCache('superCondition', '');
        if (jeedom::evaluateExpression($superCondition) == 0) {

            log::add('magictrigger', 'debug', $superCondition . ' returns false');
            return;
        }

        // Insert a plot into the database
        // @todo
        // addPlot(time(), $this->getId(), date('w'), intval(ltrim(date('Hi')), '0'));
        //
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

        $this->setConfiguration('autorefresh', '*/5 * * * *');
        $this->setConfiguration('timeOffset', 30);
        $this->setConfiguration('learning', 4);
	}

    /**
     * Use to create all the commands
     */
	public function postInsert() {
        log::add('magictrigger', 'debug', 'Entering postInsert');

        // @todo: create commands
	}

	public function preSave() {
        log::add('magictrigger', 'debug', 'Entering preSave');
	}

	public function postSave() {
        log::add('magictrigger', 'debug', 'Entering postSave');

        // @todo: See alarm plugin
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
    
        // Set the cron
        $this->addCron();

        // Build superCondition and store it in cache
        $this->setSuperCondition();

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

        // Remove the cron
        $this->removeCron();
	}

	public function postRemove() {

	}
}

class magictriggerCmd extends cmd {

	public function dontRemoveCmd() {
		return true;
	}

	public function execute($_options = array()) {
	}
}

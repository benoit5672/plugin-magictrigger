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

try {
    require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
    require_once dirname(__FILE__) . '/../class/magictriggerEvent.class.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

    ajax::init();

    if (init('action') == 'removeAllbyId') {
        
		$cmd = cmd::byId(init('id'));
		if (!is_object($cmd)) {
			throw new Exception(__('Commande ID inconnu : ', __FILE__) . init('id'));
		}
		magictriggerEvent::removeAllbyId($cmd);
		ajax::success();
	}

    if (init('action') == 'getStatistics') {
        log::add('magictrigger', 'info', 'AJAX : getStatistics');
		$cmd = cmd::byId(init('id'));
		if (!is_object($cmd)) {
			throw new Exception(__('Commande ID inconnu : ', __FILE__) . init('id'));
		}
        log::add('magictrigger', 'info', 'AJAX : getStatistics 2');
        $options['dow'] = init('dow');
        $options['period'] = init('period');
        $options['interval'] = init('interval');
        log::add('magictrigger', 'info', 'AJAX : getStatistics 3');
		ajax::success($cmd->getStatistics($options));
	}

    throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
    /*     * *********Catch exeption*************** */
} catch (Exception $e) {
    ajax::error(displayException($e), $e->getCode());
}

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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function magictrigger_install() {
    // Create the table where the triggers are stored
    $sql = 'CREATE TABLE IF NOT EXISTS `magictriggerDB` ('
           . '`added` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,'
           . '`magicId` INT(11) NOT NULL,'
           . '`dow` TINYINT(1) UNSIGNED NOT NULL,'
           . '`time` MEDIUMINT(4) UNSIGNED NOT NULL'
           . ') ENGINE=InnoDB DEFAULT CHARSET=utf8;';
    DB::Prepare($sql, array(), DB::FETCH_TYPE_ROW);

}

function magictrigger_update() {
    // @todo: capability to empty the complete table
}


function magictrigger_remove() {
    // drop the table where the triggers are stored
    DB::Prepare('DROP TABLE IF EXISTS `magictriggerDB`;', array(), DB::FETCH_TYPE_ROW);
}

?>

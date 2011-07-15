<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This is the helpdesk native plugin init script.
 * This will setup everything necessary for the plugin to function.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;
$path = "$CFG->dirroot/blocks/helpdesk/plugins/native";

// All the files the plugin needs should be here.
require_once("$path/lib_native.php");
require_once("$path/new_ticket_form.php");
require_once("$path/search_form.php");
require_once("$path/tag_ticket_form.php");
require_once("$path/change_overview_form.php");
require_once("$path/update_ticket_form.php");
require_once("$path/helpdesk_ticket_native.php");
require_once("$path/helpdesk_native.php");
?>

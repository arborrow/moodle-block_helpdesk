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
 * This is the moodle native plugin for the helpdesk. This plugin is a basic
 * helpdesk that is built into the helpdesk block. This is initially the only
 * option and will become the default option.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// Update Defines.
// stored in the ticket table
define('HELPDESK_NATIVE_STATUS_NEW', 'new');
define('HELPDESK_NATIVE_STATUS_CLOSED', 'closed');
define('HELPDESK_NATIVE_STATUS_INPROGRESS', 'workinprogress');

// stored in the update table
define('HELPDESK_NATIVE_UPDATE_COMMENT', 'comment');
define('HELPDESK_NATIVE_UPDATE_ASSIGN', 'assign');
define('HELPDESK_NATIVE_UPDATE_UNASSIGN', 'unassign');
define('HELPDESK_NATIVE_UPDATE_TAG', 'tag');
define('HELPDESK_NATIVE_UPDATE_UNTAG', 'untag');
define('HELPDESK_NATIVE_UPDATE_STATUS', 'statuschanged');
define('HELPDESK_NATIVE_UPDATE_DETAILS', 'detailschanged');

// Relation defines.
// relations that group tickets together.
define('HELPDESK_NATIVE_REL_ALL', 'alltickets');
define('HELPDESK_NATIVE_REL_REPORTEDBY', 'reportedby');
define('HELPDESK_NATIVE_REL_ASSIGNEDTO', 'assignedto');
define('HELPDESK_NATIVE_REL_NEW', 'newtickets');
define('HELPDESK_NATIVE_REL_CLOSED', 'closedtickets');
define('HELPDESK_NATIVE_REL_UNASSIGNED', 'unassignedtickets');

// Functions

/**
 * Gets all ticket related statuses.
 *
 * @return array
 */
function get_ticket_statuses() {
    $status = get_records('helpdesk_status', '', '', 'name ASC');
    return $status;
}

/**
 * Get status string. We may not have a ticket so we need this.
 *
 * @return string
 */
function get_status_string($status) {
    $hd = helpdesk::get_helpdesk();
    $instance = $hd->new_ticket();
    return $instance->get_status_string($status);
}

?>

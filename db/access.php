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
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");

// Sometimes this files gets included more than once on install.
// Lets only define if it isn't defined already.
if (!defined('HELPDESK_CAP_ASK')) {
    define('HELPDESK_CAP_ASK', 'block/helpdesk:ask');
}
if (!defined('HELPDESK_CAP_ANSWER')) {
    define('HELPDESK_CAP_ANSWER', 'block/helpdesk:answer');
}

$block_helpdesk_capabilities = array (
    HELPDESK_CAP_ASK => array (
        'riskbitmask'   => RISK_SPAM,
        'captype'       => 'write',
        'contextlevel'  => CONTEXT_SYSTEM,
        'legecy'        => array (
            'guest'             => CAP_ALLOW,
            'student'           => CAP_ALLOW,
            'teacher'           => CAP_ALLOW,
            'editingteacher'    => CAP_ALLOW,
            'coursecreator'     => CAP_ALLOW,
            'admin'             => CAP_ALLOW
        )
    ),
    HELPDESK_CAP_ANSWER => array (
        'riskbitmask'   => RISK_PERSONAL + RISK_SPAM,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'legecy' => array (
            'admin' => CAP_ALLOW,
        )
    )
);
?>

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
 * This is a preferences script. This allows the user to change settings that 
 * may alter how the helpdesk is viewed.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");
global $CFG;
require_once("$CFG->libdir/formslib.php");
require_once("$CFG->dirroot/blocks/helpdesk/lib.php");

class helpdesk_pref_form extends moodleform {
    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $update_prefs = array();
        $mform->addElement('header', 'update_prefs_fieldset', get_string('updatepreferences',
                           'block_helpdesk'));
        $mform->closeHeaderBefore('save');
        $updateprefstr  = get_string('resetonlogout', 'block_helpdesk');
        $detailstr      = get_string('showdetailedupdates', 'block_helpdesk');
        $detailhelp     = helpdesk_simple_helpbutton($detailstr, 'detailedupdates');
        $systemstr      = get_string('showsystemupdates', 'block_helpdesk');
        $systemhelp     = helpdesk_simple_helpbutton($systemstr, 'systemupdates');
        $mform->addElement('html', "<p>$updateprefstr</p>");
        $mform->addElement('advcheckbox', 'showdetailedupdates', "$detailstr $detailhelp", '',
                            array('group' => 1), array(0, 1));
        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
            $mform->addElement('advcheckbox', 'showsystemupdates', "$systemstr $systemhelp", '',
                               array('group' => 2), array(0,1));
        }
        $mform->addElement('submit', 'save', get_string('savepreferences', 'block_helpdesk'));
    }
}
?>

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
 * New ticket form which extends a standard moodleform.
 * This form is used to create new tickets.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");
global $CFG;
require_once("$CFG->libdir/formslib.php");
class new_ticket_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $mform->addElement('header', 'frm', get_string('newticketform', 'block_helpdesk'));
        $mform->addElement('text', 'summary', get_string('summary', 'block_helpdesk'));
        $mform->addRule('summary', null, 'required', 'server');

        $htmleditorparams = array (
            'rows'              => 30,
            'cols'              => 75,
            );
        $mform->addElement('htmleditor', 'detail', get_string('detail', 'block_helpdesk'), $htmleditorparams);
        $mform->setType('detail', PARAM_RAW);
        $mform->addRule('detail', null, 'required', 'server');
        $mform->addElement('submit', 'submitbutton', get_string('submitticket', 'block_helpdesk'));
    }

    function validation($data) {
        // At some point we could do custom validation, but moodleform defaults 
        // do just fine.
        return array();
    }

    /**
     * This is a workaround to allow us to add hidden values to a form. Forms 
     * appears to butcher moodle_urls or url strings with gets as an action.
     *
     * @param string    $name is the name of the hidden element.
     * @param string    $value is the value of th hidden element.
     * @return bool
     */
    function addHidden($name, $value) {
        $mform =& $this->_form;
        $mform->addElement('hidden', $name, $value);
    }
}
?>

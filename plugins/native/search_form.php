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
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");
global $CFG;
require_once("$CFG->libdir/formslib.php");
class search_form extends moodleform {

    function definition() {
        global $CFG;

        $mform =& $this->_form;

        $searchstr = get_string('search');

        $help = helpdesk_simple_helpbutton($searchstr, 'search');
        $searchphrase = get_string('searchphrase', 'block_helpdesk');

        $mform->addElement('header', 'frm', get_string('search'));
        $mform->addElement('text', 'searchstring', $searchphrase . $help);
        $mform->addRule('searchstring', null, 'required', 'server');
        $mform->addElement('submit', 'submitbutton', get_string('search'));
    }

    function validation() {
        // Add something at some point.
    }
}
?>

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
 * Update form. This handles updates to a ticket, not updating the ticket 
 * itself. Extends moodleform.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CFG;

class change_overview_form extends moodleform {

    private $ticket;

    function change_overview_form($action=null, $customdata=null, $method='post',
                                  $target='', $attributes=null, $editable=true,
                                  $ticket=null) {
        $this->ticket = $ticket;
        parent::moodleform($action, $customdata, $method, $target, $attributes,
                            $editable);
    }
    function definition() {
        $mform =& $this->_form;

        $mform->addElement('header', 'frm', get_string('updateticketoverview', 'block_helpdesk'));
        $userparams = array('readonly' => 'readonly', 'disabled' => 'disabled');
        $mform->addElement('text', 'username', get_string('submittedby', 'block_helpdesk'), $userparams);
        $mform->addElement('hidden', 'userid', '0');
        $mform->addElement('text', 'summary', get_string('summary', 'block_helpdesk'));
        $mform->addRule('summary', null, 'required', 'server');

        $htmleditorparams = array (
            'rows'              => 10,
            'cols'              => 75,
            );
        $mform->addElement('htmleditor', 'detail', get_string('detail', 'block_helpdesk'), $htmleditorparams);
        $mform->setType('detail', PARAM_RAW);
        $mform->addRule('detail', null, 'required', 'server');

        // New Status Code
        $statuses = get_ticket_statuses();
        $statuslist = array();

        $currentstatus = $this->ticket->get_status();
        $statuslist[$currentstatus->id] = get_status_string($currentstatus);
        foreach($statuses as $status) {
            if ($status->id != $currentstatus->id) {
                $statuslist[$status->id] = get_status_string($status);
            }
        }

        $mform->addElement('select', 'status', get_string('updatestatus', 'block_helpdesk'),
                           $statuslist);
        $this->set_current_status($this->ticket->get_status());

        // New Status Code _END_

        $mform->closeHeaderBefore('msgheader');
        $mform->addElement('header', 'msgheader', get_string('extrainformation', 'block_helpdesk'));
        $mform->addElement('htmleditor', 'msg', get_string('updatemessage', 'block_helpdesk'));
        $mform->setType('msg', PARAM_RAW);
        $mform->addRule('msg', null, 'required', 'server');
        $mform->closeHeaderBefore('submitbutton');
        $mform->addElement('submit', 'submitbutton', get_string('savequestion', 'block_helpdesk'));
    }

    function set_current_status($status) {
        $mform =& $this->_form;
        if (!is_object($status)) {
            return false;
        }
        $string = get_status_string($status);
        $mform->setDefault('status', 'New');
        return true;
    }

    function validation($data) {
        // Maybe at some point. Defaults work well already.
        return array();
    }

}
?>

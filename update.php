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
 * This script handles the updating of tickets by managing the UI and entry 
 * level functions for the task.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');

// We are also Helpdesk, so we shall also become a helpdesk.
require_once("$CFG->dirroot/blocks/helpdesk/lib.php");

require_login(0, false);

global $CFG;

$id = required_param('id', PARAM_INT);
$baseurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
$url = clone $baseurl;
$url->param('id', $id);
$nav = array (
    array (
        'name' => get_string('helpdesk', 'block_helpdesk'),
        'link' => $baseurl->out()
          ),
    array (
        'name' => get_string('ticketview', 'block_helpdesk'),
        'link' => $url->out()
    ),
    array (
        'name' => get_string('updateticket', 'block_helpdesk')
        )
    );

$title = get_string('helpdeskupdateticket', 'block_helpdesk');
helpdesk_print_header(build_navigation($nav), $title);
print_heading(get_string('updateticket', 'block_helpdesk'));

$hd = helpdesk::get_helpdesk();

$ticket = $hd->get_ticket($id);
if (!$ticket) {
    error(get_string('invalidticketid', 'block_helpdesk'));
}

$form = $hd->update_ticket_form($ticket);

if ( $form->is_submitted() and ($data = $form->get_data())) {
        $data->type = HELPDESK_UPDATE_TYPE_USER;
        if($ticket->add_update($data)) {
            $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
            $url->param('id', $id);
            $url = $url->out();
            redirect($url, get_string('updateadded', 'block_helpdesk'));
        } else {
            error(get_string('cannotaddupdate', 'block_helpdesk'));
        }
}
$form->display();
$hd->display_ticket($ticket, true);

print_footer();
?>

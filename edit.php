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
 * @author      Joanthan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
global $CFG;

require_once("$CFG->dirroot/blocks/helpdesk/lib.php");

require_login(0, false);

$id = required_param('id', PARAM_INT);
$newuser = optional_param('newuser', null, PARAM_INT);
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
        'name' => get_string('updateticketoverview', 'block_helpdesk')
        )
    );

$title = get_string('helpdeskeditticket', 'block_helpdesk');
helpdesk_print_header(build_navigation($nav), $title);
print_heading(get_string('updateticketoverview', 'block_helpdesk'));

$hd = helpdesk::get_helpdesk();

helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);

$ticket = $hd->get_ticket($id);
if (!$ticket) {
    error(get_string('invalidticketid', 'block_helpdesk'));
}

if ($newuser != null ) {
    $ticket->set_userid($newuser);
    notify(get_string('newuserselected', 'block_helpdesk') . "<br />" .
           get_string('changedusernotice', 'block_helpdesk'));
}

$form = $hd->change_overview_form($ticket);

if ( $form->is_submitted() and ($data = $form->get_data())) {
    $ticket->set_summary($data->summary);
    $ticket->set_detail($data->detail);
    $ticket->set_status($data->status);
    $ticket->set_userid($data->userid);
    if (!$ticket->store_edit($data->msg)) {
        error(get_string('cannotaddupdate', 'block_helpdesk'));
    }
    $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
    $url->param('id', $id);
    $url = $url->out();
    redirect($url, get_string('ticketedited', 'block_helpdesk'));
}

$form->display();

print_footer();

?>

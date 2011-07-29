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
 * This is the view script. It handles the UI and entry level function calls for 
 * displaying a respective ticket. If no parameters are passed through post or 
 * get, it will display a ticket listing for whatever user is logged on.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We are moodle, so we shall become moodle.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

// We are also Helpdesk, so we shall also become a helpdesk.
require_once("$CFG->dirroot/blocks/helpdesk/lib.php");

require_login(0, false);

$id         = optional_param('id', null, PARAM_INT);
$uid        = optional_param('uid', null, PARAM_INT);
$rel        = optional_param('rel', null, PARAM_ALPHA);
$page       = optional_param('page', null, PARAM_INT);
$count      = optional_param('count', null, PARAM_INT);

$url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
$PAGE->set_url($url);
$nav = array(array (
    'name' => get_string('helpdesk', 'block_helpdesk'),
    'link' => $url->out()
));
//$heading = get_string('helpdesk', 'block_helpdesk');
if (isset($id)) {
    $nav[] = array('name' => get_string('ticketviewer', 'block_helpdesk'), 'link'=>null);
//    $heading = get_string('ticketviewer', 'block_helpdesk');
}

$title = get_string('helpdeskticketviewer', 'block_helpdesk');

//helpdesk_print_header(build_navigation($nav), $title);
//print_heading($heading);
helpdesk_print_header($nav, $title);

// Let's construct our helpdesk.
$hd = helpdesk::get_helpdesk();

// If we have a ticket with $id, let's get right to displaying it.
if (!empty($id)) {
    // Display specific ticket.
    $ticket = $hd->get_ticket($id);
    if (!$ticket) {
        error(get_string('ticketiddoesnotexist','block_helpdesk'));
    }
    $hd->display_ticket($ticket);
} else {
    // Otherwise, we want to display a listing of tickets.
    // At this point, we don't have a specific ticket. However we may know what 
    // the user wants to see a list of.
    if (empty($count)) {
        $count = 10;
    }
    if (empty($page)) {
        $page = 0;
    }

    // We need to make this list based on capability.
    $cap = helpdesk_is_capable();
    $options = $hd->get_ticket_relations($cap);
    if ($options == false) {
        error(get_string('nocapabilities', 'block_helpdesk'));
    }

    // We have to set some defaults if optional params are empty.
    if (!$rel) {
        $rel = $hd->get_default_relation($cap);
    }

    // At this point we have an $options with all the available ticket relation 
    // views available for the user's capability. We may want to write 
    // a function to handle this automatically incase we want these options to 
    // be dynamic. So we must view the options to the user, except for the 
    // current one. (which is already stored in $rel)

    // We want to have links for all relations except for the current one.

    // Let's use a table!
    $str = get_string('relations', 'block_helpdesk');
    $relhelp = helpdesk_simple_helpbutton($str, 'relations');
    $table = new stdClass;
    $table->width = '95%';
    $table->head = array(get_string('changerelation', 'block_helpdesk') . $relhelp);
    $table->data = array();

    foreach($options as $option) {
        if ($option == $rel) {
            $table->data[] = array($hd->get_relation_string($option));
        } else {
            $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
            $url->param('rel', $option);
            $url = $url->out();
            $table->data[] = array("<a href=\"$url\">" . get_string($option, 'block_helpdesk') . '</a>');
        }
    }

    echo "<div id=\"ticketlistoptions\">
            <div class=\"left2div\">";
    $search_form = $hd->search_form();
    $search_form->display();
    echo "</div>";

    echo "<div class=\"right2div\">";
    print_table($table);
    echo "</div></div>";

    if (empty($count)) {
        $count = 10;
    }

    // If your not looking for a specific user's tickets. You don't need to be 
    // an Answerer (if $uid is empty) otherwise we have to check and make it 
    // required, only if uid is not the user's id.
    if (!is_numeric($uid)) {
        $uid = $USER->id;
    }
    if ($uid != $USER->id) {
        helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);
    }

    $relstring = $hd->get_relation_string($rel);
    echo "<h3>$relstring</h3>";

    // If a user is an answerer, we want the user to view tickets by a specific 
    // user id. This could prove to be hard without a form. We will do this 
    // later. TODO!

    // If we don't have a relation, we want to display the user's tickets and 
    // give the user options on what they can view. This will depend on the 
    // capabilities of the user. Answerers will be able to view everything.
    // This is all handled by the help desk's get_tickets() method.
    $offset = ($page) * $count;

    // Let's start getting ticket information.
    $tickets = $hd->get_tickets($uid, $rel, $offset, $count);
    $total = $hd->get_tickets_count($uid, $rel);

    // There is always a chance we will get no tickets back.
    if (empty($tickets) or !is_array($tickets)) {
        // Handle no tickets.
        notify(get_string('noticketstodisplay', 'block_helpdesk'));
    } else {
        // This is a table that will display generic information that any help 
        // desk should have.
        $table = new stdClass;
        $head = array();
        $head[] = get_string('summary', 'block_helpdesk');
        $head[] = get_string('submittedby', 'block_helpdesk');
        $head[] = get_string('status', 'block_helpdesk');
        $head[] = get_string('lastupdated', 'block_helpdesk');
        $table->head = $head;

        foreach($tickets as $ticket) {
            $user = fullname($ticket->get_user_object());
            $url = new moodle_url("$CFG->wwwroot/user/view.php");
            $url->param('id', $ticket->get_userid());
            $userurl = $url->out();

            $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
            $url->param('id', $ticket->get_idstring());
            $ticketurl = $url->out();

            $row = array();
            $row[] = "<a href=\"$ticketurl\">" . $ticket->get_summary() . '</a>';
            $row[] = "<a href=\"$userurl\">$user</a>";
            $row[] = $ticket->get_status_string();
            $row[] = helpdesk_get_date_string($ticket->get_timemodified());
            $table->data[] = $row;
        }
        print_table($table);
        $url = new moodle_url(qualified_me());
        print_paging_bar($total, $page, $count, $url, 'page');
    }
}

echo $OUTPUT->footer();

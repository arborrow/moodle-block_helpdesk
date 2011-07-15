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
 * This is the tag script. It handles all the UI and entry level functions to 
 * carry out this task.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We are moodle, so we should get necessary stuff.
require_once('../../config.php');
require_once($CFG->libdir . '/blocklib.php');
require_once($CFG->libdir . '/weblib.php');
require_once($CFG->libdir . '/datalib.php');
require_once($CFG->libdir . '/formslib.php');

// We are the helpdesk, so we need the core library.
require_once($CFG->dirroot . '/blocks/helpdesk/lib.php');

require_login(0, false);

$tid = optional_param('tid', null, PARAM_INT);
$tag = optional_param('tagid', null, PARAM_INT);
$remove = optional_param('remove', null, PARAM_INT);

// Get plugin helpdesk.
$hd = helpdesk::get_helpdesk();

// Create form and get data. There may be something, then again maybe not.
// Lets try a cleaner way to do this.
$form = $hd->tag_ticket_form($tid);

// Build Navigation
$ticketurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
$nav = array();
$nav[] = array ('name' => get_string('helpdesk', 'block_helpdesk'),
                'link' => $ticketurl->out());

$ticketurl->param('id', $tid);

$nav[] = array (
    'name' => get_string('ticketview', 'block_helpdesk'),
    'link' => $ticketurl->out()
);
$nav[] = array ('name' => get_string('tags', 'block_helpdesk'));

// User should be logged in, no guests or askers, only answerers.
helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);

$title = get_string('helpdesktagticket', 'block_helpdesk');
helpdesk_print_header(build_navigation($nav), $title);
print_heading(get_string('helpdesk', 'block_helpdesk'));

$ticket = $hd->get_ticket($tid);
// First, if we're removing a tag, that takes priority over all else.
if (is_numeric($remove)) {
    if (!$ticket->remove_tag($remove)) {
        error(get_string('unabletoremovetag', 'block_helpdesk'));
    }
    $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
    $url->param('id', $tid);
    $url = $url->out();
    redirect($url, get_string('tagremoved', 'block_helpdesk'));
} elseif ($data = $form->get_data()) {
    // Do this when we're ready to update/insert a tag.
    $data->ticketid = $tid;
    $tag = $ticket->parse_tag($data);
    if(!$ticket->add_tag($tag)) {
        error(get_string('unabletoaddtag'));
    }
    // At this point, the new tag has been added.
    $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
    $url->param('id', $data->ticketid);
    redirect($url->out(), get_string('tagadded', 'block_helpdesk'));
} else {
    $form->display();
}

print_footer();
?>

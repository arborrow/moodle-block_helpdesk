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
 * This script is for creating new tickets and handling the UI and entry level 
 * functions of this task.
 *
 * @package     block_helpdesk
 * @copyright   2010
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

// User should be logged in, no guests.
$nav = array (
    array ('name' => get_string('helpdesk', 'block_helpdesk')),
    array ('name' => get_string('newticket', 'block_helpdesk'))
    );

// We may have some special tags included in GET.
$tags = optional_param('tags', null, PARAM_TAGLIST);
$tagslist = array();
if (!empty($tags)) {
    $tags = explode(',', $tags);
    foreach($tags as $tag) {
        if (!($rval = optional_param($tag, null, PARAM_TEXT))) {
            notify(get_string('missingnewtickettag', 'block_helpdesk') . ": $tag");
        }
        $taglist[$tag] = $rval;
    }
}

// Require a minimum of asker capability on the current user.
helpdesk_is_capable(HELPDESK_CAP_ASK, true);

$title = get_string('helpdesknewticket', 'block_helpdesk');
helpdesk_print_header(build_navigation($nav), $title);
print_heading(get_string('helpdesk', 'block_helpdesk'));

// Meat and potatoes of the new ticket.
// Get plugin helpdesk.
$hd = helpdesk::get_helpdesk();

// Get new ticket form to get data or the form itself.
if (!empty($taglist)) {
    $form = $hd->new_ticket_form(array('tags' => $taglist));
} else {
    $form = $hd->new_ticket_form(array('tags' => array()));
}

// If the form is submitted (or not) we gotta do stuff.
if (!$form->is_submitted() or !($data = $form->get_data())) {
    $form->display();
    print_footer();
    exit;
}

// At this point we know that we have a ticket to add.
$ticket = $hd->new_ticket();
if (!$ticket->parse($data)) {
    error(get_string("cannotparsedata", 'block_helpdesk'));
}
if (!$ticket->store()) {
    error(get_string('unabletostoreticket', 'block_helpdesk'));
}
$id = $ticket->get_idstring();

if (!empty($data->tags)) {
    $taglist = array();
    $tags = explode(',', $data->tags);
    foreach($tags as $tag) {
        if (!($rval = $data->$tag)) {
            notify(get_string('missingnewtickettag', 'block_helpdesk') . ": $tag");
        } else {
            $taglist[$tag] = $rval;
        }
    }
    
    foreach($taglist as $key => $value) {
        $tagobject = new stdClass;
        $tagobject->ticketid = $id;
        $tagobject->name = $key;
        $tagobject->value = $value;
        if (!$ticket->add_tag($tagobject)) {
            notify(get_string('cannotaddtag', 'block_helpdesk') . ": $key");
        }
    }
}

$url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
$url->param('id', $id);
$url = $url->out();

redirect($url, get_string('newticketmsg', 'block_helpdesk'));
?>

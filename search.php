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
 * This is the core helpdesk library. This contains the building blocks of the 
 * entire helpdesk.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/weblib.php');

require_login(0, false);

global $CFG;

$nav = array (
    array (
        'name' => get_string('helpdesk', 'block_helpdesk'),
          ),
    array (
        'name' => get_string('search'),
        'link'  => "$CFG->wwwroot/blocks/helpdesk/search.php"
          )
    );

$hd = helpdesk::get_helpdesk();
$form = $hd->search_form();
$data = $form->get_data();
if ($form->is_validated()) {
    $nav[] = array ('name' => get_string('searchresults'));
}

$title = get_string('helpdesksearch', 'block_helpdesk');
helpdesk_print_header(build_navigation($nav), $title);
print_heading(get_string('helpdesk', 'block_helpdesk'));

// Always display this.
$form->display();

// Lets construct our helpdesk.
if ($form->is_validated()) {
    $tickets = $hd->search($data->searchstring);
    echo '<h3>' . get_string('searchresults') . ": 
              <small>$data->searchstring</small>
          </h3>";

    if ($tickets == false) {
        notify(get_string('noticketstodisplay', 'block_helpdesk'));
    } else {
        // This is a table that will display generic information that any help 
        // desk should have.
        $ticketnamestr = get_string('summary', 'block_helpdesk');
        $ticketstatusstr = get_string('status', 'block_helpdesk');
        $lastupdatedstr = get_string('lastupdated', 'block_helpdesk');
        $userstr = get_string('user');
        $table = new stdClass;
        $head = array();
        $head[] = $ticketnamestr;
        $head[] = $userstr;
        $head[] = $ticketstatusstr;
        $head[] = $lastupdatedstr;
        $table->head = $head;

        foreach($tickets as $ticket) {
            $user = helpdesk_get_user($ticket->get_userid());
            $userurl = new moodle_url("$CFG->wwwroot/user/view.php");
            $userurl->param('id', $user->id);
            $userurl = $userurl->out();
            $user = fullname($user);
            $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
            $url->param('id', $ticket->get_idstring());
            $url = $url->out();
            $row = array();
            $row[] = "<a href=\"$url\">" . $ticket->get_summary() . '</a>';
            $row[] = "<a href=\"$userurl\">$user</a>";
            $row[] = $ticket->get_status_string();
            $row[] = helpdesk_get_date_string($ticket->get_timemodified());
            $table->data[] = $row;
        }
        print_table($table);

    }
}

print_footer();
?>

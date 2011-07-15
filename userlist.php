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
require_once("$CFG->dirroot/user/filters/lib.php");

require_login(0, false);

$returnurl      = required_param('returnurl', PARAM_RAW);
$paramname      = required_param('paramname', PARAM_ALPHA);
$ticketid       = optional_param('tid', null, PARAM_INT);
$userid         = optional_param('userid', null, PARAM_INT);
$page           = optional_param('page', 0, PARAM_INT);
$perpage        = optional_param('perpage', 20, PARAM_INT);
$sort           = optional_param('sort', 'name', PARAM_ALPHA);
$dir            = optional_param('dir', 'ASC', PARAM_ALPHA);

$baseurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
$thisurl = new moodle_url(me());
$thisurl->remove_params();
$thisurl->param('paramname', $paramname);
$thisurl->param('returnurl', $returnurl);

$nav = array (
    array (
        'name' => get_string('helpdesk', 'block_helpdesk'),
        'link' => $baseurl->out()
    )
);
if (is_numeric($ticketid)) {
    $ticketreturn = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
    $ticketreturn->param('id', $ticketid);
    $nav[] = array (
        'name' => get_string('ticketview', 'block_helpdesk'),
        'link' => $ticketreturn->out()
        );
}
$nav[] = array (
    'name' => get_string('updateticketoverview', 'block_helpdesk'),
    'link' => $returnurl
);
$nav[] = array (
    'name' => get_string('selectauser', 'block_helpdesk'),
);

helpdesk_print_header(build_navigation($nav));
print_heading(get_string('changeuser', 'block_helpdesk'));

$hd = helpdesk::get_helpdesk();

helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);


$ufiltering = new user_filtering(null, qualified_me());

$columns = array ('fullname', 'email');
$table = new stdClass;
$table->head = array();
$table->data = array();

foreach ($columns as $column) {
    if ($column == '') {
        $table->head[] = '';
        continue;
    }
    $table->head[$column] = get_string("$column");
}

if ($sort == "name") {
    $sort = "firstname";
}

$extrasql = $ufiltering->get_sql_filter();
$users = get_users_listing($sort, $dir, $page*$perpage, $perpage, '', '', '', $extrasql);
$usercount = get_users(false);
$usersearchcount = get_users(false, '', true, "", "", '', '', '', '', '*', $extrasql);

if ($extrasql !== '') {
    print_heading("$usersearchcount / $usercount ".get_string('users'));
    $usercount = $usersearchcount;
} else {
    print_heading("$usercount ".get_string('users'));
}

$alphabet = explode(',', get_string('alphabet'));
$strall = get_string('all');

$thisurl->param('sort', $sort);
$thisurl->param('dir', $dir);
$thisurl->param('perpage', $perpage);
$thisurl = $thisurl->out() . '&';

print_paging_bar($usercount, $page, $perpage, $thisurl);

flush();

foreach($users as $user) {
    if ($user->username == 'guest') {
        continue;
    }
    $url = new moodle_url($returnurl);
    $url->param($paramname, $user->id);

    $changelink = fullname($user) . ' <small>(<a href="' . $url->out() . '">' . 
            get_string('selectuser', 'block_helpdesk') . '</a>)</small>';
    $table->data[] = array(
        $changelink,
        $user->email
        );
}
$ufiltering->display_add();
$ufiltering->display_active();
print_table($table);
print_paging_bar($usercount, $page, $perpage, $thisurl);

print_footer();
?>

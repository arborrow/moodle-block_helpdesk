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
 * @author	Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");
global $CFG;

$hdpath = "$CFG->dirroot/blocks/helpdesk";
require_once("$hdpath/db/access.php");
require_once("$hdpath/helpdesk.php");
require_once("$hdpath/helpdesk_ticket.php");
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->libdir . '/blocklib.php');
require_once($CFG->libdir . '/weblib.php');
require_once($CFG->libdir . '/datalib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/adminlib.php');

unset($hdpath);

define('HELPDESK_DATE_FORMAT', 'F j, Y, g:i a');

/**
 * These are update types that define how the update was made, and will allow
 * the user to filter what they do not need to see (such as system updates, and
 * extra updates that show when users were assigned and such.)
 */

/**
 * This type is an update created by a real user.
 */
define('HELPDESK_UPDATE_TYPE_USER', 'update_type_user');

/**
 * This is an update created from a user action, such as assigned a user, or
 * adding a tag.
 */
define('HELPDESK_UPDATE_TYPE_DETAILED', 'update_type_detailed');

/**
 * This is an update created by the system, most likely from a cron job or some
 * sort of equivalent system update.
 */
define('HELPDESK_UPDATE_TYPE_SYSTEM', 'update_type_system');

/**
 * Gets a speicificly formatted date string for the helpdesk block.
 *
 * @param int   $date is the unix time to be converted to readable string.
 * @return string
 */
function helpdesk_get_date_string($date) {
    return userdate($date);
}

function print_table_head($string, $width='95%') {
    $table = new stdClass;
    $table->width   = $width;
    $table->head    = array($string);
    print_table($table);
}

/**
 * This function is to easily determine if a user is capable or return the most
 * powerful capability the user has in the helpdesk.
 *
 * @param int   $capability Capability that is being checked.
 * @param bool  $require Makes the capability check a requirement to pass.
 * @return bool
 */
function helpdesk_is_capable($capability=null, $require=false, $user=null) {

    if (empty($user)) {
        global $USER;
        $user = $USER;
    }

    if (is_numeric($user)) {
	$user = get_record('user', 'id', $user);
    }

    $context = get_context_instance(CONTEXT_SYSTEM);
    if (empty($capability)) {
        // When returning which capability applies for the user, we can't
        // require this. The type that is returned is *mixed*.

        if ($require !== false) {
            notify(get_string('warning_getandrequire', 'block_helpdesk'));
        }

	// Order here does matter.
	$rval = false;
        $cap = has_capability(HELPDESK_CAP_ASK, $context, $user->id);
        if ($cap) {
            $rval = HELPDESK_CAP_ASK;
        }
        $cap = has_capability(HELPDESK_CAP_ANSWER, $context, $user->id);
        if ($cap) {
            $rval = HELPDESK_CAP_ANSWER;
        }
        return $rval;
    }

    if ($require) {
        require_capability($capability, $context, $user->id);
        return true;
    }
    return has_capability($capability, $context, $user->id);
}

/**
 * This function gets a specific user in Moodle. Returns false if no user has
 * a matching ID, or returns a record from the database.
 *
 * @return mixed
 */
function helpdesk_get_user($id) {
    if (empty($id)) {
        error(get_string('missingidparam', 'block_helpdesk'));
    }
    return get_record('user', 'id', $id);
}

/**
 * This function gets a specific variable out of the global session variable,
 * but only in the help desk object. It can return any number of things, or null
 * if nothing is there.
 *
 * @param string        $varname Name of the helpdesk session variable.
 * @return mixed
 */
function helpdesk_get_session_var($varname) {
    global $SESSION;
    if (isset($SESSION->block_helpdesk)) {
        return isset($SESSION->block_helpdesk->$varname) ?
	       $SESSION->block_helpdesk->$varname : false; 
    }
    $SESSION->block_helpdesk = new stdClass;
    return null;
}

/**
 * This function sets a specific attribute in the global session variable in the
 * helpdesk object. It will return a bool depending on outcome.
 *
 * @param string        $varname is the attribute's name
 * @param string        $value is the value to set.
 * @return bool
 */
function helpdesk_set_session_var($varname, $value) {
    global $SESSION;
    if (!isset($SESSION->block_helpdesk)) {
        $SESSION->block_helpdesk = new stdClass;
    }
    $SESSION->block_helpdesk->$varname = $value;
}

/**
 * Wrapper for helpbutton to make the call more simple. Always returns the HTML
 * for the help button.
 *
 * @param string        $title is the alt text/title for this help button.
 * @param string        $text is the text the user will see if they click on it.
 * @return string
 */
function helpdesk_simple_helpbutton($title, $name, $return=true) {
    global $CFG;
    $result = helpbutton($name, $title, 'block_helpdesk', true, false, '', $return);
    return $result;

}

/**
 * This is a wrapper function for Moodle's build header. This moodle function
 * gets called *a lot* so if anything changes it should be in one place.
 * Besides, the header is going to be very similar from one page to another with
 * the exception of navigation.
 *
 * @param object        $nav will be a navigation build by build_navigation().
 * @return null
 */
function helpdesk_print_header($nav, $title=null) {
    global $CFG, $COURSE;
    $helpdeskstr = get_string('helpdesk', 'block_helpdesk');
    if (empty($title)) {
	$title = $helpdeskstr;
    }
    $meta = "<meta http-equiv=\"x-ua-compatible\" content=\"IE=8\" />\n
	     <link rel=\"stylesheet\" type=\"text/css\" href=\"$CFG->wwwroot/blocks/helpdesk/style.css\" />\n";
    print_header($title, $helpdeskstr, $nav, '', $meta);
}
?>

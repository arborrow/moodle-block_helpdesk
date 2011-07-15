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
 * Help Desk Ticket, Native
 *
 * Help desk ticket native is the ticket class that handles all 
 * operations to an individual ticket.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class helpdesk_ticket_native extends helpdesk_ticket {
    // Ticket db fields.
    protected $id;
    protected $summary;
    protected $status;
    protected $detail;
    protected $timecreated;
    protected $timemodified;
    protected $userid;
    protected $firstcontact;

    // All child db tables that have a relation with this ticket object.
    protected $tags;
    protected $updates;
    protected $users;

    /**
     * Constructor for native help desk ticket. This makes empty ticket with
     * some pre-initialized variables. This only gets called by the new_ticket
     * methods in the help desk and the ticket itself.
     *
     * @return null
     */
    function __construct() {
        $tags       = array();
        $updates    = array();
        $users      = array();
    }

    /**
     * Display ticket method that was recently moved to the plugin level. This 
     * will allow plugins to customize how tickets are view depending on the 
     * features for each plugin.
     *
     * @param object    $ticket is an already fetched ticket object with a valid 
     *                  id.
     * @return bool
     */
    function display_ticket($readonly=false) {
        global $CFG;

        $hd = helpdesk::get_helpdesk();

        $this->fetch();

        $isanswerer = helpdesk_is_capable(HELPDESK_CAP_ANSWER);

        $udata = $this->get_updates($isanswerer);
        $tags  = $this->get_tags();

        $showfirstcontact = get_config(null, 'block_helpdesk_show_firstcontact');

        $user   = get_record('user', 'id', $this->get_userid());
        $url    = new moodle_url("$CFG->wwwroot/user/view.php");
        $url->param('id', $user->id);
        $url    = $url->out();

        echo "<div class=\"ticketinfo\">";
        $overviewstr = get_string('ticketinfo', 'block_helpdesk');
        $overviewhelp = helpdesk_simple_helpbutton($overviewstr, 'overview');
        $editurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/edit.php");
        $editurl->param('id', $this->get_idstring());
        $editurl = $editurl->out();
        $editstr = get_string('editoverview', 'block_helpdesk');
        $headstr = "$overviewstr $overviewhelp";
        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER) and !$readonly) {
            $headstr .= "<br /><a href=\"$editurl\">$editstr</a>";
        }
        print_table_head($headstr);

        $table = new stdClass;
        $table->size = array('30%');
        $table->width = '95%';

        $table->head = null;
        $table->align = array('left', 'left');

        $row = array();
        $row[] = get_string('ticketid', 'block_helpdesk');
        $row[] = $this->get_idstring();
        $table->data[] = $row;

        $row = array();
        $str = get_string('submittedby', 'block_helpdesk');
        if ($isanswerer and !$readonly) {
            $newuserurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/userlist.php");
            $newuserurl->param('returnurl', $editurl);
            $newuserurl->param('paramname', 'newuser');
            $newuserurl->param('tid', $this->get_idstring());
            $str .="<br /><small><a href=\"" . $newuserurl->out() . "\">" .
                   get_string('changeuser', 'block_helpdesk') . '</a></small>';
        }
        $row[] = $str;
        $row[] = "<a href=\"$url\">" . fullname($user) . '</a>';
        $table->data[] = $row;
        
        if ($this->firstcontact != null and $showfirstcontact != false) {
            $url = new moodle_url("$CFG->wwwroot/user/view.php");
            $url->param('id', $this->firstcontact->id);
            $url = $url->out();

            $help = helpdesk_simple_helpbutton(get_string('firstcontact', 'block_helpdesk'),
                                               'firstcontact');

            $row = array();
            $row[] = get_string('firstcontactuser', 'block_helpdesk') . $help;
            $row[] = "<a href=\"$url\">" . fullname($this->firstcontact) . '</a>';
            $table->data[] = $row;
        }

        $row = array();
        $row[] = get_string('timecreated', 'block_helpdesk');
        $row[] = helpdesk_get_date_string($this->get_timecreated());
        $table->data[] = $row;

        $row = array();
        $row[] = get_string('timemodified', 'block_helpdesk');
        $row[] = helpdesk_get_date_string($this->get_timemodified());
        $table->data[] = $row;

        $row = array();
        $row[] = get_string('status', 'block_helpdesk');
        $status = $this->get_status();
        if ($status->core == true and empty($status->displayname)) {
            $row[] = get_string($status->name, 'block_helpdesk');
        } else {
            $row[] = $status->displayname;
        }
        $table->data[] = $row;

        $row = array();
        $row[] = get_string('summary', 'block_helpdesk');
        $row[] = $this->get_summary();
        $table->data[] = $row;

        $row = array();
        $row[] = get_string('detail', 'block_helpdesk');
        $row[] = $this->get_detail();
        $table->data[] = $row;

        print_table($table, false);
        echo '<br />';

        // Assignments start here.
        $assignedstr = get_string('assignedusers', 'block_helpdesk');
        $assignedhelp = helpdesk_simple_helpbutton($assignedstr, 'assigned');
        $thead = $assignedstr . $assignedhelp;

        // If the user is a answerer, he can assign people to the ticket.
        if($isanswerer and !$readonly) {
            $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/assign.php");
            $url->param('tid', $this->get_idstring());
            $url = $url->out();
            $string = get_string("assignuser", 'block_helpdesk');
            $thead .= "<br /><a href=\"$url\">$string</a>";
        }
        print_table_head($thead);

        $assigned = $this->get_assigned();

        if ($assigned === false) {
            $table->data = array(array(get_string('noneassigned', 'block_helpdesk')));
        } else {
            $table->data = array();

            foreach($assigned as $user) {
                $userurl = new moodle_url("$CFG->wwwroot/user/view.php");
                $userurl->param('id', $user->id);
                $userurl = $userurl->out();

                $removeurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/assign.php");
                $removeurl->param('remove', 'true');
                $removeurl->param('uid', $user->id);
                $removeurl->param('tid', $this->get_idstring());
                $removeurl = $removeurl->out();
                $user_string = fullname($user);

                $row = array();
                $row[] = "<a href=\"$userurl\">$user_string</a>";
                if ($isanswerer and !$readonly) {
                    $row[] = "<a href=\"$removeurl\">" . get_string('remove') . "</a>";
                }
                $table->data[] = $row;
            }
        }
        $table->size = array('70%');
        print_table($table, false);
        echo '<br />';
        $table->size = array('30%');

        // Assignments end here.
        // START TAGS DISPLAY

        $tagstr = get_string('extradetailtags', 'block_helpdesk');
        $taghelp = helpdesk_simple_helpbutton($tagstr, 'tag');
        $thead = $tagstr . $taghelp;

        // If answerer, show link for adding tags.
        if ($isanswerer and !$readonly) {
            $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/tag.php");
            $url->param('tid', $this->get_idstring());
            $url = $url->out();
            $addtagstr = get_string('addtag', 'block_helpdesk');

            $thead .= "<br /><a href=\"$url\">$addtagstr</a>";
        }
        print_table_head($thead);

        $table->data = array();
        if (!$tags == null) {
            foreach($tags as $tag) {
                $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/tag.php");
                $url->param('remove', $tag->id);
                $url->param('tid', $this->get_idstring());
                $url = $url->out();
                $removestr = get_string('remove');

                $row = array();
                if ($isanswerer and !$readonly) {
                    $remove = "<br />
                               <small>
                                   <a href=\"$url\">$removestr</a>
                               </small>";
                } else {
                    $remove = '';
                }
                $table->data[] = array(
                    $tag->name . $remove,
                    $tag->value
                );
            }
        } else {
            $table->data = array(array(get_string('notags', 'block_helpdesk')));
        }
        print_table($table);

        // END TAGS DISPLAY

        echo '</div>';

        // Updates start here.
        $updatestr = get_string('updates', 'block_helpdesk');
        $updatehelp = helpdesk_simple_helpbutton($updatestr, 'update');
        echo "<div class=\"ticketupdates\">";

        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/update.php");
        $url->param('id', $this->get_idstring());
        $url = $url->out();
        $translated = get_string('updateticket', 'block_helpdesk');

        $thead = "$updatestr $updatehelp";
        if(!$readonly) {
            $thead .= "<br /><a href=\"$url\">$translated</a>";
        }
        print_table_head($thead);

        // We're going to find out now if we are displaying these updates.
        $table->data = array();
        $updateprinted = false;
        if (is_array($udata) or is_object($udata)) {
            // If we have system or detailed updates, display them.
            $showdetailed = helpdesk_get_session_var('showdetailedupdates');
            $showsystem = helpdesk_get_session_var('showsystemupdates');
            foreach($udata as $update) {
                $table->data = array();
                if ($update->type == HELPDESK_UPDATE_TYPE_DETAILED and !$showdetailed) {
                    continue;
                }
                if ($update->type == HELPDESK_UPDATE_TYPE_SYSTEM and !$showsystem) {
                    continue;
                }

                $updateprinted = true;

                if ($update->type !== false and
                    $update->type !== null) {

                    $row = array();
                    $str = get_string($update->type, 'block_helpdesk');
                    $row[] = $str;
                    if ($hd->is_update_hidden($update)) {
                        $row[] = get_string('thisupdateishidden', 'block_helpdesk');
                    } else {
                        $row[] = '';
                    }
                    $table->head = $row;
                }

                $user = get_record('user', 'id', $update->userid);
                if (!$user) {
                    error(getstring('unabletopulluser', 'block_helpdesk'));
                }

                // Who submitted the update?
                $row = array();
                $user_url = new moodle_url("$CFG->wwwroot/user/view.php");
                $user_url->param('id', $user->id);
                $user_url = $user_url->out();
                $user_name = fullname($user);
                $row[] = get_string('user', 'block_helpdesk');
                $row[] = "<a href=\"$user_url\">$user_name</a>";
                $table->data[] = $row;

                // Status
                $row = array();
                $row[] = get_string('status', 'block_helpdesk');
                $row[] = get_string($update->status, 'block_helpdesk');
                $table->data[] = $row;

                // New ticket status if status changed.
                if ($update->newticketstatus != null) {
                    $row = array();
                    $tstat = get_record('helpdesk_status', 'id', $update->newticketstatus);
                    $row[] = get_string('newquestionstatus', 'block_helpdesk');
                    $row[] = $this->get_status_string($tstat);
                    $table->data[] = $row;
                }

                // "Created On" date.
                $row = array();
                $creation_date = helpdesk_get_date_string($update->timecreated);

                // Time Created date.
                $row[] = get_string('timecreated', 'block_helpdesk');
                $row[] = $creation_date;
                $table->data[] = $row;

                // Update Note.
                $row = array();
                $row[] = get_string('note', 'block_helpdesk');
                $row[] = $update->notes;
                $table->data[] = $row;
                print_table($table);
                echo '<br />';
            }
        }
        if ($updateprinted === false) {
            $row = array();
            $row[] = get_string('noupdatestoview', 'block_helpdesk');
            $table->data[] = $row;
            print_table($table);
        }

        echo '</div>';
        return true;
    }

    /**
     * Set method to set the idstring of a ticket.
     *
     * @param string    $id idstring to be set to the ticket.
     * @return bool
     */
    function set_idstring($id) {
        $this->id = $id;
        return true;
    }

    /**
     * Set method to set the summary string of a ticket.
     *
     * @param string    $string Summary to be set to the ticket.
     * @return bool
     */
    function set_summary($string) {
        $this->summary = $string;
        return true;
    }

    /**
     * Set method to set the detail string of a ticket.
     *
     * @param string    $string detail string to be set to the ticket.
     * @return bool
     */
    function set_detail($string) {
        $this->detail = $string;
        return true;
    }

    /**
     * Set method to set the timecreated of a ticket. This method doesn't take
     * any parameters, because the only time the time created should be is when
     * this method is called.
     *
     * @return bool
     */
    function set_timecreated() {
        $this->timecreated = time();
        return true;
    }

    /**
     * Set method to set the timemodified of a ticket. This method doesn't take
     * any parameters, because the only time the timemodified should be is when
     * this method is called.
     *
     * @return bool
     */
    function set_timemodified() {
        $this->timemodified = time();
        return true;
    }

    /**
     * Set a new status to the ticket.
     *
     * @param string    $status is a status string to be set.
     * @return bool
     */
    function set_status($status) {
        if (is_numeric($status)) {
            $status = get_record('helpdesk_status', 'id', $status);
        }
        if (!is_object($status)) {
            error('Status must be an object or id.');
        }
        $this->status = $status;
        return true;
    }

    function set_userid($id) {
        $this->userid = $id;
        return true;
    }

    /**
     * Get method that returns an idstring.
     *
     * @return string
     */
    function get_idstring() {
        return $this->id;
    }

    /**
     * Get method that returns the summary of a ticket.
     *
     * @return string
     */
    function get_summary() {
        return $this->summary;
    }

    /**
     * Get method that returns the detail string of a ticket.
     *
     * @return string
     */
    function get_detail() {
        return $this->detail;
    }

    /**
     * Get method that returns the time created in unix epoch form.
     *
     * @return int
     */
    function get_timecreated() {
        return $this->timecreated;
    }

    /**
     * Get method that returns the time modified in unix epoch form.
     *
     * @return int
     */
    function get_timemodified() {
        return $this->timemodified;
    }

    /**
     * Get method that returns the array of tags associated with a ticket.
     *
     * @return array
     */
    function get_tags() {
        return $this->tags;
    }

    /**
     * Get method that returns an array of updates associated with a ticket.
     *
     * @return array
     */
    function get_updates($includehidden=false) {
        if ($includehidden == true) {
            return $this->updates;
        }
        $updates = array();
        if (!empty($this->updates)) {
            foreach($this->updates as $update) {
                if ($update->hidden == 1) {
                    continue;
                }
                $updates[] = $update;
            }
        }
        return $updates;
    }

    /**
     * Gets the id of the user that submitted a particular ticket. The output of
     * this method varies from plugin to plugin, this case it returns an int for
     * an id.
     *
     * @return int
     */
    function get_userid() {
        return $this->userid;
    }

    /**
     * Returns the value for the status, this should be a simple string.
     *
     * @return string
     */
    function get_status() {
        return $this->status;
    }

    /**
     * Slightly modified get_status_string which will default to the ticket's
     * own status if no argument is passed. Otherwise the argument is used to
     * determine the Moodle status string. Returned value is mixed. String if
     * there is a matching string, or false if not.
     *
     * @param string    $status status to be converted to a native language.
     * @return mixed
     */
    function get_status_string($status=null) {
        // Matt thinks this is evil. Now that we're moving statuses to the 
        // database, we need this to do some pre-processing of statuses.
        if ($status != null and !is_object($status)) {
            error('non-object ('.gettype($status).') passed to get_status_string()');
        }
        if ($status == null) {
            $status = $this->get_status();
        }

        if ($status->core == true and empty($status->displayname)) {
            return get_string($status->name, 'block_helpdesk');
        }
        return $status->displayname;
    }

    /**
     * This method adds an assignment to a ticket by a user's id. This method
     * assumes that access to be assigned has already been checked.
     *
     * @param int       $userid User id that is being assigned.
     * @return bool
     */
    function add_assignment($userid) {
        global $CFG;
        $assign = new stdClass;
        $assign->userid = $userid;
        $assign->ticketid = $this->id;
        $rval = insert_record('helpdesk_ticket_assignments', $assign, false);
        if (!$rval) {
            return false;
        }

        // Now lets add an update for what changed. We want to track things like
        // this from now on.
        $urecord        = get_record('user', 'id', $userid);
        $dat            = new stdClass;
        $dat->ticketid  = $this->id;
        $dat->notes     = fullname($urecord) . ' '
                          . get_string('wasassigned', 'block_helpdesk');
        $dat->status    = HELPDESK_NATIVE_UPDATE_ASSIGN;
        $dat->type      = HELPDESK_UPDATE_TYPE_DETAILED;
        if(!$this->add_update($dat)) {
            notify(get_string('cantaddupdate', 'block_helpdesk'));
        }

        $this->fetch();
        $this->store();
        return true;
    }

    /**
     * This method removes an assignment from a particular ticket. Users are
     * removed by their user id. This will return true or false depending on the
     * result.
     *
     * @param int       $userid ID of the user to remove the assignment for.
     * @return bool
     */
    function remove_assignment($userid) {
        $result = delete_records('helpdesk_ticket_assignments', 'userid', $userid, 'ticketid', $this->id);
        if ($result) {
            $this->store();
            $urecord            = get_record('user', 'id', $userid);
            $dat                = new stdClass;
            $dat->ticketid      = $this->id;
            $dat->notes         = fullname($urecord) . ' ' .
                                  get_string('wasunassigned', 'block_helpdesk');
            $dat->status        = HELPDESK_NATIVE_UPDATE_UNASSIGN;
            $dat->type          = HELPDESK_UPDATE_TYPE_DETAILED;

            if(!$this->add_update($dat)) {
                notify(get_string('cantaddupdate', 'block_helpdesk'));
            }
            return $result;
        }
    }

    /**
     * This gets all the assigned users for a particular ticket. It will return
     * an array of users, similar to a database record array from moodle.
     *
     * @return array
     */
    function get_assigned() {
        // When a new ticket is stored, there is no id. We want to stop here.
        if (empty($this->id)) {
            return false;
        }
        $records = get_records('helpdesk_ticket_assignments', 'ticketid', $this->id);

        // If there are no records, there are no users assigned.
        if (!$records) {
            return false;
        }

        // At this point we have to process each user. This may sound scary but
        // the number of assigned users is usually low.
        foreach($records as $record) {
            $users[] = helpdesk_get_user($record->userid);
        }

        return $users;
    }

    /**
     * The fetch() method gets a ticket and all respective related records that
     * reside inside a ticket object. This is all based off the currently set
     * idstring. If no ID is set or the fetch fails, a false is returned.
     * Otherwise will return true.
     *
     * @return bool
     */
    function fetch($permissionhalt=true) {
        global $USER;
        if (!$this->id) {
            return false;
        }
        $ticket = get_record('helpdesk_ticket', 'id', $this->id);
        if (!$ticket) {
            return false;
        }
        # Check for permission before proceeding.
        if (!helpdesk_is_capable(HELPDESK_CAP_ASK) or $ticket->userid != $USER->id) {
            if (!helpdesk_is_capable(HELPDESK_CAP_ANSWER, $permissionhalt)) {
                return false;
            }
        }

        $this->parse_db_ticket($ticket);
        $updates        = get_records('helpdesk_ticket_update', 'ticketid',
                                      $this->id, 'timecreated DESC');
        $tags           = get_records('helpdesk_ticket_tag', 'ticketid',
                                      $this->id, 'name ASC');
        $this->status   = get_record('helpdesk_status', 'id', $this->status);
        if(!is_object($this->status)) {
            error("Invalid status id on ticket $this->id.");
        }
        $this->parse_db_updates($updates);
        $this->parse_db_tags($tags);

        return true;
    }

    /**
     * store() is a helpdesk_native_ticket method that updates or inserts
     * a record in the database to reflect the data inside this object or
     * updates an already existing record with any changes to the ticket.
     *
     * @return bool
     **/
    function store() {
        global $USER;
        $dataobject                     = new stdClass;
        $dataobject->summary            = $this->summary;
        $dataobject->detail             = $this->detail;

        if (!is_numeric($this->timecreated)) {
            $this->set_timecreated();
        }

        $dataobject->timecreated        = $this->timecreated;
        $this->set_timemodified();
        $dataobject->timemodified       = $this->timemodified;
        $dataobject->userid             = $this->userid;

        if (empty($this->status)) {
            $this->status               = get_record('helpdesk_status', 'ticketdefault', 1);
        }

        $dataobject->status         = $this->status->id;

        if (is_numeric($this->firstcontact)) {
            if(record_exists('user', 'id', $this->firstcontact)) {
                error('Invalid first contact user id.');
            }
            $this->firstcontact = helpdesk_get_user($this->firstcontact);
        }
        $dataobject->firstcontact       = is_object($this->firstcontact) ?
                                          $this->firstcontact->id : 0;

        $assigned = $this->get_assigned();
        if ($assigned === false) {
            $dataobject->assigned_refs = 0;
        } else {
            $dataobject->assigned_refs  = count($assigned);
        }
        if (!is_numeric($this->userid)) {
            return false;
        }
        if (!empty($this->id)) {
            $dataobject->id = $this->id;
        }

        if (!empty($dataobject->id)) {
            // Ewww, this is a hack for Moodle 1.9. insert_record adds slashes
            // where update_record does not.
            foreach($dataobject as &$col) {
                $col = addslashes($col);
            }
            $result = update_record('helpdesk_ticket', $dataobject);
        } else {
            $result = insert_record('helpdesk_ticket', $dataobject, true);
            if ($result) {
                $this->id = $result;
            }
        }
        if (is_numeric($result) or $result == true) {
            $this->fetch();
            return true;
        }
        return false;
    }

    /**
     * Retrieve a ticket based on ID.
     *
     * @param int       $id id of the ticket to be fetched.
     * @return bool
     */
    function get_ticket($id) {
        if (empty($id)) {
            return false;
        }
        $this->set_idstring($id);
        if(!$this->fetch()) {
            return false;
        }
        return true;
    }

    /**
     * This takes the usable field from the $data object you pass in to fill the
     * ticket with some basic information. This is a very generalized method.
     *
     * Deprecate this method! -Jon
     *
     * @param object    $data is an object with ticket fields, such as a db record.
     * @return true
     */
    function parse($data) {
        global $USER;
        if (!is_object($data)) {
            return false;
        }
        // An id may not always exists, like if this is a new ticket.
        if (isset($data->id)) {
            $this->id           = $data->id;
        }
        $this->detail           = $data->detail;
        $this->summary          = $data->summary;
        if (empty($data->userid)) {
            $this->userid = $USER->id;
        } else {
            $this->userid       = $data->userid;
        }
        if (isset($data->timecreated)) {
            $this->timecreated  = $data->timecreated;
        } else {
            $this->timecreated  = time();
        }
        if (isset($data->timemodified)) {
            $this->timemodified = $data->timemodified;
        } else {
            $this->timemodified = time();   
        }
        return true;

    }

    /**
     * Very similar to parse, except this one strips slashes. This takes in
     * database records specifically. Like ones returned from get_record().
     *
     * @param object    $record is a database record from moodle.
     * @return true
     */
    function parse_db_ticket($record) {
        if (!is_object($record)) {
            return false;
        }
        $this->id               = $record->id;
        $this->detail           = stripslashes($record->detail);
        $this->summary          = stripslashes($record->summary);
        $this->userid           = $record->userid;
        $this->timecreated      = $record->timecreated;
        $this->timemodified     = $record->timemodified;
        $this->status           = $record->status;
        if (is_numeric($record->firstcontact) and $record->firstcontact != 0) {
            $this->firstcontact = helpdesk_get_user($record->firstcontact);
        } else {
            $this->firstcontact = null;
        }
        return true;
    }

    /**
     * Adds an update to the ticket from the data provided by the moodle form
     * for updates. Returns a bool depending on success.
     *
     * @param object    $update is the update data from a moodle form.
     * @return bool
     */
    function add_update($update) {
        global $USER;
        $hd = helpdesk::get_helpdesk();
        $isanswerer = helpdesk_is_capable(HELPDESK_CAP_ANSWER);
        if (!is_object($update)) {
            return false;
        }
        $dat->ticketid = $this->id;

        // No type is no longer allowed.
        if (!$update->type) {
            return false;
        }

        $status = $this->status;
        $update = $this->process_update($update);

        if (!is_object($this->firstcontact) and
            $this->get_userid() != $USER->id and
            $isanswerer) {

            $this->firstcontact = $USER;
        }

        $dat->notes         = $update->notes;
        $dat->userid        = !empty($dat->userid) ? $dat->userid : $USER->id;
        $dat->status        = $update->status;
        $dat->type          = $update->type;
        $dat->hidden       = isset($update->hidden) ? $update->hidden : false;
        $dat->timecreated   = time();
        $dat->timemodified  = time();
        if(isset($update->newticketstatus)) {
            $dat->newticketstatus   = $update->newticketstatus;
        }

        if ( insert_record('helpdesk_ticket_update', $dat) ) {
            
            $usefirstcontact = get_config(null, 'block_helpdesk_firstcontact');
            $isanswerer = helpdesk_is_capable(HELPDESK_CAP_ANSWER);
            if ($usefirstcontact and $isanswerer and $this->firstcontact == true) {
                $this->firstcontact = $user->id;
            }

            // You're wondering what this is. This actually updates the time
            // modified for the ticket and updates the status if it changed.
            // Not to mension updating firstcontact if it applies.
            $this->store();

            // Lets not fetch, this is quicker.
            $this->updates[] = $dat;

            // We also want to call the email update method in case email 
            // notifications are turned on.
            // NOTE: This method will automatically check to see if we can send 
            // emails out, don't worry about checking that here.
            if($dat->type == HELPDESK_UPDATE_TYPE_USER) {
                $rval = $hd->email_update($this);
            }
            return true;
        }

        return false;
    }

    private function process_update($update) {
        // This allows us to change the status of a ticket at the same time as 
        // we add an update.

        // New Method
        // If the status is a number, its a status id to change the ticket to.
        if (is_numeric($update->status)) {
            $this->status = get_record('helpdesk_status', 'id', $update->status);
            $update->newticketstatus = $this->status->id;
            if (!is_object($this->status)) {
                error('Invalid ticket status. Does not exist in status table.');
            }
            $this->store();
            $update->status = HELPDESK_NATIVE_UPDATE_STATUS;
        }
        return $update;
    }

    /**
     * Updates a tag with an id to match the fields on the object.
     *
     * @param object    $tag is a tag record with a constant id.
     * @return bool
     */
    function update_tag($tag) {
        if (update_record('helpdesk_ticket_tag', $tag)) {
            $this->store();
            $this->fetch();
            return true;
        }
        return false;
    }

    /**
     * Adds a tag to the ticket. The object has the same fields as a tag in the
     * database. There should be no id though.
     *
     * @param object    $tag is a tag to-be record without an id.
     * @return bool
     */
    function add_tag($tag) {
        if (!is_object($tag)) {
            return false;
        }
        if (!isset($tag->name) or
            !isset($tag->value) or
            !isset($tag->ticketid) or
            isset($tag->id)){

            return false;
        }

        if (!insert_record('helpdesk_ticket_tag', $tag)) {
            return false;
        }

        // Lets make an update saying we added this tag.
        $dat = new stdClass;
        $dat->ticketid  = $this->id;
        $dat->notes     = get_string('tagaddedwithnameof', 'block_helpdesk') . $tag->name;
        $dat->status    = HELPDESK_NATIVE_UPDATE_TAG;
        $dat->type      = HELPDESK_UPDATE_TYPE_DETAILED;

        if(!$this->add_update($dat)) {
            notify(get_string('cantaddupdate', 'block_helpdesk'));
        }

        // Update modified time and refresh the ticket.
        $this->store();
        $this->fetch();
        return true;
    }

    /**
     * Removes a tag from the database for a certain ticket based on an id.
     *
     * @param int       $id is the id of the tag being removed.
     * @return bool
     */
    function remove_tag($id) {
        global $CFG;
        if (!is_numeric($id)) {
            return false;
        }

        $tag = get_record('helpdesk_ticket_tag', 'id', $id);

        $result = delete_records('helpdesk_ticket_tag', 'id', $id);
        if (!$result) {
            return false;
        }
        // Lets make an update!

        $dat = new stdClass;
        $dat->ticketid      = $this->id;
        $dat->notes         = get_string('tagremovewithnameof', 'block_helpdesk') . $tag->name;
        $dat->status        = HELPDESK_NATIVE_UPDATE_UNTAG;
        $dat->type          = HELPDESK_UPDATE_TYPE_DETAILED;

        if(!$this->add_update($dat)) {
            notify(get_string('cantaddupdate', 'block_helpdesk'));
        }

        $this->store();
        return true;
    }

    /**
     * Basically copies a $tag array into the ticket. The $tags are usually
     * database records.
     *
     * @param array     $tags are tag records to be included with the ticket.
     * @return null
     */
    function parse_db_tags($tags) {
        if (!is_array($tags)) {
            $this->tags = null;
        }
        $this->tags = $tags;
    }

    /**
     * Basically copies some update records from the database and plops them
     * into our ticket.
     *
     * @param array     $updates is an array of update records from the db.
     * @return true
     */
    function parse_db_updates($updates) {
        if (!is_array($updates)) {
            $this->updates = null;
        }
        $this->updates = $updates;
        return true;
    }

    /**
     * Returns a clean tag in an object. Returns clean tag object or false if
     * failed.
     *
     * @param mixed     $data is an object or array with tag attributes.
     * @return mixed
     */
    function parse_tag($data) {
        if (is_object($data)) {
            if (isset($data->id)) {
                $tag->id = $data->id;
            }
            $tag->name          = $data->name;
            $tag->value         = $data->value;
            $tag->ticketid      = $data->ticketid;
            return $tag;
        } elseif (is_array($data)) {
            if ($data['id']) {
                $tag->id = $data->id;
            }
            $tag->name          = $data['name'];
            $tag->value         = $data['value'];
            $tag->ticketid      = $data['ticketid'];
            return $tag;
        } else {
            return false;
        }
    }

    /**
     * This is calld when an already existing ticket is edited. This allows us 
     * to make an updated associated with this edit.
     *
     * @param string    $msg is a message to leave in the update.
     * @return bool
     */
    function store_edit($msg=null) {
        if(!$this->store()) {
            return false;
        }
        $update = new stdClass;
        $update->ticketid  = $this->id;
        $update->notes     = $msg;
        $update->status    = HELPDESK_NATIVE_UPDATE_DETAILS;
        $update->type      = HELPDESK_UPDATE_TYPE_DETAILED;
        if (!$this->add_update($update)) {
            notify(get_string('unabletoaddeditupdate', 'block_helpdesk'));
        }
        return true;
    }

}
?>

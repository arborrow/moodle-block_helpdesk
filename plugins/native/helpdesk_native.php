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
 * This is the moodle native plugin for the helpdesk. This plugin is a basic
 * helpdesk that is built into the helpdesk block. This is initially the only
 * option and will become the default option.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");
global $CFG;

class helpdesk_native extends helpdesk {

    /**
     * helpdesk_native constructor. Nothing special, but this isn't called
     * directly. Help Desk base class has a factory function for construction.
     *
     * @return null
     */
    function __construct() {
        // This is now empty. No more native viewer.
    }

    /**
     * install script. this runs after the tables have been made.
     *
     * @return bool
     */
    function install() {
        // Lets define base statuses.
        $new = new stdClass;
        $new->name = 'new';
        $new->core = 1;
        $new->ticketdefault = 1;
        $new->active = 1;

        $wip = clone $new;
        $wip->name = 'workinprogress';
        $wip->ticketdefault = 0;

        $closed = clone $wip;
        $closed->name = 'closed';
        $closed->active = 0;

        $resolved = clone $closed;
        $resolved->name = 'resolved';

        $reopen = clone $wip;
        $reopen->name = 'reopened';

        $nmi = clone $wip;
        $nmi->name = 'needmoreinfo';

        $ip = clone $nmi;
        $ip->name = 'infoprovided';

        // Lets add all of our statuses.
        $rval = true;
        $rval = $rval and $new->id = insert_record('helpdesk_status', $new, true);
        $rval = $rval and $wip->id = insert_record('helpdesk_status', $wip, true);
        $rval = $rval and $closed->id = insert_record('helpdesk_status', $closed, true);
        $rval = $rval and $resolved->id = insert_record('helpdesk_status', $resolved, true);
        $rval = $rval and $reopen->id = insert_record('helpdesk_status', $reopen, true);
        $rval = $rval and $nmi->id = insert_record('helpdesk_status', $nmi, true);
        $rval = $rval and $ip->id = insert_record('helpdesk_status', $ip, true);

        // If one failed, we're doomed.
        if (!$rval) {
            error('Error adding statuses to the status table.');
        }

        // Here is the complex part. We need to do some default mappings here.
        // From New
        // For Answerer
        $rval = $rval and $this->add_status_path($new, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($new, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($new, $resolved, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($new, $closed, HELPDESK_CAP_ANSWER);
        // For Asker
        $rval = $rval and $this->add_status_path($new, $wip, HELPDESK_CAP_ASK);
        $rval = $rval and $this->add_status_path($new, $closed, HELPDESK_CAP_ASK);

        // From WIP
        // For Answerer.
        $rval = $rval and $this->add_status_path($wip, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($wip, $closed, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($wip, $resolved, HELPDESK_CAP_ANSWER);
        // For Asker.
        $rval = $rval and $this->add_status_path($wip, $closed, HELPDESK_CAP_ASK);

        // From Need More Info.
        // For Answerer.
        $rval = $rval and $this->add_status_path($nmi, $ip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($nmi, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($nmi, $closed, HELPDESK_CAP_ANSWER);
        // For Asker.
        $rval = $rval and $this->add_status_path($nmi, $ip, HELPDESK_CAP_ASK);
        $rval = $rval and $this->add_status_path($nmi, $closed, HELPDESK_CAP_ASK);

        // From Info Provided.
        // For Answerer
        $rval = $rval and $this->add_status_path($ip, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($ip, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($ip, $closed, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($ip, $resolved, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($ip, $closed, HELPDESK_CAP_ANSWER);

        // From Closed.
        // For Answerers.
        $rval = $rval and $this->add_status_path($closed, $reopen, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($closed, $reopen, HELPDESK_CAP_ASK);

        // From Resolved.
        // For Answerers.
        $rval = $rval and $this->add_status_path($resolved, $reopen, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($resolved, $reopen, HELPDESK_CAP_ASK);

        // From reopen.
        // For Answerers.
        $rval = $rval and $this->add_status_path($reopen, $wip, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($reopen, $nmi, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($reopen, $closed, HELPDESK_CAP_ANSWER);
        $rval = $rval and $this->add_status_path($reopen, $resolved, HELPDESK_CAP_ANSWER);
        // For Askers.
        $rval = $rval and $this->add_status_path($reopen, $closed, HELPDESK_CAP_ASK);

        // We're also doomed if we can't add all of the mappings.
        if ($rval == false) {
            error('Error adding status paths.');
        }

        return $rval;
    }

    /**
     * This creates a path from one status to another based on capability.
     *
     * @param object    $from is a status record. This defines the inital 
     *                  status.
     * @param object    $to is a status record of the possible next status.
     * @param string    $capability is a capability string to check users by.
     * @return bool
     */
    function add_status_path($from, $to, $capability) {
        $obj = new stdClass;
        $obj->fromstatusid = $from->id;
        $obj->tostatusid = $to->id;
        $obj->capabilityname = $capability;
        return insert_record('helpdesk_status_path', $obj);
    }

    /**
     * This method get the default ticket status from the database.
     *
     * @return object
     */
    function get_default_status() {
        return get_record('helpdesk_status', 'ticketdefault', 1);
    }

    /**
     * This method gets the possible status changes from a given status. Can 
     * also manually specify a specific capability. User's capability will be 
     * used if $cap is null.
     *
     * @param mixed     $status is a status id or status object.
     * @param string    $cap is the name of the capability.
     * @return array
     */
    function get_status_paths($status, $cap=null) {
        $id = null;
        if (is_object($status)) {
            $id = $status->id;
        } else if (is_numeric($status)) {
            $id = $status;
        } else {
            return false;
        }

        if (is_null($cap)) {
            $cap = helpdesk_is_capable();
        }
        
        $capid = get_field('capabilities', 'id', 'name', $cap);
    }

    /**
     * Cron method that runs with block's cron.
     *
     * @return true
     */
    function cron() {
        $this->email_idle();
        return true;
    }

    /**
     * Emails assignees of tickets for being idle.
     *
     * @return bool
     */
    private function email_idle() {
        $idle = get_config(null, 'block_helpdesk_ticket_idle_dur');
        if ($idle == 0) {
            return true;
        }

        // Fetches all idle tickets based on config settings.
        $tickets = $this->get_idle_tickets();

        // We must email assignees only.
        $admin = get_admin();
        foreach($tickets as $ticket) {
            $this->email_idle_notification($ticket);
            $update = new stdClass();
            $update->type = HELPDESK_UPDATE_TYPE_SYSTEM;
            $update->notes = get_string('idleemailsent', 'block_helpdesk');
            $update->status = HELPDESK_NATIVE_UPDATE_COMMENT;
            $update->userid = $admin->id;
            $update->hidden = false;
            $ticket->add_update($update);
        }
        return true;
    }

    private function get_idle_tickets($userid=null, $offset='', $count='') {
        // Askers can get their own tickets only.
        $duration = get_config(null, 'block_helpdesk_ticket_idle_dur');
        if ($duration === 0 or $duration == false) {
            return true;
        }

        if ($userid == $USER->id) {
            helpdesk_is_capable(HELPDESK_CAP_ASK, true);
        } else {
            helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);
        }

        $now = time();
        $before = $now - (3600 * $duration);

        $where = "timemodified <= $before";
        
        if ($userid != null) {
            $where .= " AND userid = $userid";
        }

        $records = get_records_select('helpdesk_ticket', $where, 'timemodified DESC',
                                      'id, status', $offset, $count);
        
        if (empty($records)) {
            return false;
        }

        foreach($records as $record) {
            if ($record->status == HELPDESK_NATIVE_STATUS_CLOSED) {
                continue;
            }
            $ticket = $this->new_ticket();
            $ticket->get_ticket($record->id);
            $tickets[] = $ticket;
        }
        return $tickets;
    }  

    /**
     * Checks to see if an update is hidden or not.
     *
     * @param object    $update that may be hidden.
     * @return bool
     */
    function is_update_hidden($update) {
        if (!is_object($update)) {
            return false;
        }
        if ($update->hidden == null or $update->hidden == false) {
            return false;
        }
        return true;
    }

    /**
     * Will email an idle notification for a particular ticket.
     *
     * @param object    $ticket is a ticket object.
     * @return bool
     */
    function email_idle_notification($ticket) {
        global $CFG;
        if(get_config(null, 'block_helpdesk_ticket_idle_dur') == false) {
            return true;
        }
        $supportuser = new stdClass;
        $supportuser = get_admin();
        $supportuser->email = get_config(null, 'block_helpdesk_email_addr');
        $supportuser->firstname = get_config(null, 'block_helpdesk_email_name');
        $supportuser->lastname = '';

        $text = get_config(null, 'block_helpdesk_idle_content');
        $html = get_config(null, 'block_helpdesk_idle_htmlcontent');
        $emailsubject = get_config(null, 'block_helpdesk_idle_subject');

        $users = array();
        $users[] = $ticket->get_user_object();
        $assigned = $ticket->get_assigned();
        if (!empty($assigned)) {
            foreach($assigned as $user) {
                $users[] = $user;
            }
        }

        $userticketurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
        $userticketurl->param('id', $ticket->get_idstring());
        $url = $userticketurl->out();
        $link = "<a href=\"$url\">$url</a>";

        foreach($users as $user) {
            $emailtext = str_replace('!username!', fullname($user), $text);
            $emailhtml = str_replace('!username!', fullname($user), $html);
            $emailtext = str_replace('!ticketlink!', $url, $emailtext);
            $emailhtml = str_replace('!ticketlink!', $link, $emailhtml);
            $emailtext = str_replace('!supportname!', $supportuser->firstname, $emailtext);
            $emailhtml = str_replace('!supportname!', $supportuser->firstname, $emailhtml);

            $rval = email_to_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            if ($rval === false) {
                notify(get_string('failedtosendemail', 'block_helpdesk'));
            }
        }

        return true;
    }

    /**
     * Will email an update for a particular ticket.
     *
     * @param object    $to is a moodle {@link $USER} object.
     * @param string    $basic is the non-html version of the email.
     * @param string    $html is the html version of the email.
     * @return bool
     */
    function email_update($ticket) {
        global $CFG, $USER;
        if(get_config(null, 'block_helpdesk_send_update_email') == false) {
            return true;
        }
        $supportuser = new stdClass;
        $supportuser = get_admin();
        $address = get_config(null, 'block_helpdesk_email_addr');
        if (!empty($address)) {
            $supportuser->email = $address;
        }
        $firstname = get_config(null, 'block_helpdesk_email_name');
        if (!empty($firstname)) {
            $supportuser->firstname = $firstname;
        }
        $supportuser->lastname = '';

        $text = get_config(null, 'block_helpdesk_email_content');
        $html = get_config(null, 'block_helpdesk_email_htmlcontent');
        $emailsubject = get_config(null, 'block_helpdesk_email_subject');
        if(empty($text)) {
            $text = get_string('emaildefaultmsgtext', 'block_helpdesk');
            set_config('block_helpdesk_email_content', $text);
        }
        if(empty($html) or strlen($html) == 0) {
            $html = '';
        }
        if(empty($emailsubject)) {
            $emailsubject = get_string('emaildefaultsubject', 'block_helpdesk');
            set_config('block_helpdesk_email_subject', $emailsubject);
        }
        $emailsubject = str_replace('!ticketid!', $ticket->get_idstring(), $emailsubject);

        $users = array();
        $users[] = $ticket->get_user_object();
        $assigned = $ticket->get_assigned();
        if (!empty($assigned)) {
            foreach($assigned as $user) {
                $users[] = $user;
            }
        }

        $userticketurl = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
        $userticketurl->param('id', $ticket->get_idstring());
        $url = $userticketurl->out();
        $link = "<a href=\"$url\">$url</a>";

        $updates = $ticket->get_updates(true);
        $lastupdate = end($updates);

        foreach($users as $user) {
            // Dont send an email to the person making the update.
            if($user->id == $USER->id) {
                continue;
            }
            // Don't send an email if the user can't see a hidden update.
            if (!helpdesk_is_capable(HELPDESK_CAP_ANSWER, false, $user) AND
                $lastupdate->hidden == true) {
                continue;
            }
            $emailtext = str_replace('!username!', fullname($user), $text);
            $emailhtml = str_replace('!username!', fullname($user), $html);
            $emailtext = str_replace('!ticketlink!', $url, $emailtext);
            $emailhtml = str_replace('!ticketlink!', $link, $emailhtml);
            $emailtext = str_replace('!supportname!', $supportuser->firstname, $emailtext);
            $emailhtml = str_replace('!supportname!', $supportuser->firstname, $emailhtml);
            $emailtext = str_replace('!updatetime!', helpdesk_get_date_string(time()), $emailtext);
            $emailhtml = str_replace('!updatetime!', helpdesk_get_date_string(time()), $emailhtml);
            
            $rval = email_to_user($user, $supportuser, $emailsubject, $emailtext, $emailhtml);
            if ($rval === false) {
                notify(get_string('failedtosendemail', 'block_helpdesk'));
            }
        }

        return true;
    }

    /**
     * This provides extra fields for the block configuration that are specific 
     * to the plugin.
     *
     * @param object    $settings is a reference to the settings variable in the 
     *                  help desk settings.php file.
     * @return bool
     */
    function plugin_settings(&$settings) {
        global $CFG;

        $settings->add(new admin_setting_heading('block_helpdesk_plugin',
                                                 get_string('pluginsettings', 'block_helpdesk'),
                                                 get_string('pluginsettingsdesc', 'block_helpdesk')));

        $settings->add(new admin_setting_configcheckbox('block_helpdesk_show_firstcontact',
                                                        get_string('showfirstcontact', 'block_helpdesk'),
                                                        get_string('showfirstcontactdesc', 'block_helpdesk'),
                                                        '0', '1', '0'));
        
        $settings->add(new admin_setting_configcheckbox('block_helpdesk_send_update_email',
                                                        get_string('sendemailupdate', 'block_helpdesk'),
                                                        get_string('sendemailupdatedesc', 'block_helpdesk'),
                                                        '0', '1', '0'));

        //$settings->add(new admin_setting_configcheckbox('block_helpdesk_get_email_tickets',
        //                                                get_string('getemailtickets', 'block_helpdesk'),
        //                                                get_string('getemailticketsdesc', 'block_helpdesk'),
        //                                                '0', '1', '0'));

        $settings->add(new admin_setting_configtext('block_helpdesk_email_addr',
                                                    get_string('emailaddr', 'block_helpdesk'),
                                                    get_string('emailaddrdesc', 'block_helpdesk'),
                                                    '', PARAM_TEXT, 28));

        $settings->add(new admin_setting_configpasswordunmask('block_helpdesk_email_passwd',
                                                              get_string('emailpasswd', 'block_helpdesk'),
                                                              get_string('emailpasswddesc', 'block_helpdesk'),
                                                              '', PARAM_TEXT, 28));

        $settings->add(new admin_setting_configtext('block_helpdesk_email_name',
                                                    get_string('emailname', 'block_helpdesk'),
                                                    get_string('emailnamedesc', 'block_helpdesk'),
                                                    '', PARAM_TEXT));

        $settings->add(new admin_setting_configtext('block_helpdesk_email_subject',
                                                    get_string('emailsubject', 'block_helpdesk'),
                                                    get_string('emailsubjectdesc', 'block_helpdesk'),
                                                    '', PARAM_TEXT));

        $settings->add(new admin_setting_configtextarea('block_helpdesk_email_content',
                                                        get_string('emailcontent', 'block_helpdesk'),
                                                        get_string('emailcontentdesc', 'block_helpdesk'),
                                                        '', PARAM_RAW));

        $base = 'admin_setting_config';
        $class = ($CFG->version >= 2007101590.00) ? "{$base}htmltextarea" : "{$base}textarea";
        $settings->add(new $class('block_helpdesk_email_htmlcontent',
                                  get_string('emailrtfcontent', 'block_helpdesk'),
                                  get_string('emailrtfcontentdesc', 'block_helpdesk'),
                                  '', PARAM_RAW));

        
        //$settings->add(new admin_setting_configtext('block_helpdesk_ticket_idle_dur',
        //                                            get_string('emailidlewait', 'block_helpdesk'),
        //                                            get_string('emailidlewaitdesc', 'block_helpdesk'),
        //                                            '0', PARAM_INT, 4));
        return true;
    }

    /**
     * This retrieves a series of unassigned tickets in the form of
     * a list that could be put into "pages." This is called primarily
     * by $this->get_tickets(). This method will either return an array
     * of records, or a false if no records exist.
     *
     * @param int       $offset Which record for the set to begin at.
     * @param int       @count Number of records to get.
     * @return mixed
     */
    private function get_unassigned_tickets($offset, $count) {
        // Answer capability required.
        helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);

        $records = get_records('helpdesk_ticket', 'assigned_refs', 0, 'timemodified DESC',
                               '*', $offset, $count);

        if (empty($records)) {
            return false;
        }

        foreach($records as $record) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($record->id);
            $ticket->fetch();
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * This is a unique function to this class. This is a method for retrieving
     * a series of status-specific tickets in the form of a list that could be put
     * into "pages." This is called primarily by $this->get_tickets(). This
     * method will either return an array of records, or a false if no records
     * exists.
     *
     * @param int       $status Numerical representation of a specific status.
     * @param int       $offset Which record for the set to begin at.
     * @param int       @count Number of records to get.
     * @return mixed
     */
    private function get_status_tickets($status, $offset='', $count='') {
        global $CFG;

        if (is_numeric($status)) {
            $status = get_record('helpdesk_status', 'id', $status);
        }

        if (!is_object($status)) {
            error('Invalid status passed to get_status_tickets().');
        }

        $sqlas = sql_as();
        $sql = "SELECT t.*
                FROM {$CFG->prefix}helpdesk_ticket $sqlas t
                    JOIN {$CFG->prefix}helpdesk_status $sqlas s
                        ON t.status = s.id
                WHERE s.id = {$status->id}";

        $records = get_records_sql($sql, $offset, $count);

        foreach($records as $record) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($record->id);
            $ticket->fetch();
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * This method gets inactive tickets along with other searchable criteria 
     * for future use. This will get tickets with statuses that are marked as 
     * inactive. This always includes "closed" and "resolved" tickets.
     *
     * @param int       $offset is the offset of the sql query, used for paging.
     * @param int       $count number of records to get.
     * @param int       $userid specifies whos tickets we're getting.
     * @param int       $assigneduserid specifies tickets we're getting and who 
     *                  they're assigned to.
     * @return mixed
     */
    private function get_inactive_tickets($offset='', $count='', $userid=null,
                                          $assigneduserid=null) {
        global $CFG;
        $sqlas = sql_as();
        $sql = "SELECT t.*
                FROM {$CFG->prefix}helpdesk_ticket $sqlas t
                    JOIN {$CFG->prefix}helpdesk_status $sqlas s
                        ON t.status = s.id
                    JOIN {$CFG->prefix}helpdesk_ticket_assignments $sqlas a
                        ON t.id = a.ticketid
                WHERE s.active = 0";
        if (!empty($userid)) {
            $sql .= " AND t.userid = $userid";
        }

        if (!empty($assigneduserid)) {
            $sql .= " AND a.userid = $assigneduserid";
        }

        $records = get_records_sql($sql, $offset, $count);

        if (empty($records)) {
            return false;
        }

        foreach($records as $record) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($record->id);
            $ticket->fetch();
            $tickets[] = $ticket;
        }

        return $tickets;
    }

    /**
     * This is nice and all and will provide us with a method to get all the
     * useable relations. A $cap is required to get the right list. We don't
     * want users who can't be assigned to look at assigned tickets, that kind
     * of idea. We will do this now and not later.
     *
     * @param string    $cap is a capability of any particular user.
     * @return array
     */
    function get_ticket_relations($cap) {
        // We need a capability. We're not happy giving relation lists to just
        // anyone, so we have to test for this.
        if (empty($cap)) {
            return false;
        }
        // All users (except empty($cap) ones) get this.
        // Since we only have askers and answerers and answers have all the caps
        // that an asker has, we'll add these now.
        $relations = array(HELPDESK_NATIVE_REL_REPORTEDBY);
        if ($cap == HELPDESK_CAP_ASK) {
            return $relations;
        }

        // Currently there should be no reason that a value other than
        // HELPDESK_CAP_ANSWER should get to this point.
        if ($cap != HELPDESK_CAP_ANSWER) {
            error(get_string('unexpectedcapability', 'block_helpdesk'));
        }

        $relations[] = HELPDESK_NATIVE_REL_ALL;
        $relations[] = HELPDESK_NATIVE_REL_ASSIGNEDTO;
        $relations[] = HELPDESK_NATIVE_REL_NEW;
        $relations[] = HELPDESK_NATIVE_REL_CLOSED;
        $relations[] = HELPDESK_NATIVE_REL_UNASSIGNED;
        return $relations;
    }

    /**
     * Determine which relation should be used by default to list
     * tickets for a user to see.
     *
     * @return string
     */
    function get_default_relation($cap=null) {
        switch($cap) {
        case HELPDESK_CAP_ANSWER:
            return HELPDESK_NATIVE_REL_ASSIGNEDTO;
        default:
            return HELPDESK_NATIVE_REL_REPORTEDBY;
        }
        return HELPDESK_NATIVE_REL_REPORTEDBY;
    }

    /**
     * This is simple for the native plugin, but may be more complex for 
     * other back-ends.
     *
     * @param mixed     $rel Relation to get the string for.
     * @return string
     */
    function get_relation_string($rel) {
        return get_string($rel, 'block_helpdesk');
    }

    /**
     * Retrieve a list of user assigned tickets. Returns either an array 
     * of records, or false if no records exist.
     *
     * @param int       $userid ID of a user to get assigned tickets from.
     * @param int       $offset Which record for the set to begin at.
     * @param int       @count Number of records to get.
     * @return mixed
     */
    private function get_assigned_tickets($userid, $offset, $count) {
        global $CFG;
        // Only answerers can be 'assigned', which is different from watching.
        helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);

        $sql = "SELECT t.id
                FROM {$CFG->prefix}helpdesk_ticket " . sql_as() . " t
                    JOIN {$CFG->prefix}helpdesk_ticket_assignments " . sql_as() . " a
                        ON a.ticketid = t.id
                    JOIN {$CFG->prefix}helpdesk_status " . sql_as() . " s
                        ON t.status = s.id
                WHERE a.userid = $userid AND s.active = 1
                ORDER BY t.timemodified DESC";

        $recordset = get_recordset_sql($sql, $offset, $count);
        
        while($record = rs_fetch_next_record($recordset)) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($record->id);
            $ticket->fetch();
            $tickets[] = $ticket;
        }
        if (empty($tickets)) {
            return false;
        }
        return $tickets;
    }

    /**
     * Retrieve a list of user-specific tickets. This method will either
     * return an array of records, or false if no records exist.
     *
     * @param int       $userid ID of a user to get assigned tickets from.
     * @param int       $offset Which record for the set to begin at.
     * @param int       @count Number of records to get.
     * @return mixed
     */
    private function get_user_tickets($userid, $offset, $count) {
        global $USER;
        // Askers can get their own tickets only.
        if ($userid == $USER->id) {
            helpdesk_is_capable(HELPDESK_CAP_ASK, true);
        } else {
            helpdesk_is_capable(HELPDESK_CAP_ANSWER, true);
        }

        $records = get_records('helpdesk_ticket', 'userid', $userid,
                               'timemodified DESC', '*', $offset, $count);
        
        if (empty($records)) {
            return false;
        }

        foreach($records as $record) {
            $ticket = $this->new_ticket();
            $ticket->get_ticket($record->id);
            $tickets[] = $ticket;
        }
        return $tickets;
    }

    /**
     * Get tickets with a $userid when applicable, a specified relation,
     * an offset and count for page-like viewing. Generally speaking,
     * this calls the other get methods, this method itself just
     * switches the relation. Will return an array of ticket objects or
     * false if no tickets were found.
     *
     * @param int       $userid id of a user if applicable.
     * @param string    $rel is a relation string that describes a users
     * relation to a series of tickets.
     * @param int       $offset is where the records begin.
     * @param int       $count is how many records (max) to return.
     * @return mixed
     */
    function get_tickets($userid, $rel, $offset=0, $count=10) {
        switch($rel) {
        case HELPDESK_NATIVE_REL_REPORTEDBY:
            $tickets = $this->get_user_tickets($userid, $offset, $count);
            break;
        case HELPDESK_NATIVE_REL_ASSIGNEDTO:
            $tickets = $this->get_assigned_tickets($userid, $offset, $count);
            break;
        case HELPDESK_NATIVE_REL_NEW:
            $status = get_field('helpdesk_status', 'id', 'name', 'new');
            $tickets = $this->get_status_tickets($status, $offset, $count);
            break;
        case HELPDESK_NATIVE_REL_CLOSED:
            $tickets = $this->get_inactive_tickets($offset, $count);
            break;
        case HELPDESK_NATIVE_REL_UNASSIGNED:
            $tickets = $this->get_unassigned_tickets($offset, $count);
            break;
        case HELPDESK_NATIVE_REL_ALL:
            $tickets = $this->get_all_tickets($offset, $count);
            break;
        default:
            return false;
        }
        if (empty($tickets)) {
            return false;
        }
        return $tickets;
    }

    /**
     * Counts the records for a given type of ticket set. This count the number
     * of records instead of returning tickets like get_tickets(). Returns an
     * int, or false (not zero) if it failed.
     *
     * @param string    $userid User id if relation calls for it.
     * @param int       $rel relation id to get tickets by.
     * @return mixed
     */
    function get_tickets_count($userid, $rel) {
        global $CFG;
        if (!isset($userid)) {
            error(__FUNCTION__ . ': Invalid userid.');
        }
        if (!isset($rel)) {
            error(__FUNCTION__ . ': Invalid relation.');
        }
        $as = sql_as();
        switch($rel) {
        case HELPDESK_NATIVE_REL_REPORTEDBY:
            return count_records('helpdesk_ticket', 'userid', $userid);
        case HELPDESK_NATIVE_REL_ASSIGNEDTO:
            $sql = "SELECT COUNT(t.id) $as count
                    FROM {$CFG->prefix}helpdesk_ticket $as t
                        JOIN {$CFG->prefix}helpdesk_ticket_assignments $as a ON a.ticketid = t.id
                        JOIN {$CFG->prefix}helpdesk_status $as s ON t.status = s.id
                    WHERE a.userid = $userid AND s.active = 1";

            $r = get_record_sql($sql);
            return $r->count;
        case HELPDESK_NATIVE_REL_NEW:
            $sql = "SELECT COUNT(t.id) $as count
                    FROM {$CFG->prefix}helpdesk_ticket $as t
                        JOIN {$CFG->prefix}helpdesk_status $as s ON t.status = s.id
                    WHERE s.name = 'new'";
            $r = get_record_sql($sql);
            return $r->count;
        case HELPDESK_NATIVE_REL_UNASSIGNED:
            return count_records('helpdesk_ticket', 'assigned_refs', '0');
        case HELPDESK_NATIVE_REL_ALL:
            return count_records('helpdesk_ticket');
        default:
            return false;
        }
    }

    /**
     * This is an overriden method which returns a newly constructed
     * helpdesk_ticket_native object.
     *
     * @return object
     */
    function new_ticket() {
        return new helpdesk_ticket_native();
    }

    /**
     * Gets a ticket object with a given idstring, false otherwise.
     *
     * @param string    $id idstring of a ticket.
     * @return mixed
     */
    function get_ticket($id) {
        $ticket = $this->new_ticket();
        if(!$ticket->get_ticket($id)) {
            return false;
        }
        return $ticket;
    }

    /**
     * This method searches tickets across multiple fields to find a match
     * according to a specific string. Basically we're "and"ing all the words
     * together and checking a bunch of stuff. We will get a mixed result, false
     * if unsucessful, or an array of tickets if we find matches.
     *
     * @return mixed
     */
    function search($string) {
        // We need to search tickets and related values for the anded values of
        // these 'words'. We're going to pull it apart based on word.
        $like = sql_ilike();
        $cname = '[' . sha1(rand()) . md5(rand()) . ']';

        $words = explode(' ', $string);
        if (!$words) {
            notify(get_string('nosearchstring', 'block_helpdesk'));
            return false;
        }

        $where = array();
        foreach($words as $word) {
            // We're going to be replacing column spot and we need something so
            // unique that it will never show up as a word.
            $where[] = "$cname $like '%$word%'";
        }
        $where = '(' . implode(' AND ', $where) . ')';

        // The searching begins here. From here we're going to be querying
        // a number of tables to try and find what the user wants.
        $totalrecordsfound = array();

        $colwhere = str_replace($cname, 'summary', $where) . ' OR ' .
                                str_replace($cname, 'detail', $where);
        if (($rval = get_records_select('helpdesk_ticket', $colwhere, '', 'id')) !== false) {
            foreach($rval as $row) {
                $totalrecordsfound[] = $row->id;
            }
        }

        $colwhere = str_replace($cname, 'value', $where);
        if (($rval = get_records_select('helpdesk_ticket_tag', $colwhere, '', 'ticketid')) !== false) {
            foreach($rval as $row) {
                $totalrecordsfound[] = $row->ticketid;
            }
        }

        $colwhere = str_replace($cname, 'notes', $where);
        if (($rval = get_records_select('helpdesk_ticket_update', $colwhere, '', 'ticketid')) !== false) {
            foreach($rval as $row) {
                $totalrecordsfound[] = $row->ticketid;
            }
        }

        if (count($totalrecordsfound) == 0) {
            return false;
        }

        $totalrecordsfound = array_unique($totalrecordsfound, SORT_NUMERIC);
        $tickets = array();
        foreach($totalrecordsfound as $id) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($id);
            if ($ticket->fetch(false)) {
                $tickets[] = $ticket;
            }
        }

        return $tickets;
    }

    function change_overview_form($ticket) {
        global $CFG;
        $id = $ticket->get_idstring();
        if (empty($id)) {
            return false;
        }
        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/edit.php");
        $url->param('id', $ticket->get_idstring());

        $user = get_record('user', 'id', $ticket->get_userid());

        $form = new change_overview_form($url->out(), null, 'post', '', null, true, $ticket);

        if (!$form->is_submitted()) {
            $data = array(
                'summary' => $ticket->get_summary(),
                'detail' => $ticket->get_detail(),
                'status' => $ticket->get_status(),
                'userid' => $ticket->get_userid(),
                'username' => fullname($user)
                );
            $form->set_data($data);
        }
        return $form;
    }

    /**
     * Overridden method to return a moodle form object for a new ticket.
     *
     * @return object
     */
    function new_ticket_form($data=null) {
        global $CFG;
        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/new.php");
        $form = new new_ticket_form($url);
        if (!is_array($data)) {
            // Do nothing.
        } elseif (!empty($data['tags'])) {
            foreach ($data['tags'] as $key => $tag) {
                $form->addHidden($key, $tag);
                #$url->param($key, $tag);
            }
            $tags = implode(',', array_flip($data['tags']));
            $form->addHidden('tags', $tags);
            #$url->param('tags', $tags);
        }
        return $form;
    }

    /**
     * Overridden method to return a moodle form for searching.
     *
     * @return object
     */
    function search_form() {
        global $CFG;
        return new search_form("$CFG->wwwroot/blocks/helpdesk/search.php", null, 'post');
    }

    /**
     * Overridden method which creates and returns a moodle form for updating
     * a ticket. This method takes in one parameter, where $data is a ticket
     * object that belongs to the ticket that the update is being added to.
     *
     * @param object    $data Ticket that is being updated.
     * @return object
     */
    function update_ticket_form($data) {
        global $CFG;

        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/update.php");
        $url->param('id', $data->get_idstring());
        $form = new update_ticket_form($url->out(), null, 'post');
        $form->add_status($data);
        if (helpdesk_is_capable(HELPDESK_CAP_ANSWER)) {
            $form->add_hidden();
        }
        $form->add_submit();
        return $form;
    }

    /**
     * Help Desk method which creates and returns a moodle form for adding a tag
     * to a ticket.
     *
     * @param object    $ticket is a ticket object that this tag will belong to.
     * @return object
     */
    function tag_ticket_form($ticketid) {
        global $CFG;
        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/tag.php");
        $url->param('tid', $ticketid);
        $form = new tag_ticket_form($url->out(), null, 'post');
        return $form;
    }

    /**
     * Get all the tickets in the help desk system.
     *
     * @param int       $offset determines where to start getting records.
     * @param int       $count determines the maxiumum records to return.
     * @return mixed
     */
    function get_all_tickets($offset, $count) {
        $records = get_records('helpdesk_ticket', '', '', 'timemodified DESC', '*',
                               $offset, $count);
        return $this->parse_db_tickets($records);
    }

    /**
     * This is an overridden method which takes in an array of records returned
     * by moodle and turns them into ticket objects. Only the ID field is
     * required in the record param. Returns false if none, or an array of
     * ticket objects.
     *
     * @param array     $records Records of tickets with an id field.
     * @return mixed
     */
    function parse_db_tickets($records) {
        if ($records == false or !is_array($records)) {
            return false;
        }
        $tickets = array();
        foreach($records as $record) {
            $ticket = $this->new_ticket();
            $ticket->set_idstring($record->id);
            if(!$ticket->fetch()) {
                return false;
            }
            $tickets[] = $ticket;
        }
        if (empty($tickets)) {
            return false;
        }
        return $tickets;
    }

    /**
     * This plugin supports tags, and includes some by default.
     *
     * Returns a moodle_url object for the 'submitnewticket' link in the block.
     *
     * @return object
     */
    function default_submit_url() {
        global $COURSE, $CFG;
        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/new.php");
        $site = get_site();
        if ($site->id != $COURSE->id) {
            $url->param('tags', 'url,coursename');
            $url->param('url', qualified_me());
            $url->param('coursename', $COURSE->fullname);
        }
        return $url;
    }

}


?>

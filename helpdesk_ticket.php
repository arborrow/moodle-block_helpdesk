<?php

/**
 * Abstract helpdesk_ticket class. This defines the layout that a ticket must
 * have and sets a layout and structure for any given ticket for a particular
 * helpdesk plugin.
 *
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class helpdesk_ticket {
    /**
     * This method displays a ticket for any particular plugin.
     *
     * @param object    $ticket is a ticket object to be outputted as html.
     * @return string
     */
    abstract function display_ticket($readonly=false);

    /**
     * fetches a ticket based on data already in the
     * object. This is determined by the overriding function. Should return
     * false if the fetch fails, and true if it gets a ticket.
     *
     * @param bool      $permissionhalt will error out if the user doesn't have 
     *                  access, setting to false will just return false.
     * @return bool
     */
    abstract function fetch($permissionhalt=true);

    /**
     * stores this ticket object back into the database.
     * This method should return false if it fails, and true if it completes
     * properly.
     *
     * @return bool
     */
    abstract function store();

    /**
     * Static returns an instance of a ticket with a given
     * id string. Should return false if no ticket with a respective id string,
     * or the ticket object itself if a ticket is found.
     *
     * @return mixed
     */
    abstract public function get_ticket($id);

    /**
     * Gets the user of the ticket in the form of a db record with relevant
     * fields. Some plugins may need to override this.
     *
     * @return object
     */
    function get_user_object() {
        return helpdesk_get_user($this->get_userid());
    }

    /**
     * This abstract parse method is supposed to parse an object with
     * associated values into a ticket. This is very generalized. Should return
     * true if there are no issues or false if the parse fails.
     *
     * @param object    $data Input object to parse into a ticket.
     * @return bool
     */
    abstract function parse($data);

    /**
     * Abstract method for setting the id string of a ticket. This is an
     * unusual function and shouldn't be used on a ticket that already exists.
     * Should return true or false depending on its success.
     *
     * @param string    $id The id string to assign to this particular ticket
     *                  object.
     * @return bool
     */
    abstract function set_idstring($id);

    /**
     * Abstract method for setting the summary of a ticket. Should return true on
     * success and false of failure.
     *
     * @param string    $string The string that is the summary of the ticket.
     * @return bool
     */
    abstract function set_summary($string);

    /**
     * Abstract method for setting the detail string on a specific ticket.
     * Returns true if successful and false if the set failed.
     *
     * @param string    $string The string that is the detail field of the
     *                  ticket.
     * @return bool
     */
    abstract function set_detail($string);

    /**
     * Abstract method for setting the time created. This should only be set to
     * the current time, so no parameters are taken. Should return true unless
     * the set fails, in which case should return a false.
     *
     * @return bool
     */
    abstract function set_timecreated();

    /**
     * Abstract method for setting the time modified. This should only be set to
     * the current time when the method is called. Should return true, unless
     * the set fails, in which case should return a false.
     *
     * @return bool
     */
    abstract function set_timemodified();

    /**
     * Set the status of a ticket.
     *
     * @param string    $status is a status string to be set.
     * @return bool
     */
    abstract function set_status($status);

    /**
     * Methods sets the userid of a question.
     *
     * @param int       $id is a userid.
     * @return bool
     */
    abstract function set_userid($id);

    /**
     * Abstract method used to return an id string. This method should return
     * false if there is no id string.
     *
     * @return mixed
     */
    abstract function get_idstring();

    /**
     * Abstract method used to return the ticket's summary. This method should
     * return false if there is no summary.
     *
     * @return mixed
     */
    abstract function get_summary();

    /**
     * Abstract method used to return the ticket's detail string. This method
     * should return false if there is no detail string.
     *
     * @return mixed
     */
    abstract function get_detail();

    /**
     * Abstract method used to return the time the ticket was created. If there
     * is no time created, the method should return false.
     *
     * @return mixed
     */
    abstract function get_timecreated();

    /**
     * Abstract method used to return the time the ticket was modified. If there
     * is no time modified, the method should return false.
     *
     * @return mixed
     */
    abstract function get_timemodified();

    /**
     * should return a status string in Moodle's set
     * language. An argument may be passed to get the status string, instead of
     * the ticket's specific status string.
     *
     * @return string
     */
    abstract function get_status_string($status=null);

    /**
     * Returns current ticket status.
     *
     * @return string
     */
    abstract function get_status();

    /**
     * returns all the tags for a ticket. If there are no
     * tags, this method should return false.
     *
     * @return mixed
     */
    abstract function get_tags();

    /**
     * returns all the updates for a ticket. If there are
     * no updates, this method should return false.
     *
     * @param bool      $includehidden if true will include hidden updates.
     * @return mixed
     */
    abstract function get_updates($includehidden=false);

    /**
     * returns the user id of the user that submitted the
     * ticket. If no userid exists, then false is returned, but should
     * only be in the case of a ticket that hasn't been stored in the database
     * yet.
     *
     * @return mixed
     */
    abstract function get_userid();

    /**
     * Abstract method for adding an update to a ticket. This method returns
     * true if successful, and false if the add failed.
     *
     * @param mixed     $update Update data to be parsed to be turned into an
     *                  update for a specific ticket.
     * @return bool
     */
    abstract function add_update($update);

    /**
     * Abstract method for adding a tag to a ticket. This method should return
     * a true if successful and false if unsuccessful.
     *
     * @param mixed     $tag Tag data to be parsed into an update for a specific
     *                  ticket.
     * @return bool
     */
    abstract function add_tag($tag);

    /**
     * takes in an object and parses it into a tag. Returns
     * a tag if successful and false if unsuccessful.
     *
     * @param mixed     $data Data to be parsed into a tag.
     * @return mixed
     */
    abstract function parse_tag($data);

    /**
     * removes a tag based on its id. Returns true if
     * successful, otherwise returns false.
     *
     * @param mixed     $id ID of the tag to be removed.
     * @return bool
     */
    abstract function remove_tag($id);

    /**
     * update a from a modified tag object. Returns a bool
     * depending on the method's success.
     *
     * @return bool
     */
    abstract function update_tag($tag);

    /**
     * assign a user to a specific ticket. Returns a bool
     * depending on the outcome of the method.
     *
     * @param mixed     $userid User id of the user to be assigned to a specific
     *                  ticket.
     * @return bool
     */
    abstract function add_assignment($userid);

    /**
     * remove an assignment by a user id from a ticket.
     * Returns a bool depending on the outcome of the method.
     *
     * @param mixed     $userid The id of the user to be taken off the ticket.
     * @return bool
     */
    abstract function remove_assignment($userid);

    /**
     * returns the assigned users to a ticket. This should
     * return false if no users are assigned to the ticket.
     *
     * @return mixed
     */
    abstract function get_assigned();

    /**
     * This should be called when an already existing ticket is edited and is to 
     * be stored in the database. Some cases a help desk may do extra things on 
     * a ticket edit.
     *
     * @param string    $msg is the message to leave in the update associated 
     *                  with this edit.
     * @return bool
     */
    abstract function store_edit($msg=null);
}

?>

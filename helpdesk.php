<?php
/**
 * Abstract helpdesk class. This defines the layout that a helpdesk plugin must
 * have and sets a layout and structure for the helpdesk.
 *
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class helpdesk {

    /**
     * Every helpdesk has access to the moodle cron for this block. This method 
     * gets called every time cron hits the block.
     *
     * @return true
     */
    abstract function cron();

    /**
     * This method can be overridden to run tasks after the tables have been 
     * created on install.
     *
     * @return bool
     */
    function install() {
        return true;
    }

    /**
     * Depending on the helpdesk being used, we want to check to see if an
     * update is hidden or not.
     *
     * @param object    $update that may be hidden.
     * @return bool
     */
    abstract function is_update_hidden($update);

    /**
     * Returns the tickets for a sepcific user by id with a specific relation.
     *
     * @return mixed
     */
    abstract function get_tickets($userid, $rel, $offset=0, $count=10);

    /**
     * Returns the number of tickets instead of the rows.
     * It is the same as get_tickets but COUNT(*)s. Will return int if successful
     * or false (not zero) if failed.
     *
     * @param string    $userid User id if relation calls for it.
     * @param int       $relation Relation id that dictates which tickets to get.
     * @return mixed
     */
    abstract function get_tickets_count($userid, $rel);

    /**
     * returns a new ticket object.
     *
     * @return object
     */
    abstract function new_ticket();

    /**
     * gets a specific ticket by a unique id. Returns false
     * if none are found, or a ticket object of the ticket with the given id.
     *
     * @param mixed $id Ticket id to be fetched which is either a string or int.
     * @return mixed
     */
    abstract function get_ticket($id);

    /**
     * Abstract search function that either returns a false is the search is
     * empty or an array of ticket objects that the search turned up.
     *
     * @param string     $string Search string.
     * @return mixed
     */
    abstract function search($string);

    /**
     * Abstract methods that returns a new ticket form for the helpdesk's
     * respective plugin.
     *
     * @param array     $data is an array of stuff to be used by the plugin, 
     *                  such as new ticket tags.
     * @return moodleform
     */
    abstract function new_ticket_form($data=null);

    /**
     * Returns a moodleform object.
     *
     * @param array     $ticket This form takes in a ticket object.
     * @return mixed
     */
    abstract function update_ticket_form($ticket);

    /**
     * creates and returns a moodleform object for adding a tag
     * to the ticket object passed to it.
     *
     * @param object    $ticketid ID of the ticket that a tag is being added to.
     * @return object
     */
    abstract function tag_ticket_form($ticketid);

    /**
     * create and returns a moodleform object for searching
     * tickets.
     *
     * @return object
     */
    abstract function search_form();

    /**
     * Generates a form for changing ticket overview details.
     *
     * @return object
     */
    abstract function change_overview_form($ticket);

    /**
     * A factory function to return a constructed helpdesk from a selected plugin
     * in the configuration of the helpdesk.
     *
     * @return object
     */
    public final static function get_helpdesk() {
        global $CFG;
        $plugin = 'native';
        if(isset($CFG->helpdesk_plugin) and strlen($CFG->helpdesk_plugin) > 0) {
            $plugin = $CFG->helpdesk_plugin;
        }
        $class = "helpdesk_$plugin";

        $initpath = "{$CFG->dirroot}/blocks/helpdesk/plugins/$plugin/init.php";

        if (!file_exists($initpath)){
            error(get_string('missingpluginfile', 'helpdesk_block') . ": init.php");
        }

        require_once($initpath);

        return new $class;
    }

    public function display_ticket($ticket, $readonly=false) {
        if (method_exists($ticket, 'fetch')) {
            return $ticket->display_ticket($readonly);
        }
        return false;
    }

    // Relation methods start here!

    /**
     * Gets ticket relations for a specific plugin and capability.
     */
    abstract function get_ticket_relations($cap);

    /**
     * Gets the default relation for a specified plugin.
     *
     * @return string
     */
    abstract function get_default_relation($cap=null);

    /**
     * Gets a language string from a relation string.
     *
     * @param string    $rel ation string to convert to a human readable string.
     * @return string
     */
    abstract function get_relation_string($rel);

    /**
     * Get the default URL to submit a ticket for this plugin.
     * Plugins can use this to collect available context data in the ticket by 
     * default, without requiring user participation on those points.
     *
     * Returns a moodle_url object for the 'submitnewticket' link in the block.
     *
     * @return object
     */
    abstract function default_submit_url();
}

?>

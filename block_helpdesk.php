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
 * This script extends a moodle block_base and is the entry point for all 
 * helpdesk  ability.
 *
 * @package     block_helpdesk
 * @copyright   2010 VLACS
 * @author      Jonathan Doane <jdoane@vlacs.org>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') or die("Direct access to this location is not allowed.");
require_once("$CFG->dirroot/blocks/helpdesk/lib.php");

class block_helpdesk extends block_base {
    var $hd;
    /**
     * Overridden block_base method. All this method does is sets the block's 
     * title and version.
     *
     * @return null
     */
    function init() {
        $this->title = get_string('helpdesk', 'block_helpdesk');
        $this->version = 2010091400;
        $this->cron = 1;
    }

    /**
     * This overridden method gets called after the block's tables are created.
     *
     * @return bool
     */
    function after_install() {
        $hd = helpdesk::get_helpdesk();
        $rval = $hd->install();
    }

    /**
     * Overridden block_base method. This generates the content in the body of 
     * the block and returns it.
     *
     * @return string
     */
    function get_content() {
        global $CFG, $USER;
        // Get objects initialized and variables declared.

        $this->content = new stdClass;

        // First thing is first, user must have some form of capbility on the 
        // helpdesk. Otherwise they shouldn't be able to access it.
        $cap = helpdesk_is_capable();
        $this->content->text = '';
        $this->content->footer = '';
        $noticketstr = get_string('noticketstodisplay', 'block_helpdesk');
        if ($cap == false) {
            return $this->content;
        }

        // Lets get the helpdesk initialized.
        $hd = helpdesk::get_helpdesk();

        // Show assigned ticket if answerer.
        if ($cap == HELPDESK_CAP_ANSWER) {
            $title = '<h3>' . get_string('myassignedtickets', 'block_helpdesk') . '</h3>';
            $this->content->text .= $title;

            $tickets = $hd->get_tickets($USER->id, $hd->get_default_relation(HELPDESK_CAP_ANSWER), 0, 5);
            if (is_array($tickets)) {
                $this->content->text .= "<ul>";
                foreach($tickets as $ticket) {
                    $summary = $ticket->get_summary();
                    if(strlen($summary) > 12) {
                        $summary = substr($summary, 0, 12) . '...';
                    }
                    $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
                    $url->param('id', $ticket->get_idstring());
                    $url = $url->out();
                    $this->content->text .= "<li>
                                             <a href=\"$url\">
                                                $summary (" . $ticket->get_status_string() . ")
                                             </a>
                                         </li>";
                }
                $this->content->text .= '</ul>';
            } else {
                $this->content->text .= notify($noticketstr, 'notifyproblem', 'center', true);
            }
        }

        // Print my tickets title. Block itself just displays first 5 user 
        // tickets. Other tickets are found in ticket listing.
        $this->content->text .= '<h3>' . get_string('mytickets', 'block_helpdesk') . '</h3>';

        // Grab the tickets to display and add to the content.
        $tickets = $hd->get_tickets($USER->id, $hd->get_default_relation(), 0, 5);
        if (is_array($tickets)) {
            $this->content->text .= '<ul>';
            foreach($tickets as $ticket) {
                $summary = $ticket->get_summary();
                if(strlen($summary) > 12) {
                    $summary = substr($summary, 0, 12) . '...';
                }
                $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/view.php");
                $url->param('id', $ticket->get_idstring());
                $url = $url->out();
                $this->content->text .= "<li>
                                            <a href=\"$url\">
                                                $summary (" . $ticket->get_status_string() . ')
                                            </a>
                                         </li>';
            }
            $this->content->text .= '</ul>';
        } else {
            $this->content->text .= notify($noticketstr, 'notifyproblem', 'center', true);
        }

        // Link for viewing all kinds of tickets.
        $url = "$CFG->wwwroot/blocks/helpdesk/view.php";
        $text = get_string('viewalltickets', 'block_helpdesk');
        $this->content->text .= "<a href=\"$url\">$text</a><br />";

        // Link for submitting a new ticket.
        $url = $hd->default_submit_url()->out();
        $text = get_string('submitnewticket', 'block_helpdesk');
        $this->content->text .= "<a href=\"$url\">$text</a><br /><br />";

        // print a footer.
        $url = new moodle_url("$CFG->wwwroot/blocks/helpdesk/preferences.php");
        $url = $url->out();
        $translated = get_string('preferences');
        $prefhelp = helpdesk_simple_helpbutton($translated, 'pref');
        $this->content->footer = "<a href=\"$url\">$translated</a>$prefhelp";

        return $this->content;
    }

    /**
     * This is an overriden method. This method is called when Moodle's cron 
     * runs. Currently this method does nothing and returns nothing.
     *
     * @return null
     */
    function cron() {
        $hd = helpdesk::get_helpdesk();
        if(!$hd->cron()) {
            notify('Warning: Plugin cron returned non-true value.');
        }
    }

    function instance_allow_multiple() {
        return false;
    }

    function has_config() {
        return true;
    }

    function instance_allow_config() {
        return false;
    }
}
?>

<?php
function xmldb_block_helpdesk_upgrade($oldversion = 0) {
    global $db, $CFG;
    $result = true;
    require_once("$CFG->dirroot/blocks/helpdesk/lib.php");

    // Any older version at this point.
    if ($oldversion < 2010082700) {
        // Create Ticket Groups Table.
        $table = new XMLDBTable('helpdesk_ticket_group');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL,
                             null, null, null, null);
        $table->addFieldInfo('description', XMLDB_TYPE_TEXT, 'big', null, null,
                             null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

        // Create Status Table
        $table = new XMLDBTable('helpdesk_status');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL,
                             null, null, null, null);
        $table->addFieldInfo('displayname', XMLDB_TYPE_CHAR, '255', null, null,
                             null, null, null, null);
        $table->addFieldInfo('core', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
                             null, null, null, null, null);
        $table->addFieldInfo('whohasball', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             null, null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

        // Create Rule Table.
        $table = new XMLDBTable('helpdesk_rule');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL,
                             null, null, null, null);
        $table->addFieldInfo('statusid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('newstatusid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             null, null, null, null, null);
        $table->addFieldInfo('duration', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('sendemail', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('plainemailbody', XMLDB_TYPE_TEXT, 'big', null, null,
                             null, null, null, null);
        $table->addFieldInfo('htmlemailbody', XMLDB_TYPE_TEXT, 'big', null, null,
                             null, null, null, null);

        // Create Rule Email Table
        $table = new XMLDBTable('helpdesk_rule_email');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('ruleid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('userassoc', XMLDB_TYPE_INTEGER, '5', null,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

        // Now lets add new fields to...
        // Ticket table, groupid
        $table = new XMLDBTable('helpdesk_ticket');
        $field = new XMLDBField('groupid');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null,
                              null, null, null, null, 'status');
        $result = $result && add_field($table, $field);

        //Ticket table, first contact.
        $table = new XMLDBTable('helpdesk_ticket');
        $field = new XMLDBField('firstcontact');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null,
                              null, null, null, null, 'groupid');
        $result = $result && add_field($table, $field);
    } 

    // Statuses are being move to the database here!
    if ($oldversion < 2010091400) {
        // Add status path table.
        $table = new XMLDBTable('helpdesk_status_path');
        $table->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null);
        $table->addFieldInfo('fromstatusid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addFieldInfo('tostatusid', XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED,
                             null, null, null, null, null);
        $table->addFieldInfo('capabilityname', XMLDB_TYPE_CHAR, '255', null,
                             XMLDB_NOTNULL, null, null, null, null);
        $table->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'));
        $result = $result && create_table($table);

        // New fields in status.
        // ticketdefault field.
        $table = new XMLDBTable('helpdesk_status');
        $field = new XMLDBField('ticketdefault');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, null, null,
                              null, null, null, 'whohasball');
        $result = $result && add_field($table, $field);

        // active field.
        $table = new XMLDBTable('helpdesk_status');
        $field = new XMLDBField('active');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                              null, null, null, null, 'ticketdefault');
        $result = $result && add_field($table, $field);

        // We have to convert all the old style statuses over to new statuses. 
        // We don't like legacy data in the database. With that said, we need to 
        // populate all the statuses, which is normally done when the block is 
        // installed. (for all versions starting with this one.)
        $hd = helpdesk::get_helpdesk();
        $hd->install();
        // Lets grab some stuff from the db first.
        $new    = get_field('helpdesk_status', 'id', 'name', 'new');
        $wip    = get_field('helpdesk_status', 'id', 'name', 'workinprogress');
        $closed = get_field('helpdesk_status', 'id', 'name', 'closed');

        // Now our statuses are installed. We're ready to convert legacy to 
        // current. This could potentially use a lot of memory.
        $table = new XMLDBTable('helpdesk_ticket');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_CHAR, '255', null, null,
                              null, null, null, null, 'assigned_refs');
        $result = $result && rename_field($table, $field, 'oldstatus');

        $table = new XMLDBTable('helpdesk_ticket');
        $field = new XMLDBField('status');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, XMLDB_NOTNULL,
                              null, null, null. null, $new);
        $result = $result && add_field($table, $field);

        // We want to update all tickets without doing them all at once. Some 
        // systems may have limited memory.
        $chunksize = 100;       // 100 Records at a time.
        $ticketcount = count_records('helpdesk_ticket');

        // Lets grab all the statuses so we can convert the old ones. This 
        // shouldn't be *too* bad.


        // Lets change all tickets to the new status. WOO!
        // We may be able to simplify this.
        $sql = "UPDATE {$CFG->prefix}helpdesk_ticket
                SET status = $new
                WHERE oldstatus = 'new'";
        $result = $result && execute_sql($sql);

        $sql = "UPDATE {$CFG->prefix}helpdesk_ticket
                SET status = $wip
                WHERE oldstatus = 'inprogress'";
        $result = $result && execute_sql($sql);

        $sql = "UPDATE {$CFG->prefix}helpdesk_ticket
                SET status = $closed
                WHERE oldstatus = 'closed'";
        $result = $result && execute_sql($sql);

        // At this point, we're done. Lets get rid of the extra field in the 
        // database that has the old statuses.
        $table = new XMLDBTable('helpdesk_ticket');
        $field = new XMLDBField('oldstatus');
        $result = $result && drop_field($table, $field);

        // Lets not forget that we're storing status changes now.
        // So we need that field added to updates.
        $table = new XMLDBTable('helpdesk_ticket_update');
        $field = new XMLDBField('newticketstatus');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '20', XMLDB_UNSIGNED, null,
                              null, null, null, null, null);
        $result = $result && add_field($table, $field);
    } 

    return $result;
}
?>

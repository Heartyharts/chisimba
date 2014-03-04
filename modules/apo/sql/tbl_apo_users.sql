<?php
    $tablename = 'tbl_apo_users';
    $options = array('comment' => 'Table for saving user information', 'collate' => 'utf8_general_ci', 'character_set' => 'utf8');
    $fields = array(
                'id' => array('type' => 'text','length' => 32, 'notnull'=>TRUE),
                'name' => array('type' => 'text', 'notnull'=>TRUE),
                'date_created' => array('type' => 'date', 'notnull'=>TRUE),
                'userid' => array('type' => 'text','length' => 15, 'notnull'=>TRUE),
                'department' => array('type' => 'text','length' => 32, 'notnull'=>TRUE),
                'role' => array('type' => 'text','length' => 32, 'notnull'=>TRUE),
                'email' => array('type' => 'text','length' => 32, 'notnull'=>TRUE),
                'telephone' => array('type' => 'text','length' => 32, 'notnull'=>TRUE),
                'deleted' => array('type' => 'text','length' => 1),
                'level' => array('type' => 'text','length' => 1),
                'path' => array('type' => 'text')
             );
?>
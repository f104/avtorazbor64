<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class EventLog extends \Brevis\Model\EventLog
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'event_log',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'user_id' => NULL,
            'timestamp' => 'CURRENT_TIMESTAMP',
            'category' => NULL,
            'subject_id' => NULL,
            'message' => NULL,
        ),
        'fieldMeta' => 
        array (
            'user_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'timestamp' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ),
            'category' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '20',
                'phptype' => 'string',
                'null' => false,
            ),
            'subject_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'message' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false,
            ),
        ),
        'aggregates' => 
        array (
            'User' => 
            array (
                'class' => '\\Brevis\\Model\\User',
                'local' => 'user_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}

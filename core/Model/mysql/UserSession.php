<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class UserSession extends \Brevis\Model\UserSession
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'user_sessions',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'session_id' => NULL,
            'user_id' => NULL,
            'access' => 'CURRENT_TIMESTAMP',
        ),
        'fieldMeta' => 
        array (
            'session_id' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => false,
            ),
            'user_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'access' => 
            array (
                'dbtype' => 'timestamp',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ),
        ),
        'indexes' => 
        array (
            'UNIQUE' => 
            array (
                'alias' => 'PRIMARY',
                'primary' => true,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'session_id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
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
            ),
        ),
    );
}

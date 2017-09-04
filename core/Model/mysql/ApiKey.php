<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class ApiKey extends \Brevis\Model\ApiKey
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'api_keys',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'email' => NULL,
            'salt' => NULL,
        ),
        'fieldMeta' => 
        array (
            'email' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
            'salt' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '20',
                'null' => false,
            ),
        ),
        'indexes' => 
        array (
            'email' => 
            array (
                'alias' => 'email',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'email' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );
}

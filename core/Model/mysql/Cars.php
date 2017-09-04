<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Cars extends \Brevis\Model\Cars
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'cars',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'mark_key' => '',
            'mark_name' => '',
            'model_key' => '',
            'model_name' => '',
            'year_key' => '',
            'year_name' => '',
            'year_start' => 0,
            'year_finish' => 0,
        ),
        'fieldMeta' => 
        array (
            'mark_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '2',
                'null' => false,
                'default' => '',
            ),
            'mark_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
                'default' => '',
            ),
            'model_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '3',
                'null' => false,
                'default' => '',
            ),
            'model_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
                'default' => '',
            ),
            'year_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '2',
                'null' => false,
                'default' => '',
            ),
            'year_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
                'default' => '',
            ),
            'year_start' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '4',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
            'year_finish' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '4',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'mark_key' => 
            array (
                'alias' => 'mark_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'mark_key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'model_key' => 
            array (
                'alias' => 'model_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'model_key' => 
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

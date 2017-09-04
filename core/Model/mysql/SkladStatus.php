<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class SkladStatus extends \Brevis\Model\SkladStatus
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'sklad_statuses',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'order' => NULL,
            'description' => NULL,
            'show' => 0,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
            'order' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'description' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
            'show' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'name' => 
            array (
                'alias' => 'name',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'name' => 
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
            'Sklads' => 
            array (
                'class' => '\\Brevis\\Model\\Sklad',
                'local' => 'id',
                'foreign' => 'status_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'validation' => 
        array (
            'rules' => 
            array (
                'name' => 
                array (
                    'required' => 
                    array (
                        'type' => 'xPDOValidationRule',
                        'rule' => 'xPDOMinLengthValidationRule',
                        'value' => '1',
                        'message' => 'Это обязательное поле',
                    ),
                ),
            ),
        ),
    );
}

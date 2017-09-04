<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class PaymentType extends \Brevis\Model\PaymentType
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'payment_types',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'active' => 0,
        ),
        'fieldMeta' => 
        array (
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => false,
            ),
            'active' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'int',
                'precision' => '1',
                'null' => false,
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
        'composites' => 
        array (
            'Payments' => 
            array (
                'class' => '\\Brevis\\Model\\Payments',
                'local' => 'id',
                'foreign' => 'type_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );
}

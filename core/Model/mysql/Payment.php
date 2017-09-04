<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Payment extends \Brevis\Model\Payment
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'payments',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'type_id' => NULL,
            'order_id' => NULL,
            'timestamp' => 'CURRENT_TIMESTAMP',
            'sum' => 0,
            'comment' => NULL,
        ),
        'fieldMeta' => 
        array (
            'type_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'order_id' => 
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
            'sum' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'default' => 0,
            ),
            'comment' => 
            array (
                'dbtype' => 'varshar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
        ),
        'aggregates' => 
        array (
            'PaymentType' => 
            array (
                'class' => '\\Brevis\\Model\\PaymentType',
                'local' => 'type_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Order' => 
            array (
                'class' => '\\Brevis\\Model\\Order',
                'local' => 'order_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}

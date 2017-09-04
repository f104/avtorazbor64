<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class ItemImages extends \Brevis\Model\ItemImages
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'item_images',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'item_id' => NULL,
            'item_key' => NULL,
            'filename' => NULL,
            'url' => NULL,
            'prefix' => NULL,
            'binary' => NULL,
            'hash' => NULL,
            'order' => 0,
        ),
        'fieldMeta' => 
        array (
            'item_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'item_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '16',
                'null' => false,
            ),
            'filename' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '30',
            ),
            'url' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'prefix' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '4',
                'phptype' => 'string',
                'null' => false,
            ),
            'binary' => 
            array (
                'dbtype' => 'longtext',
                'phptype' => 'string',
            ),
            'hash' => 
            array (
                'dbtype' => 'varshar',
                'phptype' => 'string',
                'precision' => '64',
                'null' => true,
            ),
            'order' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'model_key' => 
            array (
                'alias' => 'item_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'item_id' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                    'item_key' => 
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
            'Item' => 
            array (
                'class' => '\\Brevis\\Model\\Item',
                'local' => 'item_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}

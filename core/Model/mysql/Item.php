<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Item extends \Brevis\Model\Item
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'items',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'mark_key' => NULL,
            'model_key' => NULL,
            'year_key' => NULL,
            'category_key' => NULL,
            'element_key' => NULL,
            'counter' => NULL,
            'name' => NULL,
            'kol' => 1,
            'price' => NULL,
            'prefix' => NULL,
            'code' => NULL,
            'vendor_code' => NULL,
            'published' => 0,
            'sklad_id' => NULL,
            'supplier_id' => 0,
            'moderate' => 0,
            'moderate_message' => NULL,
            'reserved' => 0,
            'updatedon' => NULL,
            'condition' => 0,
            'condition_comment' => NULL,
            'source' => 'site',
            'error' => NULL,
            'body_type' => 0,
            'comment' => NULL,
            'remote_key' => NULL,
            'remote_id' => 0,
        ),
        'fieldMeta' => 
        array (
            'mark_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '2',
                'null' => false,
            ),
            'model_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '3',
                'null' => false,
            ),
            'year_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '2',
                'null' => false,
            ),
            'category_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '1',
                'null' => false,
            ),
            'element_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '4',
                'null' => false,
            ),
            'counter' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '4',
                'null' => false,
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => false,
            ),
            'kol' => 
            array (
                'dbtype' => 'int',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 1,
            ),
            'price' => 
            array (
                'dbtype' => 'int',
                'precision' => '11',
                'phptype' => 'integer',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'prefix' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '4',
                'phptype' => 'string',
                'null' => false,
            ),
            'code' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '16',
                'null' => false,
            ),
            'vendor_code' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '20',
                'null' => true,
            ),
            'published' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'default' => 0,
                'null' => false,
            ),
            'sklad_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'supplier_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'moderate' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'default' => 0,
                'null' => false,
            ),
            'moderate_message' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'reserved' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'default' => 0,
                'null' => false,
            ),
            'updatedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'datetime',
                'null' => true,
            ),
            'condition' => 
            array (
                'dbtype' => 'smallint',
                'phptype' => 'integer',
                'precision' => '2',
                'null' => false,
                'default' => 0,
            ),
            'condition_comment' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'source' => 
            array (
                'dbtype' => 'enum',
                'precision' => '\'1C\',\'site\',\'remote\'',
                'phptype' => 'string',
                'null' => false,
                'default' => 'site',
            ),
            'error' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
            'body_type' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'comment' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'remote_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'remote_id' => 
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
            'code' => 
            array (
                'alias' => 'code',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'code' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
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
            'year_key' => 
            array (
                'alias' => 'year_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'year_key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'category_key' => 
            array (
                'alias' => 'category_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'category_key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'element_key' => 
            array (
                'alias' => 'element_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'element_key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'remote_key' => 
            array (
                'alias' => 'remote_key',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'remote_key' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => true,
                    ),
                ),
            ),
            'remote_id' => 
            array (
                'alias' => 'remote_id',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'remote_id' => 
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
            'Images' => 
            array (
                'class' => '\\Brevis\\Model\\ItemImages',
                'local' => 'id',
                'foreign' => 'item_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'Cars' => 
            array (
                'class' => 'Cars',
                'local' => 'mark_key',
                'foreign' => 'mark_key',
                'cardinality' => 'many',
            ),
            'Category' => 
            array (
                'class' => '\\Brevis\\Model\\Category',
                'local' => 'category_key',
                'foreign' => 'key',
                'cardinality' => 'one',
            ),
            'Element' => 
            array (
                'class' => '\\Brevis\\Model\\Element',
                'local' => 'element_key',
                'foreign' => 'key',
                'cardinality' => 'one',
            ),
            'Sklad' => 
            array (
                'class' => '\\Brevis\\Model\\Sklad',
                'local' => 'sklad_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Supplier' => 
            array (
                'class' => '\\Brevis\\Model\\Supplier',
                'local' => 'supplier_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'BodyType' => 
            array (
                'class' => '\\Brevis\\Model\\BodyType',
                'local' => 'body_type',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Condition' => 
            array (
                'class' => '\\Brevis\\Model\\Condition',
                'local' => 'condition',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Order' => 
            array (
                'class' => '\\Brevis\\Model\\Order',
                'local' => 'id',
                'foreign' => 'item_id',
                'cardinality' => 'one',
                'owner' => 'local',
            ),
        ),
    );
}

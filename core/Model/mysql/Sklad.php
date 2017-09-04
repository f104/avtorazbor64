<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Sklad extends \Brevis\Model\Sklad
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'sklads',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'prefix' => NULL,
            'locked' => 0,
            'updatedon' => '0000-00-00 00:00:00',
            'name' => NULL,
            'status_id' => 2,
            'status_message' => NULL,
            'supplier_id' => NULL,
            'address' => NULL,
            'switchon' => 1,
            'country_id' => 1,
            'region_id' => 0,
            'city_id' => 0,
            'additional_emails' => NULL,
        ),
        'fieldMeta' => 
        array (
            'prefix' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '4',
                'phptype' => 'string',
                'null' => true,
            ),
            'locked' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'updatedon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'timestamp',
                'null' => false,
                'default' => '0000-00-00 00:00:00',
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '50',
                'phptype' => 'string',
                'null' => true,
            ),
            'status_id' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '3',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 2,
            ),
            'status_message' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
            'supplier_id' => 
            array (
                'dbtype' => 'int',
                'precision' => '10',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => true,
            ),
            'address' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '255',
                'phptype' => 'string',
                'null' => true,
            ),
            'switchon' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 1,
            ),
            'country_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 1,
            ),
            'region_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'city_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'additional_emails' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
        ),
        'indexes' => 
        array (
            'prefix' => 
            array (
                'alias' => 'prefix',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'prefix' => 
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
            'Items' => 
            array (
                'class' => '\\Brevis\\Model\\Item',
                'local' => 'id',
                'foreign' => 'sklad_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'Status' => 
            array (
                'class' => '\\Brevis\\Model\\SkladStatus',
                'local' => 'status_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreigh',
            ),
            'Supplier' => 
            array (
                'class' => '\\Brevis\\Model\\Supplier',
                'local' => 'supplier_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreigh',
            ),
            'Country' => 
            array (
                'class' => '\\Brevis\\Model\\Country',
                'local' => 'country_id',
                'foreign' => 'id',
                'cardinality' => 'one',
            ),
            'Region' => 
            array (
                'class' => '\\Brevis\\Model\\Region',
                'local' => 'region_id',
                'foreign' => 'id',
                'cardinality' => 'one',
            ),
            'City' => 
            array (
                'class' => '\\Brevis\\Model\\City',
                'local' => 'city_id',
                'foreign' => 'id',
                'cardinality' => 'one',
            ),
        ),
    );
}

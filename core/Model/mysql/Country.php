<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Country extends \Brevis\Model\Country
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'countries',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'iso' => NULL,
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
            'iso' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '4',
                'null' => false,
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
            'Regions' => 
            array (
                'class' => '\\Brevis\\Model\\Region',
                'local' => 'id',
                'foreign' => 'country_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Cities' => 
            array (
                'class' => '\\Brevis\\Model\\City',
                'local' => 'id',
                'foreign' => 'country_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'Suppliers' => 
            array (
                'class' => '\\Brevis\\Model\\Supplier',
                'local' => 'id',
                'foreign' => 'country_id',
                'cardinality' => 'many',
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

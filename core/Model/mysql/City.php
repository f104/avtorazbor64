<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class City extends \Brevis\Model\City
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'cities',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'country_id' => 1,
            'region_id' => 11,
            'name' => NULL,
        ),
        'fieldMeta' => 
        array (
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
                'default' => 11,
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
        ),
        'aggregates' => 
        array (
            'Country' => 
            array (
                'class' => '\\Brevis\\Model\\Country',
                'local' => 'country_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Region' => 
            array (
                'class' => '\\Brevis\\Model\\Region',
                'local' => 'region_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Users' => 
            array (
                'class' => '\\Brevis\\Model\\User',
                'local' => 'id',
                'foreign' => 'city_id',
                'cardinality' => 'many',
            ),
            'Suppliers' => 
            array (
                'class' => '\\Brevis\\Model\\Supplier',
                'local' => 'id',
                'foreign' => 'city_id',
                'cardinality' => 'many',
            ),
            'Sklads' => 
            array (
                'class' => '\\Brevis\\Model\\Sklad',
                'local' => 'id',
                'foreign' => 'city_id',
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

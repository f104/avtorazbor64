<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Region extends \Brevis\Model\Region
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'regions',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'country_id' => 1,
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
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
        ),
        'composites' => 
        array (
            'Cities' => 
            array (
                'class' => '\\Brevis\\Model\\City',
                'local' => 'id',
                'foreign' => 'region_id',
                'cardinality' => 'many',
                'owner' => 'local',
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

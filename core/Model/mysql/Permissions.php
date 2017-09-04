<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Permissions extends \Brevis\Model\Permissions
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'permissions',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'key' => NULL,
            'description' => NULL,
        ),
        'fieldMeta' => 
        array (
            'key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '20',
                'null' => false,
            ),
            'description' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
        ),
        'indexes' => 
        array (
            'key' => 
            array (
                'alias' => 'key',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'key' => 
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
            'PermissionGroups' => 
            array (
                'class' => '\\Brevis\\Model\\GroupPermissions',
                'local' => 'id',
                'foreign' => 'perm_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'PermissionSuppliers' => 
            array (
                'class' => '\\Brevis\\Model\\SupplierPermission',
                'local' => 'id',
                'foreign' => 'perm_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
    );
}

<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class SupplierPermission extends \Brevis\Model\SupplierPermission
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'supplier_permissions',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'status_id' => NULL,
            'perm_id' => NULL,
        ),
        'fieldMeta' => 
        array (
            'status_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
            'perm_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
            ),
        ),
        'aggregates' => 
        array (
            'Status' => 
            array (
                'class' => '\\Brevis\\Model\\SupplierStatus',
                'local' => 'status_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
            'Permission' => 
            array (
                'class' => '\\Brevis\\Model\\Permissions',
                'local' => 'perm_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}

<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class GroupPermissions extends \Brevis\Model\GroupPermissions
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'group_permissions',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'group_id' => NULL,
            'perm_id' => NULL,
        ),
        'fieldMeta' => 
        array (
            'group_id' => 
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
            'Group' => 
            array (
                'class' => '\\Brevis\\Model\\Group',
                'local' => 'group_id',
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

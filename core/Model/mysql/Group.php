<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Group extends \Brevis\Model\Group
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'groups',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'name' => NULL,
            'description' => NULL,
            'nochange' => 0,
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
            'description' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'nochange' => 
            array (
                'dbtype' => 'tityint',
                'phptype' => 'integer',
                'attributes' => 'unsigned',
                'null' => false,
                'default' => 0,
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
            'UserGroupMembers' => 
            array (
                'class' => '\\Brevis\\Model\\UserGroupMember',
                'local' => 'id',
                'foreign' => 'group_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'GroupPermissions' => 
            array (
                'class' => '\\Brevis\\Model\\GroupPermissions',
                'local' => 'id',
                'foreign' => 'group_id',
                'cardinality' => 'many',
                'owner' => 'local',
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

<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class User extends \Brevis\Model\User
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'users',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'email' => NULL,
            'name' => NULL,
            'passhash' => NULL,
            'hash' => '',
            'active' => 0,
            'blocked' => 0,
            'createdon' => NULL,
            'lastlogin' => NULL,
            'country_id' => 1,
            'region_id' => 11,
            'city_id' => 2,
            'phone' => NULL,
            'balance' => 0,
            'buyer_level' => 1,
        ),
        'fieldMeta' => 
        array (
            'email' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
            'passhash' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
            ),
            'hash' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '32',
                'null' => false,
                'default' => '',
            ),
            'active' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'null' => false,
                'default' => 0,
            ),
            'blocked' => 
            array (
                'dbtype' => 'tinyint',
                'phptype' => 'integer',
                'precision' => '1',
                'null' => false,
                'default' => 0,
            ),
            'createdon' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'timestamp',
                'null' => false,
            ),
            'lastlogin' => 
            array (
                'dbtype' => 'datetime',
                'phptype' => 'timestamp',
                'null' => true,
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
                'default' => 11,
            ),
            'city_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 2,
            ),
            'phone' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => false,
            ),
            'balance' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '11',
                'null' => false,
                'default' => 0,
            ),
            'buyer_level' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'default' => 1,
            ),
        ),
        'indexes' => 
        array (
            'email' => 
            array (
                'alias' => 'email',
                'primary' => false,
                'unique' => true,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'email' => 
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
                'foreign' => 'user_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'UserSupplier' => 
            array (
                'class' => '\\Brevis\\Model\\Supplier',
                'local' => 'id',
                'foreign' => 'user_id',
                'cardinality' => 'one',
                'owner' => 'local',
            ),
            'Orders' => 
            array (
                'class' => '\\Brevis\\Model\\Order',
                'local' => 'id',
                'foreign' => 'user_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Fees' => 
            array (
                'class' => '\\Brevis\\Model\\Fee',
                'local' => 'id',
                'foreign' => 'user_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'EventLogs' => 
            array (
                'class' => '\\Brevis\\Model\\EventLog',
                'local' => 'id',
                'foreign' => 'user_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'UserSession' => 
            array (
                'class' => '\\Brevis\\Model\\UserSession',
                'local' => 'id',
                'foreign' => 'user_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'UserHauthServices' => 
            array (
                'class' => '\\Brevis\\Model\\UserHauthService',
                'local' => 'id',
                'foreign' => 'user_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'City' => 
            array (
                'class' => '\\Brevis\\Model\\City',
                'local' => 'city_id',
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
            'BuyerLevel' => 
            array (
                'class' => '\\Brevis\\Model\\BuyerLevel',
                'local' => 'buyer_level',
                'foreign' => 'id',
                'cardinality' => 'one',
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
                'phone' => 
                array (
                    'required' => 
                    array (
                        'type' => 'xPDOValidationRule',
                        'rule' => 'xPDOMinLengthValidationRule',
                        'value' => '1',
                        'message' => 'Это обязательное поле',
                    ),
                ),
                'email' => 
                array (
                    'email' => 
                    array (
                        'type' => 'preg_match',
                        'rule' => '/^([a-z0-9_\\.-]+)@([a-z0-9_\\.-]+)$/',
                        'message' => 'Введите email',
                    ),
                ),
            ),
        ),
    );
}

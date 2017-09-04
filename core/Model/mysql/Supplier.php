<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class Supplier extends \Brevis\Model\Supplier
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'suppliers',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'user_id' => 0,
            'name' => NULL,
            'code' => NULL,
            'status_id' => 1,
            'status_message' => NULL,
            'user_pos' => NULL,
            'company_name' => NULL,
            'country_id' => 1,
            'region_id' => 0,
            'city_id' => 0,
            'index' => NULL,
            'legal_address' => NULL,
            'actual_address' => NULL,
            'email' => NULL,
            'phone' => NULL,
            'fax' => NULL,
            'inn' => NULL,
            'kpp' => NULL,
            'dir_name' => NULL,
            'buch_name' => NULL,
            'bank_name' => NULL,
            'bik' => NULL,
            'r_schet' => NULL,
            'k_schet' => NULL,
            'okved' => NULL,
            'okpo' => NULL,
            'ogrn' => NULL,
            'comment' => NULL,
        ),
        'fieldMeta' => 
        array (
            'user_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 0,
            ),
            'name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'code' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '8',
                'null' => false,
            ),
            'status_id' => 
            array (
                'dbtype' => 'int',
                'phptype' => 'integer',
                'precision' => '10',
                'null' => false,
                'attributes' => 'unsigned',
                'default' => 1,
            ),
            'status_message' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '500',
                'null' => true,
            ),
            'user_pos' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'company_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
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
            'index' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '10',
                'null' => true,
            ),
            'legal_address' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'actual_address' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'email' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'phone' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'fax' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'inn' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '12',
                'null' => true,
            ),
            'kpp' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '9',
                'null' => true,
            ),
            'dir_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'buch_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'bank_name' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => true,
            ),
            'bik' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '9',
                'null' => true,
            ),
            'r_schet' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'k_schet' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'okved' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'okpo' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'ogrn' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '50',
                'null' => true,
            ),
            'comment' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
        ),
        'indexes' => 
        array (
            'code' => 
            array (
                'alias' => 'code',
                'primary' => false,
                'unique' => true,
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
        ),
        'composites' => 
        array (
            'Items' => 
            array (
                'class' => '\\Brevis\\Model\\Item',
                'local' => 'id',
                'foreign' => 'supllier_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
            'Sklad' => 
            array (
                'class' => '\\Brevis\\Model\\Sklad',
                'local' => 'id',
                'foreign' => 'supplier_id',
                'cardinality' => 'many',
                'owner' => 'local',
            ),
        ),
        'aggregates' => 
        array (
            'User' => 
            array (
                'class' => '\\Brevis\\Model\\User',
                'local' => 'user_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
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
            'Status' => 
            array (
                'class' => '\\Brevis\\Model\\SupplierStatus',
                'local' => 'status_id',
                'foreign' => 'id',
                'cardinality' => 'one',
                'owner' => 'foreign',
            ),
        ),
    );
}

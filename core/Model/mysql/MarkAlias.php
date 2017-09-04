<?php
namespace Brevis\Model\mysql;

use xPDO\xPDO;

class MarkAlias extends \Brevis\Model\MarkAlias
{

    public static $metaMap = array (
        'package' => 'Brevis\\Model',
        'version' => '3.0',
        'table' => 'mark_aliases',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'fields' => 
        array (
            'mark_key' => '',
            'alias' => '',
        ),
        'fieldMeta' => 
        array (
            'mark_key' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '2',
                'null' => false,
                'default' => '',
            ),
            'alias' => 
            array (
                'dbtype' => 'varchar',
                'phptype' => 'string',
                'precision' => '255',
                'null' => false,
                'default' => '',
            ),
        ),
    );
}

<?php
return [
    'detail' => [

        'id'         => [
            'name'     => 'id',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'int(11)',
        ],
        'type'       => [
            'name'     => 'type',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'status'     => [
            'name'     => '状态',
            'tip'      => '',
            'type'     => 'checkbox',
            'sql_type' => 'int(1)',
        ],
        'name'       => [
            'name'     => '名称',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'created_at' => [
            'name'     => '创建时间',
            'tip'      => '',
            'type'     => 'timestamp',
            'sql_type' => 'timestamp',
        ],
        'updated_at' => [
            'name'     => '修改时间',
            'tip'      => '',
            'type'     => 'timestamp',
            'sql_type' => 'timestamp',
        ],
    ],
];

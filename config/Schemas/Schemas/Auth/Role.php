<?php
return [
    'detail' => [

        'id' => [
            'name' => 'id',
            'tip' => '',
            'type' => 'text',
            'sql_type' => 'int(10) unsigned',
        ],
        'name' => [
            'name' => '角色名',
            'tip' => '',
            'type' => 'text',
            'sql_type' => 'varchar(255)',
        ],

        'actions' => [
            'name' => '角色',
            'tip' => '',
            'type' => 'select',
            'sql_type' => 'varchar(255)',
            'link' => [
                'model' => [
                    'belongsToMany',
                    'App\\Models\\Auth\\AuthAction',
                ],
                'select' => ['id', 'name'],
                'field' => ['id', 'name','resource','action','param'],
            ],
        ],

        'scopes' => [
            'name' => '作用域',
            'tip' => '',
            'type' => 'select',
            'sql_type' => 'varchar(255)',
            'link' => [
                'model' => [
                    'belongsToMany',
                    'App\\Models\\Auth\\AuthScope',
                ],
                'as'=>'scopes',
                'select' => ['id', 'name','model','scope','rule'],
                'field' => ['id', 'name','model','scope','rule'],
            ],
        ],

        'status' => [
            'name' => '有效',
            'tip' => '',
            'type' => 'checkbox',
            'sql_type' => 'tinyint(1)',
        ],
        'created_at' => [
            'name' => '创建时间',
            'tip' => '',
            'type' => 'timestamp',
            'sql_type' => 'timestamp',
        ],
        'updated_at' => [
            'name' => '修改时间',
            'tip' => '',
            'type' => 'timestamp',
            'sql_type' => 'timestamp',
        ],
        'tree_nav' => [
            'name' => 'tree_nav',
            'tip' => '',
            'type' => 'text',
            'sql_type' => 'varchar(255)',
        ],
    ],
];
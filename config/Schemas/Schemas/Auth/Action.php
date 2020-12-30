<?php
return [
    'detail'=>[
        
            'id'=>[
                'name'=>'id',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'int(10) unsigned',
            ],
            'name'=>[
                'name'=>'角色名',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
            ],
            'resource'=>[
                'name'=>'资源',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
            ],
            'action'=>[
                'name'=>'操作',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
            ],
            'param'=>[
                'name'=>'参数',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
            ],
            'rule'=>[
                'name'=>'其他规则',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
            ],
            'status'=>[
                'name'=>'有效',
                'tip'=>'',
                'type'=>'number',
                'sql_type'=>'tinyint(1)',
            ],
            'created_at'=>[
                'name'=>'created_at',
                'tip'=>'',
                'type'=>'timestamp',
                'sql_type'=>'timestamp',
            ],
            'updated_at'=>[
                'name'=>'updated_at',
                'tip'=>'',
                'type'=>'timestamp',
                'sql_type'=>'timestamp',
            ],

        'roles' => [
            'name' => '角色',
            'tip' => '',
            'type' => 'select',
            'sql_type' => 'varchar(255)',
            'link' => [
                'model' => [
                    'belongsToMany',
                    'App\\Models\\Auth\\AuthRole',
                ],
                'select' => ['id', 'name'],
                'field' => ['id', 'name'],
            ],
        ],
    ],
];
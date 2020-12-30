<?php
return [
    'detail'=>[
        
            'id'=>[
                'name'=>'编号',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'int(10) unsigned',
            ],
            'type'=>[
                'name'=>'类型',
                'tip'=>'',
                'type'=>'select',
                'sql_type'=>'tinyint(3) unsigned',
                'option'=>[1=>'总管理',2=>'普通管理',3=>'第三方管理',4=>'客服管理']
            ],
            'user_id'=>[
                'name'=>'用户',
                'tip'=>'',
                'type'=>'select',
                'sql_type'=>'bigint(20) unsigned',
                'link' => [
                    'model' => [
                        'hasOne',
                        'App\\Models\\Common\\CommonUser',
                        'id',
                        'user_id',
                    ],
                    'select' => ['id','name','phone'],
                    'field' => ['id','name','phone'],
                ],
            ],
            'status'=>[
                'name'=>'启用',
                'tip'=>'',
                'type'=>'checkbox',
                'sql_type'=>'varchar(255)',
            ],
            'site'=>[
                'name'=>'站点',
                'tip'=>'',
                'type'=>'select',
                'sql_type'=>'int(10) unsigned',
                'link' => [
                    'model' => [
                        'belongsTo',
                        'App\\Models\\System\\SystemSite',
                        'site',
                        'id',
                    ],
                    'select' => ['id','name'],
                    'field' => ['id','name'],
                ],
            ],
            'created_at'=>[
                'name'=>'注册时间',
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
        'roles'=>[
            'name'=>'角色',
            'tip'=>'',
            'type'=>'select',
            'sql_type'=>'varchar(255)',
            'link' => [
                'model' => [
                    'belongsToMany',
                    'App\\Models\\Auth\\AuthRole',
                ],
                'select' => ['id','name'],
                'fields' => ['id','name'],
                'as'=>'roles',
            ],
        ],

    ],
];
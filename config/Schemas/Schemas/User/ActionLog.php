<?php
return [
    'detail'=>[
        
            'id'=>[
                'name'=>'编号',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'int(10) unsigned',
            ],
            'user_id'=>[
                'name'=>'用户编号',
                'tip'=>'',
                'type'=>'select',
                'sql_type'=>'int(11)',
                'link' => [
                    'model' => [
                        'belongsTo',
                        'App\\Models\\Common\\CommonUser',
                        'user_id',
                        'id',
                    ],
                    'select' => ['id', 'name'],
                    'field' => ['id', 'name'],
                ],
            ],
            'user_type'=>[
                'name'=>'用户类型',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'int(10) unsigned',
            ],
            'type'=>[
                'name'=>'类型',
                'tip'=>'',
                'type'=>'select',
                'sql_type'=>'tinyint(3) unsigned',
                'option'=>[0=>'添加',1=>'修改']
            ],
            'ip'=>[
                'name'=>'ip',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(15)',
            ],
            'from'=>[
                'name'=>'操作来源',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(15)',
            ],
            'site'=>[
                'name'=>'站点',
                'tip'=>'',
                'type'=>'select',
                'sql_type'=>'varchar(5)',
                'link' => [
                    'model' => [
                        'belongsTo',
                        'App\\Models\\System\\SystemSite',
                        'site',
                        'id',
                    ],
                    'select' => ['id', 'name'],
                    'field' => ['id', 'name'],
                ],
            ],
            'after_content'=>[
                'name'=>'操作前',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'text',
            ],
            'before_content'=>[
                'name'=>'操作后',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'text',
            ],
            'title'=>[
                'name'=>'操作',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
            ],
            'model'=>[
                'name'=>'模块',
                'tip'=>'',
                'type'=>'text',
                'sql_type'=>'varchar(255)',
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
    ],
];
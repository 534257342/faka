<?php
return [
    'detail' => [
        'id'             => [
            'name'     => '编号',
            'tip'      => '',
            'type'     => 'number',
            'sql_type' => 'bigint(20) unsigned',
        ],
        'name'           => [
            'name'     => '姓名',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'avatar'         => [
            'name'     => '头像',
            'tip'      => '',
            'type'     => 'image',
            'sql_type' => 'varchar(255)',
        ],
        'company_name'   => [
            'name'     => '企业名称',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'company_code'   => [
            'name'     => '企业编号',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'company_type'   => [
            'name'   => '企业类型',
            'tip'    => '',
            'type'   => 'select',
            'option' => [0 => '公司', 1 => '个体']
        ],
        'company_pic'    => [
            'name'     => '营业执照',
            'tip'      => '',
            'type'     => 'image',
            'sql_type' => 'varchar(255)',
        ],

        'sex'=>[
            'name'=>'关联',
            'tip'=>'',
            'type'=>'muilticheckbox',
            'link'=>[
                'model'=>[
                    'belongsToMany',
                    'App\\Models\\Common\\CommonBanner',
                ],
                'select'=>['id','name'],
                'field'=>['id','name'],//lon修改处
            ],
            'multi'=>true,
        ],

        'company_log'    => [
            'name'     => '企业logo',
            'tip'      => '',
            'type'     => 'image',
            'sql_type' => 'varchar(255)',
        ],
/*        'sex'            => [
            'name'     => '性别',
            'tip'      => '',
            'type'     => 'select',
            'sql_type' => 'tinyint(3) unsigned',
            'option'   => [0 => '未知', 1 => '男', 2 => '女']
        ],*/
        'api_token'      => [
            'name'     => 'token值',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(64)',
        ],

        'phone'          => [
            'name'     => '电话',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(11)',
            'rule'     => ['required', 'min:11' => '最小11位', 'max:11' => '最大11位']
        ],
        'email'          => [
            'name'     => '邮箱',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'id_number'      => [
            'name'     => '身份证号',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'id_pic_front'   => [
            'name'     => '身份证照前',
            'tip'      => '',
            'type'     => 'image',
            'sql_type' => 'varchar(255)',
        ],
        'id_pic_behind'  => [
            'name'     => '身份证照后',
            'tip'      => '',
            'type'     => 'image',
            'sql_type' => 'varchar(255)',
        ],
        'city'           => [
            'name'     => '城市',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'money'          => [
            'name'     => '金钱',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],

        'commission'     => [
            'name'     => '手续费(百分比 0.xx)',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'password'       => [
            'name'     => '密码',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'roles'          => [
            'name'     => '角色',
            'tip'      => '',
            'type'     => 'select',
            'sql_type' => 'varchar(255)',
            'link'     => [
                'model'  => [
                    'belongsToMany',
                    'App\\Models\\Auth\\AuthRole',
                ],
                'select' => ['id', 'name'],
                'fields' => ['id', 'name'],
            ],
        ],
        'status'         => [
            'name'     => '状态',
            'tip'      => '',
            'type'     => 'checkbox',
            'sql_type' => 'int(3)',
        ],
        'remember_token' => [
            'name'     => 'remember_token',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(100)',
        ],
        'banner_id'  => [
            'name' => '关联',
            'tip'  => '',
            'type' => 'select',
            'link' => [
                'model'  => [
                    'belongsTo',
                    'App\\Models\\Common\\CommonBanner',
                    'banner_id',
                    'id',
                ],
                'select' => ['id', 'name'],
                'field'  => ['id', 'name'],
            ]
        ],
        'created_at'     => [
            'name'     => '注册时间',
            'tip'      => '',
            'type'     => 'timestamp',
            'sql_type' => 'timestamp',
        ],
        'updated_at'     => [
            'name'     => '更新时间',
            'tip'      => '',
            'type'     => 'timestamp',
            'sql_type' => 'timestamp',
        ],
    ],
];

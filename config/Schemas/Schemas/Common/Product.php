<?php
return [
    'detail' => [
        'id'              => [
            'name'     => 'id',
            'tip'      => '',
            'type'     => 'number',
            'sql_type' => 'int(11)',
        ],
        'pd_picture'      => [
            'name'     => '商品图片',
            'tip'      => '',
            'type'     => 'image',
            'sql_type' => 'varchar(255)',
        ],
        'pd_name'         => [
            'name'     => '商品名称',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'actual_price'    => [
            'name'     => '实际售价',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'decimal(10,2)',
        ],
        'cost_price'      => [
            'name'     => '原价',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'decimal(10,2)',
        ],
        'in_stock'        => [
            'name'     => '库存',
            'tip'      => '',
            'type'     => 'number',
            'sql_type' => 'bigint(255)',
        ],
        'brief_desc'      => [
            'name'     => '简介',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'varchar(255)',
        ],
        'sales_volume'    => [
            'name'     => '销量',
            'tip'      => '',
            'type'     => 'number',
            'sql_type' => 'int(50)',
        ],
/*        'pd_type'         => [
            'name'     => '所属类型',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'int(4)',
        ],*/
        'pd_type' => [
            'name' => '所属类型',
            'tip'  => '',
            'type' => 'select',
            'link' => [
                'model' => [
                    'belongsTo',
                    'App\\Models\\Common\\CommonType',
                    'pd_type',
                    'id',
                ],
                'select'=>['id','name'],
                'field'=>['id','name'],
            ]
        ],

        'isopen_coupon'   => [
            'name'     => '是否开启优惠券',
            'tip'      => '',
            'type'     => 'checkbox',
            'sql_type' => 'int(1)',
        ],
        'wholesale_price' => [
            'name'     => '批发价配置',
            'tip'      => '',
            'type'     => 'text',
            'sql_type' => 'text',
        ],
        'weight'          => [
            'name'     => '排序',
            'tip'      => '0-9999',
            'type'     => 'number',
            'sql_type' => 'int(100)',
        ],
        'pd_info'         => [
            'name'     => '详情',
            'tip'      => '',
            'type'     => 'editor',
            'sql_type' => 'text',
        ],
        'status'          => [
            'name'     => '状态',
            'tip'      => '',
            'type'     => 'checkbox',
            'sql_type' => 'int(11)',
        ],
        'created_at'      => [
            'name'     => '创建时间',
            'tip'      => '',
            'type'     => 'timestamp',
            'sql_type' => 'timestamp',
        ],
        'updated_at'      => [
            'name'     => '更新时间',
            'tip'      => '',
            'type'     => 'timestamp',
            'sql_type' => 'timestamp',
        ],
    ],
];

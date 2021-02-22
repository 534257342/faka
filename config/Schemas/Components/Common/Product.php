<?php
/**
 * 其他可以用组建默认的参数
 * 也可以自己指定
 */
return [
    'detail' => [
        'table'  => [
            'search' => [
                'pd_name',
                'pd_type',
                'created_at',
            ],
            'fields' => [
                'id',
                'pd_picture',
                'pd_name',
                'actual_price',
                'cost_price',
                'in_stock',
                'sales_volume',
                'pd_type',
                'isopen_coupon',
                'wholesale_price',
                'weight',
                'status',
                'created_at',
                'updated_at'
            ],
        ],
        'add'    => [
            'fields' => [
                'pd_picture',
                'pd_name',
                'actual_price',
                'cost_price',
                'in_stock',
                'brief_desc',
                'pd_info',
                'sales_volume',
                'pd_type',
                'isopen_coupon',
                'wholesale_price',
                'weight',
                'status'
            ],
        ],
        'edit'   => [
            'fields' => [
                'pd_picture',
                'pd_name',
                'actual_price',
                'cost_price',
                'in_stock',
                'brief_desc',
                'pd_info',
                'sales_volume',
                'pd_type',
                'isopen_coupon',
                'wholesale_price',
                'weight',
                'status'
            ],
        ],
        'detail' => [
            'fields' => [
                'id',
                'pd_picture',
                'pd_name',
                'actual_price',
                'cost_price',
                'in_stock',
                'brief_desc',
                'pd_info',
                'sales_volume',
                'pd_type',
                'isopen_coupon',
                'wholesale_price',
                'weight',
                'status',
                'created_at',
                'updated_at'
            ],
        ],
    ],
];

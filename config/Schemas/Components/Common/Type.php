<?php
/**
 * 其他可以用组建默认的参数
 * 也可以自己指定
 */
return [
    'detail' => [
        'table'  => [
            'fields' => [
                'name',
                'status',
                'created_at',
                'updated_at'
            ],
        ],
        'add'    => [
            'fields' => [
                'status',
                'name'
            ],
        ],
        'edit'   => [
            'fields' => [
                'status',
                'name'
            ],
        ],
        'detail' => [
            'fields' => [
                'id',
                'status',
                'name',
                'created_at',
                'updated_at'
            ],
        ],
    ],
];

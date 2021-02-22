<?php
/**
 * 其他可以用组建默认的参数
 * 也可以自己指定
 */
return [
    'detail' => [
        'table'  => [
            'search' => [
                'name',
                'phone',
                'created_at',
            ],
            'config' => [
                'action' => [
                    'del' => null,
                ]
            ],
            'fields' => [
                'name',
                'phone',
                'status',
                'banner_id',
                'money',
         /*       'company_pic',*/
/*                'sex',*/
                'status',
                'roles',
                'created_at',
                //'updated_at'
            ],
        ],
        'add'    => [
            'fields' => [
                'name',
                'phone',
                'banner_id',
            //    'company_pic',
                'password',
                'status',
                'roles',
                'sex',
                'company_name',
            ],
        ],
        'edit'   => [
            'fields' => [
                'name',
                'sex',
                'banner_id',
        /*        'company_pic',*/
                'roles'
/*                'company_pic',
                'phone',
                'email',
                'password',
                'company_name',
                'sex',*/
 /*               'roles',*/
            ],
        ],
        'show' => [
            'fields' => [
                'name',
                'phone',
                'status',
                'created_at',
                'sex',
         /*       'roles',*/
            ],
        ],
    ],
];

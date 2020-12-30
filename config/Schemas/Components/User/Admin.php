<?php
/**
 * 其他可以用组建默认的参数
 * 也可以自己指定
 */
return [
    'detail'=>[
        'table'=>[
            'search'=>[
                'user_id',
                'status',
            ],
            'fields'=>[
                'id',
                'user_id',
//                'type',
                'roles',
                'status',
                'created_at',
                //'updated_at'
             ],
        ],
        'add'=>[
            'fields'=>[
                'roles',
                'user_id',
                'type',
                'site',
                'status',
             ],
        ],
        'edit'=>[
            'fields'=>[
//                'type',
                'roles',
                'status',
                'site',
                'user_id'
            ],
        ],
        'detail'=>[
            'fields'=>[
                'id',
                //'type',
                'status',
                'user_id',
                'created_at',
                'updated_at'
            ],
        ],
    ],
];
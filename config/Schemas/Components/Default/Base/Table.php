<?php
return function($data,$component,$target){
    $def = [
        'fields'=>[],
        'search'=>[],
        'show'=>[ //配置显示
            'is_show'=>[
                'default'=>1,
            ],
            'is_drop'=>[
                'default'=>0,
                'action'=>'',
            ],
        ],
        'config'=>[
            'api'=>'/{$COMPONENT_API}',
            'quick_change_api'=>'/{$COMPONENT_API}/{{id}}',
            'button'=>[
                'add'=>[
                    'type'=>'primary',
                    'html'=>'添加',
                    'action'=>'add',
                    'icon'=>'el-icon-plus',
                    'url'=>'/dialog/edit/{$COMPONENT}'
                ],
            ],
            'action'=>[
                'detail'=>[
                    'type'=>'warning',
                    'html'=>'详情',
                    'action'=>'/',
                    'url'=>'/dialog/show/{$COMPONENT}/{{id}}',
                ],
                'edit'=>[
                    'type'=>'primary',
                    'html'=>'编辑',
                    'action'=>'/',
                    'url'=>'/dialog/edit/{$COMPONENT}/{{id}}',
                ],
                'del'=>[
                    'type'=>'danger',
                    'html'=>'删除',
                    'action'=>'delRows',
                    'api'=>'/{$COMPONENT_API}/{{id}}',
                ],
            ]
        ],
        'html'=>''
    ];

    return array_merges($def,$data);
};
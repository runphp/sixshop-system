<?php
declare(strict_types=1);

return [
    [
        'type' => 'switch',
        'field' => 'is_cache',
        'title' => '缓存开关',
        'info' => '是否开启缓存',
        '$required' => false,
        'props' => [
            'activeValue' => true,
            'inactiveValue' => false
        ],
        '_fc_id' => 'id_Fjzbmcdo513lahc',
        'name' => 'ref_Fzt4mcdo513laic',
        'display' => true,
        'hidden' => false,
        '_fc_drag_tag' => 'switch'
    ],
    [
        'type' => 'checkbox',
        'field' => 'normal_module_list',
        'title' => '普通模块加载列表',
        'info' => '选择状态的模块才会加载',
        'effect' => [
            'fetch' => [
                'action' => '{{API_BASE_URL}}/admin/system/extension/normal',
                'method' => 'GET',
                'dataType' => 'json',
                'headers' => [
                    "Authorization" => "Bearer {{API_TOKEN}}"
                ],
                'query' => [],
                'data' => [],
                'parse' => '',
                'beforeFetch' => '',
                'onError' => '',
                'to' => 'options'
            ]
        ],
        '$required' => false,
        'props' => [
            '_optionType' => 1
        ],
        '_fc_id' => 'id_Ffunmdtoraneacc',
        'name' => 'ref_Fq8pmdtoraneadc',
        'display' => true,
        'hidden' => true,
        '_fc_drag_tag' => 'checkbox'
    ],
    [
        'type' => 'group',
        'field' => 'category',
        'value' => [
            [
                'text' => '核心扩展',
                'code' => 'core'
            ],
            [
                'text' => '支付',
                'code' => 'pay'
            ],
            [
                'text' => '定制',
                'code' => 'custom'
            ],
            [
                'text' => '其他扩展',
                'code' => 'other'
            ],
        ],
        'title' => '分类',
        'info' => '',
        '$required' => false,
        'props' => [
            'expand' => 1,
            'rule' => [
                [
                    'type' => 'fcRow',
                    'children' => [
                        [
                            'type' => 'col',
                            'props' => [
                                'span' => 12
                            ],
                            'children' => [
                                [
                                    'type' => 'input',
                                    'field' => 'text',
                                    'title' => '分类名',
                                    'info' => '',
                                    '$required' => false,
                                    '_fc_id' => 'id_Ffmvmclvt0mfauc',
                                    'name' => 'ref_Fwifmclvt0mfavc',
                                    'display' => true,
                                    'hidden' => false,
                                    '_fc_drag_tag' => 'input'
                                ],
                                [
                                    'type' => 'input',
                                    'field' => 'code',
                                    'title' => '分类编码',
                                    'info' => '',
                                    '$required' => false,
                                    '_fc_id' => 'id_F217mclvt3a5axc',
                                    'name' => 'ref_Flwvmclvt3a5ayc',
                                    'display' => true,
                                    'hidden' => false,
                                    '_fc_drag_tag' => 'input'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];
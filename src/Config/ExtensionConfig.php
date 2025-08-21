<?php
declare(strict_types=1);

namespace SixShop\System\Config;

/**
 * 模块配置
 */
class ExtensionConfig
{
    /**
     * 模块共用的配置
     * @var array
     */
    const array BASE = [
        [
            'type' => 'switch',
            'field' => 'debug',
            'title' => 'Debug开关',
            'info' => '您可以开启或关闭debug模式',
            'required' => false,
            'props' => [
                'activeValue' => true,
                'inactiveValue' => false,
                'disabled' => false,
                'activeText' => '开启',
                'inactiveText' => '关闭',
                'activeColor' => '#40FB07FF',
                'inactiveColor' => '#FF0000FF'
            ],
            '_fc_id' => 'id_Fakamcartp0rawc',
            'name' => 'ref_F15kmcartp0raxc',
            'display' => true,
            'hidden' => false,
            '_fc_drag_tag' => 'switch'
        ]
    ];

}
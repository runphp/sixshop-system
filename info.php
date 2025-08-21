<?php
declare(strict_types=1);

return [
    'id' => 'system', # 扩展的唯一标识符
    'name' => '扩展管理系统', # 扩展的名称
    'is_core' => true, # 是否核心扩展'
    'category' => 'core', # 扩展的分类 core:核心扩展，other:其他扩展
    'description' => '这是用来管理扩展的扩展，主要是展示扩展列表，安装扩展，卸载扩展，更新扩展，扩展配置等功能。', # 扩展的描述
    'version' => '1.0.0',  # 扩展的版本
    'core_version' => '^1.0',  # 支持的核心版本
    'author' => 'runphp', # 作者
    'email' => 'runphp@qq.com', # 作者的邮箱
    'website' => '', # 扩展的地址，可以是扩展的仓库地址，帮助用户寻找扩展，安装扩展等网络地址
    'image' => '', # 扩展的图片，用于展示扩展的图标，或者是扩展的截图等图片地址
    'license' => 'MIT', # 扩展的开源协议
    'weight' => 101, # 扩展的权重，用于控制加载先后顺序, 普通扩展请使用>=10000
];

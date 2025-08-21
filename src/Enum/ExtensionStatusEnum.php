<?php

namespace SixShop\System\Enum;

enum ExtensionStatusEnum:int
{
    case UNINSTALLED = 1; // 未安装
    case INSTALLED = 2;
    case ENABLED = 3;
    case DISABLED = 4;

    public function toString(): string
    {
        return match ($this) {
            self::UNINSTALLED => '未安装',
            self::INSTALLED => '已安装',
            self::ENABLED => '已启用',
            self::DISABLED => '已禁用',
        };
    }
}

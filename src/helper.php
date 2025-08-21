<?php
declare(strict_types=1);

use SixShop\System\ExtensionManager;


if (!function_exists('extension_config')) {
    /**
     * 获取模块配置
     */
    function extension_config(string $moduleName, string $key = '', bool $onlyValue = true): mixed
    {
        return app(ExtensionManager::class)->getExtensionConfig($moduleName, $key, $onlyValue);
    }
}

if (!function_exists('array_to_map')) {
    function array_to_map(array|null $array, string $key, string $value): array
    {
        if (empty($array)) {
            return [];
        }
        $map = [];
        foreach ($array as $item) {
            $map[$item[$key]] = $item[$value];
        }
        return $map;
    }
}
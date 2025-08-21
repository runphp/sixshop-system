<?php
declare(strict_types=1);

namespace SixShop\System;

use PHPUnit\Framework\TestCase;

class ExtensionManagerTest extends TestCase
{
    public function testInstall()
    {
        app()->make(ExtensionManager::class)->install('hello');
    }

    public function testUninstall()
    {
        app()->make(ExtensionManager::class)->uninstall('hello');
    }

    public function testGetExtensionConfig()
    {
        $result = array_to_map(app(ExtensionManager::class)->getExtensionConfig('system', 'category'), 'code', 'text');
    }
}
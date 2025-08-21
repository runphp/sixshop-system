<?php
declare(strict_types=1);

namespace SixShop\System;

use SixShop\Core\ExtensionAbstract;
use SixShop\System\Cron\SystemCron;
use SixShop\System\Hook\ExtensionHook;
use SixShop\System\Hook\GatheringCrontabEventHook;

class Extension extends ExtensionAbstract
{

    public function getHooks(): array
    {
        return [
            ExtensionHook::class,
            GatheringCrontabEventHook::class
        ];
    }

    protected function getBaseDir(): string
    {
        return dirname(__DIR__);
    }

    public function getCronJobs(): array
    {
        return [
            SystemCron::class
        ];
    }
}
<?php
declare(strict_types=1);

namespace SixShop\System\Hook;

use ReflectionClass;
use ReflectionMethod;
use SixShop\Core\Attribute\Cron;
use SixShop\Core\Attribute\Hook;
use SixShop\Core\Helper;
use SixShop\System\Event\CrontabWorkerStartEvent;
use SixShop\System\ExtensionManager;
use think\App;
use Workerman\Crontab\Crontab;

class GatheringCrontabEventHook
{
    public function __construct(private App $app)
    {
    }

    #[Hook(CrontabWorkerStartEvent::class)]
    public function onWorkerStart(): void
    {
        $extensionManager = $this->app->make(ExtensionManager::class);
        foreach (Helper::extension_name_list() as $extensionName) {
            $extension = $extensionManager->getExtension($extensionName);
            $cronJobs = $extension->getCronJobs();
            foreach ($cronJobs as $cronJobClass) {
                $ref = new ReflectionClass($cronJobClass);
                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $attributes = $method->getAttributes(Cron::class);
                    foreach ($attributes as $attribute) {
                        $cronInstance = $attribute->newInstance();
                        $name = $cronInstance->name ?: $cronJobClass . '@' . $method->getName();
                        new Crontab($cronInstance->rule, [$this->app->make($cronJobClass), $method->getName()], $name);
                    }
                }
            }
        }
    }
}
<?php
declare(strict_types=1);
namespace SixShop\System\Cron;

use SixShop\Core\Attribute\Cron;
use think\Cache;
use Workerman\Crontab\Crontab;

readonly class SystemCron
{
    public function __construct(private Cache $cache)
    {
    }

    #[Cron('1 * * * * *', 'system.cron')]
    public function onWorkerStart(): void
    {
        $crontabList = [];
        foreach (Crontab::getAll() as $item) {
            /* @var Crontab $item */
            $crontabList[] = [
                'rule' => $item->getRule(),
                'name' => $item->getName(),
                'id' => $item->getId(),
                'time' => date('Y-m-d H:i:s'),
            ];
        }
        $this->cache->set('crontab_list', $crontabList);
    }
}
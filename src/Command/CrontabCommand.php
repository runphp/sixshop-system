<?php
declare(strict_types=1);

namespace SixShop\System\Command;

use SixShop\System\Event\CrontabWorkerStartEvent;
use think\App;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Event;
use Workerman\Worker;

class CrontabCommand extends Command
{
    public function configure(): void
    {
        $this->setName('crontab')
            ->addArgument('action', Argument::REQUIRED, 'action')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'daemon mode')
            ->addOption('grace', 'g', Option::VALUE_NONE, 'graceful shutdown')
            ->setDescription('Crontab command');
    }

    protected function execute(Input $input, Output $output): void
    {
        $argv = [$input->getArgument('action')];
        $daemon = $input->getOption('daemon');
        if ($daemon) {
            $argv[] = '-d';
        }
        $grace = $input->getOption('grace');
        if ($grace) {
            $argv[] = '-g';
        }
        $worker = new class ($argv, $this->app) extends Worker {
            private static array $argv;

            public function __construct(array $argv, private readonly App $app, ?string $socketName = null, array $socketContext = [])
            {
                parent::__construct($socketName, $socketContext);
                self::$argv = $argv;
                self::$pidFile = $app->getRootPath() . 'runtime/crontab.pid';
                self::$logFile = $app->getRootPath() . 'runtime/crontab.log';
                self::$statisticsFile = $app->getRootPath() . 'runtime/crontab.statistics.php';
            }

            public function setOnWorkerStart(callable $worker): void
            {
                $this->onWorkerStart = $worker;
            }
        };

        $worker->setOnWorkerStart(function () {
            Event::trigger(CrontabWorkerStartEvent::class);
        });
        Worker::runAll();
    }
}
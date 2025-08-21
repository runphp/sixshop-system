<?php
declare(strict_types=1);

use SixShop\System\Command\CoreExtensionConfigCommand;
use SixShop\System\Command\CrontabCommand;
use SixShop\System\Command\ExtensionManagementCommand;
use SixShop\System\Command\ModelPropertyCommand;
use SixShop\System\Command\ExtensionScaffoldMakeCommand;

return [
    'amp:property' => ModelPropertyCommand::class,
    'core:config' => CoreExtensionConfigCommand::class,
    'extension:manage' => ExtensionManagementCommand::class,
    'extension:make' => ExtensionScaffoldMakeCommand::class,
    'crontab' => CrontabCommand::class,
];
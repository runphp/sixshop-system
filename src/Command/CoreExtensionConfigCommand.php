<?php
declare(strict_types=1);

namespace SixShop\System\Command;

use SixShop\Core\Helper;
use SixShop\System\ExtensionManager;
use SixShop\System\Migrate;
use SixShop\System\Model\ExtensionModel;
use think\console\Command;
use think\db\exception\PDOException;

class CoreExtensionConfigCommand extends Command
{
    public function configure(): void
    {
        $this->setName('core:config')
            ->setDescription('Set the core extension default configuration')
            ->addOption('force', 'f', null, 'Force update the core extension default configuration');
    }

    public function handle(): void
    {
        $start = microtime(true);
        $force = $this->input->getOption('force');
        $extensionManager = $this->app->make(ExtensionManager::class);
        // 确保系统扩展迁移已安装
        $moduleList = array_diff(Helper::extension_name_list(), ['system']);
        array_unshift($moduleList, 'system');
        try {
            $installModuleList = ExtensionModel::where(['is_core' => 0])
                ->where('status', '>', 1)
                ->column('id');
        } catch (PDOException $e) {
            $installModuleList = [];
        }
        foreach ($moduleList as $moduleName) {
            $extension = $extensionManager->getExtension($moduleName);
            $info = $extension->getInfo();
            try {
                $config = $extensionManager->getExtensionConfig($moduleName);
            } catch (PDOException $e) {
                $config = [];
            }
            if ((isset($info['is_core']) && $info['is_core'] == 1) || in_array($moduleName, $installModuleList)) {
                $migrate = app(Migrate::class, [$this->app, $moduleName], true);
                if ($force) {
                    $migrate->uninstall();
                    $this->output->writeln("Uninstall extension migration for module: $moduleName");
                }
                $installVersions = $migrate->install();
                foreach ($installVersions as $version) {
                    $this->output->writeln("Install extension migration for module: $moduleName, version: $version");
                }
                if (empty($config) || $force) {
                    $updateData = [];
                    $formConfig = $extension->getConfig();
                    foreach ($formConfig as $item) {
                        if (isset($item['value'])) {
                            $updateData[$item['field']] = $item['value'];
                            $value = is_array($item['value']) ? json_encode($item['value'], JSON_UNESCAPED_UNICODE) : $item['value'];
                            $this->output->writeln("Set extension default configuration for module: $moduleName, field: {$item['field']}, value: $value");
                        }
                    }
                    if (!empty($updateData)) {
                        $extensionManager->saveConfig($moduleName, $updateData);
                    }
                }
            }
        }

        $end = microtime(true);

        $this->output->writeln('');
        $this->output->writeln('<comment>All Done. Took ' . sprintf('%.4fs', $end - $start) . '</comment>');
    }
}
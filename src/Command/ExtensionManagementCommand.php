<?php
declare(strict_types=1);

namespace SixShop\System\Command;

use SixShop\Core\Helper;
use SixShop\System\ExtensionManager;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\console\Table;

class ExtensionManagementCommand extends Command
{
    const int FAILURE = 1;
    const int SUCCESS = 0;

    protected function configure(): void
    {
        $this->setName('extension:manage')
            ->setDescription('扩展管理命令：创建、安装、启用、禁用、卸载、列表')
            ->addArgument('action', Argument::REQUIRED, '操作类型: create|list|install|enable|disable|uninstall')
            ->addArgument('module', Argument::OPTIONAL, '扩展模块名（create、install、enable、disable、uninstall 需指定）');
    }

    protected function execute(Input $input, Output $output): int
    {
        $action = $input->getArgument('action');
        $module = $input->getArgument('module');
        $extensionManager = $this->app->make(ExtensionManager::class);


        switch ($action) {
            case 'list':
                $list = $extensionManager->getExtensionList();
                $table = new Table();
                $table->setHeader([
                    'ID', '名称', '状态', '分类', '版本', '作者', '描述'
                ]);
                $rows = [];
                foreach ($list as $ext) {
                    $rows[] = [
                        $ext['id'],
                        $ext['name'],
                        $ext['status_text'] ?? $ext['status'],
                        $ext['category_text'] ?? ($ext['category'] ?? ''),
                        $ext['version'] ?? '',
                        $ext['author'] ?? '',
                        mb_strimwidth($ext['description'] ?? '', 0, 32, '...')
                    ];
                }
                $table->setRows($rows);
                $table->setStyle('box-double');
                $output->writeln("<info>扩展列表：</info>");
                $output->writeln($table->render());
                break;
            case 'create':
                if (!$module) {
                    $output->error('请指定要创建的扩展模块名');
                    return self::FAILURE;
                }
                $basePath = Helper::extension_path($module);
                if (is_dir($basePath)) {
                    $output->error("扩展 {$module} 已存在");
                    return self::FAILURE;
                }
                // 创建目录结构
                @mkdir($basePath . 'src', 0777, true);
                @mkdir($basePath . 'database/migrations', 0777, true);
                // info.php
                $info = [
                    'id' => $module,
                    'name' => $module,
                    'is_core' => false,
                    'category' => 'other',
                    'description' => $module . ' 扩展',
                    'version' => '1.0.0',
                    'core_version' => '^1.0',
                    'author' => 'yourname',
                    'email' => '',
                    'website' => '',
                    'image' => '',
                    'license' => 'MIT',
                ];
                $infoExport = var_export($info, true);
                $infoExport = str_replace(['array (', ')'], ['[', ']'], $infoExport);
                file_put_contents($basePath . 'info.php', "<?php\ndeclare(strict_types=1);\nreturn " . $infoExport . ";\n");
                // Extension.php
                $extClass = "<?php\ndeclare(strict_types=1);\n\nnamespace SixShop\\Extension\\{$module};\n\nuse SixShop\\Extension\\core\\ExtensionAbstract;\n\nclass Extension extends ExtensionAbstract\n{\n    protected function getBaseDir(): string\n    {\n        return dirname(__DIR__);\n    }\n}\n";
                file_put_contents($basePath . 'src/Extension.php', $extClass);
                // README.md
                file_put_contents($basePath . 'README.md', "# $module\n\n扩展说明\n");
                // config.php
                file_put_contents($basePath . 'config.php', "<?php\ndeclare(strict_types=1);\n\nreturn [];\n");
                $output->warning("扩展 {$module} 创建成功，目录：$basePath");
                break;
            case 'install':
                if (!$module) {
                    $output->error('请指定要安装的扩展模块名');
                    return self::FAILURE;
                }
                $extensionManager->install($module);
                $output->warning("扩展 {$module} 安装成功");
                break;
            case 'enable':
                if (!$module) {
                    $output->error('请指定要启用的扩展模块名');
                    return self::FAILURE;
                }
                $extensionManager->enable($module);
                $output->warning("扩展 {$module} 启用成功");
                break;
            case 'disable':
                if (!$module) {
                    $output->error('请指定要禁用的扩展模块名');
                    return self::FAILURE;
                }
                $extensionManager->disable($module);
                $output->warning("扩展 {$module} 禁用成功");
                break;
            case 'uninstall':
                if (!$module) {
                    $output->error('请指定要卸载的扩展模块名');
                    return self::FAILURE;
                }
                $extensionManager->uninstall($module);
                $output->warning("扩展 {$module} 卸载成功");
                break;
            default:
                $output->error('不支持的操作类型: ' . $action);
                return self::FAILURE;
        }

        return self::SUCCESS;
    }
}

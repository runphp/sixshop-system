<?php
declare(strict_types=1);

namespace SixShop\System\Command;

use SixShop\Core\Helper;
use Throwable;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;

class ExtensionScaffoldMakeCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('extension:make')
            ->setDescription('生成扩展脚手架骨架（后端+前端，可选 Service/Entity/FFI/Frontend）')
            ->addArgument('module', Argument::REQUIRED, '扩展模块名（目录名，建议小写下划线）')
            ->addOption('with-api', null, Option::VALUE_NONE, '生成 API 路由与控制器')
            ->addOption('with-admin', null, Option::VALUE_NONE, '生成 Admin 路由与控制器')
            ->addOption('with-service', null, Option::VALUE_NONE, '生成 Service 层')
            ->addOption('with-entity', null, Option::VALUE_NONE, '生成 Entity 层')
            ->addOption('with-migration', null, Option::VALUE_NONE, '生成迁移与安装/卸载 SQL 样板')
            ->addOption('with-frontend', null, Option::VALUE_NONE, '生成前端 Admin 模板')
            ->addOption('with-ffi', null, Option::VALUE_NONE, '生成 FFI 目录与构建脚本样板')
            ->addOption('adapter', null, Option::VALUE_REQUIRED, '服务适配默认实现 php|ffi|auto', 'php')
            ->addOption('desc', null, Option::VALUE_REQUIRED, 'info.php 描述', '')
            ->addOption('author', null, Option::VALUE_REQUIRED, '作者', 'yourname')
            ->addOption('dry-run', null, Option::VALUE_NONE, '仅预览将要创建的目录与文件，不实际写入')
            ->addOption('force', null, Option::VALUE_NONE, '允许在已存在的模块目录内覆盖写入文件');
    }

    protected function execute(Input $input, Output $output): int
    {
        $module = (string)$input->getArgument('module');
        $withApi = (bool)$input->getOption('with-api');
        $withAdmin = (bool)$input->getOption('with-admin');
        $withService = (bool)$input->getOption('with-service');
        $withEntity = (bool)$input->getOption('with-entity');
        $withMigration = (bool)$input->getOption('with-migration');
        $withFrontend = (bool)$input->getOption('with-frontend');
        $withFFI = (bool)$input->getOption('with-ffi');
        $adapter = (string)$input->getOption('adapter');
        $desc = (string)$input->getOption('desc');
        $author = (string)$input->getOption('author');
        $dryRun = (bool)$input->getOption('dry-run');
        $force = (bool)$input->getOption('force');

        if (!$module) {
            $output->error('模块名不能为空');
            return 1;
        }

        // 默认行为：如果用户未显式指定任何 with-* 选项，则默认生成“完整插件”（除 FFI）
        $anySpecified = $withApi || $withAdmin || $withService || $withEntity || $withMigration || $withFrontend || $withFFI;
        if (!$anySpecified) {
            $withApi = $withAdmin = $withService = $withEntity = $withMigration = $withFrontend = true;
            // $withFFI 默认为 false，避免环境未开启 FFI 导致构建失败
        }

        $base = rtrim(Helper::extension_path($module), '/');
        if (is_dir($base) && !$force) {
            $output->error("扩展 {$module} 已存在：{$base}，可使用 --force 覆盖写入");
            return 1;
        }

        // 目录结构
        $dirs = [
            "$base/src/Controller/Api",
            "$base/src/Controller/Admin",
            "$base/src/Service",
            "$base/src/Entity",
            "$base/src/Hook",
            "$base/database/migrations",
            "$base/database/seeds",
            "$base/route",
            "$base/config",
        ];
        // 计划文件（用于 dry-run 展示）
        $ns = "SixShop\\\\Extension\\\\{$module}";
        $studly = str_replace(['-', '_'], '', ucwords($module, '-_'));
        $planFiles = [
            "$base/info.php",
            "$base/config.php",
            "$base/README.md",
            "$base/src/Extension.php",
            "$base/src/Hook/{$studly}Hook.php",
        ];
        if ($withApi) {
            $planFiles[] = "$base/route/api.php";
            $planFiles[] = "$base/src/Controller/Api/HelloController.php";
            $planFiles[] = "$base/src/Controller/Api/ItemController.php";
        }
        if ($withAdmin) {
            $planFiles[] = "$base/route/admin.php";
            $planFiles[] = "$base/src/Controller/Admin/DashboardController.php";
            $planFiles[] = "$base/src/Controller/Admin/ManageController.php";
            $planFiles[] = "$base/src/Controller/Admin/ItemController.php";
            $planFiles[] = "$base/src/Controller/Admin/UploadController.php";
        }
        if ($withService) $planFiles[] = "$base/src/Service/{$studly}Service.php";
        if ($withEntity) $planFiles[] = "$base/src/Entity/{$studly}.php";
        if ($withMigration) {
            $planFiles[] = "$base/config/install.sql";
            $planFiles[] = "$base/config/uninstall.sql";
        }

        if ($dryRun) {
            $output->writeln("[DRY-RUN] 将创建以下目录：");
            foreach ($dirs as $d) { $output->writeln("  - $d"); }
            $output->writeln("[DRY-RUN] 将创建以下关键文件（部分）：");
            foreach ($planFiles as $f) { $output->writeln("  - $f"); }
            return 0;
        }

        foreach ($dirs as $d) @mkdir($d, 0777, true);

        // info.php
        // 生成完整 info.php（参考 guimi）
        $info = [
            'id' => $module,
            'name' => $module,
            // 分类：core|content|shop|other|custom，默认 custom
            'category' => 'custom',
            'description' => $desc ?: ($module . ' 扩展模块'),
            'version' => '0.1.0',
            'core_version' => '^1.0',
            'author' => $author ?: 'sixshop',
            'email' => '',
            'website' => '',
            'image' => '',
            'license' => 'MIT',
            'keywords' => [],
            'dependencies' => [],
            'conflicts' => [],
            'requires' => [
                'php' => '>=8.0.0',
                'extensions' => ['json', 'pdo'],
            ],
        ];
        $infoExport = var_export($info, true);
        $infoExport = str_replace(['array (', ')'], ['[', ']'], $infoExport);
        file_put_contents("$base/info.php", "<?php\ndeclare(strict_types=1);\n\nreturn " . $infoExport . ";\n");

        // Extension.php（使用 Nowdoc + sprintf 注入命名空间）
        $ns = 'SixShop\\Extension\\' . $module;
        $extClass = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s;

use SixShop\Core\ExtensionAbstract;
use think\facade\Db;

class Extension extends ExtensionAbstract
{
    protected function getBaseDir(): string
    {
        return dirname(__DIR__);
    }

    public function install(): void
    {
        $sqlFile = __DIR__ . '/../config/install.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql) { Db::execute($sql); }
        }
    }

    public function uninstall(): void
    {
        $sqlFile = __DIR__ . '/../config/uninstall.sql';
        if (is_file($sqlFile)) {
            $sql = file_get_contents($sqlFile);
            if ($sql) { Db::execute($sql); }
        }
    }
}
PHP, $ns);
        file_put_contents("$base/src/Extension.php", $extClass);

        // config.php（form-create 占位）
        $configPhp = <<<PHP
<?php
declare(strict_types=1);

return [
    'form' => [
        [
            'type' => 'input',
            'field' => 'title',
            'title' => '标题',
            'value' => '',
            'props' => ['placeholder' => '请输入标题'],
        ],
    ],
];
PHP;
        file_put_contents("$base/config.php", $configPhp);

        // README
        file_put_contents("$base/README.md", "# {$module}\n\n自动生成的扩展骨架。\n");

        // 上面已生成 Extension.php，这里不再重复生成

        // Hook 占位（下面统一生成一次）

        // 安装/卸载 SQL 样板
        if ($withMigration) {
            $install = "-- 安装 SQL 示例\n";
            $uninstall = "-- 卸载 SQL 示例\n";
            file_put_contents("$base/config/install.sql", $install);
            file_put_contents("$base/config/uninstall.sql", $uninstall);
        }

        // 路由（注意：系统会自动加 /api/{$module} 或 /admin/{$module} 前缀，这里不需要再包一层模块分组）
        $apiRoute = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

use SixShop\Extension\%s\Controller\Api\ItemController;
use think\facade\Route;

// 注意：前缀由系统自动添加，这里只写相对路径

// 健康检查
Route::get('ping', fn() => json(['code' => 0, 'msg' => 'ok', 'data' => ['pong' => true]]))->middleware(['auth']);

// 示例：业务分组-具体动作（放在资源路由之前，避免 :id 冲突）
Route::group('item', function () {
    Route::get('info', [ItemController::class, 'info']);
    Route::post('check', [ItemController::class, 'check']);
})->middleware(['auth']);

PHP, $module);
        $adminRoute = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

use think\facade\Route;
use SixShop\Extension\%s\Controller\Admin\DashboardController;
use SixShop\Extension\%s\Controller\Admin\ItemController;
use SixShop\Extension\%s\Controller\Admin\UploadController;

// 首页/仪表盘控制器路由（对齐 guimi 写法）
Route::get('dashboard/stats', [DashboardController::class, 'stats'])->middleware(['auth']);
// 可按需继续追加：relation-trend / verification-trend / redemption-trend / latest 等

// 通用上传
Route::post('upload', [UploadController::class, 'handle'])->middleware(['auth']);
PHP, $module, $module, $module);
        if ($withApi) file_put_contents("$base/route/api.php", $apiRoute);
        if ($withAdmin) file_put_contents("$base/route/admin.php", $adminRoute);

        // 控制器样板
        if ($withApi) {
            $apiCtrl = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Controller\Api;

use think\Request; use think\Response;

class HelloController
{
    private function uid(Request $r): ?int { return $r->userID ?? ($r->adminID ?? null); }
    public function index(Request $r): Response
    {
        if (!$this->uid($r)) return json(['code'=>401,'msg'=>'未登录']);
        return json(['code'=>0,'msg'=>'ok','data'=>['now'=>date('c')]]);
    }
}
PHP, $ns);
            file_put_contents("$base/src/Controller/Api/HelloController.php", $apiCtrl);

            // API 资源控制器
            $apiItemCtrl = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Controller\Api;

use think\Request; use think\Response;

class ItemController
{
    private function uid(Request $r): ?int { return $r->userID ?? ($r->adminID ?? null); }

    public function index(Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>['list'=>[], 'total'=>0]]); }
    public function read(int $id): Response { return json(['code'=>0,'msg'=>'ok','data'=>['id'=>$id]]); }
    public function save(Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }
    public function update(int $id, Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }
    public function delete(int $id): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }

    // 具体动作示例（与路由匹配）
    public function info(Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>['info'=>[]]]); }
    public function check(Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }
}
PHP, $ns);
            file_put_contents("$base/src/Controller/Api/ItemController.php", $apiItemCtrl);
        }
        if ($withAdmin) {
            $adminCtrl = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Controller\Admin;

use think\Request; use think\Response;

class ManageController
{
    public function list(Request $r): Response
    {
        return json(['code'=>0,'msg'=>'ok','data'=>['list'=>[], 'total'=>0]]);
    }
}
PHP, $ns);
            file_put_contents("$base/src/Controller/Admin/ManageController.php", $adminCtrl);

            // 首页/仪表盘控制器（对齐 guimi：dashboard/*）
            $dashboardCtrl = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Controller\Admin;

use think\Response;

class DashboardController
{
    public function stats(): Response
    {
        // 首页统计占位：可返回卡片统计与趋势入口
        return json(['code' => 0, 'msg' => 'ok', 'data' => [
            'cards' => [
                ['title' => '总数', 'value' => 0],
            ],
        ]]);
    }
}
PHP, $ns);
            file_put_contents("$base/src/Controller/Admin/DashboardController.php", $dashboardCtrl);

            // Admin 资源控制器
            $adminItemCtrl = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Controller\Admin;

use think\Request; use think\Response;

class ItemController
{
    public function index(Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>['list'=>[], 'total'=>0]]); }
    public function read(int $id): Response { return json(['code'=>0,'msg'=>'ok','data'=>['id'=>$id]]); }
    public function save(Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }
    public function update(int $id, Request $r): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }
    public function delete(int $id): Response { return json(['code'=>0,'msg'=>'ok','data'=>true]); }
}
PHP, $ns);
            file_put_contents("$base/src/Controller/Admin/ItemController.php", $adminItemCtrl);

            // Admin 上传控制器
            $uploadCtrl = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Controller\Admin;

use think\Request; use think\Response;

class UploadController
{
    public function handle(Request $r): Response
    {
        // TODO: 接入实际存储逻辑，返回 { url, name }
        return json(['code'=>0,'msg'=>'ok','data'=>['url'=>'','name'=>'']]);
    }
}
PHP, $ns);
            file_put_contents("$base/src/Controller/Admin/UploadController.php", $uploadCtrl);
        }

        // Service / Entity 占位
        if ($withService) {
            $svc = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Service;

class %sService
{
    public function ping(): array { return ['pong' => true]; }
}
PHP, $ns, $studly);
            file_put_contents("$base/src/Service/{$studly}Service.php", $svc);
        }
        if ($withEntity) {
            $ent = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Entity;

class %sEntity
{
    public const TABLE = 'extension_%s_item';
}
PHP, $ns, $studly, $module);
            file_put_contents("$base/src/Entity/{$studly}Entity.php", $ent);
        }

        // 迁移 & 安装/卸载 SQL
        if ($withMigration) {
            $install = "-- 安装 SQL 示例\n" .
                       "CREATE TABLE IF NOT EXISTS `extension_{$module}_item`(\n" .
                       "  `id` int unsigned NOT NULL AUTO_INCREMENT,\n" .
                       "  `title` varchar(255) NOT NULL DEFAULT '',\n" .
                       "  `created_at` int unsigned NOT NULL DEFAULT 0,\n" .
                       "  PRIMARY KEY (`id`)\n" .
                       ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";
            $uninstall = "DROP TABLE IF EXISTS `extension_{$module}_item`;\n";
            file_put_contents("$base/config/install.sql", $install);
            file_put_contents("$base/config/uninstall.sql", $uninstall);
        }

        // Hook 示例（统一在此生成一次）
        $hook = sprintf(<<<'PHP'
<?php
declare(strict_types=1);

namespace %s\Hook;

class %sHook
{
    /** 示例：用户登录后 */
    public function onUserLogin(array $payload): void {}
}
PHP, $ns, $studly);
        file_put_contents("$base/src/Hook/{$studly}Hook.php", $hook);

        // FFI 样板
        if ($withFFI) {
            @mkdir("$base/ffi/model", 0777, true);
            $gomod = sprintf("module %s\n\ngo 1.21\n", $module);
            file_put_contents("$base/ffi/go.mod", $gomod);
            $mainGo = <<<'GO'
package main

// TODO: 实现导出方法
func main() {}
GO;
            file_put_contents("$base/ffi/main.go", $mainGo);
            $mk = sprintf(<<<'MK'
.PHONY: build
build:
    go build -buildmode=c-shared -o lib_%s.so main.go
MK, $module);
            file_put_contents("$base/ffi/Makefile", $mk);
            $buildSh = <<<'SH'
#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/ffi"
make build
cd ..
echo "[提示] 如使用 FFI，请重启 PHP-FPM 并在 Service Adapter 中切换实现"
SH;
            file_put_contents("$base/build.sh", $buildSh);
            @chmod("$base/build.sh", 0755);
        }

        // 前端 Admin 模板
        if ($withFrontend) {
            // 注意：root_path() 指向 backend/ 应用根；我们需要仓库根目录
            $projectRoot = rtrim(dirname(root_path()), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            // 视图目录名使用连字符（kebab-case），避免下划线
            $feViewName = str_replace('_', '-', $module);
            $feBase = $projectRoot . 'frontend/admin/src/views/' . $feViewName;
            @mkdir($feBase . '/components', 0777, true);
            @mkdir($feBase . '/composables', 0777, true);
            $indexVue = sprintf(<<<'VUE'
<template>
  <div class="%s-page">
    <a-tabs v-model:activeKey="tab">
      <a-tab-pane key="dashboard" tab="仪表盘" />
      <a-tab-pane key="list" tab="列表" />
      <a-tab-pane key="settings" tab="设置" />
    </a-tabs>
    <component :is="currentComp" />
  </div>
</template>
<script setup lang="ts">
import { ref, computed } from 'vue'
const tab = ref('dashboard')
const currentComp = computed(() => {
  return tab.value === 'list' ? 'ListPanel' : (tab.value === 'settings' ? 'SettingsPanel' : 'DashboardPanel')
})
</script>
VUE, $feViewName);
            file_put_contents($feBase . '/index.vue', $indexVue);
            $dash = "<template><div>DashboardPanel - {$feViewName}</div></template>\n";
            $list = "<template><div>ListPanel - {$feViewName}</div></template>\n";
            $settings = "<template><div>SettingsPanel - {$feViewName}</div></template>\n";
            file_put_contents($feBase . '/components/DashboardPanel.vue', $dash);
            file_put_contents($feBase . '/components/ListPanel.vue', $list);
            file_put_contents($feBase . '/components/SettingsPanel.vue', $settings);
            $useApi = sprintf(<<<'TS'
import request from '@/utils/request'

export function apiGet(url: string, params?: any) { return request.get(url, { params }) }
export function apiPost(url: string, data?: any) { return request.post(url, data) }
export const %sApi = {
  ping: () => apiGet(`/api/%s/ping`),
}
TS, $module, $module);
            file_put_contents($feBase . '/composables/useApi.ts', $useApi);
        }

        // 提示
        $output->writeln("<info>扩展骨架已生成：</info> $base");
        if ($withFrontend) $output->writeln("<comment>前端模板：</comment> frontend/admin/src/views/{$module}");
        $output->writeln("<comment>下一步：</comment> 1) 根据业务完善 Service/Entity 2) 配置路由与菜单 3) 如需 FFI 执行 {$module}/build.sh");
        return 0;
    }

    private function studly(string $value): string
    {
        $value = str_replace(['-', '_'], ' ', $value);
        $value = ucwords($value);
        return str_replace(' ', '', $value);
    }
}

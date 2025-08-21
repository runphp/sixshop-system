<?php
declare(strict_types=1);

namespace SixShop\System;

use RuntimeException;
use SixShop\Core\Contracts\ExtensionInterface;
use SixShop\Core\Helper;
use SixShop\Extension\payment\Contracts\PaymentExtensionInterface;
use SixShop\System\Config\ExtensionConfig;
use SixShop\System\Enum\ExtensionStatusEnum;
use SixShop\System\Model\ExtensionConfigModel;
use SixShop\System\Model\ExtensionModel;
use think\db\Query;
use think\exception\ValidateException;
use think\facade\Db;
use think\facade\Event;
use think\facade\Log;
use think\facade\Validate;
use think\Service;

class ExtensionManager extends Service
{
    /**
     * @var array 扩展列表
     */
    private array $extensionList = [];

    /**
     * @var array 分类列表
     */
    private array $categoryMap = [];


    /**
     * 安装扩展
     */
    public function install(string $moduleName): void
    {
        $extensionModel = ExtensionModel::where(['id' => $moduleName])->findOrFail();
        if ($extensionModel->status === ExtensionStatusEnum::INSTALLED) {
            throw new RuntimeException("{$moduleName}扩展已安装");
        }
        $this->app->make(Migrate::class, [$this->app, $moduleName])->install();
        $extension = $this->getExtension($moduleName);
        $extension->install();
        $config = $this->getExtensionConfig($moduleName);
        if (empty($config)) {
            $updateData = [];
            $formConfig = $extension->getConfig();
            foreach ($formConfig as $item) {
                if (isset($item['value'])) {
                    $updateData[$item['field']] = $item['value'];
                }
            }
            if (!empty($updateData)) {
                $this->saveConfig($moduleName, $updateData);
            }
        }
        $extensionModel->status = ExtensionStatusEnum::INSTALLED;
        $extensionModel->save();
    }

    public function getExtension(string $moduleName): ExtensionInterface|PaymentExtensionInterface
    {
        return $this->app->get('extension.' . $moduleName);
    }

    public function getExtensionConfig(string $moduleName, string $key = '', bool $onlyValue = true): mixed
    {
        $extensionConfig = ExtensionConfigModel::where('extension_id', $moduleName)->when($key, function (Query $query) use ($key) {
            $query->where('key', $key);
        })->column(['value', 'type',], 'key', true);

        if (count($extensionConfig) === 0) {
            return $key ? null : [];
        }
        if ($onlyValue) {
            $extensionConfig = array_map(fn($item) => $item['value'], $extensionConfig);
        }

        return $key != '' ? $extensionConfig[$key] : $extensionConfig;
    }

    public function saveConfig(string $moduleName, array $data): bool
    {
        $config = array_merge(ExtensionConfig::BASE, $this->getExtension($moduleName)->getConfig());
        $updateData = [];
        foreach ($config as $item) {
            if (isset($item['field'])) {
                if (isset($data[$item['field']])) {
                    $updateData[] = [
                        'extension_id' => $moduleName,
                        'key' => $item['field'],
                        'value' => $data[$item['field']],
                        'type' => $item['type'],
                        'title' => $item['title']
                    ];
                }
            } else {
                if (isset($item['children'])) {
                    foreach ($item['children'] as $childItem) {
                        if (isset($childItem['field'], $data[$childItem['field']])) {
                            $updateData[] = [
                                'extension_id' => $moduleName,
                                'key' => $childItem['field'],
                                'value' => $data[$childItem['field']],
                                'type' => $childItem['type'],
                                'title' => $childItem['title']
                            ];
                        }
                        if (isset($childItem['children'])) {
                            foreach ($childItem['children'] as $grandChildItem) {
                                if (isset($grandChildItem['field'], $data[$grandChildItem['field']])) {
                                    $updateData[] = [
                                        'extension_id' => $moduleName,
                                        'key' => $grandChildItem['field'],
                                        'value' => $data[$grandChildItem['field']],
                                        'type' => $grandChildItem['type'],
                                        'title' => $grandChildItem['title'],
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!empty($updateData)) {
            Db::transaction(function () use ($updateData) {
                foreach ($updateData as $item) {
                    $configModel = ExtensionConfigModel::where([
                        'extension_id' => $item['extension_id'],
                        'key' => $item['key']
                    ])->findOrEmpty();
                    $configModel->save($item);
                    Event::trigger('after_write_extension_config:' . $item['extension_id'] . ':' . $item['key'], $item);
                }
                Event::trigger('after_write_extension_config:' . $item['extension_id'], array_column($updateData, null,'key'));
            });
        }
        return true;
    }

    /**
     * 卸载扩展
     */
    public function uninstall(string $moduleName): void
    {
        $extensionModel = ExtensionModel::where(['id' => $moduleName])->findOrFail();
        if ($extensionModel->status === ExtensionStatusEnum::UNINSTALLED) {
            throw new RuntimeException("{$moduleName}扩展未安装");
        }
        $this->app->make(Migrate::class, [$this->app, $moduleName])->uninstall();
        $this->getExtension($moduleName)->uninstall();
        $extensionModel->status = ExtensionStatusEnum::UNINSTALLED;
        $extensionModel->save();
    }

    /**
     * 启用扩展
     */
    public function enable(string $moduleName): void
    {
        $extensionModel = ExtensionModel::where(['id' => $moduleName])->findOrFail();
        match ($extensionModel->status) {
            ExtensionStatusEnum::UNINSTALLED => throw new RuntimeException("{$moduleName}扩展未安装"),
            ExtensionStatusEnum::ENABLED => throw new RuntimeException("{$moduleName}扩展已启用"),
            default => null,
        };
        $extensionModel->status = ExtensionStatusEnum::ENABLED;
        $extensionModel->save();
    }

    /**
     * 禁用扩展
     */
    public function disable(string $moduleName): void
    {
        $extensionModel = ExtensionModel::where(['id' => $moduleName])->findOrFail();
        if ($extensionModel->status != ExtensionStatusEnum::ENABLED) {
            throw new RuntimeException("{$moduleName}扩展未启用");
        }
        $extensionModel->status = ExtensionStatusEnum::DISABLED;
        $extensionModel->save();
    }

    /**
     * 获取扩展信息
     */
    public function getInfo(string $name): ExtensionModel
    {
        return $this->extensionList[$name] ?? ($this->extensionList[$name] = $this->app->cache->remember(
            sprintf(ExtensionModel::EXTENSION_INFO_CACHE_KEY, $name),
            function () use ($name) {
                return $this->initExtensionInfo($name);
            }));
    }

    private function initExtensionInfo(string $name): ExtensionModel
    {
        $categoryMap = $this->getCategoryMap();
        $extensionInfo = $this->getExtension($name)->getInfo();
        try {
            Validate::rule([
                'id' => 'require|max:50',
                'name' => 'require|max:100',
                'is_core' => 'in:0,1',
                'category' => 'in:' . implode(',', array_keys($categoryMap)),
                'description' => 'max:65535',
                'version' => 'require|max:20',
                'core_version' => 'require|max:20',
                'author' => 'require|max:100',
                'email' => 'email|max:100',
                'website' => 'url|max:255',
                'image' => 'url|max:255',
                'license' => 'max:50',
            ])->failException()->check($extensionInfo);
        } catch (ValidateException $exception) {
            Log::warning('module(' . $name . ') info error:' . $exception->getError());
        }
        if (!isset($extensionInfo['id']) || $extensionInfo['id'] !== $name) {
            throw new RuntimeException("{$name}扩展id与目录名不一致");
        }
        $extension = ExtensionModel::where(['id' => $name])->append(['status_text'])->findOrEmpty();
        if ($extension->isEmpty()) {
            $extensionInfo['status'] = 1; // 下载的扩展默认未安装
            if (isset($extensionInfo['is_core']) && $extensionInfo['is_core'] == 1) {
                $extensionInfo['status'] = 3; // 核心扩展默认启用
            }
            $extension->save($extensionInfo);
        }
        $extension['category_text'] = $categoryMap[$extension['category']] ?? '未知';
        return $this->extensionList[$name] = $extension;
    }

    /**
     * @return array
     */
    public function getCategoryMap(): array
    {
        if (empty($this->categoryMap)) {
            $this->categoryMap = array_to_map($this->getExtensionConfig('system', 'category'), 'code', 'text');
        }
        return $this->categoryMap;
    }

    public function getExtensionList(): array
    {
        foreach (Helper::extension_name_list() as $name) {
            $this->app->cache->set(sprintf(ExtensionModel::EXTENSION_INFO_CACHE_KEY, $name), $this->initExtensionInfo($name));
        }
        return $this->extensionList;
    }

    public function getExtensionConfigForm(string $moduleName): array
    {
        $config = array_merge(ExtensionConfig::BASE, array_values($this->getExtension($moduleName)->getConfig()));
        $extensionConfig = ExtensionConfigModel::where('extension_id', $moduleName)->column(['value', 'type',], 'key', true);
        foreach ($config as $key => &$item) {
            if (isset($item['field'])) {
                if (isset($extensionConfig[$item['field']])) {
                    $config[$key]['value'] = $extensionConfig[$item['field']]['value'];
                }
            } else {
                if (isset($item['children'])) {
                    foreach ($item['children'] as $childKey => &$childItem) {
                        if (isset($childItem['field'], $extensionConfig[$childItem['field']])) {
                            $config[$key]['children'][$childKey]['value'] = $extensionConfig[$childItem['field']]['value'];
                        }
                        if (isset($childItem['children'])) {
                            foreach ($childItem['children'] as $grandChildKey => $grandChildItem) {
                                if (isset($grandChildItem['field'], $extensionConfig[$grandChildItem['field']])) {
                                    $config[$key]['children'][$childKey]['children'][$grandChildKey]['value'] = $extensionConfig[$grandChildItem['field']]['value'];
                                }
                            }
                        }
                    }
                }
            }
        }
        Event::trigger('after_read_extension_config', [$config, $moduleName]);

        return $config;
    }

    public function migrations(string $id)
    {
        return app(Migrate::class, [$this->app, $id])->getMigrationList();
    }
}
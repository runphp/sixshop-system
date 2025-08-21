<?php
declare(strict_types=1);
namespace SixShop\System\Controller;

use SixShop\Core\Helper;
use SixShop\System\Enum\ExtensionStatusEnum;
use SixShop\System\ExtensionManager;
use SixShop\System\Migrate;
use think\App;
use think\facade\Event;
use think\paginator\driver\Bootstrap;
use think\Response;

class ExtensionController
{
    public function index(ExtensionManager $extensionManager): Response
    {
        $extensionList = $extensionManager->getExtensionList();
        $data = [
            'total' => count($extensionList),
            'enabled' => 0,
            'disabled' => 0,
            'installed'  => 0,
            'uninstalled' => 0,
            'category_map' => $extensionManager->getCategoryMap(),
        ];
        
        // 获取ExtensionService实例，用于检查菜单状态
        $extensionService = new \app\common\service\ExtensionService();
        
        foreach ($extensionList as &$extension) {
            // 检查每个扩展是否已有菜单
            try {
                $extension['has_menu'] = $extensionService->hasExtensionMenu($extension['id']);
            } catch (\Exception $e) {
                $extension['has_menu'] = false;
            }
            
            match ($extension['status']) {
                ExtensionStatusEnum::ENABLED => $data['enabled']++,
                ExtensionStatusEnum::DISABLED => $data['disabled']++,
                ExtensionStatusEnum::UNINSTALLED => $data['uninstalled']++,
                default => null,
            };
        }
        $data['installed'] = $data['total'] - $data['uninstalled'];
        return Helper::page_response(new Bootstrap(array_values($extensionList), $data['total'], 1, $data['total']), $data);
    }

    public function read(string $id, ExtensionManager $extensionManager): Response
    {
        $data = $extensionManager->getInfo($id);
        $filePath = Helper::extension_path($id) . '/README.md';
        if (file_exists($filePath)) {
            $data['markdown'] = file_get_contents($filePath);
        } else {
            // 处理文件不存在的情况
            $data['markdown'] = '无文档请补充文档,请将文档保存在扩展目录下'.$id.'/README.md';
        }
        $data['migrations'] = $extensionManager->migrations($id);
        return Helper::success_response($data);
    }
    public function install(string $id, ExtensionManager $extensionManager): Response
    {
        Event::trigger('before_install_extension', $id);
        $extensionManager->install($id);
        Event::trigger('after_install_extension', $id);
        return Helper::success_response();
    }

    public function uninstall(string $id, ExtensionManager $extensionManager): Response
    {
        Event::trigger('before_uninstall_extension', $id);
        Event::trigger('before_uninstall_'.$id.'_extension');
        $extensionManager->uninstall($id);
        Event::trigger('after_uninstall_extension', $id);
        Event::trigger('after_uninstall_'.$id.'_extension');
        return Helper::success_response();
    }

    public function enable(string $id, ExtensionManager $extensionManager): Response
    {
        $extensionManager->enable($id);
        Event::trigger('after_enable_extension', $id);
        return Helper::success_response();
    }

    public function disable(string $id, ExtensionManager $extensionManager): Response
    {
        $extensionManager->disable($id);
        Event::trigger('after_disable_extension', $id);
        return Helper::success_response();
    }

    public function normal(App $app): Response
    {
        $extensionPath = Helper::extension_path();
        $extensionDirs = array_diff(scandir($extensionPath), ['.', '..']);
        $options = [];
        foreach ($extensionDirs as $item) {
            if (!is_dir($extensionPath . $item)) {
                continue;
            }
            $infoFile = $extensionPath . $item . '/info.php';
            if (is_file($infoFile)) {
                $info = require $infoFile;
                if (!($info['is_core']?? false)) {
                    $options[] = [
                        'value' => $info['id'],
                        'label' => $info['name'],
                    ];
                }
            }
        }

        return Helper::success_response($options);
    }
}
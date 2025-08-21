<?php
declare(strict_types=1);

namespace SixShop\System\Controller;

use SixShop\Core\Helper;
use SixShop\System\ExtensionManager;
use think\Request;
use think\Response;
use think\response\Json;

class ExtensionConfigController
{
    public function read(string $id, ExtensionManager $extensionManager): Response
    {
        return Helper::success_response($extensionManager->getExtensionConfig($id));
    }
    public function edit(string $id, ExtensionManager $extensionManager): Response
    {
        return Helper::success_response($extensionManager->getExtensionConfigForm($id));
    }

    public function update(string $id, ExtensionManager $extensionManager, Request $request): Response
    {
        return Helper::success_response($extensionManager->saveConfig($id, $request->post()));
    }
}
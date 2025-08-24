<?php
declare(strict_types=1);

namespace SixShop\System\Hook;

use Closure;
use SixShop\Core\Attribute\Hook;
use SixShop\Core\Event\BeforeRegisterRouteEvent;
use SixShop\Core\Helper;
use SixShop\Core\Request;
use SixShop\System\Enum\ExtensionStatusEnum;
use SixShop\System\ExtensionManager;

class ExtensionStatusHook
{
    public function __construct(protected ExtensionManager $extensionManager)
    {
    }

    #[Hook(BeforeRegisterRouteEvent::class)]
    public function addMiddleware(BeforeRegisterRouteEvent $event)
    {
        $event->addMiddleware(Closure::fromCallable([$this, 'handle']));


    }

    public function handle(Request $request, Closure $next, $moduleName)
    {
        $extensionModel = $this->extensionManager->getInfo($moduleName);
        return match ($extensionModel->status) {
            ExtensionStatusEnum::ENABLED => $next($request),
            default => Helper::error_response(msg: '模块`' . $moduleName . '`未启用', httpCode: 403)
        };
    }
}
<?php
declare(strict_types=1);

namespace SixShop\System\Middleware;

use Closure;
use SixShop\Core\Request;
use think\Exception;
use think\Response;

class MacroPageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->macro('pageAndLimit', function (): array {
            $page = input('page/d', 1);
            $limit = input('limit/d', 10);
            throw_if($page < 1, Exception::class,'页码不能小于1');
            throw_if($limit < 1 || $limit > 100, Exception::class,'每页数量必须在1-100之间');
            return ['page' => $page, 'list_rows' => $limit];
        });
        return $next($request);
    }
}
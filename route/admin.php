<?php
declare(strict_types=1);

use SixShop\System\Controller\{ExtensionConfigController, ExtensionController};
use think\facade\Route;

Route::group('extension', function () {
    Route::get('normal', [ExtensionController::class, 'normal'])->option(['name' => 'system:extension:normal', 'description' => '获取普通扩展列表']);
    Route::resource('', ExtensionController::class, function () {
        Route::post('install', [ExtensionController::class, 'install'])->option(['name' => 'system:extension:install', 'description' => '安装扩展']);
        Route::post('uninstall', [ExtensionController::class, 'uninstall'])->option(['name' => 'system:extension:uninstall', 'description' => '卸载扩展']);
        Route::post('enable', [ExtensionController::class, 'enable'])->option(['name' => 'system:extension:enable', 'description' => '启用扩展']);
        Route::post('disable', [ExtensionController::class, 'disable'])->option(['name' => 'system:extension:disable', 'description' => '禁用扩展']);
    })->only([
        'index',
        'read',
    ]);
})->option([
    'name' => 'system:extension',
    'description' => '扩展'
])->middleware([
    'auth'
]);

Route::resource('extension_config', ExtensionConfigController::class)->only([
    'read',
    'edit',
    'update'
])->option([
    'name' => 'system:extension_config',
    'description' => '扩展配置'
])->middleware([
    'auth'
]);


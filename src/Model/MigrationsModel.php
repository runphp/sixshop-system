<?php
declare(strict_types=1);
namespace SixShop\System\Model;

use think\Model;

class MigrationsModel extends Model
{
    protected function getOptions(): array
    {
        return [
            'name' => 'migrations_',
            'pk' => 'version',
        ];
    }
}
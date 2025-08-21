<?php
declare(strict_types=1);

namespace SixShop\System\Model;

use SixShop\System\Enum\ExtensionStatusEnum;
use think\facade\Cache;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * Class SixShop\System\Model\ExtensionModel
 *
 * @property ExtensionStatusEnum $status 状态(1:未安装,2:安装,3:启用,4:禁用)
 * @property bool $is_core 是否核心扩展
 * @property string $author 作者
 * @property string $category 分类
 * @property string $core_version 支持的核心版本
 * @property string $create_time 创建时间
 * @property string $delete_time 删除时间
 * @property string $description 扩展描述
 * @property string $email 作者邮箱
 * @property string $id 扩展唯一标识符
 * @property string $image 扩展图片地址
 * @property string $license 开源协议
 * @property string $name 扩展名称
 * @property string $update_time 更新时间
 * @property string $version 扩展版本
 * @property string $website 扩展地址
 * @property-read mixed $status_text
 * @method static \think\db\Query onlyTrashed()
 * @method static \think\db\Query withTrashed()
 */
class ExtensionModel extends Model
{
    public const string EXTENSION_INFO_CACHE_KEY = 'extension_info:%s';

    public function getStatusTextAttr($value, $data): string
    {
        return $data['status']->toString();
    }

    use SoftDelete;

    public function onAfterWrite($model): void
    {
        Cache::delete(sprintf(self::EXTENSION_INFO_CACHE_KEY, $model->id));
    }

    protected function getOptions(): array
    {
        return [
            'name' => 'extension',
            'pk' => 'id',
            'type' => [
                'status' => ExtensionStatusEnum::class,
            ]
        ];
    }
}
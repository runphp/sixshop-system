<?php
declare(strict_types=1);
namespace SixShop\System\Model;

use think\Model;
use think\model\type\Json;

/**
 * Class SixShop\System\Model\ExtensionConfigModel
 *
 * @property array $value 配置值，JSON格式存储
 * @property int $id 主键
 * @property string $create_time 创建时间
 * @property string $extension_id 模块ID，关联extension表
 * @property string $key 配置项名称
 * @property string $title 配置名称
 * @property string $type 配置类型：input, radio, select等
 * @property string $update_time 更新时间
 */
class ExtensionConfigModel extends Model
{
    protected $name = 'extension_config';
    protected $pk = 'id';

    protected function getOptions(): array
    {
        return [
            'type' => [
                'value' => 'json'
            ],
            'jsonAssoc' => true,
        ];
    }

    public function getValueAttr(Json $value, array $data)
    {
        $raw = $value->value();

        $firstOrSelf = function ($val) {
            if (is_array($val)) {
                if (empty($val)) {
                    return '';
                }
                $vals = array_values($val);
                return $vals[0] ?? '';
            }
            return $val;
        };

        return match ($data['type']) {
            'radio', 'select', 'elTreeSelect', 'input' => (string)$firstOrSelf($raw),
            'switch' => (bool)$raw,
            'timePicker', 'colorPicker', 'datePicker', 'fcEditor' => (fn($val) => (is_array($val) && count($val) == 1) ? (array_values($val)[0] ?? '') : $val)($raw),
            default => $raw,
        };
    }
}
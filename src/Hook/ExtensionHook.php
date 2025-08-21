<?php
declare(strict_types=1);
namespace SixShop\System\Hook;

use SixShop\Core\Attribute\Hook;
use SixShop\System\Entity\ExtensionEntity;

class ExtensionHook
{
    public function __construct(private ExtensionEntity $extensionEntity)
    {
    }

    #[Hook('extension_version_change')]
    public function onExtensionVersionChange(array $extension): void
    {
        [$id, $version] = $extension;
        $this->extensionEntity->update($id, ['version' => $version]);
    }
}
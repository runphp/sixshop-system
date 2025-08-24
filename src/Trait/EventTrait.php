<?php
declare(strict_types=1);
namespace SixShop\System\Trait;


use think\facade\Event;

trait EventTrait
{
    public static function trigger(mixed $content): static
    {
        $event = new static($content);
        Event::trigger($event);
        return $event;
    }
}
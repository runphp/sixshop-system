<?php
declare(strict_types=1);
namespace SixShop\System\Job;

use SixShop\Core\Job\BaseJob;
use Closure;
class ClosureJob extends BaseJob
{

    protected bool $isClosure = true;
    protected function execute(Closure $data)
    {
        return value($data);
    }
}
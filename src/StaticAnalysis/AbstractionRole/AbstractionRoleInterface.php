<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use UnitEnum;

interface AbstractionRoleInterface extends UnitEnum
{
    /**
     * @return class-string
     */
    public function contract(): string;
}

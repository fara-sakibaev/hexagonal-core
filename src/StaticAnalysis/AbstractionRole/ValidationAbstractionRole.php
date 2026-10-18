<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\AbstractionRole;

use Hexagonal\Validation\RuleInterface;
use Hexagonal\Validation\ValidationGuard;
use Hexagonal\Validation\ValidationInputInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\Violation;
use Hexagonal\Validation\ViolationCode;
use Hexagonal\Validation\ViolationList;
use Hexagonal\Validation\ViolationPath;
use Override;

enum ValidationAbstractionRole implements AbstractionRoleInterface
{
    case Rule;
    case ValidationGuard;
    case ValidationInput;
    case Validator;
    case Violation;
    case ViolationCode;
    case ViolationList;
    case ViolationPath;

    /** @return class-string */
    #[Override]
    public function contract(): string
    {
        return match ($this) {
            self::Rule => RuleInterface::class,
            self::ValidationGuard => ValidationGuard::class,
            self::ValidationInput => ValidationInputInterface::class,
            self::Validator => ValidatorInterface::class,
            self::Violation => Violation::class,
            self::ViolationCode => ViolationCode::class,
            self::ViolationList => ViolationList::class,
            self::ViolationPath => ViolationPath::class,
        };
    }
}

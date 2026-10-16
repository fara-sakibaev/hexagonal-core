<?php

declare(strict_types=1);

use Hexagonal\Domain\PolicyInterface;
use Hexagonal\Validation\ValidationInputInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\ViolationList;

final class LegacyDto
{
    public function validateShape(): bool { return true; }
}

final readonly class LegacyInput implements ValidationInputInterface {}

/** @implements ValidatorInterface<LegacyInput> */
final class AllowedValidator implements ValidatorInterface
{
    public function validate(LegacyInput $input): ViolationList { return new ViolationList(); }
}

final class AllowedPolicy implements PolicyInterface
{
    public function validate(): bool { return true; }
}

interface ExternalValidationApi
{
    public function validateExternal(): bool;
}

final class RequiredExternalOverride implements ExternalValidationApi
{
    #[\Override]
    public function validateExternal(): bool { return true; }
}

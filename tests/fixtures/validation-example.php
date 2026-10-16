<?php

declare(strict_types=1);

namespace Hexagonal\Example\Domain;

use Hexagonal\Validation\RuleInterface;
use Hexagonal\Validation\ValidationInputInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\Violation;
use Hexagonal\Validation\ViolationCode;
use Hexagonal\Validation\ViolationList;
use Hexagonal\Validation\ViolationPath;

final readonly class NameSnapshot implements ValidationInputInterface
{
    public function __construct(public string $name, public bool $alreadyUsed) {}
}

/** @implements RuleInterface<NameSnapshot> */
final class NameRequiredRule implements RuleInterface
{
    public function validate(NameSnapshot $input): ViolationList
    {
        return trim($input->name) === ''
            ? new ViolationList(new Violation(new ViolationCode('name.required'), new ViolationPath('name')))
            : new ViolationList();
    }
}

/** @implements RuleInterface<NameSnapshot> */
final class NameAvailableRule implements RuleInterface
{
    public function validate(NameSnapshot $input): ViolationList
    {
        return $input->alreadyUsed
            ? new ViolationList(new Violation(new ViolationCode('name.used'), new ViolationPath('name')))
            : new ViolationList();
    }
}

/** @implements ValidatorInterface<NameSnapshot> */
final readonly class NameValidator implements ValidatorInterface
{
    public function __construct(private NameRequiredRule $required, private NameAvailableRule $available) {}

    public function validate(NameSnapshot $input): ViolationList
    {
        return $this->required->validate($input)->merge($this->available->validate($input));
    }
}

final readonly class RegisterNamePort
{
    public function __construct(private NameValidator $validator) {}

    public function check(string $name, bool $alreadyUsed): ViolationList
    {
        return $this->validator->validate(new NameSnapshot($name, $alreadyUsed));
    }
}

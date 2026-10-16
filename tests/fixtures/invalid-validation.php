<?php

declare(strict_types=1);

use Hexagonal\Validation\RuleInterface;
use Hexagonal\Validation\ValidationInputInterface;
use Hexagonal\Validation\ValidatorInterface;
use Hexagonal\Validation\ViolationList;

final readonly class IntendedInput implements ValidationInputInterface {}
final readonly class OtherInput implements ValidationInputInterface {}

/** @implements ValidatorInterface<IntendedInput> */
final class MissingValidatorMethod implements ValidatorInterface {}

/** @implements ValidatorInterface<IntendedInput> */
final class WrongValidatorInput implements ValidatorInterface
{
    public function validate(OtherInput $input): ViolationList { return new ViolationList(); }
}

/** @implements ValidatorInterface<IntendedInput> */
final class BroadValidatorInput implements ValidatorInterface
{
    public function validate(ValidationInputInterface $input): ViolationList { return new ViolationList(); }
}

/** @implements RuleInterface<IntendedInput> */
final class WrongRuleReturn implements RuleInterface
{
    public function validate(IntendedInput $input): string { return ''; }
}

/** @implements RuleInterface<IntendedInput> */
final class MissingRuleMethod implements RuleInterface {}

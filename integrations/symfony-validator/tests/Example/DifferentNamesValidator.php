<?php

declare(strict_types=1);

namespace Hexagonal\SymfonyValidator\Tests\Example;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class DifferentNamesValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DifferentNames || !($value instanceof RegistrationInput || $value instanceof TransferInput)) {
            return;
        }
        if ($value->first === $value->second) {
            $this->context->buildViolation('Names must differ.')->addViolation();
        }
    }
}

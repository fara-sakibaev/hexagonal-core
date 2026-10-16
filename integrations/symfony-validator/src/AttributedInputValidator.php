<?php

declare(strict_types=1);

namespace Hexagonal\SymfonyValidator;

use Hexagonal\Validation\ValidationInputInterface;
use Hexagonal\Validation\Violation;
use Hexagonal\Validation\ViolationCode;
use Hexagonal\Validation\ViolationList;
use Hexagonal\Validation\ViolationPath;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class AttributedInputValidator
{
    public function __construct(private ValidatorInterface $validator) {}

    public function validate(ValidationInputInterface $input): ViolationList
    {
        $violations = [];
        foreach ($this->validator->validate($input) as $failure) {
            $constraint = $failure->getConstraint();
            $code = $failure->getCode();
            if ($code === null || $code === '') {
                $code = $constraint instanceof Constraint
                    ? 'symfony.constraint.'.strtolower(str_replace('\\', '.', $constraint::class))
                    : 'symfony.constraint.unknown';
            }
            $violations[] = new Violation(new ViolationCode($code), new ViolationPath($failure->getPropertyPath()));
        }
        return new ViolationList(...$violations);
    }
}

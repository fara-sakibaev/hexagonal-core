<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/Example/DifferentNames.php';
require __DIR__.'/Example/DifferentNamesValidator.php';
require __DIR__.'/Example/RegistrationInput.php';
require __DIR__.'/Example/TransferInput.php';

use Hexagonal\SymfonyValidator\AttributedInputValidator;
use Hexagonal\SymfonyValidator\Tests\Example\AddressInput;
use Hexagonal\SymfonyValidator\Tests\Example\DifferentNames;
use Hexagonal\SymfonyValidator\Tests\Example\RegistrationInput;
use Hexagonal\SymfonyValidator\Tests\Example\TransferInput;
use Symfony\Component\Validator\Validation;

$bridge = new AttributedInputValidator(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator());
$violations = iterator_to_array($bridge->validate(new RegistrationInput('', '', new AddressInput(''))));
$paths = array_map(static fn ($v) => $v->path->value, $violations);
assert(in_array('first', $paths, true));
assert(in_array('address.city', $paths, true));
assert(in_array('', $paths, true));
$fieldViolation = array_values(array_filter($violations, static fn ($v) => 'first' === $v->path->value))[0];
assert('' !== $fieldViolation->code->value);
$nestedViolation = array_values(array_filter($violations, static fn ($v) => 'address.city' === $v->path->value))[0];
assert('' !== $nestedViolation->code->value);
$classViolation = array_values(array_filter($violations, static fn ($v) => '' === $v->path->value))[0];
assert($classViolation->code->value === 'symfony.constraint.'.strtolower(str_replace('\\', '.', DifferentNames::class)));
assert(1 === count(iterator_to_array($bridge->validate(new TransferInput('same', 'same')))));
assert($bridge->validate(new RegistrationInput('a', 'b', new AddressInput('Yerevan')))->isEmpty());

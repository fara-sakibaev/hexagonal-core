<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';
require __DIR__.'/fixtures/validation-example.php';

use Hexagonal\Example\Domain\NameAvailableRule;
use Hexagonal\Example\Domain\NameRequiredRule;
use Hexagonal\Example\Domain\NameSnapshot;
use Hexagonal\Example\Domain\NameValidator;
use Hexagonal\Example\Domain\RegisterNamePort;
use Hexagonal\Validation\ValidationGuard;
use Hexagonal\Validation\ViolationList;
use Hexagonal\Validation\ViolationPath;

$port = new RegisterNamePort(new NameValidator(new NameRequiredRule(), new NameAvailableRule()));
assert($port->check('available', false)->isEmpty());
$violations = iterator_to_array($port->check('', true));
assert(2 === count($violations));
assert('name.required' === $violations[0]->code->value);
assert('name.used' === $violations[1]->code->value);
assert('name' === $violations[0]->path->value);
assert('' === ViolationPath::root()->value);

$validator = new NameValidator(new NameRequiredRule(), new NameAvailableRule());
$guard = new ValidationGuard(
    $validator->validate(...),
    static fn (ViolationList $violations): DomainException => new DomainException((string) count($violations)),
);
$guard->assertValid(new NameSnapshot('available', false));
try {
    $guard->assertValid(new NameSnapshot('', true));
    assert(false, 'Expected validation exception.');
} catch (DomainException $exception) {
    assert('2' === $exception->getMessage());
}

<?php

declare(strict_types=1);

namespace Hexagonal\Validation;

use Closure;
use Throwable;

/** @template TInput of ValidationInputInterface */
final readonly class ValidationGuard
{
    /** @var Closure(TInput): ViolationList */
    private Closure $validate;

    /** @var Closure(ViolationList): Throwable */
    private Closure $exceptionFactory;

    /**
     * @param callable(TInput): ViolationList    $validate
     * @param callable(ViolationList): Throwable $exceptionFactory
     */
    public function __construct(callable $validate, callable $exceptionFactory)
    {
        $this->validate = $validate(...);
        $this->exceptionFactory = $exceptionFactory(...);
    }

    /** @param TInput $input */
    public function assertValid(ValidationInputInterface $input): void
    {
        $violations = ($this->validate)($input);
        if (!$violations->isEmpty()) {
            throw ($this->exceptionFactory)($violations);
        }
    }
}

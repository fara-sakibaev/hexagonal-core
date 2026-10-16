<?php

declare(strict_types=1);

namespace Hexagonal\SymfonyValidator\Tests\Example;

use Hexagonal\Validation\ValidationInputInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[DifferentNames]
final readonly class RegistrationInput implements ValidationInputInterface
{
    public function __construct(
        #[Assert\NotBlank] public string $first,
        public string $second,
        #[Assert\Valid] public AddressInput $address,
    ) {
    }
}

final readonly class AddressInput
{
    public function __construct(#[Assert\NotBlank] public string $city)
    {
    }
}

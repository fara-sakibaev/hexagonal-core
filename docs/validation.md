# Typed validation

`Hexagonal\Validation` is layer neutral. Each input implements `ValidationInputInterface`. A concrete validator implements `ValidatorInterface<TInput>` and declares `public validate(TInput): ViolationList`; a reusable named rule implements `RuleInterface<TInput>` with the same concrete signature. PHPStan and Psalm enforce these signatures because PHP cannot narrow a parameter declared on an interface.

Place a concrete validator where its rule belongs: domain facts in Domain, request and source format checks in Application or an adapter. Domain validation needs no Application or Symfony dependency. Keep aggregate invariants and decisions about mutable business state in domain policies.

`tests/fixtures/validation-example.php` shows `RegisterNamePort` receiving `NameValidator` explicitly. Two named rules return independent violations. The port obtains an `alreadyUsed` fact before creating `NameSnapshot`; it does not ask the validator to fetch it. Production callers should read facts within the same consistency boundary as the write when a stale snapshot would invalidate the decision. Normalize text, dates, and source formats before creating the input. Validation reports facts; it does not alter them.

An empty `ViolationList` is valid. `ViolationCode` holds a stable machine code; `ViolationPath::root()` represents an object-level error. Callers map codes and paths to localized messages. `merge()` preserves rule order.

When a caller must reject invalid input, inject `ValidationGuard<TInput>`. Configure it with the concrete validator's first-class callable and a factory for the caller's exception:

```php
$guard = new ValidationGuard(
    $nameValidator->validate(...),
    static fn (ViolationList $violations): InvalidName => new InvalidName($violations),
);
$guard->assertValid($snapshot);
```

The guard does nothing for an empty list and passes the complete list to the exception factory otherwise. Keep the exception type in the consuming layer. The validator still returns violations for callers that need to display or combine them. Register a named guard in DI for each input and exception mapping.

## Symfony attributes

Install `fara-sakibaev/hexagonal-symfony-validator` separately. It requires `symfony/validator`; the core package does not. Build Symfony's validator with attribute mapping, then inject it into `Hexagonal\SymfonyValidator\AttributedInputValidator`. This bridge returns core `ViolationList` and preserves Symfony property paths, including nested paths and the empty root path. Symfony codes are retained. A missing code becomes `symfony.constraint.<lowercase dotted fully qualified constraint class>`; an absent constraint becomes `symfony.constraint.unknown`.

The runnable example is `integrations/symfony-validator/tests/integration.php`. Its `RegistrationInput` uses `#[Assert\NotBlank]` and `#[Assert\Valid]`. `RegistrationInput` and `TransferInput` both reference the named `#[DifferentNames]` constraint; `DifferentNamesValidator` contains the shared rule. Attribute metadata remains in the optional package. For a typed port, wrap `AttributedInputValidator` in a concrete validator for that port's input type.

## Consumer migration

1. Inventory `validate*`, `assert*`, and inline checks; classify input checks, normalization, and aggregate policies.
2. Move normalization before typed input construction. Create one named validator per input and extract shared checks to named rules.
3. Inject validators into callers. Capture external facts once and include them in a typed snapshot.
4. Enable the optional `phpstan-legacy-validation.neon` rule or `Hexagonal\StaticAnalysis\Psalm\LegacyValidationPlugin` in consumer CI after migration. Both report `validate*` declarations outside validators and policies, except signatures required by external interfaces or abstract parents. Symfony `#[Assert\Callback]` methods still require migration. The setup command imports only the standard handler and signature checks; it does not enable this profile.
5. Map violation codes to presentation text at the consumer boundary. Review renamed and inline checks manually; the legacy profile cannot detect them.

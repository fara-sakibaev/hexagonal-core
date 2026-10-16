<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

use function class_exists;

final class LegacyValidationPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(LegacyValidationCheck::class);
        $registration->registerHooksFromClass(LegacyValidationCheck::class);
    }
}

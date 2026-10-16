<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

final class LegacyValidationPlugin implements PluginEntryPointInterface
{
    #[\Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(LegacyValidationCheck::class);
        $registration->registerHooksFromClass(LegacyValidationCheck::class);
    }
}

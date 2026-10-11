<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

final class HandlerPlugin implements PluginEntryPointInterface
{
    #[\Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(HandlerCheck::class);
        $registration->registerHooksFromClass(HandlerCheck::class);
    }
}

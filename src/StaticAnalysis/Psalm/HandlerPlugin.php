<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Override;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

use function class_exists;

final class HandlerPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        class_exists(HandlerCheck::class);
        $registration->registerHooksFromClass(HandlerCheck::class);
    }
}

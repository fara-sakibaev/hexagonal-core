<?php

declare(strict_types=1);

namespace Hexagonal\StaticAnalysis\Psalm;

use Override;
use Psalm\Config;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\PluginRegistrationSocket;
use SimpleXMLElement;

use function class_exists;

final class HandlerPlugin implements PluginEntryPointInterface
{
    #[Override]
    public function __invoke(PluginRegistrationSocket $registration, ?SimpleXMLElement $config = null): void
    {
        $registration->config->safeSetCustomErrorLevel('DeprecatedReadPort', Config::REPORT_ERROR);
        class_exists(ReadPortCheck::class);
        $registration->registerHooksFromClass(ReadPortCheck::class);
        class_exists(PortMethodCheck::class);
        $registration->registerHooksFromClass(PortMethodCheck::class);
        class_exists(GenericPortMethodCheck::class);
        $registration->registerHooksFromClass(GenericPortMethodCheck::class);
        class_exists(HandlerCheck::class);
        $registration->registerHooksFromClass(HandlerCheck::class);
        if (null !== $config && isset($config->architecture)) {
            class_exists(ArchitectureCheck::class);
            ArchitectureCheck::$configuration = (string) $config->architecture['path'];
            $registration->registerHooksFromClass(ArchitectureCheck::class);
        }
    }
}

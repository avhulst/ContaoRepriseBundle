<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Exposes the entrypoints.json path of every Reprise build as a parameter.
 *
 * Reprise only sets "reprise.entrypoints_paths" when its cache is enabled and has
 * no API to list entries, so the paths are read from its lookup services.
 */
class EntrypointsPathsPass implements CompilerPassInterface
{
    public const PARAMETER = 'trassd_contao_reprise.entrypoints_paths';

    private const DEFAULT_LOOKUP = 'reprise.entrypoints_lookup';

    public function process(ContainerBuilder $container): void
    {
        $paths = [];

        foreach ($container->getDefinitions() as $id => $definition) {
            if (self::DEFAULT_LOOKUP === $id) {
                $build = '_default';
            } elseif (str_starts_with($id, self::DEFAULT_LOOKUP.'.')) {
                $build = substr($id, \strlen(self::DEFAULT_LOOKUP) + 1);
            } else {
                continue;
            }

            $path = $definition->getArguments()[0] ?? null;

            if (\is_string($path)) {
                $paths[$build] = $path;
            }
        }

        $container->setParameter(self::PARAMETER, $paths);
    }
}

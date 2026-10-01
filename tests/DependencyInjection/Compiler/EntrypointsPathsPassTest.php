<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\MergeExtensionConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Reprise\Asset\EntrypointsLookup;
use Symfony\Reprise\RepriseBundle;
use Trassd\ContaoRepriseBundle\DependencyInjection\Compiler\EntrypointsPathsPass;

class EntrypointsPathsPassTest extends TestCase
{
    public function testCollectsDefaultAndNamedBuilds(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('reprise.entrypoints_lookup', new Definition(EntrypointsLookup::class, ['/app/public/layout/entrypoints.json', true]));
        $container->setDefinition('reprise.entrypoints_lookup.landing', new Definition(EntrypointsLookup::class, ['/app/public/landing/entrypoints.json', true]));
        $container->setDefinition('reprise.entrypoints_lookup_collection', new Definition(\stdClass::class, ['/not/a/build/entrypoints.json']));
        $container->setDefinition('some.other_service', new Definition(EntrypointsLookup::class, ['/ignored/entrypoints.json']));

        (new EntrypointsPathsPass())->process($container);

        $this->assertSame(
            [
                '_default' => '/app/public/layout/entrypoints.json',
                'landing' => '/app/public/landing/entrypoints.json',
            ],
            $container->getParameter(EntrypointsPathsPass::PARAMETER),
        );
    }

    public function testSetsAnEmptyMapWithoutRepriseDefinitions(): void
    {
        $container = new ContainerBuilder();

        (new EntrypointsPathsPass())->process($container);

        $this->assertSame([], $container->getParameter('trassd_contao_reprise.entrypoints_paths'));
    }

    public function testSkipsLookupDefinitionsWithoutAPathArgument(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('reprise.entrypoints_lookup', new Definition(EntrypointsLookup::class));
        $container->setDefinition('reprise.entrypoints_lookup.landing', new Definition(EntrypointsLookup::class, ['/app/public/landing/entrypoints.json']));

        (new EntrypointsPathsPass())->process($container);

        $this->assertSame(['landing' => '/app/public/landing/entrypoints.json'], $container->getParameter(EntrypointsPathsPass::PARAMETER));
    }

    /**
     * Guards against changes of the Reprise service ids or argument layout the pass
     * relies on.
     */
    public function testReadsThePathsFromTheRealRepriseExtension(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.project_dir', '/app');
        $container->setParameter('kernel.build_dir', '/app/var/cache');
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'prod');

        $bundle = new RepriseBundle();
        $container->registerExtension($bundle->getContainerExtension());
        $container->loadFromExtension('reprise', [
            'output_path' => '%kernel.project_dir%/public/layout',
            'builds' => ['landing' => '/app/public/landing'],
        ]);

        (new MergeExtensionConfigurationPass())->process($container);
        (new EntrypointsPathsPass())->process($container);

        $this->assertSame(
            [
                '_default' => '/app/public/layout/entrypoints.json',
                'landing' => '/app/public/landing/entrypoints.json',
            ],
            $container->getParameterBag()->resolveValue($container->getParameter(EntrypointsPathsPass::PARAMETER)),
        );
    }
}

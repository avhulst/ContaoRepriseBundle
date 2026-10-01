<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Trassd\ContaoRepriseBundle\ContaoRepriseBundle;
use Trassd\ContaoRepriseBundle\DependencyInjection\Compiler\EntrypointsPathsPass;

class ContaoRepriseBundleTest extends TestCase
{
    public function testRegistersTheEntrypointsPathsPass(): void
    {
        $container = new ContainerBuilder();

        (new ContaoRepriseBundle())->build($container);

        $passes = array_filter(
            $container->getCompilerPassConfig()->getBeforeOptimizationPasses(),
            static fn (object $pass): bool => $pass instanceof EntrypointsPathsPass,
        );

        $this->assertCount(1, $passes);
    }
}

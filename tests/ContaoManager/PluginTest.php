<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Reprise\RepriseBundle;
use Trassd\ContaoRepriseBundle\ContaoManager\Plugin;
use Trassd\ContaoRepriseBundle\ContaoRepriseBundle;

class PluginTest extends TestCase
{
    public function testRegistersRepriseAndTheBundleAfterCore(): void
    {
        $bundles = (new Plugin())->getBundles($this->createStub(ParserInterface::class));

        $this->assertCount(2, $bundles);
        $this->assertContainsOnlyInstancesOf(BundleConfig::class, $bundles);

        $this->assertSame(RepriseBundle::class, $bundles[0]->getName());

        $this->assertSame(ContaoRepriseBundle::class, $bundles[1]->getName());
        $this->assertSame([ContaoCoreBundle::class, RepriseBundle::class], $bundles[1]->getLoadAfter());
    }
}

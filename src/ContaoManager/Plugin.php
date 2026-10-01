<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Symfony\Reprise\RepriseBundle;
use Trassd\ContaoRepriseBundle\ContaoRepriseBundle;

class Plugin implements BundlePluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(RepriseBundle::class),
            BundleConfig::create(ContaoRepriseBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class, RepriseBundle::class]),
        ];
    }
}

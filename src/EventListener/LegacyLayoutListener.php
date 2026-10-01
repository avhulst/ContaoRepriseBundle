<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Trassd\ContaoRepriseBundle\Entrypoint\EntrypointTagInjector;

#[AsHook('getPageLayout')]
class LegacyLayoutListener
{
    public function __construct(private readonly EntrypointTagInjector $injector)
    {
    }

    public function __invoke(PageModel $page, LayoutModel $layout, PageRegular $pageRegular): void
    {
        if ('default' !== $layout->type) {
            return;
        }

        $this->injector->inject($layout);
    }
}

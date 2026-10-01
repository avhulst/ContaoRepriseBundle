<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\EventListener;

use Contao\CoreBundle\Event\LayoutEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Trassd\ContaoRepriseBundle\Entrypoint\EntrypointTagInjector;

#[AsEventListener]
class ModernLayoutListener
{
    public function __construct(private readonly EntrypointTagInjector $injector)
    {
    }

    public function __invoke(LayoutEvent $event): void
    {
        $layout = $event->getLayout();

        if (null === $layout || 'modern' !== $layout->type) {
            return;
        }

        $this->injector->inject($layout);
    }
}

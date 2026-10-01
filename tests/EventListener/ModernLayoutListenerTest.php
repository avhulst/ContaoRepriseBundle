<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\EventListener;

use Contao\CoreBundle\Event\LayoutEvent;
use Contao\CoreBundle\Twig\LayoutTemplate;
use Contao\LayoutModel;
use Contao\PageModel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Response;
use Trassd\ContaoRepriseBundle\Entrypoint\EntrypointTagInjector;
use Trassd\ContaoRepriseBundle\EventListener\ModernLayoutListener;

class ModernLayoutListenerTest extends ContaoTestCase
{
    public function testInjectsForModernLayouts(): void
    {
        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'modern']);

        $injector = $this->createMock(EntrypointTagInjector::class);
        $injector
            ->expects($this->once())
            ->method('inject')
            ->with($layout)
        ;

        (new ModernLayoutListener($injector))($this->createEvent($layout));
    }

    public function testIgnoresOtherLayoutTypes(): void
    {
        $injector = $this->createMock(EntrypointTagInjector::class);
        $injector
            ->expects($this->never())
            ->method('inject')
        ;

        (new ModernLayoutListener($injector))(
            $this->createEvent($this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'default'])),
        );
    }

    public function testIgnoresPagesWithoutLayout(): void
    {
        $injector = $this->createMock(EntrypointTagInjector::class);
        $injector
            ->expects($this->never())
            ->method('inject')
        ;

        (new ModernLayoutListener($injector))($this->createEvent(null));
    }

    private function createEvent(LayoutModel|null $layout): LayoutEvent
    {
        return new LayoutEvent(
            new LayoutTemplate('page/layout', static fn (): Response => new Response()),
            $this->createStub(PageModel::class),
            $layout,
        );
    }
}

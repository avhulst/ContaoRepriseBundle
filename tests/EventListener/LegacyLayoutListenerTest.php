<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\EventListener;

use Contao\LayoutModel;
use Contao\PageModel;
use Contao\PageRegular;
use Contao\TestCase\ContaoTestCase;
use Trassd\ContaoRepriseBundle\Entrypoint\EntrypointTagInjector;
use Trassd\ContaoRepriseBundle\EventListener\LegacyLayoutListener;

class LegacyLayoutListenerTest extends ContaoTestCase
{
    public function testInjectsForDefaultLayouts(): void
    {
        $layout = $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'default']);

        $injector = $this->createMock(EntrypointTagInjector::class);
        $injector
            ->expects($this->once())
            ->method('inject')
            ->with($layout)
        ;

        (new LegacyLayoutListener($injector))(
            $this->createStub(PageModel::class),
            $layout,
            $this->createStub(PageRegular::class),
        );
    }

    public function testIgnoresOtherLayoutTypes(): void
    {
        $injector = $this->createMock(EntrypointTagInjector::class);
        $injector
            ->expects($this->never())
            ->method('inject')
        ;

        (new LegacyLayoutListener($injector))(
            $this->createStub(PageModel::class),
            $this->createClassWithPropertiesStub(LayoutModel::class, ['type' => 'modern']),
            $this->createStub(PageRegular::class),
        );
    }
}

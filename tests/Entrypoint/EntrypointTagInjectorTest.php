<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\Entrypoint;

use Contao\LayoutModel;
use Contao\TestCase\ContaoTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Reprise\Asset\EntrypointsLookup;
use Symfony\Reprise\Asset\EntrypointsLookupCollection;
use Symfony\Reprise\Asset\TagRenderer;
use Symfony\Reprise\Exception\EntrypointNotFoundException;
use Symfony\Reprise\Exception\EntrypointsFileNotFoundException;
use Trassd\ContaoRepriseBundle\Entrypoint\EntrypointTagInjector;

class EntrypointTagInjectorTest extends ContaoTestCase
{
    private const FIXTURES = __DIR__.'/../Fixtures';

    private const APP_CSS = '<link rel="stylesheet" href="/layout/app-def456.css">';

    private const APP_JS = '<script src="/layout/app-abc123.js" type="module"></script>';

    private const VITE_CLIENT = '<script type="module" src="https://example.ddev.site:8080/layout/@vite/client"></script>';

    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_HEAD']);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_HEAD']);

        parent::tearDown();
    }

    public function testDoesNothingWhenTheFieldIsNull(): void
    {
        $this->createInjector()->inject($this->createLayout(null));

        $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);
    }

    public function testDoesNothingWhenNoEntryIsSelected(): void
    {
        $this->createInjector()->inject($this->createLayout(serialize([])));

        $this->assertArrayNotHasKey('TL_HEAD', $GLOBALS);
    }

    public function testAddsLinksBeforeScriptsInTheSelectedOrder(): void
    {
        $this->createInjector()->inject($this->createLayout(serialize(['landing', 'app'])));

        $this->assertSame(
            [
                self::APP_CSS,
                '<script src="/layout/landing-111.js" type="module"></script>',
                self::APP_JS,
            ],
            $GLOBALS['TL_HEAD'],
        );
    }

    public function testAddsASharedStylesheetOnlyOnce(): void
    {
        $this->createInjector()->inject($this->createLayout(serialize(['app', 'shared'])));

        $this->assertSame(
            [
                self::APP_CSS,
                self::APP_JS,
                '<script src="/layout/shared-222.js" type="module"></script>',
            ],
            $GLOBALS['TL_HEAD'],
        );
    }

    public function testRendersANamedBuildWithTheDevServerClientOnlyOnce(): void
    {
        $this->createInjector()->inject($this->createLayout(serialize(['dev:app', 'dev:admin'])));

        $this->assertSame(
            [
                self::VITE_CLIENT.'<script src="https://example.ddev.site:8080/layout/app.js" type="module"></script>',
                '<script src="https://example.ddev.site:8080/layout/admin.js" type="module"></script>',
            ],
            $GLOBALS['TL_HEAD'],
        );
    }

    public function testThrowsForAnUnknownEntryInDebugMode(): void
    {
        $this->expectException(EntrypointNotFoundException::class);

        $this->createInjector(debug: true)->inject($this->createLayout(serialize(['nope'])));
    }

    public function testThrowsForAMissingEntrypointsFileInDebugMode(): void
    {
        $this->expectException(EntrypointsFileNotFoundException::class);

        $this->createInjector(debug: true)->inject($this->createLayout(serialize(['missing:app'])));
    }

    /**
     * @param list<string> $values
     */
    #[DataProvider('provideInvalidValues')]
    public function testSkipsAndLogsInvalidEntriesInProduction(array $values): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains(\sprintf('"%s"', $values[0])),
                $this->callback(static fn (array $context): bool => ($context['exception'] ?? null) instanceof \Throwable),
            )
        ;

        $this->createInjector($logger)->inject($this->createLayout(serialize([...$values, 'app'])));

        $this->assertSame([self::APP_CSS, self::APP_JS], $GLOBALS['TL_HEAD']);
    }

    /**
     * @return iterable<string, array{list<string>}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'unknown entry' => [['nope']];
        yield 'missing entrypoints.json' => [['missing:app']];
        yield 'broken entrypoints.json' => [['broken:app']];
        yield 'unknown build' => [['unknown:app']];
    }

    public function testRespectsReprisesNonStrictMode(): void
    {
        $this->createInjector(debug: true)->inject($this->createLayout(serialize(['lenient-missing:app', 'lenient:nope', 'app'])));

        $this->assertSame([self::APP_CSS, self::APP_JS], $GLOBALS['TL_HEAD']);
    }

    public function testAcceptsNumericValuesFromTheDatabase(): void
    {
        $injector = $this->createInjector(defaultBuild: self::FIXTURES.'/numeric/entrypoints.json');
        $injector->inject($this->createLayout(serialize([2024])));

        $this->assertSame(['<script src="/layout/2024-333.js" type="module"></script>'], $GLOBALS['TL_HEAD']);
    }

    public function testParsesValues(): void
    {
        $this->assertSame([null, 'app'], EntrypointTagInjector::parseValue('app'));
        $this->assertSame(['landing', 'main'], EntrypointTagInjector::parseValue('landing:main'));
        $this->assertSame(['landing', 'a:b'], EntrypointTagInjector::parseValue('landing:a:b'));
    }

    private function createInjector(LoggerInterface|null $logger = null, bool $debug = false, string $defaultBuild = self::FIXTURES.'/prod/entrypoints.json'): EntrypointTagInjector
    {
        $builds = [
            '_default' => $defaultBuild,
            'dev' => self::FIXTURES.'/dev/entrypoints.json',
            'broken' => self::FIXTURES.'/broken/entrypoints.json',
            'missing' => self::FIXTURES.'/missing/entrypoints.json',
        ];

        // Like the shared services in the container: one lookup instance per build
        $lookups = array_map(
            static function (string $path): \Closure {
                $lookup = new EntrypointsLookup($path);

                return static fn (): EntrypointsLookup => $lookup;
            },
            $builds,
        );

        // reprise.strict_mode: false
        $lenient = new EntrypointsLookup(self::FIXTURES.'/prod/entrypoints.json', false);
        $lenientMissing = new EntrypointsLookup(self::FIXTURES.'/missing/entrypoints.json', false);
        $lookups['lenient'] = static fn (): EntrypointsLookup => $lenient;
        $lookups['lenient-missing'] = static fn (): EntrypointsLookup => $lenientMissing;

        $tagRenderer = new TagRenderer(
            new EntrypointsLookupCollection(new ServiceLocator($lookups), '_default'),
            new Packages(new Package(new EmptyVersionStrategy())),
            preload: false,
        );

        return new EntrypointTagInjector($tagRenderer, $logger ?? $this->createStub(LoggerInterface::class), $debug);
    }

    private function createLayout(string|null $entrypoints): LayoutModel
    {
        return $this->createClassWithPropertiesStub(LayoutModel::class, [
            'id' => 42,
            'type' => 'default',
            'reprise_entrypoints' => $entrypoints,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Tests\Entrypoint;

use PHPUnit\Framework\TestCase;
use Trassd\ContaoRepriseBundle\Entrypoint\EntrypointOptionsProvider;

class EntrypointOptionsProviderTest extends TestCase
{
    private const FIXTURES = __DIR__.'/../Fixtures';

    public function testListsTheEntriesOfTheDefaultBuild(): void
    {
        $provider = new EntrypointOptionsProvider(['_default' => self::FIXTURES.'/prod/entrypoints.json']);

        $this->assertSame(['app' => 'app', 'landing' => 'landing', 'shared' => 'shared'], $provider());
    }

    public function testPrefixesNamedBuildsAndLabelsThemWithTheBuildName(): void
    {
        $provider = new EntrypointOptionsProvider([
            '_default' => self::FIXTURES.'/prod/entrypoints.json',
            'dev' => self::FIXTURES.'/dev/entrypoints.json',
        ]);

        $this->assertSame(
            [
                'app' => 'app',
                'landing' => 'landing',
                'shared' => 'shared',
                'dev:app' => 'app (dev)',
                'dev:admin' => 'admin (dev)',
            ],
            $provider(),
        );
    }

    public function testIgnoresMissingAndBrokenFiles(): void
    {
        $provider = new EntrypointOptionsProvider([
            '_default' => self::FIXTURES.'/missing/entrypoints.json',
            'broken' => self::FIXTURES.'/broken/entrypoints.json',
            'dev' => self::FIXTURES.'/dev/entrypoints.json',
        ]);

        $this->assertSame(['dev:app' => 'app (dev)', 'dev:admin' => 'admin (dev)'], $provider());
    }

    public function testReturnsNoOptionsForAnEmptyEntrypointsObject(): void
    {
        $provider = new EntrypointOptionsProvider(['_default' => self::FIXTURES.'/empty/entrypoints.json']);

        $this->assertSame([], $provider());
    }

    public function testReturnsNoOptionsForAFileWithoutEntryPoints(): void
    {
        $provider = new EntrypointOptionsProvider(['_default' => self::FIXTURES.'/incomplete/entrypoints.json']);

        $this->assertSame([], $provider());
    }

    public function testReturnsNumericEntryNamesAsStrings(): void
    {
        $provider = new EntrypointOptionsProvider(['_default' => self::FIXTURES.'/numeric/entrypoints.json']);

        $this->assertSame([2024 => '2024'], $provider());
    }

    public function testReturnsNoOptionsWithoutBuilds(): void
    {
        $this->assertSame([], (new EntrypointOptionsProvider([]))());
    }
}

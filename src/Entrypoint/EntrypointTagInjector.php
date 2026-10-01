<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Entrypoint;

use Contao\LayoutModel;
use Contao\StringUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Reprise\Asset\TagRenderer;
use Symfony\Reprise\Exception\ExceptionInterface;

/**
 * Adds the link and script tags of the entrypoints selected in a page layout to
 * the page head.
 */
class EntrypointTagInjector
{
    public function __construct(
        #[Autowire(service: 'reprise.tag_renderer')]
        private readonly TagRenderer $tagRenderer,
        #[Autowire(service: 'monolog.logger.contao.error')]
        private readonly LoggerInterface $logger,
        #[Autowire(param: 'kernel.debug')]
        private readonly bool $debug,
    ) {
    }

    public function inject(LayoutModel $layout): void
    {
        $links = [];
        $scripts = [];

        foreach (StringUtil::deserialize($layout->reprise_entrypoints, true) as $value) {
            $value = (string) $value;
            [$build, $entry] = self::parseValue($value);

            // Reprise decides: in strict mode (default) unknown entries and missing files
            // throw, with "reprise.strict_mode: false" they render nothing.
            try {
                $links[] = $this->tagRenderer->renderLinkTags($entry, build: $build);
                $scripts[] = $this->tagRenderer->renderScriptTags($entry, build: $build);
            } catch (ExceptionInterface|\JsonException $e) {
                if ($this->debug) {
                    throw $e;
                }

                $this->logger->error(
                    \sprintf('Skipped Vite entrypoint "%s" of page layout ID %s: %s', $value, $layout->id, $e->getMessage()),
                    ['exception' => $e],
                );
            }
        }

        foreach ([...$links, ...$scripts] as $tags) {
            if ('' !== $tags) {
                $GLOBALS['TL_HEAD'][] = $tags;
            }
        }
    }

    /**
     * Splits "build:entry" at the first colon; a value without a colon targets the
     * default build.
     *
     * @return array{0: string|null, 1: string}
     */
    public static function parseValue(string $value): array
    {
        $pos = strpos($value, ':');

        if (false === $pos) {
            return [null, $value];
        }

        return [substr($value, 0, $pos), substr($value, $pos + 1)];
    }
}

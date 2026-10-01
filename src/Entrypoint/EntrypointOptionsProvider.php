<?php

declare(strict_types=1);

namespace Trassd\ContaoRepriseBundle\Entrypoint;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lists the entries of every Reprise build as options for
 * tl_layout.reprise_entrypoints.
 *
 * Stored values that no longer exist are kept by Contao itself
 * (CheckBoxWizard "unknownOption").
 */
#[AsCallback(table: 'tl_layout', target: 'fields.reprise_entrypoints.options')]
class EntrypointOptionsProvider
{
    /**
     * @param array<string, string> $entrypointsPaths build name => path to entrypoints.json
     */
    public function __construct(
        #[Autowire(param: 'trassd_contao_reprise.entrypoints_paths')]
        private readonly array $entrypointsPaths,
    ) {
    }

    /**
     * Numeric entry names become integer keys (PHP array semantics); the labels
     * stay strings.
     *
     * @return array<array-key, string>
     */
    public function __invoke(): array
    {
        $options = [];

        foreach ($this->entrypointsPaths as $build => $path) {
            foreach ($this->readEntryNames($path) as $entry) {
                if ('_default' === $build) {
                    $options[$entry] = $entry;
                } else {
                    $options[$build.':'.$entry] = \sprintf('%s (%s)', $entry, $build);
                }
            }
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private function readEntryNames(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        if (!\is_array($data) || !\is_array($data['entryPoints'] ?? null)) {
            return [];
        }

        return array_map(strval(...), array_keys($data['entryPoints']));
    }
}

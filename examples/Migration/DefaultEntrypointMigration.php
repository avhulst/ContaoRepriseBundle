<?php

declare(strict_types=1);

/*
 * Example project migration (copy to src/Migration/ of your project and adjust the namespace).
 *
 * Assigns the "app" entrypoint to every page layout whose field is still NULL, e.g. right
 * after the bundle was installed on a production system. Without it, pages have no CSS/JS
 * until an editor selects the entrypoints in the back end.
 *
 * Layouts in which an editor deliberately deselected all entries store an empty value, not
 * NULL, and are therefore left untouched.
 */

namespace App\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

class DefaultEntrypointMigration extends AbstractMigration
{
    private const ENTRYPOINTS = ['app'];

    public function __construct(private readonly Connection $connection)
    {
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // contao:migrate runs migrations again after the schema update has added the column
        if (!$schemaManager->tablesExist(['tl_layout']) || !isset($schemaManager->listTableColumns('tl_layout')['reprise_entrypoints'])) {
            return false;
        }

        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_layout WHERE reprise_entrypoints IS NULL') > 0;
    }

    public function run(): MigrationResult
    {
        $this->connection->executeStatement(
            'UPDATE tl_layout SET reprise_entrypoints = :entrypoints WHERE reprise_entrypoints IS NULL',
            ['entrypoints' => serialize(self::ENTRYPOINTS)],
        );

        return $this->createResult(true);
    }
}

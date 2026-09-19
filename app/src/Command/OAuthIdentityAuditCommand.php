<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:oauth:identity-audit', description: 'Detect duplicate OAuth identities before applying unique indexes.')]
final class OAuthIdentityAuditCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct('app:oauth:identity-audit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $hasTenantColumn = $this->hasTenantColumn();
        if (!$hasTenantColumn) {
            $io->note('Colonne azure_tenant_id absente : base antérieure à la migration, toutes les identités Microsoft sont traitées comme historiques.');
        }

        $hasDuplicates = false;
        foreach ($this->checks($hasTenantColumn) as $label => $sql) {
            $duplicates = $this->connection->fetchAllAssociative($sql);
            if ($duplicates === []) {
                $io->success($label.': none found.');
                continue;
            }

            $hasDuplicates = true;
            $io->error($label.': duplicates found; resolve them manually before deployment.');
            $io->table(['Identity', 'Count'], array_map(
                static fn (array $row): array => [(string) $row['identity_value'], (string) $row['duplicate_count']],
                $duplicates
            ));
        }

        return $hasDuplicates ? Command::FAILURE : Command::SUCCESS;
    }

    /** @return array<string, string> */
    private function checks(bool $hasTenantColumn): array
    {
        $checks = [
            'Google IDs' => 'SELECT google_id AS identity_value, COUNT(*) AS duplicate_count FROM user WHERE google_id IS NOT NULL GROUP BY google_id HAVING COUNT(*) > 1',
        ];

        if (!$hasTenantColumn) {
            $checks['Microsoft legacy IDs'] = 'SELECT azure_id AS identity_value, COUNT(*) AS duplicate_count FROM user WHERE azure_id IS NOT NULL GROUP BY azure_id HAVING COUNT(*) > 1';

            return $checks;
        }

        $checks['Microsoft canonical identities'] = 'SELECT CONCAT(azure_tenant_id, \' / \', azure_id) AS identity_value, COUNT(*) AS duplicate_count FROM user WHERE azure_tenant_id IS NOT NULL AND azure_id IS NOT NULL GROUP BY azure_tenant_id, azure_id HAVING COUNT(*) > 1';
        $checks['Microsoft legacy IDs'] = 'SELECT azure_id AS identity_value, COUNT(*) AS duplicate_count FROM user WHERE azure_tenant_id IS NULL AND azure_id IS NOT NULL GROUP BY azure_id HAVING COUNT(*) > 1';

        return $checks;
    }

    private function hasTenantColumn(): bool
    {
        foreach ($this->connection->createSchemaManager()->listTableColumns('user') as $column) {
            if ($column->getName() === 'azure_tenant_id') {
                return true;
            }
        }

        return false;
    }
}

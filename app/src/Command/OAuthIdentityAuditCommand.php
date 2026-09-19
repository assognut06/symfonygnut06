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
    /** @var array<string, string> */
    private const CHECKS = [
        'Google IDs' => 'SELECT google_id AS identity_value, COUNT(*) AS duplicate_count FROM user WHERE google_id IS NOT NULL GROUP BY google_id HAVING COUNT(*) > 1',
        'Microsoft IDs' => 'SELECT azure_id AS identity_value, COUNT(*) AS duplicate_count FROM user WHERE azure_id IS NOT NULL GROUP BY azure_id HAVING COUNT(*) > 1',
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct('app:oauth:identity-audit');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $hasDuplicates = false;

        foreach (self::CHECKS as $label => $sql) {
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
}

<?php

namespace App\Tests\Functional;

use Symfony\Component\Process\Process;

class DeleteInvalidUsersBashTest extends WebTestCase
{
    private const TEST_EMAILS = [
        'bash-expired-invalid@test.com',
        'bash-recent-invalid@test.com',
        'bash-verified@test.com',
        'bash-with-role@test.com',
    ];

    public function testBashScriptDeletesOnlyEligibleUsersFromTestDatabase(): void
    {
        $this->deleteTestUsers();

        try {
            $this->runSql(<<<'SQL'
                INSERT INTO user (email, roles, password, is_verified, created_at, updated_at)
                VALUES
                    ('bash-expired-invalid@test.com', JSON_ARRAY(), 'unused', 0, DATE_SUB(NOW(), INTERVAL 16 DAY), NOW()),
                    ('bash-recent-invalid@test.com', JSON_ARRAY(), 'unused', 0, DATE_SUB(NOW(), INTERVAL 14 DAY), NOW()),
                    ('bash-verified@test.com', JSON_ARRAY(), 'unused', 1, DATE_SUB(NOW(), INTERVAL 16 DAY), NOW()),
                    ('bash-with-role@test.com', JSON_ARRAY('ROLE_ADMIN'), 'unused', 0, DATE_SUB(NOW(), INTERVAL 16 DAY), NOW())
                SQL);

            $process = new Process(
                [dirname(__DIR__, 2).'/bin/delete-invalid-users.sh', 'test'],
                dirname(__DIR__, 2),
                ['APP_ENV' => 'test', 'PHP_BINARY' => PHP_BINARY]
            );
            $process->mustRun();

            self::assertStringContainsString('deleted_users', $process->getOutput());
            self::assertMatchesRegularExpression('/\b1\b/', $process->getOutput());

            $remainingEmails = $this->em->getConnection()->fetchFirstColumn(sprintf(
                "SELECT email FROM user WHERE email IN ('%s') ORDER BY email",
                implode("', '", self::TEST_EMAILS)
            ));

            self::assertSame([
                'bash-recent-invalid@test.com',
                'bash-verified@test.com',
                'bash-with-role@test.com',
            ], $remainingEmails);
        } finally {
            $this->deleteTestUsers();
        }
    }

    private function deleteTestUsers(): void
    {
        $this->runSql(sprintf(
            "DELETE FROM user WHERE email IN ('%s')",
            implode("', '", self::TEST_EMAILS)
        ));
    }

    private function runSql(string $sql): void
    {
        $process = new Process(
            [PHP_BINARY, 'bin/console', 'dbal:run-sql', $sql, '--env=test', '--no-debug', '--no-interaction'],
            dirname(__DIR__, 2),
            ['APP_ENV' => 'test']
        );
        $process->mustRun();
    }
}

<?php

namespace App\Tests\Functional;

use App\Entity\User;

class DeleteInvalidUsersFunctionTest extends WebTestCase
{
    public function testItDeletesOnlyUnverifiedRolelessUsersOlderThanFifteenDays(): void
    {
        $expiredInvalidUser = $this->createUser(
            'expired-invalid@test.com',
            'Test1234!',
            [],
            false
        );
        $expiredInvalidUser->setCreatedAt(new \DateTimeImmutable('-16 days'));

        $recentInvalidUser = $this->createUser(
            'recent-invalid@test.com',
            'Test1234!',
            [],
            false
        );
        $recentInvalidUser->setCreatedAt(new \DateTimeImmutable('-14 days'));

        $verifiedUser = $this->createUser(
            'verified@test.com',
            'Test1234!',
            [],
            true
        );
        $verifiedUser->setCreatedAt(new \DateTimeImmutable('-16 days'));

        $userWithRole = $this->createUser(
            'with-role@test.com',
            'Test1234!',
            ['ROLE_ADMIN'],
            false
        );
        $userWithRole->setCreatedAt(new \DateTimeImmutable('-16 days'));

        $this->em->flush();

        $result = $this->em->getConnection()->executeQuery('CALL delete_invalid_users()');
        $deletedCount = (int) $result->fetchOne();
        $result->free();

        $this->em->clear();
        $repository = $this->em->getRepository(User::class);

        self::assertSame(1, $deletedCount);
        self::assertNull($repository->findOneBy(['email' => 'expired-invalid@test.com']));
        self::assertNotNull($repository->findOneBy(['email' => 'recent-invalid@test.com']));
        self::assertNotNull($repository->findOneBy(['email' => 'verified@test.com']));
        self::assertNotNull($repository->findOneBy(['email' => 'with-role@test.com']));
    }
}

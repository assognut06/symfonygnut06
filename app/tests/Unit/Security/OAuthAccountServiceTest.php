<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\OAuth\OAuthAccountException;
use App\Security\OAuth\OAuthAccountService;
use App\Security\OAuth\OAuthIdentity;
use App\Security\OAuth\OAuthProvider;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OAuthAccountServiceTest extends TestCase
{
    public function testExistingEmailCannotBeAutomaticallyLinkedDuringLogin(): void
    {
        $existingUser = new User();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturnCallback(static function (array $criteria) use ($existingUser): ?User {
            return isset($criteria['email']) ? $existingUser : null;
        });
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($repository);
        $entityManager->expects(self::never())->method('persist');

        $service = new OAuthAccountService($entityManager, $this->createMock(UserPasswordHasherInterface::class));

        $this->expectException(OAuthAccountException::class);
        $service->login(new OAuthIdentity(OAuthProvider::Google, 'google-subject', 'existing@example.test', true));
    }

    public function testIdentityAlreadyOwnedByAnotherAccountCannotBeLinked(): void
    {
        $owner = $this->userWithId(1);
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn($owner);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($repository);
        $service = new OAuthAccountService($entityManager, $this->createMock(UserPasswordHasherInterface::class));

        $this->expectException(OAuthAccountException::class);
        $service->assertCanLink($this->userWithId(2), new OAuthIdentity(OAuthProvider::Google, 'google-subject', 'new@example.test', true));
    }

    public function testExistingIdentityCanLoginWithoutAnEmailClaim(): void
    {
        $owner = $this->userWithId(1);
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())->method('findOneBy')->with(['googleId' => 'google-subject'])->willReturn($owner);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($repository);
        $service = new OAuthAccountService($entityManager, $this->createMock(UserPasswordHasherInterface::class));

        self::assertSame($owner, $service->login(new OAuthIdentity(OAuthProvider::Google, 'google-subject', null, false)));
    }

    /**
     * A Microsoft email claim is contact data, not proof of ownership, so a
     * provisioned account must stay unverified and be gated by the user checker.
     */
    public function testMicrosoftProvisionedAccountIsNeverCreatedVerified(): void
    {
        $persisted = null;
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($repository);
        $entityManager->expects(self::once())->method('persist')->willReturnCallback(
            static function (User $user) use (&$persisted): void { $persisted = $user; }
        );

        $service = new OAuthAccountService($entityManager, $this->createMock(UserPasswordHasherInterface::class));
        $user = $service->login(new OAuthIdentity(OAuthProvider::Microsoft, 'object-id', 'claimed@example.test', true));

        self::assertSame($persisted, $user);
        self::assertFalse($user->isVerified(), 'A Microsoft claim must never self-verify an account.');
    }

    /** Google may verify on creation, but only when the provider asserts email_verified. */
    public function testGoogleAccountIsOnlyVerifiedWhenTheProviderConfirmsTheEmail(): void
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('findOneBy')->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->with(User::class)->willReturn($repository);

        $service = new OAuthAccountService($entityManager, $this->createMock(UserPasswordHasherInterface::class));

        self::assertTrue($service->login(new OAuthIdentity(OAuthProvider::Google, 'sub-1', 'ok@example.test', true))->isVerified());

        $this->expectException(OAuthAccountException::class);
        $service->login(new OAuthIdentity(OAuthProvider::Google, 'sub-2', 'unverified@example.test', false));
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setValue($user, $id);
        return $user;
    }
}

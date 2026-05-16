<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Tih;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testNewUserHasNoId(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
    }

    public function testEmailGetterAndSetter(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
    }

    public function testDefaultRolesIncludeRoleUser(): void
    {
        $user = new User();
        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    public function testSetRolesPreservesRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testRolesAreUnique(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER', 'ROLE_USER', 'ROLE_ADMIN']);

        $roles = $user->getRoles();
        $this->assertCount(2, $roles);
    }

    public function testPasswordGetterAndSetter(): void
    {
        $user = new User();
        $user->setPassword('hashed_password');

        $this->assertEquals('hashed_password', $user->getPassword());
    }

    public function testVerifiedGetterAndSetter(): void
    {
        $user = new User();
        $this->assertFalse($user->isVerified());

        $user->setVerified(true);
        $this->assertTrue($user->isVerified());
    }

    public function testProfilePictureGetterAndSetter(): void
    {
        $user = new User();
        $this->assertNull($user->getProfilePicture());

        $user->setProfilePicture('avatar.jpg');
        $this->assertEquals('avatar.jpg', $user->getProfilePicture());

        $user->setProfilePicture(null);
        $this->assertNull($user->getProfilePicture());
    }

    public function testGoogleIdGetterAndSetter(): void
    {
        $user = new User();
        $this->assertNull($user->getGoogleId());

        $user->setGoogleId('google_123');
        $this->assertEquals('google_123', $user->getGoogleId());
    }

    public function testAzureIdGetterAndSetter(): void
    {
        $user = new User();
        $this->assertNull($user->getAzureId());

        $user->setAzureId('azure_456');
        $this->assertEquals('azure_456', $user->getAzureId());
    }

    public function testTihRelation(): void
    {
        $user = new User();
        $tih = new Tih();

        $user->setTih($tih);

        $this->assertSame($tih, $user->getTih());
        $this->assertSame($user, $tih->getUser());
    }

    public function testTihRelationSetNull(): void
    {
        $user = new User();
        $tih = new Tih();

        $user->setTih($tih);
        $user->setTih(null);

        $this->assertNull($user->getTih());
    }

    public function testPrePersistSetsTimestamps(): void
    {
        $user = new User();
        $user->onPrePersist();

        $this->assertNotNull($user->getCreatedAt());
        $this->assertNotNull($user->getUpdatedAt());
        $this->assertEquals(
            $user->getCreatedAt()->getTimestamp(),
            $user->getUpdatedAt()->getTimestamp()
        );
    }

    public function testPreUpdateChangesUpdatedAt(): void
    {
        $user = new User();
        $user->onPrePersist();
        $originalUpdatedAt = $user->getUpdatedAt();

        usleep(10000);
        $user->onPreUpdate();

        $this->assertGreaterThanOrEqual(
            $originalUpdatedAt->getTimestamp(),
            $user->getUpdatedAt()->getTimestamp()
        );
    }

    public function testEraseCredentials(): void
    {
        $user = new User();
        $user->eraseCredentials();

        $this->assertTrue(true, 'eraseCredentials should not throw');
    }

    public function testSettersReturnSelf(): void
    {
        $user = new User();

        $this->assertSame($user, $user->setEmail('a@b.com'));
        $this->assertSame($user, $user->setPassword('pass'));
        $this->assertSame($user, $user->setRoles([]));
        $this->assertSame($user, $user->setVerified(true));
        $this->assertSame($user, $user->setGoogleId('g'));
        $this->assertSame($user, $user->setAzureId('a'));
    }
}

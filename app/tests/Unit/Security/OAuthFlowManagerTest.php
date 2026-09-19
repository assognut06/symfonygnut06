<?php

namespace App\Tests\Unit\Security;

use App\Entity\User;
use App\Security\OAuth\OAuthFlowManager;
use App\Security\OAuth\OAuthFlowPurpose;
use App\Security\OAuth\OAuthIdentity;
use App\Security\OAuth\OAuthProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class OAuthFlowManagerTest extends TestCase
{
    public function testLinkIntentIsBoundToTheAuthenticatedUser(): void
    {
        $manager = new OAuthFlowManager();
        $request = $this->request();
        $user = $this->userWithId(42);

        $manager->authorizeLink($request, $user, OAuthProvider::Google);
        $flow = $manager->start($request, OAuthProvider::Google);

        self::assertSame(OAuthFlowPurpose::Link, $flow['purpose']);
        self::assertSame(42, $flow['userId']);
        self::assertSame(OAuthProvider::Google, $flow['provider']);
    }

    public function testPendingLinkCannotBeReadByAnotherUser(): void
    {
        $manager = new OAuthFlowManager();
        $request = $this->request();
        $owner = $this->userWithId(42);

        $manager->stageLink($request, $owner, new OAuthIdentity(
            OAuthProvider::Microsoft,
            'object-id',
            'owner@example.test',
            false,
        ));

        self::assertNull($manager->pendingLink($request, $this->userWithId(43)));
        self::assertSame('object-id', $manager->pendingLink($request, $owner)?->subject);
    }

    public function testStartingAnotherProviderInvalidatesThePreviousFlow(): void
    {
        $manager = new OAuthFlowManager();
        $request = $this->request();

        $manager->start($request, OAuthProvider::Google);
        $manager->start($request, OAuthProvider::Microsoft);

        $this->expectException(\App\Security\OAuth\OAuthAccountException::class);
        $manager->requireFlow($request, OAuthProvider::Google);
    }

    private function request(): Request
    {
        $request = new Request();
        $request->setSession(new Session(new MockArraySessionStorage()));
        return $request;
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        $property = new \ReflectionProperty(User::class, 'id');
        $property->setValue($user, $id);
        return $user;
    }
}

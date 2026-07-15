<?php

namespace App\Tests\Functional;

use App\Entity\User;

/**
 * Scenario-based tests for admin user management (list, search, promote, delete).
 */
class AdminUserTest extends WebTestCase
{
    public function testIndexListsUsers(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser('listed-user@test.com');

        $this->client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#users-table', (string) $target->getId());
        $this->assertSelectorTextContains('table#users-table', 'listed-user@test.com');
    }

    public function testIndexSearchFiltersByEmail(): void
    {
        $this->loginAsAdmin();
        $this->createUser('alpha-user@test.com');
        $this->createUser('beta-user@test.com');

        $this->client->request('GET', '/admin/user', ['q' => 'alpha-user']);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('table#users-table', 'alpha-user@test.com');
        $this->assertSelectorTextNotContains('table#users-table', 'beta-user@test.com');
    }

    public function testPromoteUserWithValidCsrfGrantsAdminRole(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser('promote-me@test.com');
        $userId = $target->getId();

        $this->client->request('POST', '/admin/user/promote/' . $userId, [
            '_token' => $this->getAdminUserPromoteCsrfToken($userId),
        ]);

        $this->assertResponseRedirects('/admin/user');
        $this->em->clear();
        $updated = $this->em->getRepository(User::class)->find($userId);

        $this->assertNotNull($updated);
        $this->assertContains('ROLE_ADMIN', $updated->getRoles());
    }

    public function testDemoteAdminWithValidCsrfRemovesAdminRole(): void
    {
        $this->loginAsAdmin();
        $target = $this->createAdmin('demote-me@test.com');
        $userId = $target->getId();

        $this->client->request('POST', '/admin/user/promote/' . $userId, [
            '_token' => $this->getAdminUserPromoteCsrfToken($userId),
        ]);

        $this->assertResponseRedirects('/admin/user');
        $this->em->clear();
        $updated = $this->em->getRepository(User::class)->find($userId);

        $this->assertNotNull($updated);
        $this->assertNotContains('ROLE_ADMIN', $updated->getRoles());
        $this->assertContains('ROLE_USER', $updated->getRoles());
    }

    public function testDeleteUserWithValidCsrfRemovesUser(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser('delete-me@test.com');
        $userId = $target->getId();

        $this->client->request('POST', '/admin/user/delete/' . $userId, [
            '_method' => 'DELETE',
            '_token' => $this->getAdminUserDeleteCsrfToken($userId),
        ]);

        $this->assertResponseRedirects('/admin/user');
        $this->assertNull($this->em->getRepository(User::class)->find($userId));
    }

    public function testPromoteWithInvalidCsrfLeavesRolesUnchanged(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser('csrf-promote@test.com');
        $userId = $target->getId();

        $this->client->request('POST', '/admin/user/promote/' . $userId, [
            '_token' => 'invalid_token',
        ]);

        $response = $this->client->getResponse();
        $this->assertTrue(
            $response->getStatusCode() === 403 || $response->isRedirection(),
            'Invalid CSRF must not promote the user'
        );
        $this->em->refresh($target);
        $this->assertNotContains('ROLE_ADMIN', $target->getRoles());
    }

    public function testDeleteWithInvalidCsrfKeepsUser(): void
    {
        $this->loginAsAdmin();
        $target = $this->createUser('csrf-delete@test.com');
        $userId = $target->getId();

        $this->client->request('POST', '/admin/user/delete/' . $userId, [
            '_method' => 'DELETE',
            '_token' => 'invalid_token',
        ]);

        $this->assertResponseRedirects('/admin/user');
        $this->assertNotNull($this->em->getRepository(User::class)->find($userId));
    }
}

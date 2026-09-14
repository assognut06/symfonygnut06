<?php

namespace App\Tests\Functional;

use App\Service\ApiFrameVrService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MetaversAccessControlTest extends WebTestCase
{
    public function testFrameEndpointRedirectsAnonymousToLogin(): void
    {
        $this->replaceFrameVrServiceWithForbiddenMock();

        $this->client->request('GET', $this->generateFrameUrl());

        $this->assertResponseRedirects(
            static::getContainer()->get(UrlGeneratorInterface::class)
                ->generate('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );
    }

    public function testFrameEndpointDeniesRegularUser(): void
    {
        $this->replaceFrameVrServiceWithForbiddenMock();
        $this->loginAsNewUser();

        $this->client->request('GET', $this->generateFrameUrl());

        // The access-denied login controller redirects verified users to their profile.
        $this->assertResponseRedirects(
            static::getContainer()->get(UrlGeneratorInterface::class)
                ->generate('app_profil')
        );
    }

    public function testFrameEndpointRemainsAccessibleToAdmin(): void
    {
        $apiFrameVrService = $this->createMock(ApiFrameVrService::class);
        $apiFrameVrService
            ->expects($this->once())
            ->method('getSomeData')
            ->with('frame-id', 'frame')
            ->willReturn(['id' => 'frame-id']);
        $this->client->getContainer()->set(ApiFrameVrService::class, $apiFrameVrService);
        $this->loginAsAdmin();

        $this->client->request('GET', $this->generateFrameUrl());

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(
            '{"id":"frame-id"}',
            (string) $this->client->getResponse()->getContent()
        );
    }

    private function generateFrameUrl(): string
    {
        return static::getContainer()
            ->get(UrlGeneratorInterface::class)
            ->generate('app_metavers_ohme', ['idFrame' => 'frame-id']);
    }

    private function replaceFrameVrServiceWithForbiddenMock(): void
    {
        $apiFrameVrService = $this->createMock(ApiFrameVrService::class);
        $apiFrameVrService
            ->expects($this->never())
            ->method('getSomeData');

        $this->client->getContainer()->set(ApiFrameVrService::class, $apiFrameVrService);
    }
}

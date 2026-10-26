<?php

namespace App\Tests\Functional;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class MetaversTest extends WebTestCase
{
    public function testMetaversPageRemainsPublic(): void
    {
        $url = static::getContainer()
            ->get(UrlGeneratorInterface::class)
            ->generate('app_metavers');

        $this->client->request('GET', $url);

        $this->assertResponseIsSuccessful();
    }

    public function testFrameVrProxyRouteIsRemoved(): void
    {
        $routes = static::getContainer()
            ->get(RouterInterface::class)
            ->getRouteCollection();

        self::assertNull($routes->get('app_metavers_frame'));
        self::assertNull($routes->get('app_metavers_ohme'));
    }
}

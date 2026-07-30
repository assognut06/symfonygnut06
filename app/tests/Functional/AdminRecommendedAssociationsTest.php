<?php

namespace App\Tests\Functional;

use App\Entity\AssoRecommander;

class AdminRecommendedAssociationsTest extends WebTestCase
{
    public function testAdminListDisplaysRecommendedAssociations(): void
    {
        $association = (new AssoRecommander())
            ->setOrganizationSlug('association-visible-dans-administration')
            ->setName('Association visible dans administration')
            ->setType('Association1901Rig')
            ->setLogo('/images/LogoNew.png');

        $this->em->persist($association);
        $this->em->flush();
        $this->loginAsAdmin();

        $this->client->request('GET', '/admin/asso/recommander/liste');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            'table',
            'Association visible dans administration'
        );
    }
}

<?php

namespace App\Tests\Functional;

final class FooterPartnersMenuAccessibilityTest extends WebTestCase
{
    public function testPartnersDropdownUsesAccessibleMenuButtonMarkup(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorCount(1, '#footerPartnersMenuButton');
        $this->assertSelectorExists(
            'button#footerPartnersMenuButton[type="button"]'
            . '[data-bs-toggle="dropdown"]'
            . '[aria-haspopup="menu"]'
            . '[aria-expanded="false"]'
            . '[aria-controls="footerPartnersMenu"]'
        );
        $this->assertSelectorTextSame(
            '#footerPartnersMenuButton',
            'Vivre avec un handicap'
        );

        $this->assertSelectorExists(
            '#footerPartnersMenu[role="menu"]'
            . '[aria-labelledby="footerPartnersMenuButton"]'
        );
        $this->assertSelectorCount(9, '#footerPartnersMenu > li[role="none"]');
        $this->assertSelectorCount(
            9,
            '#footerPartnersMenu a.dropdown-item[role="menuitem"][href]'
        );
        $this->assertSelectorNotExists('#footerPartnersMenu a[role="button"]');
    }
}

<?php

namespace App\Tests\Unit\Template;

use PHPUnit\Framework\TestCase;

class AnalyticsTemplateTest extends TestCase
{
    public function testExternalResourcesUseExplicitHttpsUrls(): void
    {
        $template = file_get_contents(dirname(__DIR__, 3).'/templates/_partials/analytics.html.twig');

        $this->assertIsString($template);
        $this->assertDoesNotMatchRegularExpression(
            '/(?:src\s*=|\.src\s*=)\s*["\']\/\//',
            $template,
            'Analytics resources must not use protocol-relative URLs.'
        );
    }
}

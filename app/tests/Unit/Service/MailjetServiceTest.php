<?php

namespace App\Tests\Unit\Service;

use App\Service\MailjetService;
use PHPUnit\Framework\TestCase;

class MailjetServiceTest extends TestCase
{
    public function testServiceCanBeInstantiated(): void
    {
        $service = new MailjetService('test_key', 'test_secret');
        $this->assertInstanceOf(MailjetService::class, $service);
    }
}

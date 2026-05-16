<?php

namespace App\Tests\Unit\Service;

use App\Service\RecaptchaVerifier;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RecaptchaVerifierTest extends TestCase
{
    public function testVerifyReturnsTrueInDevEnvironment(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->never())->method('request');

        $verifier = new RecaptchaVerifier($client, 'secret', 'dev', 'secret');
        $request = Request::create('/test', 'POST');

        $this->assertTrue($verifier->verify($request));
    }

    public function testVerifyReturnsFalseWithoutRecaptchaResponse(): void
    {
        $client = $this->createMock(HttpClientInterface::class);

        $verifier = new RecaptchaVerifier($client, 'secret', 'prod', 'secret');
        $request = Request::create('/test', 'POST');

        $this->assertFalse($verifier->verify($request));
    }

    public function testVerifyReturnsTrueOnSuccessfulValidation(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')
            ->willReturn(json_encode(['success' => true, 'score' => 0.9]));

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $verifier = new RecaptchaVerifier($client, 'secret', 'prod', 'secret');
        $request = Request::create('/test', 'POST', [
            'g-recaptcha-response' => 'valid-token',
        ]);

        $this->assertTrue($verifier->verify($request));
    }

    public function testVerifyReturnsFalseOnFailedValidation(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')
            ->willReturn(json_encode(['success' => false]));

        $client = $this->createMock(HttpClientInterface::class);
        $client->method('request')->willReturn($response);

        $verifier = new RecaptchaVerifier($client, 'secret', 'prod', 'secret');
        $request = Request::create('/test', 'POST', [
            'g-recaptcha-response' => 'invalid-token',
        ]);

        $this->assertFalse($verifier->verify($request));
    }

    public function testVerifySendsCorrectParameters(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')
            ->willReturn(json_encode(['success' => true]));

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://www.google.com/recaptcha/api/siteverify',
                $this->callback(function ($options) {
                    return isset($options['body']['secret'])
                        && $options['body']['secret'] === 'my-secret'
                        && isset($options['body']['response'])
                        && $options['body']['response'] === 'test-token';
                })
            )
            ->willReturn($response);

        $verifier = new RecaptchaVerifier($client, 'my-secret', 'prod', 'my-secret');
        $request = Request::create('/test', 'POST', [
            'g-recaptcha-response' => 'test-token',
        ]);

        $verifier->verify($request);
    }
}

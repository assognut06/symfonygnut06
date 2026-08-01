<?php

namespace App\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class UploadDirectoryAccessTest extends TestCase
{
    private HttpClientInterface $httpClient;
    private string $baseUrl;

    protected function setUp(): void
    {
        $configuredBaseUrl = getenv('UPLOAD_DIRECTORY_TEST_BASE_URL');
        $this->baseUrl = rtrim(
            is_string($configuredBaseUrl) ? $configuredBaseUrl : 'https://127.0.0.1',
            '/'
        );

        $options = (new HttpOptions())
            ->verifyPeer(false)
            ->verifyHost(false)
            ->setTimeout(5.0)
            ->toArray();

        $this->httpClient = HttpClient::create($options);
    }

    /**
     * @dataProvider uploadDirectoryProvider
     */
    public function testUploadDirectoryListingIsForbidden(string $directory): void
    {
        $response = $this->httpClient->request('GET', $this->baseUrl.$directory, [
            'max_redirects' => 0,
        ]);

        self::assertSame(
            Response::HTTP_FORBIDDEN,
            $response->getStatusCode(),
            sprintf('The upload directory "%s" must return HTTP 403.', $directory)
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function uploadDirectoryProvider(): iterable
    {
        yield 'volunteer CVs' => ['/uploads/cv/'];
        yield 'donation receipts' => ['/uploads/bordereau/'];
        yield 'profile pictures' => ['/uploads/profilePictures/'];
    }
}

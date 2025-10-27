<?php

declare(strict_types=1);

namespace App\Tests\Support;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for functional tests.
 * Provides HTTP client, database access, and fixture management.
 *
 * Note: Each test is automatically wrapped in a transaction by DAMA Doctrine Test Bundle.
 * Changes are rolled back after each test, ensuring perfect isolation.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    use FixtureHelper;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    /**
     * Restore exception handler after each test.
     *
     * Symfony Kernel modifies the exception handler when booting.
     * This is normal behavior, but PHPUnit marks tests as "risky" if handlers aren't restored.
     * We explicitly restore the handler here to avoid false positives.
     */
    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    /**
     * Make a GET request and return the response.
     *
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $server
     */
    protected function get(string $uri, array $parameters = [], array $server = []): void
    {
        $this->client->request('GET', $uri, $parameters, [], $server);
    }

    /**
     * Make a POST request with JSON body.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $server
     */
    protected function postJson(string $uri, array $data, array $server = []): void
    {
        $server = array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $server);

        $this->client->request(
            'POST',
            $uri,
            [],
            [],
            $server,
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }

    /**
     * Assert that the response has the given status code.
     */
    protected function assertResponseStatusCode(int $expectedCode): void
    {
        $response = $this->getResponse();
        $actual = $response->getStatusCode();
        self::assertSame(
            $expectedCode,
            $actual,
            \sprintf('Expected status code %d, got %d. Response: %s', $expectedCode, $actual, $response->getContent()),
        );
    }

    /**
     * Decode JSON response.
     *
     * @return array<string, mixed>
     */
    protected function getJsonResponse(): array
    {
        $response = $this->getResponse();
        $content = $response->getContent();
        self::assertNotFalse($content, 'Response content is empty');

        /** @var null|array<string, mixed> $decoded */
        $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    protected function getResponse(): Response
    {
        $response = $this->client->getResponse();
        self::assertInstanceOf(Response::class, $response);

        return $response;
    }
}

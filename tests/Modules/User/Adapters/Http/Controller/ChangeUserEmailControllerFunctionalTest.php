<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Http\Controller;

use App\Tests\Fixtures\Modules\User\Factory\UserFixtureFactory;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for Change User Email API endpoint.
 *
 * Tests the PATCH /api/users/{id}/email endpoint.
 *
 * @internal
 *
 * @covers \App\Modules\User\Adapters\Http\Controller\ChangeUserEmailController
 */
final class ChangeUserEmailControllerFunctionalTest extends FunctionalTestCase
{
    private UserFixtureFactory $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = self::getContainer()->get(UserFixtureFactory::class);
    }

    public function testChangeEmailWithValidDataReturnsSuccess(): void
    {
        // Arrange
        $user = $this->users->create(static function ($b) {
            $b->withEmail('old@example.com');
        });

        $newEmailData = [
            'email' => 'new@example.com',
        ];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($newEmailData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK);

        $response = $this->getJsonResponse();

        // Verify success message
        self::assertArrayHasKey('message', $response);
        self::assertSame('Email changed successfully', $response['message']);
    }

    public function testChangeEmailWithMissingEmailReturnsBadRequest(): void
    {
        // Arrange
        $user = $this->users->create();
        $invalidData = []; // Missing email

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($invalidData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertIsString($response['error']);
        self::assertStringContainsStringIgnoringCase('email', $response['error']);
    }

    public function testChangeEmailWithInvalidEmailFormatReturnsBadRequest(): void
    {
        // Arrange
        $user = $this->users->create();
        $invalidData = [
            'email' => 'invalid-email-format',
        ];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($invalidData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertIsString($response['error']);
        self::assertStringContainsStringIgnoringCase('email', $response['error']);
    }

    public function testChangeEmailWithNonExistentUserReturnsBadRequest(): void
    {
        // Arrange
        $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';
        $newEmailData = [
            'email' => 'new@example.com',
        ];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $nonExistentId . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($newEmailData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
    }

    public function testChangeEmailWithSameEmailAsCurrentReturnsBadRequest(): void
    {
        // Arrange
        $user = $this->users->create(static function ($b) {
            $b->withEmail('same@example.com');
        });

        $sameEmailData = [
            'email' => 'same@example.com', // Same as current
        ];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($sameEmailData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertIsString($response['error']);
        self::assertStringContainsStringIgnoringCase('same', $response['error']);
    }

    public function testChangeEmailWithInvalidJsonReturnsBadRequest(): void
    {
        // Arrange
        $user = $this->users->create();
        $invalidJson = '{invalid json';

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $invalidJson
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertIsString($response['error']);
        self::assertStringContainsStringIgnoringCase('json', $response['error']);
    }

    public function testChangeEmailAcceptsOnlyPatchMethod(): void
    {
        // Arrange
        $user = $this->users->create();
        $emailData = ['email' => 'new@example.com'];

        // Test POST not allowed
        $this->postJson('/api/users/' . $user->getId() . '/email', $emailData);
        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $this->getResponse()->getStatusCode());

        // Test GET not allowed
        $this->get('/api/users/' . $user->getId() . '/email');
        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $this->getResponse()->getStatusCode());
    }

    public function testChangeEmailReturnsJsonContentType(): void
    {
        // Arrange
        $user = $this->users->create();
        $emailData = ['email' => 'new@example.com'];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($emailData, JSON_THROW_ON_ERROR)
        );

        // Assert
        self::assertStringContainsString(
            'application/json',
            $this->getResponse()->headers->get('Content-Type') ?? ''
        );
    }

    public function testChangeEmailWithEmptyEmailReturnsBadRequest(): void
    {
        // Arrange
        $user = $this->users->create();
        $invalidData = [
            'email' => '',
        ];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($invalidData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
    }

    public function testChangeEmailWithExtremelyLongEmailReturnsBadRequest(): void
    {
        // Arrange
        $user = $this->users->create();
        $longEmail = str_repeat('a', 300) . '@example.com'; // Very long email

        $invalidData = [
            'email' => $longEmail,
        ];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($invalidData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
    }

    public function testChangeEmailMultipleTimes(): void
    {
        // Arrange
        $user = $this->users->create(static function ($b) {
            $b->withEmail('original@example.com');
        });

        // Act & Assert - First change
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['email' => 'first@example.com'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCode(Response::HTTP_OK);

        // Act & Assert - Second change
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['email' => 'second@example.com'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCode(Response::HTTP_OK);

        // Act & Assert - Third change
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode(['email' => 'third@example.com'], JSON_THROW_ON_ERROR)
        );
        $this->assertResponseStatusCode(Response::HTTP_OK);
    }

    public function testChangeEmailReturnsErrorsArrayForValidation(): void
    {
        // Arrange
        $user = $this->users->create();
        $invalidData = ['email' => 'not-an-email'];

        // Act
        $this->client->request(
            'PATCH',
            '/api/users/' . $user->getId() . '/email',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            json_encode($invalidData, JSON_THROW_ON_ERROR)
        );

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertArrayHasKey('errors', $response);
        self::assertIsArray($response['errors']);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Http\Controller;

use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\UserId;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for Create User API endpoint.
 * Tests the complete HTTP flow from request to response.
 *
 * @group functional
 *
 * @internal
 *
 * @covers \App\Modules\User\Adapters\Http\Controller\CreateUserController
 */
final class CreateUserControllerFunctionalTest extends FunctionalTestCase
{
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userRepository = self::getContainer()->get(UserRepositoryInterface::class);
    }

    public function testCreateUserWithValidDataReturnsCreated(): void
    {
        // Arrange
        $userData = [
            'email' => 'john@example.com',
            'name' => 'John Doe',
        ];

        // Act
        $this->postJson('/api/users', $userData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_CREATED);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('id', $response);
        self::assertIsString($response['id']);

        // Verify user was actually created in database
        $user = $this->userRepository->get(UserId::fromString($response['id']));
        self::assertSame('john@example.com', $user->getEmail()->toString());
        self::assertSame('John Doe', $user->getName());
        self::assertTrue($user->isPending()); // New users should be pending
    }

    public function testCreateUserWithMissingEmailReturnsBadRequest(): void
    {
        // Arrange
        $userData = [
            'name' => 'John Doe',
            // missing email
        ];

        // Act
        $this->postJson('/api/users', $userData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertIsString($response['error']);
        self::assertStringContainsStringIgnoringCase('email', $response['error']);
    }

    public function testCreateUserWithMissingNameReturnsBadRequest(): void
    {
        // Arrange
        $userData = [
            'email' => 'john@example.com',
            // missing name
        ];

        // Act
        $this->postJson('/api/users', $userData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
        self::assertIsString($response['error']);
        self::assertStringContainsStringIgnoringCase('name', $response['error']);
    }

    public function testCreateUserWithInvalidEmailReturnsBadRequest(): void
    {
        // Arrange
        $userData = [
            'email' => 'invalid-email',
            'name' => 'John Doe',
        ];

        // Act
        $this->postJson('/api/users', $userData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_BAD_REQUEST);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
    }
}

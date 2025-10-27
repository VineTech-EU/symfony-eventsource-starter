<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Adapters\Http\Controller;

use App\Tests\Fixtures\Modules\User\Factory\UserFixtureFactory;
use App\Tests\Support\FunctionalTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional tests for Get User API endpoint.
 *
 * Tests the GET /api/users/{id} endpoint.
 *
 * @internal
 *
 * @covers \App\Modules\User\Adapters\Http\Controller\GetUserController
 */
final class GetUserControllerFunctionalTest extends FunctionalTestCase
{
    private UserFixtureFactory $users;

    protected function setUp(): void
    {
        parent::setUp();
        $this->users = self::getContainer()->get(UserFixtureFactory::class);
    }

    public function testGetUserWithValidIdReturnsUserData(): void
    {
        // Arrange
        $user = $this->users->create(static function ($builder) {
            $builder->withName('John Doe');
            $builder->withEmail('john@example.com');
        });

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK);

        $response = $this->getJsonResponse();

        // Verify structure
        self::assertArrayHasKey('id', $response);
        self::assertArrayHasKey('email', $response);
        self::assertArrayHasKey('name', $response);
        self::assertArrayHasKey('status', $response);
        self::assertArrayHasKey('roles', $response);
        self::assertArrayHasKey('created_at', $response);

        // Verify data
        self::assertSame($user->getId(), $response['id']);
        self::assertSame('john@example.com', $response['email']);
        self::assertSame('John Doe', $response['name']);
        self::assertSame('pending', $response['status']);
        self::assertIsArray($response['roles']);
        self::assertContains('ROLE_USER', $response['roles']);
    }

    public function testGetUserWithNonExistentIdReturnsNotFound(): void
    {
        // Arrange
        $nonExistentId = '550e8400-e29b-41d4-a716-446655440000';

        // Act
        $this->get('/api/users/' . $nonExistentId);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);

        $response = $this->getJsonResponse();

        // Verify error message
        self::assertArrayHasKey('error', $response);
        self::assertSame('User not found', $response['error']);
    }

    public function testGetUserReturnsApprovedStatus(): void
    {
        // Arrange
        $user = $this->users->createApproved();

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK);

        $response = $this->getJsonResponse();

        // Verify approved status
        self::assertSame('approved', $response['status']);
    }

    public function testGetUserReturnsPendingStatus(): void
    {
        // Arrange
        $user = $this->users->createPending();

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK);

        $response = $this->getJsonResponse();

        // Verify pending status
        self::assertSame('pending', $response['status']);
    }

    public function testGetUserWithInvalidUuidFormatReturnsNotFound(): void
    {
        // Act
        $this->get('/api/users/invalid-uuid-format');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND);

        $response = $this->getJsonResponse();
        self::assertArrayHasKey('error', $response);
    }

    public function testGetUserAcceptsOnlyGetMethod(): void
    {
        // Arrange
        $user = $this->users->create();

        // Test POST not allowed
        $this->postJson('/api/users/' . $user->getId(), []);
        self::assertSame(Response::HTTP_METHOD_NOT_ALLOWED, $this->getResponse()->getStatusCode());
    }

    public function testGetUserReturnsJsonContentType(): void
    {
        // Arrange
        $user = $this->users->create();

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        self::assertStringContainsString(
            'application/json',
            $this->getResponse()->headers->get('Content-Type') ?? ''
        );
    }

    public function testGetUserWithMultipleRoles(): void
    {
        // Arrange
        $user = $this->users->create();

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK);

        $response = $this->getJsonResponse();

        // Verify roles array
        self::assertIsArray($response['roles']);
        self::assertNotEmpty($response['roles']);
        self::assertContains('ROLE_USER', $response['roles']);
    }

    public function testGetUserReturnsValidTimestamp(): void
    {
        // Arrange
        $user = $this->users->create();

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        $response = $this->getJsonResponse();

        // Verify created_at is a valid timestamp
        self::assertArrayHasKey('created_at', $response);
        self::assertIsString($response['created_at']);

        // Try to parse as DateTime
        $createdAt = new \DateTimeImmutable($response['created_at']);
        self::assertInstanceOf(\DateTimeImmutable::class, $createdAt);
    }

    public function testGetMultipleUsersReturnsCorrectData(): void
    {
        // Arrange
        $user1 = $this->users->create(static function ($b) {
            $b->withEmail('user1@example.com');
        });
        $user2 = $this->users->create(static function ($b) {
            $b->withEmail('user2@example.com');
        });

        // Act & Assert - User 1
        $this->get('/api/users/' . $user1->getId());
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $response1 = $this->getJsonResponse();
        self::assertSame('user1@example.com', $response1['email']);

        // Act & Assert - User 2
        $this->get('/api/users/' . $user2->getId());
        $this->assertResponseStatusCode(Response::HTTP_OK);
        $response2 = $this->getJsonResponse();
        self::assertSame('user2@example.com', $response2['email']);

        // Verify they are different users
        self::assertNotSame($response1['id'], $response2['id']);
    }

    public function testGetUserResponseStructureIsComplete(): void
    {
        // Arrange
        $user = $this->users->create();

        // Act
        $this->get('/api/users/' . $user->getId());

        // Assert
        $response = $this->getJsonResponse();

        // Verify all expected fields are present
        $expectedFields = ['id', 'email', 'name', 'status', 'status_label', 'roles', 'created_at', 'updated_at'];
        foreach ($expectedFields as $field) {
            self::assertArrayHasKey($field, $response, "Field '{$field}' should be present in response");
        }

        // Verify no unexpected fields
        self::assertCount(\count($expectedFields), $response, 'Response should not contain unexpected fields');
    }
}

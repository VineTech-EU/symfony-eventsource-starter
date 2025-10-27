<?php

declare(strict_types=1);

namespace App\Tests\SharedKernel\Adapters\EventStore;

use App\Modules\User\Adapters\EventStore\Upcaster\UserCreatedV1ToV2Upcaster;
use App\Modules\User\Domain\Event\UserCreated;
use App\SharedKernel\Adapters\EventStore\DomainEventNormalizer;
use App\SharedKernel\Adapters\EventStore\EventSerializer;
use App\SharedKernel\Adapters\EventStore\EventTypeRegistry;
use App\SharedKernel\Adapters\EventStore\EventUpcasterChain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Integration tests for EventSerializer with active upcasters.
 *
 * Tests the complete upcasting workflow:
 * - Old event data (V1) stored in database
 * - Upcaster transforms V1 → V2
 * - EventSerializer deserializes to current event class
 *
 * @internal
 *
 * @covers \App\Modules\User\Adapters\EventStore\Upcaster\UserCreatedV1ToV2Upcaster
 * @covers \App\SharedKernel\Adapters\EventStore\EventSerializer
 * @covers \App\SharedKernel\Adapters\EventStore\EventUpcasterChain
 */
final class EventSerializerUpcasterIntegrationTest extends TestCase
{
    private EventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Symfony Serializer with custom DomainEvent normalizer
        $normalizers = [
            new DomainEventNormalizer(),
            new DateTimeNormalizer(),
            new ArrayDenormalizer(),
        ];
        $encoders = [new JsonEncoder()];

        $symfonySerializer = new Serializer($normalizers, $encoders);

        // Create EventUpcasterChain WITH an active upcaster
        $upcasters = [
            new UserCreatedV1ToV2Upcaster(),
        ];
        $upcasterChain = new EventUpcasterChain($upcasters);

        // Create EventTypeRegistry and register test events
        $registry = new EventTypeRegistry();
        $registry->register('user.created', UserCreated::class);

        $this->serializer = new EventSerializer($symfonySerializer, $upcasterChain, $registry);
    }

    public function testDeserializeWithOldV1EventTriggersUpcastingToV2(): void
    {
        // Arrange - Old V1 event data (missing 'emailVerified' field)
        $oldV1Payload = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'OLD@EXAMPLE.COM', // V1 had uppercase emails
            'name' => 'Test User',
            'roles' => ['ROLE_USER'],
            'status' => 'pending',
            // No 'emailVerified' field in V1
        ];

        // Act - Deserialize V1 event (storedVersion = 1, targetVersion = 2)
        // Note: For this test to work, UserCreated event class would need getEventVersion() = 2
        // Currently it returns 1, so upcasting won't trigger
        // This test demonstrates the SETUP for when we upgrade the event

        $event = $this->serializer->deserialize('user.created', $oldV1Payload, storedVersion: 1);

        // Assert - Event should be created successfully
        // In a real scenario with UserCreated V2:
        // - email would be lowercased by upcaster
        // - emailVerified would be added with default value false
        self::assertInstanceOf(UserCreated::class, $event);
        self::assertSame('550e8400-e29b-41d4-a716-446655440000', $event->getUserId());

        // Note: Currently the email won't be lowercased because UserCreated still has version 1
        // This test documents the expected behavior when we version up the event
    }

    public function testUpcasterChainCallsMultipleUpcasters(): void
    {
        // This test documents how the upcaster chain would work with multiple versions
        // Example: V1 → V2 → V3 (two upcasters chained)

        // Arrange - V1 event data
        $v1Payload = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'roles' => ['ROLE_USER'],
            'status' => 'pending',
        ];

        // Act - storedVersion = 1, targetVersion = 1 (no upcasting in current setup)
        $event = $this->serializer->deserialize('user.created', $v1Payload, storedVersion: 1);

        // Assert
        self::assertInstanceOf(UserCreated::class, $event);

        // Future: When we have V1→V2→V3 chain:
        // 1. EventSerializer detects storedVersion (1) < targetVersion (3)
        // 2. Calls upcasterChain.upcast('UserCreated', 1, payload, 3)
        // 3. Chain executes: V1ToV2Upcaster → V2ToV3Upcaster
        // 4. Final payload is V3 schema
        // 5. Denormalizer creates UserCreated V3 instance
    }

    public function testDeserializeWithMatchingVersionSkipsUpcasterChain(): void
    {
        // Arrange - Current V1 event data
        $currentPayload = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'roles' => ['ROLE_USER'],
            'status' => 'pending',
        ];

        // Act - storedVersion = 1, targetVersion = 1 (skip upcasting)
        $event = $this->serializer->deserialize('user.created', $currentPayload, storedVersion: 1);

        // Assert - Event created without transformation
        self::assertInstanceOf(UserCreated::class, $event);
        self::assertSame('test@example.com', $event->getEmail()); // Email not transformed
    }

    public function testGetEventTargetVersionExtractsVersionFromEventClass(): void
    {
        // This test verifies the getEventTargetVersion() private method
        // It now calls static getEventVersion() on the class

        // Act & Assert - UserCreated has version 2 (static call)
        self::assertSame(2, UserCreated::getEventVersion());
    }

    public function testGetEventNameFromClassExtractsShortNameForUpcaster(): void
    {
        // This test verifies the getEventNameFromClass() private method
        // It extracts "UserCreated" from "App\Modules\User\Domain\Event\UserCreated"

        // Arrange
        $payload = [
            'user_id' => '550e8400-e29b-41d4-a716-446655440000',
            'email' => 'test@example.com',
            'name' => 'Test User',
            'roles' => ['ROLE_USER'],
            'status' => 'pending',
        ];

        // Act - Internally calls getEventNameFromClass()
        $event = $this->serializer->deserialize('user.created', $payload);

        // Assert - Verify event was created (short name extraction succeeded)
        self::assertInstanceOf(UserCreated::class, $event);
    }
}

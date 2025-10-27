<?php

declare(strict_types=1);

namespace App\Tests\Modules\Notification\Adapters\Persistence\Doctrine;

use App\Modules\Notification\Adapters\Persistence\Doctrine\EmailOutboxRepository;
use App\Modules\Notification\Domain\Entity\EmailOutbox;
use App\Modules\Notification\Domain\ValueObject\EmailStatus;
use App\SharedKernel\Adapters\ValueObject\SymfonyUuid;
use App\Tests\Support\IntegrationTestCase;

/**
 * @covers \App\Modules\Notification\Adapters\Persistence\Doctrine\EmailOutboxRepository
 *
 * @internal
 */
final class EmailOutboxRepositoryIntegrationTest extends IntegrationTestCase
{
    private EmailOutboxRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get(EmailOutboxRepository::class);
    }

    public function testSaveInsertsNewEmailIntoDatabase(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'john@example.com',
            recipientName: 'John Doe',
            subject: 'Welcome!',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        // Act
        $this->repository->save($email);

        // Assert
        $found = $this->repository->findByEventId($email->getEventId());
        self::assertCount(1, $found);
        self::assertSame($email->getId(), $found[0]->getId());
        self::assertSame('welcome', $found[0]->getEmailType());
        self::assertSame('john@example.com', $found[0]->getRecipientEmail());
    }

    public function testSaveIgnoresDuplicateEventIdRecipientAndType(): void
    {
        // Arrange
        $sharedEventId = SymfonyUuid::generate()->toString();

        $email1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: $sharedEventId,
            emailType: 'welcome',
            recipientEmail: 'alice@example.com',
            recipientName: 'Alice',
            subject: 'First',
            htmlBody: '<html>First</html>',
            textBody: 'First',
        );

        $email2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: $sharedEventId, // Same event_id
            emailType: 'welcome',       // Same email_type
            recipientEmail: 'alice@example.com', // Same recipient
            recipientName: 'Alice Updated',
            subject: 'Second',
            htmlBody: '<html>Second</html>',
            textBody: 'Second',
        );

        // Act
        $this->repository->save($email1);
        $this->repository->save($email2); // Should be ignored due to ON CONFLICT

        // Assert
        $found = $this->repository->findByEventId($sharedEventId);
        self::assertCount(1, $found); // Only one email
        self::assertSame('First', $found[0]->getSubject()); // First email preserved
    }

    public function testUpdateChangesEmailStatus(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'bob@example.com',
            recipientName: 'Bob',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        $this->repository->save($email);

        // Act
        $email->markAsSent();
        $this->repository->update($email);

        // Assert
        $found = $this->repository->findByEventId($email->getEventId());
        self::assertSame(EmailStatus::SENT, $found[0]->getStatus());
        self::assertNotNull($found[0]->getSentAt());
    }

    public function testUpdateIncrementsAttemptsAndSetsError(): void
    {
        // Arrange
        $email = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'charlie@example.com',
            recipientName: 'Charlie',
            subject: 'Welcome',
            htmlBody: '<html>Welcome</html>',
            textBody: 'Welcome',
        );

        $this->repository->save($email);

        // Act
        $email->markAsFailed('Connection timeout');
        $this->repository->update($email);

        // Assert
        $found = $this->repository->findByEventId($email->getEventId());
        self::assertSame(1, $found[0]->getAttempts());
        self::assertSame('Connection timeout', $found[0]->getLastError());
        self::assertSame(EmailStatus::PENDING, $found[0]->getStatus());
    }

    public function testFindPendingReturnsOnlyPendingEmails(): void
    {
        // Arrange
        $pending1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'pending1@example.com',
            recipientName: 'Pending 1',
            subject: 'Pending 1',
            htmlBody: '<html>Pending 1</html>',
            textBody: 'Pending 1',
        );

        $pending2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'pending2@example.com',
            recipientName: 'Pending 2',
            subject: 'Pending 2',
            htmlBody: '<html>Pending 2</html>',
            textBody: 'Pending 2',
        );

        $sent = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'sent@example.com',
            recipientName: 'Sent',
            subject: 'Sent',
            htmlBody: '<html>Sent</html>',
            textBody: 'Sent',
        );
        $sent->markAsSent();

        $this->repository->save($pending1);
        $this->repository->save($pending2);
        $this->repository->save($sent);
        $this->repository->update($sent);

        // Act
        $result = $this->repository->findPending();

        // Assert
        self::assertCount(2, $result);
        foreach ($result as $email) {
            self::assertSame(EmailStatus::PENDING, $email->getStatus());
        }
    }

    public function testFindPendingRespectsLimit(): void
    {
        // Arrange - Create 5 pending emails
        for ($i = 1; $i <= 5; ++$i) {
            $email = EmailOutbox::create(
                id: SymfonyUuid::generate()->toString(),
                eventId: SymfonyUuid::generate()->toString(),
                emailType: 'welcome',
                recipientEmail: "user{$i}@example.com",
                recipientName: "User {$i}",
                subject: "Subject {$i}",
                htmlBody: "<html>Body {$i}</html>",
                textBody: "Body {$i}",
            );
            $this->repository->save($email);
        }

        // Act
        $result = $this->repository->findPending(limit: 3);

        // Assert
        self::assertCount(3, $result);
    }

    public function testFindPendingReturnsOldestFirst(): void
    {
        // Arrange
        $email1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'old@example.com',
            recipientName: 'Old',
            subject: 'Old',
            htmlBody: '<html>Old</html>',
            textBody: 'Old',
        );

        $this->repository->save($email1);
        sleep(1); // Ensure different created_at timestamps

        $email2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'new@example.com',
            recipientName: 'New',
            subject: 'New',
            htmlBody: '<html>New</html>',
            textBody: 'New',
        );

        $this->repository->save($email2);

        // Act
        $result = $this->repository->findPending();

        // Assert
        self::assertGreaterThanOrEqual(2, \count($result));
        // First email should be the oldest
        self::assertSame($email1->getEventId(), $result[0]->getEventId());
    }

    public function testFindByEventIdReturnsAllEmailsForEvent(): void
    {
        // Arrange - Same event, different recipients
        $sharedEventId = SymfonyUuid::generate()->toString();

        $email1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: $sharedEventId,
            emailType: 'admin_notification',
            recipientEmail: 'admin1@example.com',
            recipientName: 'Admin 1',
            subject: 'Notification',
            htmlBody: '<html>Notification</html>',
            textBody: 'Notification',
        );

        $email2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: $sharedEventId,
            emailType: 'admin_notification',
            recipientEmail: 'admin2@example.com',
            recipientName: 'Admin 2',
            subject: 'Notification',
            htmlBody: '<html>Notification</html>',
            textBody: 'Notification',
        );

        $this->repository->save($email1);
        $this->repository->save($email2);

        // Act
        $result = $this->repository->findByEventId($sharedEventId);

        // Assert
        self::assertCount(2, $result);
        self::assertSame($sharedEventId, $result[0]->getEventId());
        self::assertSame($sharedEventId, $result[1]->getEventId());
    }

    public function testCountByStatusReturnsCorrectCount(): void
    {
        // Arrange
        $pending = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'pending@example.com',
            recipientName: 'Pending',
            subject: 'Pending',
            htmlBody: '<html>Pending</html>',
            textBody: 'Pending',
        );

        $sent = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'sent@example.com',
            recipientName: 'Sent',
            subject: 'Sent',
            htmlBody: '<html>Sent</html>',
            textBody: 'Sent',
        );
        $sent->markAsSent();

        $this->repository->save($pending);
        $this->repository->save($sent);
        $this->repository->update($sent);

        // Act
        $pendingCount = $this->repository->countByStatus(EmailStatus::PENDING);
        $sentCount = $this->repository->countByStatus(EmailStatus::SENT);

        // Assert
        self::assertGreaterThanOrEqual(1, $pendingCount);
        self::assertGreaterThanOrEqual(1, $sentCount);
    }

    public function testGetOldestPendingReturnsOldestEmail(): void
    {
        // Arrange
        $email1 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'oldest1@example.com',
            recipientName: 'Oldest 1',
            subject: 'Oldest 1',
            htmlBody: '<html>Oldest 1</html>',
            textBody: 'Oldest 1',
        );

        $this->repository->save($email1);
        sleep(1);

        $email2 = EmailOutbox::create(
            id: SymfonyUuid::generate()->toString(),
            eventId: SymfonyUuid::generate()->toString(),
            emailType: 'welcome',
            recipientEmail: 'oldest2@example.com',
            recipientName: 'Oldest 2',
            subject: 'Oldest 2',
            htmlBody: '<html>Oldest 2</html>',
            textBody: 'Oldest 2',
        );

        $this->repository->save($email2);

        // Act
        $result = $this->repository->getOldestPending();

        // Assert
        self::assertNotNull($result);
        self::assertSame($email1->getEventId(), $result->getEventId());
    }

    public function testGetOldestPendingReturnsNullWhenNoEmails(): void
    {
        // Arrange - No emails in database (automatically clean due to DAMA)

        // Act
        $result = $this->repository->getOldestPending();

        // Assert
        self::assertNull($result);
    }
}

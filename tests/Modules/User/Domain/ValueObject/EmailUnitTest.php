<?php

declare(strict_types=1);

namespace App\Tests\Modules\User\Domain\ValueObject;

use App\Modules\User\Domain\ValueObject\Email;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Email value object.
 * Tests email validation and immutability.
 *
 * @internal
 *
 * @covers \App\Modules\User\Domain\ValueObject\Email
 */
final class EmailUnitTest extends TestCase
{
    public function testFromStringWithValidEmailCreatesEmail(): void
    {
        // Arrange
        $emailAddress = 'john@example.com';

        // Act
        $email = Email::fromString($emailAddress);

        // Assert
        self::assertSame($emailAddress, $email->toString());
    }

    /**
     * @dataProvider provideFromStringWithInvalidEmailThrowsExceptionCases
     */
    public function testFromStringWithInvalidEmailThrowsException(string $invalidEmail): void
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email');

        Email::fromString($invalidEmail);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideFromStringWithInvalidEmailThrowsExceptionCases(): iterable
    {
        return [
            'no at symbol' => ['notanemail'],
            'no domain' => ['notan@'],
            'no local part' => ['@example.com'],
            'no tld' => ['user@domain'],
            'spaces' => ['user @example.com'],
            'double at' => ['user@@example.com'],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider provideFromStringWithVariousValidEmailsCases
     */
    public function testFromStringWithVariousValidEmails(string $validEmail): void
    {
        // Act
        $email = Email::fromString($validEmail);

        // Assert
        self::assertSame($validEmail, $email->toString());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function provideFromStringWithVariousValidEmailsCases(): iterable
    {
        return [
            'simple' => ['user@example.com'],
            'subdomain' => ['user@mail.example.com'],
            'plus sign' => ['user+tag@example.com'],
            'hyphen' => ['user-name@example.com'],
            'dot' => ['user.name@example.com'],
            'numbers' => ['user123@example.com'],
            'short tld' => ['user@example.co'],
        ];
    }

    public function testEqualsReturnsTrueForSameEmail(): void
    {
        // Arrange
        $email1 = Email::fromString('test@example.com');
        $email2 = Email::fromString('test@example.com');

        // Act
        $result = $email1->equals($email2);

        // Assert
        self::assertTrue($result);
    }

    public function testEqualsReturnsFalseForDifferentEmails(): void
    {
        // Arrange
        $email1 = Email::fromString('test1@example.com');
        $email2 = Email::fromString('test2@example.com');

        // Act
        $result = $email1->equals($email2);

        // Assert
        self::assertFalse($result);
    }

    public function testEmailIsImmutable(): void
    {
        // Arrange
        $originalEmail = 'test@example.com';
        $email = Email::fromString($originalEmail);

        // Act
        $emailString = $email->toString();

        // Assert - toString() should return same value (immutable)
        self::assertSame($originalEmail, $emailString);
        self::assertSame($originalEmail, $email->toString()); // Second call should be identical
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Persistence\Projection\ValueObject;

/**
 * User Status Enum - Projection Layer.
 *
 * This is a SEPARATE enum from Domain's UserStatus.
 * It exists in the Adapters/Projection layer.
 *
 * Why a separate enum?
 * ✅ Projection doesn't depend on Domain (CQRS principle)
 * ✅ Can diverge from Domain if needed (different statuses in read model)
 * ✅ Autocomplete in IDE
 * ✅ Type safety
 * ✅ No magic strings
 *
 * Why not use Domain's UserStatus?
 * ❌ Violates CQRS separation (Read depending on Write)
 * ❌ Projection coupled to Domain changes
 * ❌ Performance (object instantiation)
 *
 * This enum can have:
 * - Same values as Domain (most common)
 * - Different values (if projection has different needs)
 * - Additional helper methods for read-side queries
 */
enum UserStatusEnum: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';

    /**
     * Check if status is pending.
     */
    public function isPending(): bool
    {
        return self::PENDING === $this;
    }

    /**
     * Check if status is approved.
     */
    public function isApproved(): bool
    {
        return self::APPROVED === $this;
    }

    /**
     * Check if status is rejected.
     */
    public function isRejected(): bool
    {
        return self::REJECTED === $this;
    }

    /**
     * Get human-readable label (for UI).
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Pending Approval',
            self::APPROVED => 'Approved',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * Get color for UI (optional, projection-specific).
     */
    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'orange',
            self::APPROVED => 'green',
            self::REJECTED => 'red',
        };
    }

    /**
     * Get all possible statuses.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }
}

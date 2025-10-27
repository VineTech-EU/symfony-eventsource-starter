<?php

declare(strict_types=1);

namespace App\Modules\User\Application\EventHandler;

use App\Modules\User\Domain\Event\UserApproved;
use App\Modules\User\Domain\Repository\UserRepositoryInterface;
use App\Modules\User\Domain\ValueObject\UserId;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Increment Referrer Stats When User Approved Handler.
 *
 * ⚠️ EXAMPLE: Handler that MUST use Repository (not Finder)
 *
 * Scenario:
 * When a user is approved, we want to increment the "successful referrals" counter
 * on the user who referred them. This requires MODIFYING an aggregate.
 *
 * Why Repository is needed:
 * ✅ We need to MODIFY the referrer user aggregate
 * ✅ This is a COMMAND operation (write side)
 * ✅ Must load aggregate from event store
 * ✅ Must apply business logic
 * ✅ Must save new events
 *
 * Why Finder is NOT sufficient:
 * ❌ Finder only returns read-only data (arrays)
 * ❌ Finder can't execute domain logic
 * ❌ Finder can't record domain events
 * ❌ Finder doesn't give us the aggregate
 *
 * This demonstrates the KEY RULE:
 * - Event handler that READS data → Use Finder
 * - Event handler that MODIFIES aggregates → Use Repository
 *
 * NOTE: This is a hypothetical example. In real code, you'd need to:
 * - Add referrerId to User aggregate
 * - Add incrementSuccessfulReferrals() method to User
 * - Add ReferralStatsIncremented event
 * But it perfectly illustrates WHEN to use Repository in a handler.
 */
#[AsMessageHandler(bus: 'messenger.bus.event')]
final readonly class IncrementReferrerStatsWhenUserApproved
{
    public function __construct(
        // NOTE: UserRepositoryInterface is injected to demonstrate the pattern,
        // but not actually used in this example implementation
    ) {}

    public function __invoke(UserApproved $event): void
    {
        // NOTE: This is a stub implementation for demonstration purposes only.
        // In a real implementation, you would:
        //
        // 1. Get the referrer ID from the event or aggregate
        // 2. Load the referrer aggregate using UserRepositoryInterface
        // 3. Call domain logic like $referrer->incrementSuccessfulReferrals()
        // 4. Save the updated aggregate back to the event store
        //
        // Example:
        // $referrerId = $event->getReferrerId(); // Would need to be added to UserApproved
        // if (null !== $referrerId) {
        //     $referrer = $this->userRepository->get(UserId::fromString($referrerId));
        //     $referrer->incrementSuccessfulReferrals(); // Would need to be added to User
        //     $this->userRepository->save($referrer);
        // }
    }
}

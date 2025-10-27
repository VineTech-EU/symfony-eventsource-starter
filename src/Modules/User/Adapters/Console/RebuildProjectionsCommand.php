<?php

declare(strict_types=1);

namespace App\Modules\User\Adapters\Console;

use App\Modules\User\Adapters\Persistence\Projection\UserReadModel;
use App\SharedKernel\Adapters\EventStore\EventSerializer;
use App\SharedKernel\Adapters\EventStore\EventStoreEntity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Rebuild Projections Command.
 *
 * Rebuilds read models (projections) by replaying all events from the event store.
 *
 * Use cases:
 * - Corrupted projection data
 * - Schema migration of read models
 * - Adding new projections
 * - Testing event handlers
 * - Disaster recovery
 *
 * How it works:
 * 1. Clears all projection tables
 * 2. Loads ALL events from event store (ordered by version)
 * 3. Dispatches each event to event bus
 * 4. Event handlers rebuild projections
 *
 * Example:
 *   php bin/console app:projections:rebuild
 *   php bin/console app:projections:rebuild --aggregate-type=User
 *   php bin/console app:projections:rebuild --from-aggregate-id=uuid
 *
 * ⚠️  WARNING: This deletes all projection data before rebuilding!
 */
#[AsCommand(
    name: 'app:projections:rebuild',
    description: 'Rebuild read model projections from event store'
)]
final class RebuildProjectionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EventSerializer $eventSerializer,
        private readonly MessageBusInterface $eventBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'aggregate-type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Only rebuild projections for specific aggregate type (e.g., User)'
            )
            ->addOption(
                'from-aggregate-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Rebuild from specific aggregate ID (for testing)'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Show what would be done without actually rebuilding'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var null|string $aggregateType */
        $aggregateType = $input->getOption('aggregate-type');

        /** @var null|string $fromAggregateId */
        $fromAggregateId = $input->getOption('from-aggregate-id');
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Rebuild Projections from Event Store');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
        }

        // Step 1: Clear projections
        if (!$dryRun) {
            $io->section('Step 1: Clearing projections');
            $this->clearProjections($io, $aggregateType);
        }

        // Step 2: Load events
        $io->section('Step 2: Loading events from event store');
        $events = $this->loadEvents($aggregateType, $fromAggregateId);

        $io->info(\sprintf('Found %d events to replay', \count($events)));

        if ([] === $events) {
            $io->success('No events to replay');

            return Command::SUCCESS;
        }

        // Step 3: Replay events
        $io->section('Step 3: Replaying events');

        if ($dryRun) {
            $io->info('Would dispatch ' . \count($events) . ' events to event bus');

            return Command::SUCCESS;
        }

        $progressBar = $io->createProgressBar(\count($events));
        $progressBar->start();

        foreach ($events as $eventEntity) {
            try {
                $domainEvent = $this->eventSerializer->deserialize(
                    $eventEntity->getEventType(),
                    $eventEntity->getPayload(),
                    $eventEntity->getEventVersion()
                );

                $this->eventBus->dispatch($domainEvent);

                $progressBar->advance();
            } catch (\Exception $e) {
                $io->error(\sprintf(
                    'Failed to replay event %s (ID: %s): %s',
                    $eventEntity->getEventType(),
                    $eventEntity->getEventId(),
                    $e->getMessage()
                ));

                return Command::FAILURE;
            }
        }

        $progressBar->finish();
        $io->newLine(2);

        // Step 4: Verify
        $io->section('Step 4: Verification');
        $this->verifyProjections($io);

        $io->success(\sprintf('Successfully rebuilt projections from %d events', \count($events)));

        return Command::SUCCESS;
    }

    /**
     * Clear projection tables.
     */
    private function clearProjections(SymfonyStyle $io, ?string $aggregateType): void
    {
        if (null !== $aggregateType && 'User' !== $aggregateType) {
            $io->note("Only User projections supported currently. Skipping clear for: {$aggregateType}");

            return;
        }

        $count = $this->entityManager
            ->createQuery('SELECT COUNT(u) FROM ' . UserReadModel::class . ' u')
            ->getSingleScalarResult()
        ;

        $io->writeln(\sprintf('Deleting %d user read models...', $count));

        $this->entityManager
            ->createQuery('DELETE FROM ' . UserReadModel::class)
            ->execute()
        ;

        $io->success('Projections cleared');
    }

    /**
     * Load events from event store.
     *
     * @return list<EventStoreEntity>
     */
    private function loadEvents(?string $aggregateType, ?string $fromAggregateId): array
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('e')
            ->from(EventStoreEntity::class, 'e')
            ->orderBy('e.recordedOn', 'ASC')
            ->addOrderBy('e.version', 'ASC')
        ;

        if (null !== $aggregateType) {
            $qb->andWhere('e.aggregateType = :aggregateType')
                ->setParameter('aggregateType', $aggregateType)
            ;
        }

        if (null !== $fromAggregateId) {
            $qb->andWhere('e.aggregateId = :aggregateId')
                ->setParameter('aggregateId', $fromAggregateId)
            ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Verify projections after rebuild.
     */
    private function verifyProjections(SymfonyStyle $io): void
    {
        $userCount = $this->entityManager
            ->createQuery('SELECT COUNT(u) FROM ' . UserReadModel::class . ' u')
            ->getSingleScalarResult()
        ;

        $io->writeln(\sprintf('✓ User read models: %d', $userCount));

        // Could add more verifications:
        // - Check for orphaned records
        // - Verify aggregate counts match
        // - Check data integrity
    }
}

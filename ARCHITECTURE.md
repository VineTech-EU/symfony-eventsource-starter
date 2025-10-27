# Architecture Decision Records (ADRs)

This document contains the key architectural decisions for this project.

---

## ADR-001: Modular Monolith Architecture

**Status:** Accepted
**Date:** 2025-01-07

### Decision

**We adopt a Modular Monolith architecture with clear bounded context separation.**

This architecture provides:
- Clear boundaries between different business domains
- Ability to scale specific features independently
- Physical isolation to prevent accidental coupling
- Potential to extract modules into microservices later

### Structure

**Implemented Modules** (2 complete examples):

```
src/
├── Modules/
│   ├── User/                        # User Management (complete example)
│   └── Notification/                # Email & Notifications (complete example)
│
└── SharedKernel/                     # Shared Adapters
    ├── Domain/                      # AggregateRoot, DomainEvent, EventBusInterface
    ├── Application/                 # CommandBusInterface, QueryBusInterface
    └── Adapters/                    # Event Store, Buses, Monitoring
```

Each module contains:
```
{Module}/
├── Domain/            # Pure business rules
├── Application/       # Use Cases, Handlers, Event Handlers
└── Adapters/          # Controllers, Repositories, Projections
```

### Rationale

#### 1. Clear Module Boundaries

**Each module is a complete vertical slice:**
- Own Domain layer (aggregates, events, value objects)
- Own Application layer (use cases, handlers)
- Own Adapters layer (controllers, repositories, projections)

**Example - User Module:**
```
Modules/User/
├── Domain/
│   ├── Entity/User.php
│   ├── Event/UserCreated.php
│   └── Repository/UserRepositoryInterface.php
├── Application/
│   ├── UseCase/CreateUser.php
│   ├── Command/CreateUserCommandHandler.php
│   └── EventHandler/UserProjectionHandler.php
└── Adapters/
    ├── Http/CreateUserController.php
    └── Persistence/Repository/DoctrineUserRepository.php
```

#### 2. Physical Isolation via Schema-per-Module

**PostgreSQL database organized by schemas:**
- `public` schema: `event_store` table (shared across all modules)
- `user_module` schema: `user_read_model` table
- `notification_module` schema: `notification_read_model`, `email_outbox` tables

**Benefits:**
- Physical isolation prevents accidental cross-module queries
- Independent migrations per module
- Clear data ownership
- Easy to monitor module-specific metrics

#### 3. Event-Driven Communication

**Modules communicate ONLY via domain events through message bus (RabbitMQ).**

**Example - Cross-Module Flow:**
```php
// 1️⃣ User Module - Create a user
namespace App\Modules\User\Application\UseCase;

$user = User::create(
    UserId::fromString($userId),
    Email::fromString($email),
    $name
);
$this->userRepository->save($user);
// → Emits UserCreated domain event
// → Publishes UserWasCreatedIntegrationEvent to RabbitMQ

// 2️⃣ Notification Module - React to UserWasCreatedIntegrationEvent
namespace App\Modules\Notification\Application\EventHandler;

#[AsMessageHandler(bus: 'messenger.bus.event')]
class SendWelcomeEmailWhenUserCreated
{
    public function __invoke(UserWasCreatedIntegrationEvent $event): void
    {
        // NO direct dependency on User module!
        // Receives only primitives from event
        $this->sendEmail->execute(
            to: $event->email,
            subject: 'Welcome!',
            template: 'welcome',
            data: ['name' => $event->name]
        );
    }
}
```

**Key Rules:**
- ❌ NO direct imports between modules (`use App\Modules\User\...` in Notification module)
- ❌ NO cross-module repository access
- ❌ NO synchronous calls between modules
- ✅ Events via message bus ONLY
- ✅ Eventual consistency between modules

#### 4. SharedKernel

**Common infrastructure in `SharedKernel/`:**
- Domain building blocks: `AggregateRoot`, `DomainEvent`, `EventBusInterface`
- Application interfaces: `CommandBusInterface`, `QueryBusInterface`
- Adapters implementations: Event Store, Messenger buses, Monitoring

**All modules can depend on `SharedKernel/` but NOT on each other.**

### Benefits

| Benefit                    | Description                                        |
|----------------------------|----------------------------------------------------|
| **Scalability**            | Can extract a module into a microservice if needed |
| **Team Organization**      | Different teams can own different modules          |
| **Clear Boundaries**       | Physical isolation prevents accidental coupling    |
| **Independent Testing**    | Test modules in isolation                          |
| **Focused Development**    | Work on one module without affecting others        |
| **Technology Flexibility** | Could use different storage per module later       |
| **Monitoring**             | Module-specific metrics and dashboards             |
| **Migration Path**         | Easy evolution to microservices if needed          |

### Trade-offs

| Advantage           | Disadvantage                            |
|---------------------|-----------------------------------------|
| Clear boundaries    | More files/folders                      |
| Physical isolation  | Need to manage events carefully         |
| Independent scaling | Eventual consistency complexity         |
| Module ownership    | Cross-cutting concerns need shared code |

### Module Communication Matrix

```
                User  Notification
User            ✅    📧
Notification    ⬅️    ✅

Legend:
✅ = Internal operations
📧 = Emits integration events
⬅️ = Consumes integration events
```

### Implementation Guidelines

1. **Creating a New Module:**
   - Create `Modules/{ModuleName}/` folder structure
   - Add PostgreSQL schema: `{module_name}_module`
   - Define module-specific events
   - Implement use cases
   - Add event handlers for cross-module reactions

2. **Cross-Module Communication:**
   - Always use events via RabbitMQ
   - Include all necessary data in event payload (use primitives, not value objects)
   - Never query another module's read model directly
   - Design events for reusability (multiple handlers can react)

3. **Shared Code:**
   - Domain building blocks → `SharedKernel/Domain/`
   - Application interfaces → `SharedKernel/Application/`
   - Adapters (Event Store, buses) → `SharedKernel/Adapters/`
   - NO business logic in `SharedKernel/`

### Implemented Modules

- **User Module**: User management, authentication, approval workflow
  - Demonstrates: Aggregates, Value Objects, Domain Events, Use Cases, Projections
  - Features: User creation, email change, approval workflow, role management

- **Notification Module**: Email notifications with Transactional Outbox pattern
  - Demonstrates: Cross-module event handling, reliable email delivery, outbox pattern
  - Features: Sends welcome emails, admin notifications, reacts to user events

### References

- See [CLAUDE.md](CLAUDE.md) for complete module structure
- See [.claude/docs/MODULES.md](.claude/docs/MODULES.md) for module creation guide
- See [.claude/docs/guides/CROSS_MODULE_EVENTS.md](.claude/docs/guides/CROSS_MODULE_EVENTS.md) for event patterns

---

## ADR-002: Use Cases vs Command Handlers

## Context

In DDD/CQRS/Clean Architecture, we need to decide how to structure the Application layer:
- Should Commands/Queries BE the Use Cases?
- Or should Commands/Queries be separate from Use Cases?

## Decision

**We separate Use Cases from Command/Query Handlers.**

## Structure (Modular)

```
Modules/User/Application/
├── UseCase/                    # Business logic (framework-agnostic)
│   ├── CreateUser.php
│   ├── ChangeUserEmail.php
│   └── GetUser.php
├── Command/                    # Messenger adapters
│   ├── CreateUserCommand.php   # DTO
│   └── CreateUserCommandHandler.php  # Calls CreateUser use case
└── Query/                      # Messenger adapters
    ├── GetUserQuery.php        # DTO
    └── GetUserQueryHandler.php # Calls GetUser use case
```

## Rationale

### Use Cases (Application Layer)

**What**: Core business orchestration logic

**Characteristics**:
- Framework-agnostic
- Reusable from any entry point
- Testable without infrastructure
- Single Responsibility: orchestrate domain objects

**Example**:
```php
final readonly class CreateUser
{
    public function execute(string $userId, string $email, string $name): void
    {
        $user = User::create(
            UserId::fromString($userId),
            Email::fromString($email),
            $name
        );

        $this->repository->save($user);
    }
}
```

### Command Handlers (Adapters)

**What**: Symfony Messenger adapters

**Characteristics**:
- Adapters concern (couples to Messenger)
- Thin layer that delegates to use case
- Handles message bus-specific concerns

**Example**:
```php
#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CreateUserCommandHandler
{
    public function __construct(private CreateUser $createUser) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $this->createUser->execute(
            $command->userId,
            $command->email,
            $command->name
        );
    }
}
```

## Benefits

### 1. Reusability

The same use case can be called from multiple entry points:

```php
// HTTP Controller (direct call)
final class CreateUserController
{
    public function __construct(private CreateUser $createUser) {}

    public function __invoke(Request $request): Response
    {
        $this->createUser->execute($userId, $email, $name);
        return new JsonResponse(['id' => $userId], 201);
    }
}

// CLI Command (direct call)
final class CreateUserCliCommand extends Command
{
    public function __construct(private CreateUser $createUser) {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createUser->execute($userId, $email, $name);
        return Command::SUCCESS;
    }
}

// Command Handler (async via bus)
#[AsMessageHandler]
final class CreateUserCommandHandler
{
    public function __construct(private CreateUser $createUser) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $this->createUser->execute(...);
    }
}

// Event Handler (reacts to other events)
#[AsMessageHandler]
final class CreateUserWhenOrderPlaced
{
    public function __construct(private CreateUser $createUser) {}

    public function __invoke(OrderPlaced $event): void
    {
        $this->createUser->execute(...);
    }
}
```

### 2. Testability

Use cases can be tested without Symfony Messenger:

```php
class CreateUserTest extends TestCase
{
    public function testCreatesUser(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $useCase = new CreateUser($repository);

        // No message bus needed!
        $useCase->execute($userId, $email, $name);

        // Assert...
    }
}
```

### 3. No Framework Coupling

Use cases don't depend on:
- Symfony Messenger
- Message bus interfaces
- Handler attributes

They're pure business logic.

### 4. Flexibility in Entry Points

Different entry points can have different behaviors:

```php
// HTTP: Synchronous, immediate error handling
$controller->createUser->execute(...);

// Message Bus: Async, fire-and-forget
$commandBus->dispatch(new CreateUserCommand(...));

// CLI: Synchronous, with progress output
$cliCommand->createUser->execute(...);
```

### 5. Clear Separation of Concerns

| Layer       | Responsibility                           | Example                             |
|-------------|------------------------------------------|-------------------------------------|
| Adapters    | HTTP parsing, CLI input, Message routing | Controllers, CLI Commands, Handlers |
| Application | Business orchestration                   | Use Cases                           |
| Domain      | Business rules                           | Aggregates, Value Objects           |

## Trade-offs

### Additional Complexity
- ✅ More files (Handler + Use Case vs just Handler)
- ❌ But better separation and reusability

### When to Skip Separation

For **trivial CRUD** with no business logic:
```php
// Simple read - no use case needed
#[AsMessageHandler]
class GetUserQueryHandler
{
    public function __invoke(GetUserQuery $query): ?array
    {
        return $this->repository->findById($query->userId)?->toArray();
    }
}
```

For **complex business operations** - always separate.

## Controllers: Direct Call vs Bus

### Direct Call (Recommended for HTTP)
```php
public function __construct(private CreateUser $createUser) {}

public function __invoke(Request $request): Response
{
    try {
        $this->createUser->execute(...);
        return new JsonResponse(['id' => $userId], 201);
    } catch (DomainException $e) {
        return new JsonResponse(['error' => $e->getMessage()], 400);
    }
}
```

**Benefits**:
- Synchronous error handling
- Immediate validation feedback
- Simpler for typical HTTP APIs

### Via Command Bus (Use for Async)
```php
public function __construct(private CommandBusInterface $bus) {}

public function __invoke(Request $request): Response
{
    $this->bus->dispatch(new CreateUserCommand(...));
    return new JsonResponse(['message' => 'Processing'], 202);
}
```

**Use when**:
- Long-running operations
- Need to return immediately (202 Accepted)
- Want fire-and-forget semantics

## Layer Placement

### ✅ Correct: Handlers in Application (per Module)

```
Modules/User/Application/
└── Command/
    └── CreateUserCommandHandler.php  ← Orchestration logic
```

**Why**: Handlers contain business orchestration, not technical details.

### ❌ Wrong: Handlers in Adapters Layer

```
SharedKernel/Adapters/Messaging/
└── CreateUserCommandHandler.php  ← NO! Contains business logic
```

**Problem**: Adapters layer should only contain technical adapters, not business logic.

### Controllers in Module Adapters Layer ✅

```
Modules/User/Adapters/
└── Http/
    └── CreateUserController.php  ← HTTP adapter
```

**Why**: Controllers are HTTP adapters (technical detail) specific to the module.

## Comparison

| Aspect       | Use Case        | Command Handler       | Controller             |
|--------------|-----------------|-----------------------|------------------------|
| Layer        | Application     | Application           | Adapters               |
| Nature       | Business logic  | Adapter               | Adapter                |
| Dependencies | Domain only     | Use Case + Messenger  | Use Case + HTTP        |
| Testability  | Pure unit tests | Needs bus mock        | Needs HTTP mock        |
| Reusable     | ✅ Yes           | ❌ No (coupled to bus) | ❌ No (coupled to HTTP) |
| Framework    | ❌ None          | ✅ Symfony Messenger   | ✅ Symfony HTTP         |

## Examples in This Project (Modular Structure)

### CreateUser Use Case
```php
// src/Modules/User/Application/UseCase/CreateUser.php
namespace App\Modules\User\Application\UseCase;

final readonly class CreateUser
{
    public function execute(string $userId, string $email, string $name): void
    {
        // Pure business logic
    }
}
```

### Command Handler (Adapter)
```php
// src/Modules/User/Application/Command/CreateUserCommandHandler.php
namespace App\Modules\User\Application\Command;

#[AsMessageHandler(bus: 'messenger.bus.command')]
final readonly class CreateUserCommandHandler
{
    public function __construct(private CreateUser $createUser) {}

    public function __invoke(CreateUserCommand $command): void
    {
        $this->createUser->execute(...);
    }
}
```

### HTTP Controller (Adapter)
```php
// src/Modules/User/Adapters/Http/CreateUserController.php
namespace App\Modules\User\Adapters\Http;

final readonly class CreateUserController
{
    public function __construct(private CreateUser $createUser) {}

    public function __invoke(Request $request): JsonResponse
    {
        $this->createUser->execute(...);
        return new JsonResponse(...);
    }
}
```

### CLI Command (Adapter)
```php
// src/Modules/User/Adapters/Cli/CreateUserCommand.php
namespace App\Modules\User\Adapters\Cli;

final class CreateUserCommand extends Command
{
    public function __construct(private CreateUser $createUser) {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->createUser->execute(...);
        return Command::SUCCESS;
    }
}
```

## Conclusion

**Use Cases are the heart of the Application layer.**

They contain the business orchestration logic and are:
- Framework-agnostic
- Reusable from any entry point
- Easily testable
- The "what" (business logic)

**Handlers and Controllers are adapters.**

They handle:
- Message routing (Handlers)
- HTTP concerns (Controllers)
- CLI concerns (CLI Commands)
- The "how" (technical delivery)

This separation provides maximum flexibility and maintainability.

---

**Version:** 1.0
**Last Updated:** 2025-01-11

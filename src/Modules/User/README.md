# User Module

**Bounded Context**: User Management & Authentication

## Responsibilities

- User registration and account management
- User approval workflow (pending → approved)
- Email management and verification
- User roles (ROLE_USER, ROLE_ADMIN)
- User status tracking (pending, approved, suspended)

## Domain Model

**Aggregate**: `User`
- Event-sourced aggregate
- Commands: CreateUser, ChangeUserEmail, ApproveUser
- Events: UserCreated, UserEmailChanged, UserApproved

**Value Objects**:
- `UserId` - UUID identifier
- `Email` - Validated email address
- `UserRole` - Enum (ROLE_USER, ROLE_ADMIN)
- `UserStatus` - Enum (pending, approved, suspended)

## Public API (Integration Events)

Other modules can listen to these events:

- `UserWasCreatedIntegrationEvent` - Fired when new user registers
- `UserWasApprovedIntegrationEvent` - Fired when admin approves user
- `UserEmailWasChangedIntegrationEvent` - Fired when user changes email

## Dependencies

- **Shared Kernel**: Event Store, Domain Events, Aggregate Root
- No dependencies on other modules (this is a foundational module)

## Database Schema

PostgreSQL schema: `user_module`

Tables:
- `public.event_store` - Event source (shared across all modules)
- `user_module.user_read_model` - User projection for queries

## Architecture

```
Domain/
  ├── Entity/User.php (Aggregate Root)
  ├── ValueObject/ (Email, UserId, UserRole, UserStatus)
  ├── Event/ (UserCreated, UserEmailChanged, UserApproved)
  ├── Repository/UserRepositoryInterface.php
  └── Exception/UserNotFoundException.php

Application/
  ├── UseCase/ (CreateUser, ChangeUserEmail, ApproveUser, GetUser)
  ├── Command/ (CreateUserCommand + Handler)
  ├── Query/ (GetUserQuery + Handler, UserFinderInterface)
  └── EventHandler/
      ├── UserProjectionHandler.php (Updates read model)
      ├── SendWelcomeEmailHandler.php (Sends welcome email)
      ├── NotifyAdminsOfNewUserHandler.php (Notifies admins)
      ├── PublishUserCreatedIntegrationEvent.php (Publishes to other modules)
      ├── PublishUserApprovedIntegrationEvent.php
      └── PublishUserEmailChangedIntegrationEvent.php

Adapters/
  ├── Http/Controller/ (REST API endpoints)
  ├── Cli/Command/ (CLI commands)
  ├── Persistence/
  │   ├── Repository/DoctrineUserRepository.php (Event-sourced)
  │   └── Projection/UserReadModel.php, UserFinder.php
  └── Integration/Event/ (Integration Events for other modules)
```

## Testing

```bash
# Unit tests
vendor/bin/phpunit tests/Unit/Domain/User/

# Functional tests
vendor/bin/phpunit tests/Functional/Api/User/

# Integration tests
vendor/bin/phpunit tests/Modules/User/Adapters/Persistence/Repository/DoctrineUserRepositoryIntegrationTest.php
```

## API Endpoints

- `POST /api/users` - Create new user
- `GET /api/users/{id}` - Get user by ID
- `PATCH /api/users/{id}/email` - Change user email
- `POST /api/users/{id}/approve` - Approve user (admin only)

# Symfony Event Sourcing Starter - Modular Monolith

A complete, production-ready Symfony 7.1 starter kit demonstrating:
- **Modular Monolith Architecture** - 5 bounded contexts with physical isolation
- **Domain-Driven Design (DDD)** - Strategic and tactical patterns
- **Clean Architecture** - Domain → Application → Adapters per module
- **Event Sourcing** - Event Store as single source of truth
- **CQRS (Command Query Responsibility Segregation)** - Read models optimized for queries
- **Event-Driven Communication** - Modules communicate via async events (RabbitMQ)

## Quick Start

```bash
# Clone the repository
git clone <your-repo-url>
cd symfony-eventsource-starter

# Bootstrap everything (builds, installs, configures)
make reset
```

The `make reset` command will:
1. Copy `docker-compose.override.yml.dist` if needed
2. Start all services (PostgreSQL, RabbitMQ, Mailpit, Traefik, etc.)
3. Install dependencies and run migrations
4. Configure `/etc/hosts` if you confirm

**The application is now running at http://eventsource.localhost**

See **[ARCHITECTURE.md](ARCHITECTURE.md)** for architecture decisions and the **[Makefile](Makefile)** for all available commands.

## Features

### Architecture
✅ **Modular Monolith** - 2 demonstration modules (User + Notification) with complete vertical slices
✅ **Schema-per-Module** - PostgreSQL with separate schemas for physical isolation
✅ **Event-Driven** - Async cross-module communication via RabbitMQ
✅ **Event Sourcing** - Complete audit trail, time travel, event replay
✅ **CQRS** - Separate read models (projections) optimized for queries  

### Infrastructure
✅ Fully dockerized (Docker + docker-compose)  
✅ Complete Makefile for easy development  
✅ One command setup: `make reset`  
✅ PostgreSQL 16 with Event Store + module schemas  
✅ RabbitMQ for async messaging between modules  
✅ Mailpit for email testing  
✅ Traefik for local domain routing  

### Quality & Monitoring
✅ PHPStan (level 8) + PHP-CS-Fixer  
✅ PHPUnit with modular test structure (168 tests)  
✅ Prometheus & Grafana monitoring  
✅ Structured logging ready for production  

## Tech Stack

- PHP 8.4
- Symfony 7.1
- PostgreSQL 16
- RabbitMQ 3
- Doctrine ORM
- Symfony Messenger

## Common Commands

```bash
# Development
make start          # Start all containers
make stop           # Stop containers
make php_sh         # Enter PHP container

# Database
make db             # Reset database and run migrations
make db-diff        # Generate new migration
make db-migr        # Run migrations

# Messenger
make start-worker   # Start event and command consumers
make stop-worker    # Stop consumers

# Testing & Quality
make test           # Run all tests
make qa             # Run quality checks (PHPStan + CS-Fixer)
make cs-fix         # Fix code style

# Complete reset
make reset          # Kill everything and start fresh
```

## Services Access

With Traefik (default):

| Service                 | URL                                     | Credentials                                         |
|-------------------------|-----------------------------------------|-----------------------------------------------------|
| **Application**         | http://eventsource.localhost            | -                                                   |
| **RabbitMQ Management** | http://rabbitmq.eventsource.localhost   | guest / guest                                       |
| **Mailpit UI**          | http://mailpit.eventsource.localhost    | -                                                   |
| **Grafana**             | http://grafana.eventsource.localhost    | admin / admin                                       |
| **Prometheus**          | http://prometheus.eventsource.localhost | -                                                   |
| **PostgreSQL**          | localhost:5432                          | user: `symfony`, pass: `symfony`, db: `symfony_ddd` |
| **Traefik Dashboard**   | http://localhost:8080                   | -                                                   |

## API Example

### Create User
```bash
curl -X POST http://eventsource.localhost/api/users \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "name": "John Doe"}'
```

### Get User
```bash
curl http://eventsource.localhost/api/users/{userId}
```

## Architecture Overview

**Modular Monolith** with 2 demonstration modules showing complete bounded context implementation.

**Implemented Modules:**
- **User** - User management, authentication, approval workflow
- **Notification** - Email notifications using Transactional Outbox pattern

**Key Principles:**
- Each module is a complete vertical slice (Domain → Application → Adapters)
- Modules communicate ONLY via domain events through RabbitMQ (zero direct dependencies)
- Each module has its own PostgreSQL schema (user_module, notification_module)
- Shared Event Store (`public.event_store`) used by ALL modules
- Shared infrastructure (Event Store, buses) lives in `SharedKernel/`

```
src/
├── Modules/
│   ├── User/               # User management (complete example)
│   │   ├── Domain/         # Business Rules
│   │   ├── Application/    # Use Cases, Handlers
│   │   └── Adapters/       # Controllers, Repositories, Projections
│   │
│   └── Notification/       # Email notifications (complete example)
│       ├── Domain/
│       ├── Application/
│       └── Adapters/
│
└── SharedKernel/           # Shared Adapters (Event Store, Buses, Monitoring)
```

## Documentation

### Core Documentation

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Architecture Decision Records (ADR)
- **[Makefile](Makefile)** - All available commands with descriptions

### Complete Guides

Detailed guides are available in the project covering:
- **Modular Architecture** - Module structure, boundaries, creation, and communication
- **Event-Driven Communication** - Cross-module event patterns
- **Database Schemas** - Schema-per-module PostgreSQL setup
- **Testing** - Unit, integration, and functional tests
- **Recipes** - Practical code examples
- **Monitoring** - Prometheus & Grafana setup
- **Troubleshooting** - Common issues and solutions

Access these guides through your development environment or ask your AI assistant for specific topics.

## Git Commits

This project follows **Conventional Commits** specification with GitLab integration support.

Format: `type(scope)[#issue]: description`

Example: `feat(user)[#123]: add email verification system`

## Requirements

- Docker
- Docker Compose
- Make

That's all you need! No local PHP, Composer, database, or reverse proxy installation required.

## License

MIT

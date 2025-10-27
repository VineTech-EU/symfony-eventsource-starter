# User Module - Data Fixtures

Development fixtures for the User module.

## Usage

```bash
# Load all fixtures (from all modules)
make fixtures

# Or directly with Symfony console
bin/console doctrine:fixtures:load --no-interaction
```

## Fixtures Included

### UserFixtures

Creates sample users for development:
- 5 pending users (awaiting approval)
- 10 approved users
- 1 test user with known credentials:
  - ID: `00000000-0000-0000-0000-000000000001`
  - Email: `test@example.com`
  - Name: `Test User`
  - Status: `approved`

## Architecture

Fixtures are located **inside each module** (not in global `src/DataFixtures/`):

```
src/Modules/User/
└── Adapters/
    └── DataFixtures/        ← Module-specific fixtures
        └── UserFixtures.php
```

**Benefits:**
- ✅ Module autonomy - Each module manages its own test data
- ✅ Extraction ready - Copy module folder = copy everything (code + tests + fixtures)
- ✅ Clean Architecture - Fixtures = Adapter (output port to database)

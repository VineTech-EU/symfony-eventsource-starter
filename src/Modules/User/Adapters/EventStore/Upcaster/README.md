# Event Upcasters - User Module

## Purpose

Upcasters transform **old event versions** to **new schemas** during event replay, enabling Event Sourcing schema evolution without data migration.

## When to Use Upcasters

✅ **Use upcasters when:**
- Adding a new required field to an event
- Renaming a field in an event
- Changing field data type (string → int, etc.)
- Splitting one field into multiple fields
- Normalizing data format (email lowercase, date formats, etc.)

❌ **Don't use upcasters for:**
- Fixing bugs in domain logic (fix the aggregate instead)
- Changing business rules (create new events instead)
- Deleting sensitive data (use GDPR-compliant event encryption/deletion)

## Lifecycle

```
Event Store (V1) → Upcaster Chain → Domain Event (V2) → Aggregate
```

1. **Event Store** contains events at various versions (V1, V2, V3...)
2. **EventSerializer** checks if upcast is needed
3. **EventUpcasterChain** applies all matching upcasters sequentially
4. **Aggregate** receives events at current version

## File Naming Convention

```
{EventName}V{OldVersion}ToV{NewVersion}Upcaster.php
```

Examples:
- `UserCreatedV1ToV2Upcaster.php` - Upgrades V1 → V2
- `UserCreatedV2ToV3Upcaster.php` - Upgrades V2 → V3
- `UserEmailChangedV1ToV2Upcaster.php` - Different event

## Example: Adding a Field

**Before (V1):**
```json
{
  "userId": "123",
  "email": "user@example.com",
  "name": "John Doe"
}
```

**After (V2):**
```json
{
  "userId": "123",
  "email": "user@example.com",
  "name": "John Doe",
  "emailVerified": false  // ← New required field
}
```

**Upcaster:**
```php
public function supports(): string
{
    return 'user.created';
}

public function fromVersion(): int
{
    return 1; // Upgrades FROM version 1
}

public function toVersion(): int
{
    return 2; // Upgrades TO version 2
}

public function upcast(array $eventData): array
{
    $eventData['emailVerified'] = false; // Sensible default
    return $eventData;
}
```

## Activation Process

1. **Create upcaster** in this directory
2. **Register in services.yaml**:
   ```yaml
   App\Modules\User\Adapters\EventStore\Upcaster\UserCreatedV1ToV2Upcaster:
       tags: ['app.event_upcaster']
   ```
3. **Test with production backup**:
   ```bash
   # Load production snapshot
   bin/console app:projections:rebuild --dry-run
   ```
4. **Deploy and rebuild projections**:
   ```bash
   bin/console app:projections:rebuild
   ```

## Testing Strategy

### Unit Test
Test the upcaster in isolation:
```php
public function testUpcastAddsEmailVerifiedField(): void
{
    $upcaster = new UserCreatedV1ToV2Upcaster();

    self::assertSame('user.created', $upcaster->supports());
    self::assertSame(1, $upcaster->fromVersion());
    self::assertSame(2, $upcaster->toVersion());

    $result = $upcaster->upcast([
        'userId' => '123',
        'email' => 'test@example.com',
    ]);

    self::assertFalse($result['emailVerified']);
    self::assertSame('test@example.com', $result['email']);
}
```

### Integration Test
Test with real Event Store:
```php
public function testReplayWithUpcasterRebuildsProjection(): void
{
    // 1. Insert V1 event in event store
    // 2. Rebuild projection
    // 3. Verify projection has V2 data
}
```

## Best Practices

### ✅ DO
- Keep upcasters **simple and focused**
- Add **sensible defaults** for new fields
- **Document the reason** for the change
- **Version control** upcasters (never delete old ones)
- **Test thoroughly** before production deploy

### ❌ DON'T
- Modify existing upcasters (create new V2→V3 instead)
- Put business logic in upcasters (they're data transforms only)
- Skip testing with real production data
- Delete old upcasters (needed for historical replay)
- Use upcasters to fix aggregate bugs

## Common Patterns

### 1. Add Field with Default
```php
$eventData['newField'] = 'default_value';
```

### 2. Rename Field
```php
$eventData['newName'] = $eventData['oldName'];
unset($eventData['oldName']);
```

### 3. Transform Field Type
```php
$eventData['amount'] = (int) $eventData['amount']; // string → int
```

### 4. Split Field
```php
[$firstName, $lastName] = explode(' ', $eventData['fullName'], 2);
$eventData['firstName'] = $firstName;
$eventData['lastName'] = $lastName;
unset($eventData['fullName']);
```

### 5. Normalize Data
```php
$eventData['email'] = strtolower($eventData['email']);
$eventData['createdAt'] = $this->normalizeDate($eventData['createdAt']);
```

## Troubleshooting

### Projection Rebuild Fails
- Check upcaster `supports()` returns correct event type
- Verify event type name matches exactly (e.g., 'user.created')
- Enable debug logging in EventSerializer

### Infinite Loop
- Ensure `fromVersion()` and `toVersion()` are different
- Never upcast to same version

### Performance Issues
- Upcasters run on EVERY event load
- Keep transforms lightweight (no DB queries!)
- Consider batch migration for large datasets

## Resources

- [Event Versioning and Conversion (Greg Young)](https://leanpub.com/esversioning)
- [Versioning in an Event Sourced System](https://leanpub.com/esversioning)
- [Event Store Upcasters Documentation](https://buildplease.com/pages/fpc-6/)

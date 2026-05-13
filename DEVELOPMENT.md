# Development Guide

This document is for developers who want to understand the package architecture, contribute, or maintain the codebase.

## Table of Contents

- [Package Structure](#package-structure)
- [Architecture](#architecture)
- [Code Quality Standards](#code-quality-standards)
- [Testing](#testing)
- [Development Setup](#development-setup)
- [Contributing](#contributing)

## Package Structure

```
src/
├── Base/              # Base classes for easy integration
│   ├── GatekeeperResource.php
│   ├── GatekeeperPage.php
│   ├── GatekeeperWidget.php
│   ├── GatekeeperAuthenticatable.php
│   └── GatekeeperApiResource.php
├── Commands/          # Artisan commands
│   ├── InstallCommand.php
│   ├── SyncPermissionsCommand.php
│   ├── DeletePermissionsCommand.php
│   ├── ClearCacheCommand.php
│   └── GeneratePoliciesCommand.php
├── Concerns/          # Traits for resources, controllers, etc.
│   ├── HasResourcePermissions.php
│   ├── HasFieldPermissions.php
│   ├── HasColumnPermissions.php
│   ├── HasActionPermissions.php
│   ├── HasRelationPermissions.php
│   ├── HasPagePermissions.php
│   ├── HasWidgetPermissions.php
│   ├── HasApiPermissions.php
│   └── InteractsWithGatekeeperCache.php
├── Contracts/         # Interfaces for services
│   ├── PermissionRegistrarInterface.php
│   └── GatekeeperInterface.php
├── Http/              # Middleware
│   ├── GatekeeperApiMiddleware.php
│   └── GatekeeperResourceMiddleware.php
├── Models/            # Eloquent models
│   ├── Permission.php
│   └── Role.php
├── Resources/         # Filament resources
│   ├── RoleResource.php
│   └── PermissionResource.php
├── Services/          # Core business logic
│   ├── Gatekeeper.php
│   ├── PermissionRegistrar.php
│   ├── PermissionCache.php
│   └── PolicyGenerator.php
└── Support/           # Discovery classes and utilities
    ├── Discovery/
    │   ├── ModelDiscovery.php
    │   ├── ResourceDiscovery.php
    │   ├── PageDiscovery.php
    │   ├── WidgetDiscovery.php
    │   ├── FieldDiscovery.php
    │   └── ColumnDiscovery.php
    └── Traits/
        ├── InteractsWithPathScanning.php
        ├── InteractsWithModuleDiscovery.php
        └── InteractsWithExclusions.php
```

## Architecture

### Design Patterns

- **Service Pattern** - Core business logic in service classes (`Gatekeeper`, `PermissionRegistrar`)
- **Repository Pattern** - Data access through Eloquent models
- **Factory Pattern** - Model factories for testing
- **Strategy Pattern** - Multiple discovery sources for fields/columns

### SOLID Principles

- **Single Responsibility** - Each class has one clear purpose
- **Open/Closed** - Extensible through interfaces and traits
- **Liskov Substitution** - Base classes can be replaced with implementations
- **Interface Segregation** - Focused interfaces (`PermissionRegistrarInterface`, `GatekeeperInterface`)
- **Dependency Inversion** - Depend on abstractions (interfaces) not concretions

### Key Components

#### Gatekeeper
Core service for permission checking. Handles:
- Permission checks with OR logic support
- Guard management
- Super admin bypass
- Permission matrix caching

#### PermissionRegistrar
Service for discovering and syncing permissions:
- Discovers resources, pages, widgets, models
- Syncs permissions to database
- Handles field/column/action/relation permissions

#### Discovery Services
Auto-discovery of fields and columns:
- `FieldDiscovery` - Discovers form fields
- `ColumnDiscovery` - Discovers table columns
- Multiple sources: config, fillable, database, resource

## Code Quality Standards

### PHPDoc Requirements

All classes, methods, and properties must have comprehensive PHPDoc blocks:

```php
/**
 * Service for managing permission synchronization.
 *
 * This service handles the discovery and synchronization of permissions
 * for resources, pages, widgets, models, fields, columns, actions, and relations.
 *
 * @package LaraArabDev\FilamentGatekeeper\Services
 */
class PermissionRegistrar implements PermissionRegistrarInterface
{
    /**
     * Synchronize all permissions.
     *
     * Discovers and creates permissions for all entity types including
     * models, resources, pages, widgets, fields, columns, actions, and relations.
     *
     * @return array<string, array<string>> Sync operation log
     */
    public function syncAll(): array
    {
        // ...
    }
}
```

### Type Hints

- All method parameters must have type hints
- All methods must have return type declarations
- Use union types where appropriate (`string|int`, `?Authenticatable`)
- Use array type hints with generics where possible (`array<string>`)

### Code Style

- **No Inline Comments** - Code should be self-documenting
- **Early Returns** - Use guard clauses for better readability
- **Simplified Conditionals** - Avoid nested if statements
- **Consistent Naming** - Follow Laravel conventions (camelCase for methods, PascalCase for classes)

### File Organization

- Constants at the top
- Properties next
- Constructor
- Static methods
- Public methods
- Protected methods
- Private methods

## Testing

### Test Structure

```
tests/
├── Feature/           # Integration tests
│   ├── Commands/
│   ├── Middleware/
│   └── PermissionFlowTest.php
├── Unit/              # Unit tests
│   ├── Models/
│   ├── Services/
│   ├── Concerns/
│   └── Support/
└── Pest.php          # Main test configuration
```

### Test Groups

Tests are organized into groups using `Pest.php` files:

- `feature` - Integration tests
- `unit` - Unit tests
- `commands` - Command tests
- `middleware` - Middleware tests
- `models` - Model tests
- `services` - Service tests
- `concerns` - Trait tests
- `discovery` - Discovery service tests

### Running Tests

```bash
# Run all tests
vendor/bin/pest

# Run by group
vendor/bin/pest --group=feature
vendor/bin/pest --group=unit

# Run with coverage
vendor/bin/pest --coverage
```

### Test Factories

The package includes factories for all models:

- `PermissionFactory` - Generate test permissions
- `RoleFactory` - Generate test roles
- `UserFactory` - Generate test users

## Development Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js (for frontend assets if needed)

### Setup Steps

```bash
# Clone the repository
git clone https://github.com/laraarabdev/filament-gatekeeper.git
cd filament-gatekeeper

# Install dependencies
composer install

# Run tests
composer test

# Run static analysis
composer analyse

# Check code style
composer cs-check

# Fix code style
composer cs-fix
```

### Composer Scripts

- `composer test` - Run all tests
- `composer test-coverage` - Run tests with coverage
- `composer format` - Format code with Pint
- `composer analyse` - Run PHPStan static analysis

## Contributing

### Code Standards

- Follow PSR-12 coding standards
- Write comprehensive PHPDoc blocks
- Include type hints for all parameters and return types
- Write tests for all new features
- Keep methods focused and single-purpose
- Extract common logic into traits or services
- No inline comments - code should be self-documenting

### Pull Request Process

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write/update tests
5. Ensure all tests pass
6. Run static analysis
7. Submit a pull request

### Testing Requirements

- All new features must include tests
- Tests should cover both success and failure cases
- Use factories instead of direct model creation
- Group related tests using Pest groups

### Documentation

- Update README.md for user-facing changes
- Update DEVELOPMENT.md for architectural changes
- Add PHPDoc for all new classes and methods
- Include usage examples in code comments if needed

## Maintenance

### Regular Tasks

- Update dependencies regularly
- Review and update tests
- Check for deprecated PHP/Laravel features
- Update documentation as needed

### Version Compatibility

The package supports:
- PHP 8.2+
- Laravel 10.x, 11.x, 12.x
- Filament 3.x
- Spatie Laravel Permission 6.x

### Breaking Changes

When making breaking changes:
1. Update version number appropriately
2. Document changes in CHANGELOG.md
3. Update README.md if needed
4. Provide migration guide if necessary


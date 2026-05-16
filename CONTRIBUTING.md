# Contributing to FilamentGatekeeper

Thank you for your interest in contributing! We welcome bug reports, feature requests, and pull requests.

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Git

### Local Setup

```bash
git clone https://github.com/laraarabdev/filament-gatekeeper.git
cd filament-gatekeeper
composer install
```

### Running Tests

```bash
composer test
```

### Code Style

We use [Laravel Pint](https://laravel.com/docs/pint) for code formatting:

```bash
composer format
```

### Static Analysis

We use [PHPStan](https://phpstan.org/) at level 5:

```bash
composer analyse
```

All PRs must pass both `composer test` and `composer analyse` before merging.

---

## How to Contribute

### Reporting Bugs

Use the **Bug Report** issue template on GitHub. Please include:
- PHP, Laravel, Filament, and package versions
- Steps to reproduce
- Expected vs. actual behavior
- Any relevant error messages or stack traces

### Requesting Features

Use the **Feature Request** issue template. Describe the problem you're solving and your proposed solution.

### Submitting a Pull Request

1. **Fork** the repository and create your branch from `main`:
   ```bash
   git checkout -b fix/your-bug-description
   # or
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**, following the project's coding conventions.

3. **Add or update tests** covering your changes. We aim for high test coverage.

4. **Run the full suite** before pushing:
   ```bash
   composer format
   composer analyse
   composer test
   ```

5. **Open a Pull Request** against the `main` branch using the provided PR template.

### Branch Naming

| Type | Pattern | Example |
|------|---------|---------|
| Bug fix | `fix/short-description` | `fix/role-sync-guard` |
| New feature | `feature/short-description` | `feature/team-scoped-roles` |
| Docs | `docs/short-description` | `docs/multi-guard-guide` |
| Refactor | `refactor/short-description` | `refactor/permission-cache` |

---

## Code Guidelines

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards (enforced by Pint).
- Keep methods small and focused.
- Write self-documenting code; add comments only where logic is non-obvious.
- New public API methods should have docblocks.
- Avoid breaking changes without a discussion in an issue first.

---

## Security Vulnerabilities

**Do not open a public issue for security vulnerabilities.**
Please follow our [Security Policy](SECURITY.md) and report privately.

---

## Code of Conduct

Be respectful and constructive. We are here to build great software together.

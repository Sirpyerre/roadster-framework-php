# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Roadster is a general-purpose PHP framework for APIs and microservices: Artisan-style scaffolding for quick CRUD apps, and long-lived runtimes (RoadRunner today, optional Swoole later) for high concurrency. It uses Mezzio (PSR-7/PSR-15), FastRoute for routing, and Symfony Console for CLI commands.

**Status**: MVP in development (v0.1). The planned work is broken into small increments in `ROADMAP.md`; each one is an OpenSpec change under `openspec/`.

## Build and Development Commands

```bash
# Install dependencies
composer install

# Initialize project (creates .env, verifies RoadRunner)
./bin/roadster init

# Start development server
./bin/roadster serve
./bin/roadster serve --port=8080 --workers=4

# Database migrations (Doctrine Migrations, table `migrations`)
./bin/roadster migrate            # run pending migrations
./bin/roadster migrate:rollback   # revert the last executed migration
./bin/roadster migrate:status

# Server status / reload workers / stop
./bin/roadster serve:status
./bin/roadster serve:reload
./bin/roadster serve:stop

# Run all checks (static analysis + tests)
composer check

# Run tests only
composer test
./vendor/bin/pest                    # All tests
./vendor/bin/pest tests/Feature/     # Feature tests only
./vendor/bin/pest tests/Unit/        # Unit tests only
./vendor/bin/pest --filter=HealthCheck  # Single test by name

# Run static analysis only
composer analyse
./vendor/bin/phpstan analyse

# Test coverage
composer test:coverage
```

## Code Generation Commands

```bash
# Generate request handler
./bin/roadster make:handler CreateUser
./bin/roadster make:handler User/Create  # With subdirectory

# Generate service class
./bin/roadster make:service UserService

# Generate middleware
./bin/roadster make:middleware Auth

# Generate repository interface + JSON implementation + container binding
./bin/roadster make:repository Invoice

# Generate a database migration (database/migrations/Version<timestamp><Name>.php)
./bin/roadster make:migration CreateProducts

# Generate complete CRUD (5 handlers + service + repository + routes + binding)
./bin/roadster scaffold:crud Product

# List all routes
./bin/roadster routes:list
```

## Architecture

### Request Lifecycle
```
HTTP Request → RoadRunner Worker → Mezzio Pipeline → Middleware Stack →
FastRoute → Handler → Service → Repository → JSON Response
```

### Directory Structure
- `src/App/` - Framework core (middleware, container, helpers)
- `src/Console/` - CLI application and commands
- `src/Domain/` - Business logic (services, repositories)
- `src/Infrastructure/` - Concrete implementations
- `src/Web/Rest/` - HTTP handlers (controllers)
- `config/` - DI container, routes, RoadRunner config
- `templates/` - Stub files for code generators (*.php.stub)
- `public/index.php` - PSR-7 entry point for RoadRunner

### Key Patterns
- **Handlers** implement `Psr\Http\Server\RequestHandlerInterface`
- **Services** contain business logic, injected into handlers
- **Repositories** handle data access, injected into services
- DI configured in `config/container.php` using Laminas ServiceManager. Concrete classes with typed constructor dependencies are autowired (`ReflectionBasedAbstractFactory`); no factory needed
- Interface bindings go in `config/dependencies/*.php` (`aliases`/`factories` only), loaded automatically; generators create these files
- Core routes in `config/routes.php`; per-resource routes in `config/routes/*.php`
- Database: Doctrine DBAL, parameters built in `config/database.php` from `DB_*` env vars (SQLite `storage/database.sqlite` by default; `mysql` and `pgsql` supported). The container provides a shared lazy `Doctrine\DBAL\Connection` (`config/dependencies/database.php`); repositories are still JSON-backed
- Migrations: `config/migrations.php`, classes in `database/migrations/` (namespace `Database\Migrations`, loaded by Doctrine's finder, not Composer). The commands get the DB connection lazily, so other CLI commands never need valid `DB_*`. MySQL has no transactional DDL
- `/health` runs `SELECT 1` and returns 503 (`status: unhealthy`) if the database fails, never exposing connection details
- Use `jsonResponse()` helper for JSON responses

## Code Style

- PHP 8.3+ with strict types (`declare(strict_types=1);`)
- PSR-12 compliant
- All classes require return type hints and property type hints
- Handlers should be `final class`

## Commit Message Format

```
<type>(<scope>): <description>

Types: feat, fix, docs, style, refactor, test, chore
Examples:
  feat(cli): add scaffold:crud command
  fix(routing): handle route parameter casting
```

# CAMR AI Guidelines

This directory contains custom AI guidelines for the CAMR (Centralized Automated Meter Reading) application. These guidelines help AI agents understand the domain, architecture, and conventions specific to this project.

## Overview

CAMR is a Laravel 12 + Vue 3 + Inertia.js application for monitoring and managing automated meter reading infrastructure. The system provides a hierarchical structure for organizing sites, buildings, locations, gateways, and meters.

## Quick Reference

### Core Hierarchy

```
Site (campus/facility)
  └── Building (structure)
       └── Location (specific area)
            └── Gateway (communication device)
                 └── Meter (measuring device)
```

### Technology Stack

**Backend:**
- Laravel 12 (PHP 8.2+)
- Laravel Fortify (authentication with 2FA)
- SQLite/MySQL/PostgreSQL
- Pest v4 (testing)
- Laravel Pint (code style)

**Frontend:**
- Vue 3 with Composition API
- TypeScript
- Inertia.js v2
- Tailwind CSS v4
- shadcn-vue (Reka UI)
- Laravel Wayfinder (type-safe routing)

**Development:**
- Vite (bundler)
- Laravel Herd (local development)
- Concurrently (run multiple services)

## Guidelines

### [Domain Knowledge](camr/domain.md)

Core business concepts and data model:
- **Hierarchical Infrastructure Model** - Site → Building → Location → Gateway → Meter
- **Core Entities** - Site, Building, Location, Gateway, Meter, Company, Division
- **Site Context Filtering** - Persistent filtering across pages
- **Status Tracking** - Online/offline status based on last_log_update (24-hour threshold)
- **Business Rules** - Validation, hierarchy integrity, code uniqueness
- **Common Workflows** - Adding meters, monitoring status, importing data, generating reports

### [Features](camr/features.md)

Key application features:
- **Data Import System** - SQL/CSV uploads with validation and background processing
- **Site Context Filtering** - Selecting and persisting site selection
- **Analytics and Dashboard** - Quick stats, status indicators, reports
- **Configuration File Management** - Upload/download gateway/meter configs
- **Table Features** - Column customization, search, filter, sort, export
- **User Management** - Roles, permissions, two-factor authentication

### [Frontend Patterns](camr/frontend.md)

Frontend architecture and conventions:
- **Project Structure** - Components, layouts, pages, composables, types
- **Path Aliases** - @/, @/components, @/layouts, @/ui, etc.
- **Inertia.js Patterns** - Page components, navigation, form handling
- **Laravel Wayfinder** - Type-safe routing, form binding
- **Layouts** - AppLayout, AuthLayout
- **UI Components** - shadcn-vue usage patterns
- **Tailwind CSS v4** - Utility classes, dark mode, responsive design

### [Testing](camr/testing.md)

Testing conventions and patterns:
- **Pest v4** - Modern testing framework with browser testing
- **Test Organization** - Feature, Unit, Browser tests
- **Factory Usage** - Creating test data
- **Common Scenarios** - CRUD, site filtering, status tracking, data imports
- **Browser Testing** - Testing complex interactions
- **Best Practices** - Descriptive names, arrange-act-assert, test isolation

### [Architecture](camr/architecture.md)

Application architecture and patterns:
- **Backend Structure** - Controllers, models, form requests, routes
- **Controllers** - RESTful conventions, thin controllers
- **Models** - Relationships, attributes, casts, fillable
- **Eloquent Patterns** - Eager loading, query scopes, filtering
- **Jobs and Queues** - Background processing
- **Frontend Structure** - Pages, layouts, components, composables
- **Database** - Migrations, factories
- **Authentication** - Laravel Fortify, policies
- **Best Practices** - Code organization, conventions

## Common Patterns

### Creating a New Entity

1. Create migration: `php artisan make:migration create_entity_table`
2. Create model: `php artisan make:model Entity`
3. Create factory: `php artisan make:factory EntityFactory`
4. Create controller: `php artisan make:controller EntityController --resource`
5. Create form requests: `php artisan make:request EntityStoreRequest`
6. Define routes in `routes/web.php`
7. Create Vue page: `resources/js/pages/entity/Index.vue`
8. Write tests: `php artisan make:test EntityTest --pest`

### Site Context Filtering Implementation

**Backend (Controller):**
```php
$query->when(session('selected_site_id'), fn($q, $siteId) =>
    $q->where('site_id', $siteId)
);
```

**Frontend (Page):**
```vue
<script setup lang="ts">
interface Props {
  items: Array<Item>
  selectedSiteId?: number
}

const props = defineProps<Props>()
</script>
```

### Status Tracking Pattern

**Model Attribute:**
```php
protected function status(): Attribute
{
    return Attribute::make(
        get: fn () => $this->last_log_update && 
            $this->last_log_update >= now()->subDay()
    );
}
```

**Frontend Display:**
```vue
<Badge :variant="item.status ? 'success' : 'destructive'">
  {{ item.status ? 'Online' : 'Offline' }}
</Badge>
```

## Development Workflow

### Starting Development

```bash
composer dev  # Starts all services (PHP server, queue, logs, Vite)
```

### Running Tests

```bash
composer test                                    # All tests
php artisan test tests/Feature/SiteTest.php     # Specific file
php artisan test --filter="it creates a site"   # Specific test
```

### Code Quality

```bash
./vendor/bin/pint      # PHP code style
npm run lint           # TypeScript/Vue linting
npm run format         # Prettier formatting
```

### Database

```bash
php artisan migrate              # Run migrations
php artisan migrate:fresh --seed # Fresh database with seed data
php artisan tinker               # Interactive REPL
```

## Key Files

- **WARP.md** - Main development guide (in project root)
- **README.md** - User-facing documentation (in project root)
- **boost.json** - Laravel Boost configuration
- **CLAUDE.md** - Generated AI guidelines (auto-generated, gitignored)
- **composer.json** - PHP dependencies and scripts
- **package.json** - Node dependencies and scripts
- **vite.config.ts** - Vite configuration
- **tsconfig.json** - TypeScript configuration

## Resources

- Laravel Documentation: https://laravel.com/docs
- Inertia.js Documentation: https://inertiajs.com
- Vue 3 Documentation: https://vuejs.org
- Tailwind CSS v4: https://tailwindcss.com
- Pest v4 Documentation: https://pestphp.com
- shadcn-vue: https://www.shadcn-vue.com

## Notes for AI Agents

When working with this codebase:

1. **Always check existing patterns** - Look at similar components/controllers before creating new ones
2. **Follow the hierarchy** - Understand the Site → Building → Location → Gateway → Meter relationships
3. **Respect site context filtering** - Most entities filter by selected site
4. **Status is computed** - Never store status, always compute from last_log_update
5. **Use factories in tests** - Always use factories, never create records manually
6. **Type everything** - Backend uses PHP type hints, frontend uses TypeScript
7. **Write tests first** - Follow TDD principles
8. **Keep controllers thin** - Business logic goes in Actions or Services
9. **Use Form Requests** - Never validate inline in controllers
10. **Follow existing conventions** - Check sibling files for patterns

## Getting Help

- Use the `search-docs` MCP tool for Laravel ecosystem documentation
- Use the `tinker` MCP tool to test PHP code
- Use the `database-query` MCP tool to inspect database
- Use the `list-artisan-commands` MCP tool to see available commands
- Check existing code in similar features for patterns

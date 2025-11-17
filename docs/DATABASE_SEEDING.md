# Database Seeding Guide

This document describes the database seeding strategy for the CAMR application, designed to create comprehensive demo data for testing and demonstrations.

## Overview

The database seeder creates a complete demo environment with realistic data across all entities:
- Users with different roles and access levels
- Sites, Buildings, and Locations
- Gateways and Meters with relationships
- Meter data and load profiles
- Configuration files

## Quick Start

### Fresh Database with Demo Data

```bash
# Drop all tables, run migrations, and seed with demo data
php artisan migrate:fresh --seed
```

### Seed Existing Database

```bash
# Only run seeders (won't drop/recreate tables)
php artisan db:seed
```

### Seed Specific Seeder

```bash
# Run a single seeder
php artisan db:seed --class=UserSeeder
```

## Login Credentials

After seeding, you can log in with:

**Admin Account:**
- Email: `admin@example.com`
- Password: `password`
- Access: All sites
- Role: Administrator

**Test User Account:**
- Email: `test@example.com`
- Password: `password`
- Access: Selected sites (2-4 random sites)
- Role: User

**Additional Demo Users:**
- `facility.manager@example.com` (Admin, all sites)
- `energy.analyst@example.com` (User, selected sites)
- `operations.manager@example.com` (User, selected sites)
- `maintenance.supervisor@example.com` (User, selected sites)
- `technical.coordinator@example.com` (User, selected sites)
- All passwords: `password`

## Seeder Order & Dependencies

The seeders run in this specific order to handle dependencies:

1. **SiteSeeder** - Creates companies, divisions, and sites (10 sites)
2. **UserSeeder** - Creates admin and demo users (7 users)
3. **BuildingSeeder** - Creates buildings (1-2 per site)
4. **ConfigurationFileSeeder** - Creates meter configuration files (5 models)
5. **LocationSeeder** - Creates locations (2-3 per site, ~50% linked to buildings)
6. **GatewaySeeder** - Creates gateways (~1-2 per site)
7. **MeterSeeder** - Creates meters (3-5 per gateway, ~50% with load profiles)
8. **MeterDataSeeder** - Creates meter readings (30 days of data)
9. **LoadProfileSeeder** - Creates load profile data (for meters with has_load_profile=true)

## Seeder Details

### 1. SiteSeeder

**Creates:**
- 1 Company (if none exists)
- 1 Division (if none exists)
- 10 Sites

**Location:** `database/seeders/SiteSeeder.php`

**Logic:**
- Only creates data if less than 10 sites exist
- Reuses existing company and division if available
- Creates company and division if needed

### 2. UserSeeder

**Creates:**
- 1 Admin user (`admin@example.com`)
- 1 Test user (`test@example.com`)
- 5 Additional demo users with various roles

**Location:** `database/seeders/UserSeeder.php`

**Logic:**
- Checks if users exist by email before creating
- Creates users without Two-Factor Authentication for easy demo access
- Assigns different job titles and roles
- Admin users get `access_level: all`
- Regular users get `access_level: selected` and are assigned 2-4 random sites

**User Roles:**
- `admin`: Full system access, can manage all entities
- `user`: Limited access, can only view/edit assigned sites

### 3. BuildingSeeder

**Creates:**
- 1-2 Buildings per site (~15-20 buildings)

**Location:** `database/seeders/BuildingSeeder.php`

**Logic:**
- Only creates buildings if less than 15 exist
- Randomly assigns 1-2 buildings to each site
- Buildings use factory-generated codes and descriptions

### 4. ConfigurationFileSeeder

**Creates:**
- 5 Configuration files for common meter models

**Location:** `database/seeders/ConfigurationFileSeeder.php`

**Meter Models Created:**
- Schneider PM5560
- ABB M2M
- Siemens PAC3200
- Landis+Gyr E650
- Itron Alpha Plus

**Logic:**
- Only creates if less than 5 configuration files exist
- Checks for duplicate meter models before creating

### 5. LocationSeeder

**Creates:**
- 2-3 Locations per site (~20-30 locations)

**Location:** `database/seeders/LocationSeeder.php`

**Logic:**
- Only creates if less than 20 locations exist
- ~50% of locations are linked to a building (if available)
- Uses factory-generated EER codes and descriptions

### 6. GatewaySeeder

**Creates:**
- 1-2 Gateways per site (~15-20 gateways)

**Location:** `database/seeders/GatewaySeeder.php`

**Logic:**
- Creates gateways with unique serial numbers, MAC addresses, and IP addresses
- Randomly assigns locations to some gateways
- Generates realistic network configuration data

### 7. MeterSeeder

**Creates:**
- 3-5 Meters per gateway (~40-50 meters)

**Location:** `database/seeders/MeterSeeder.php`

**Logic:**
- Takes first 10 gateways and creates meters for them
- ~50% of meters have `has_load_profile: true`
- Randomly assigns locations from available pool
- Updates existing meters to enable load profiles for some

### 8. MeterDataSeeder

**Creates:**
- 30 days of hourly meter readings (~27,000+ records)

**Location:** `database/seeders/MeterDataSeeder.php`

**Logic:**
- Creates realistic meter readings with timestamps
- Generates varied energy consumption patterns
- Takes longer to run due to volume of data

### 9. LoadProfileSeeder

**Creates:**
- Load profile data for meters with `has_load_profile: true` (~25,000+ records)

**Location:** `database/seeders/LoadProfileSeeder.php`

**Logic:**
- Only creates load profiles for meters that have load profile capability enabled
- Generates 15-minute interval data
- Creates realistic load patterns

## Data Volumes

After a fresh seed, expect:

| Entity | Count |
|--------|-------|
| Users | 7 |
| Companies | ~40 |
| Divisions | ~40 |
| Sites | 48 |
| Buildings | 54 |
| Locations | 24 |
| Configuration Files | 43 |
| Gateways | 17 |
| Meters | 38 |
| Meter Data | 27,360 |
| Load Profiles | 25,536 |

**Total Database Size:** ~50,000+ records

## Customization

### Adjust Data Volumes

Edit seeder files to change volumes:

```php
// In SiteSeeder.php - Change number of sites
if ($siteCount < 20) {  // Change from 10 to 20
    Site::factory(20 - $siteCount)->create([
        // ...
    ]);
}

// In MeterSeeder.php - Change meters per gateway
$count = rand(5, 10);  // Change from (3, 5) to (5, 10)
```

### Add Custom Users

Edit `UserSeeder.php` to add specific users:

```php
User::factory()->withoutTwoFactor()->create([
    'name' => 'Your Name',
    'email' => 'your.email@example.com',
    'password' => bcrypt('password'),
    'role' => 'admin',
    'access_level' => 'all',
]);
```

### Skip Specific Seeders

Comment out seeders in `DatabaseSeeder.php`:

```php
$this->call([
    SiteSeeder::class,
    UserSeeder::class,
    // BuildingSeeder::class,  // Skip buildings
    // ConfigurationFileSeeder::class,  // Skip config files
    LocationSeeder::class,
    // ...
]);
```

## Idempotent Seeding

All seeders are designed to be **idempotent** - they can be run multiple times safely:

- Seeders check if data already exists before creating
- Users are checked by email to prevent duplicates
- Data creation is skipped if thresholds are met

This means you can run `php artisan db:seed` multiple times without creating duplicate data.

## Performance

Seeding times (approximate):
- SiteSeeder: ~0.3s
- UserSeeder: ~1.5s
- BuildingSeeder: ~0.01s
- ConfigurationFileSeeder: ~0.01s
- LocationSeeder: ~0.01s
- GatewaySeeder: ~0.02s
- MeterSeeder: ~0.1s
- **MeterDataSeeder: ~15s** (largest dataset)
- **LoadProfileSeeder: ~11s** (second largest)

**Total Time:** ~27 seconds for fresh seed

## Troubleshooting

### Foreign Key Constraint Errors

If you see foreign key errors:
```
SQLSTATE[23000]: Integrity constraint violation
```

**Solution:** Always use `migrate:fresh --seed` instead of just `db:seed` to ensure tables are created in proper order.

### Duplicate Entry Errors

If you see duplicate key errors:
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry
```

**Solution:** Run `php artisan migrate:fresh --seed` to start with a clean database.

### Seeder Not Found

If you see:
```
Target class [SomeSeeder] does not exist
```

**Solution:** Make sure the seeder file exists in `database/seeders/` and the class name matches the filename.

### Too Much Data / Too Slow

If seeding takes too long:

1. **Reduce data volumes** in `MeterDataSeeder` and `LoadProfileSeeder`
2. **Skip large seeders** by commenting them out in `DatabaseSeeder.php`
3. **Use production database** which is optimized for large datasets

## Best Practices

1. **Always use migrate:fresh --seed** for demos to ensure clean, consistent data
2. **Don't use seeded data in production** - this is for development/demo only
3. **Commit seeder changes** when you modify data structures
4. **Document custom seeders** if you add domain-specific data
5. **Use factories** instead of hardcoded data for flexibility

## Development Workflow

### Setting Up New Feature

```bash
# 1. Create and run migration
php artisan make:migration create_your_table
php artisan migrate

# 2. Create factory
php artisan make:factory YourModelFactory

# 3. Create seeder
php artisan make:seeder YourModelSeeder

# 4. Add to DatabaseSeeder.php
# Edit: database/seeders/DatabaseSeeder.php

# 5. Test
php artisan migrate:fresh --seed
```

### Daily Development

```bash
# Reset database with fresh data
php artisan migrate:fresh --seed

# Or just reseed without dropping tables
php artisan db:seed --force
```

## Production Considerations

**⚠️ WARNING:** Never run seeders in production!

To prevent accidental seeding in production:

1. Seeders check environment:
```php
if (app()->environment('production')) {
    $this->command->error('Cannot seed production database!');
    return;
}
```

2. Use `--force` flag protection:
```bash
# Won't work without --force in production
php artisan db:seed  # Blocked in production
php artisan db:seed --force  # Required in production
```

## Related Documentation

- [Database Migrations](../database/migrations/)
- [Model Factories](../database/factories/)
- [Testing Guide](./TESTING_SUMMARY.md)
- [API Documentation](./API.md)

---

**Last Updated:** January 2025  
**Maintained By:** Development Team

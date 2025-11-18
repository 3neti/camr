# DEC CAMR - Centralized Automated Meter Reading

A Laravel 12 + Vue 3 + Inertia.js application for managing automated meter reading infrastructure across multiple sites, buildings, locations, gateways, and meters.

## 📋 Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Installation](#installation)
- [Configuration](#configuration)
- [User Guide](#user-guide)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)

## 🎯 Overview

DEC CAMR (Centralized Automated Meter Reading) is a comprehensive system for monitoring and managing automated meter reading infrastructure. The application provides a hierarchical view of:

- **Sites** - Physical locations (e.g., campuses, facilities)
- **Buildings** - Structures within sites
- **Locations** - Specific areas within buildings
- **Gateways** - Communication devices that collect meter data
- **Meters** - Individual measuring devices (electric, water, gas, etc.)

The system tracks device status, configuration files, and generates reports for analysis.

## ✨ Features

### Core Functionality

- **Hierarchical Data Management** - Organize infrastructure in a site → building → location → gateway → meter hierarchy
- **Real-time Status Monitoring** - Track online/offline status of gateways and meters
- **Site Context Filtering** - Select a site and automatically filter related data across all pages
- **Configuration Management** - Manage gateway and meter configuration files
- **Reporting** - Generate and view reports on meter readings and device status
- **User Management** - Multi-user support with authentication and authorization

### UI/UX Features

- **Modern Interface** - Built with shadcn-vue components and Tailwind CSS v4
- **Dark Mode Support** - Automatic theme switching
- **Responsive Design** - Works on desktop, tablet, and mobile
- **Customizable Tables** - Configure visible columns, order, and preferences
- **Filter Presets** - Save and reuse common filter combinations
- **Export Capabilities** - Export data to CSV
- **Search & Sort** - Fast search and multi-column sorting

## 🛠 Tech Stack

### Backend
- **Laravel 12** (PHP 8.2+)
- **Laravel Fortify** - Authentication with 2FA support
- **SQLite/MySQL/PostgreSQL** - Database flexibility
- **Laravel Pint** - Code style enforcement

### Frontend
- **Vue 3** with Composition API
- **TypeScript** - Type-safe development
- **Inertia.js** - SPA-like experience without separate API
- **Tailwind CSS v4** - Utility-first styling
- **shadcn-vue (Reka UI)** - Accessible UI components
- **Vite** - Fast build tool with HMR

### Testing & Quality
- **Pest** - Modern PHP testing framework
- **ESLint + Prettier** - Code linting and formatting

## 📦 Installation

### Prerequisites

- PHP 8.2 or higher
- Composer 2.x
- Node.js 18+ and npm
- SQLite/MySQL/PostgreSQL (SQLite included by default)

### Quick Start

```bash
# Clone the repository
git clone https://github.com/3neti/camr.git
cd camr

# Run automated setup
composer setup
```

The `composer setup` command will:
1. Install PHP dependencies (`composer install`)
2. Copy `.env.example` to `.env`
3. Generate application key
4. Run database migrations
5. Install Node.js dependencies (`npm install`)
6. Build frontend assets (`npm run build`)

### Manual Setup

If you prefer manual setup:

```bash
# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Install Node.js dependencies
npm install

# Build assets
npm run build
```

## ⚙️ Configuration

### Environment Configuration

Edit `.env` file with your settings:

```env
APP_NAME="DEC CAMR"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Manila

# Database (SQLite is default)
DB_CONNECTION=sqlite
# For MySQL/PostgreSQL, uncomment and configure:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=camr
# DB_USERNAME=root
# DB_PASSWORD=

# Queue (uses database by default)
QUEUE_CONNECTION=database

# Mail (uses log for development)
MAIL_MAILER=log
```

### Important Configuration Settings

#### 1. Application Timezone

Set your timezone in `config/app.php` or `.env`:

```php
// config/app.php
'timezone' => 'Asia/Manila',
```

Or in `.env`:
```env
APP_TIMEZONE=Asia/Manila
```

#### 2. Table Column Configuration (Optional)

Customize visible columns and their properties for each index table:

```bash
# Copy the example configuration
cp resources/js/config/tableColumns.example.ts resources/js/config/tableColumns.ts

# Edit the file to customize column visibility, order, width, alignment, etc.
```

See `docs/TABLE_COLUMN_CONFIGURATION.md` for detailed instructions.

#### 3. Gateway/Meter Status Threshold

Devices are considered "online" if they reported data within the last 24 hours. To change this threshold, edit:

```php
// app/Models/Gateway.php or app/Models/Meter.php
protected function status(): Attribute
{
    return Attribute::make(
        get: fn () => $this->last_log_update && 
            $this->last_log_update >= now()->subDay() // Change this duration
    );
}
```

## 📖 User Guide

### Getting Started

1. **Login** - Use your credentials to access the system
2. **Dashboard** - Overview of system status and quick stats
3. **Navigation** - Use the sidebar to access different sections

### Managing Sites

**Sites** are the top level of the hierarchy (e.g., a campus or facility).

1. Navigate to **Sites** in the sidebar
2. Click **Add Site** to create a new site
3. Fill in the site details (code, company, division)
4. Click **row** to select a site
5. Use the icon buttons (🏢📍📡⚡) to view related resources

#### Site Context Filtering

When you select a site:
- Click the building icon (🏢) to view buildings for that site
- Click the location icon (📍) to view locations for that site
- Click the gateway icon (📡) to view gateways for that site
- Click the meter icon (⚡) to view meters for that site

The selected site filter persists as you navigate between pages. To clear the filter, select "All Sites" in any filter dropdown.

### Managing Buildings

**Buildings** belong to sites and contain locations.

1. Navigate to **Buildings**
2. Use the **Site** dropdown to filter by site
3. Click **Add Building** to create a new building
4. Assign the building to a site

### Managing Locations

**Locations** are specific areas within buildings where meters are installed.

1. Navigate to **Locations**
2. Filter by **Site** and/or **Building**
3. Click **Add Location** to create a new location
4. Specify the site and optionally the building

### Managing Gateways

**Gateways** are communication devices that collect data from meters.

1. Navigate to **Gateways**
2. Filter by **Site** and/or **Status** (Online/Offline)
3. Click **Add Gateway** to register a new gateway
4. Assign gateway to a site and location
5. Monitor status in real-time (green = online, red = offline)

**Gateway Status:** A gateway is considered online if it has reported data within the last 24 hours.

### Managing Meters

**Meters** are the actual measuring devices (electric, water, gas, etc.).

1. Navigate to **Meters**
2. Filter by **Site**, **Gateway**, and/or **Status**
3. Click **Add Meter** to register a new meter
4. Assign meter to a gateway and location
5. Configure meter type, brand, and customer information

### Configuration Files

Manage configuration files for gateways and meters:

1. Navigate to **Config Files**
2. Upload configuration files
3. Associate files with gateways or meters
4. Download or view configuration history

### Reports

Generate and view reports on meter readings and device status:

1. Navigate to **Reports**
2. Select report type and date range
3. Apply filters (site, gateway, meter)
4. Export reports to CSV

### User Management

Manage system users and permissions:

1. Navigate to **Users**
2. Click **Add User** to invite new users
3. Assign roles and permissions
4. Enable/disable two-factor authentication

### Table Features

All index pages support:

- **Search** - Quick search by code or name
- **Sort** - Click column headers to sort
- **Filter** - Use dropdowns to filter results
- **Column Preferences** - Show/hide columns, customize order
- **Filter Presets** - Save and reuse filter combinations
- **Export** - Export data to CSV
- **Pagination** - Navigate through large datasets

### Keyboard Shortcuts

- **Cmd/Ctrl + K** - Focus search box (where available)
- **Click row** - Select site (Sites page)
- **Esc** - Close modals/dialogs

## 🚀 Development

### Development Server

Start all development services at once:

```bash
composer dev
```

This starts:
- PHP development server (port 8000)
- Queue worker (background jobs)
- Log tail (real-time logs)
- Vite dev server (HMR for frontend)

### Individual Services

```bash
# PHP server only
php artisan serve

# Vite dev server only  
npm run dev

# Queue worker
php artisan queue:listen

# View logs
php artisan pail
```

### Building Assets

```bash
# Development build
npm run build

# Production build
npm run build

# With SSR support
npm run build:ssr
```

### Code Quality

```bash
# Format PHP code
./vendor/bin/pint

# Lint JavaScript/TypeScript
npm run lint

# Format with Prettier
npm run format

# Check formatting
npm run format:check
```

### Database

```bash
# Run migrations
php artisan migrate

# Fresh database (⚠️ drops all tables)
php artisan migrate:fresh

# Seed database
php artisan db:seed

# Fresh + seed
php artisan migrate:fresh --seed
```

## 🧪 Testing

Run tests with Pest:

```bash
# All tests
composer test
# or
php artisan test

# Specific test file
php artisan test tests/Feature/SiteTest.php

# Specific test method
php artisan test --filter=test_can_create_site

# With coverage
php artisan test --coverage
```

## 📂 Project Structure

```
camr/
├── app/
│   ├── Http/
│   │   ├── Controllers/      # Route controllers
│   │   ├── Middleware/       # Custom middleware
│   │   └── Requests/         # Form validation
│   ├── Models/               # Eloquent models
│   └── Actions/              # Fortify actions
├── resources/
│   ├── js/
│   │   ├── actions/          # Wayfinder route actions
│   │   ├── components/       # Vue components
│   │   │   └── ui/           # shadcn-vue components
│   │   ├── composables/      # Vue composables
│   │   ├── layouts/          # Page layouts
│   │   ├── pages/            # Inertia pages
│   │   ├── routes/           # Generated routes (Wayfinder)
│   │   └── types/            # TypeScript types
│   └── css/                  # Stylesheets
├── routes/
│   ├── web.php              # Web routes
│   └── settings.php         # Settings routes
├── database/
│   └── migrations/          # Database migrations
├── tests/
│   ├── Feature/             # Feature tests
│   └── Unit/                # Unit tests
└── docs/                    # Documentation
```

## 🚢 Deployment

### Production Build

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Build assets
npm run build

# Run migrations
php artisan migrate --force

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Link storage
php artisan storage:link
```

### Environment Variables

Update `.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Use proper database
DB_CONNECTION=mysql

# Set queue to redis or database
QUEUE_CONNECTION=database

# Configure mail driver
MAIL_MAILER=smtp
```

### Web Server Configuration

#### Apache

```apache
<VirtualHost *:80>
    DocumentRoot /path/to/camr/public
    
    <Directory /path/to/camr/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/camr/public;
    
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 📝 Additional Documentation

- [Table Column Configuration](docs/TABLE_COLUMN_CONFIGURATION.md)
- [Installation Guide](docs/INSTALLATION.md)
- [Site Context Filtering](docs/SITE_CONTEXT_FILTERING.md)
- [Testing Guide](docs/TESTING_SITE_CONTEXT.md)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is proprietary software. All rights reserved.

## 🙋 Support

For questions or support, please contact the development team or create an issue on GitHub.

---

**Built with ❤️ using Laravel, Vue, and Inertia.js**

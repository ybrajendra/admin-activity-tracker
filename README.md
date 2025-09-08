# CloudCommerce Admin Activity Tracker

A comprehensive Magento 2 module for tracking and auditing admin user activities in the backend panel.

## Features

- **Complete Activity Tracking**: Monitors admin login/logout, entity creation, updates, and deletions
- **Change Detection**: Compares current data with snapshots to identify specific changes
- **Configurable Exclusions**: Exclude specific sections from tracking (products, orders, customers, etc.)
- **Data Retention Management**: Automatic cleanup of old activity logs based on configurable retention period
- **Comprehensive Logging**: Custom logger with automatic log rotation and compression
- **Admin Grid Interface**: View and filter activity logs through dedicated admin grid
- **Security Focused**: Tracks IP addresses, user agents, and timestamps for security auditing

## Installation

### Method 1: Composer Installation (Recommended)

1. Install via Composer:
   ```bash
   composer require cloudcommerce/admin-activity-tracker
   ```

2. Enable the module:
   ```bash
   php bin/magento module:enable CloudCommerce_AdminActivityTracker
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

### Method 2: Manual Installation

1. Copy the module to your Magento installation:
   ```
   app/code/CloudCommerce/AdminActivityTracker/
   ```

2. Enable the module:
   ```bash
   php bin/magento module:enable CloudCommerce_AdminActivityTracker
   php bin/magento setup:upgrade
   php bin/magento setup:di:compile
   php bin/magento cache:flush
   ```

## Configuration

Navigate to **Admin Panel > Admin Activity Tracker > Configuration**

### General Settings

- **Enable Admin Activity Tracker**: Enable/disable the module
- **Exclude Sections from Tracking**: Select sections to exclude (products, categories, orders, CMS, customers, attributes)
- **Data Retention Period**: How long to keep activity data (1-36 months)
- **Cleanup Cron Schedule**: Cron expression for automatic cleanup (default: daily at 2 AM)

## Usage

### Viewing Activity Logs

1. Go to **Admin Panel > Admin Activity Tracker > Activity Log**
2. Use filters to search by:
   - Admin user
   - Action type (login, logout, create, update, delete)
   - Entity type
   - Date range

### Tracked Activities

- **Authentication**: Admin login/logout events
- **Entity Operations**: Create, update, delete operations on:
  - Products and categories
  - Orders and invoices
  - Customers and addresses
  - CMS pages and blocks
  - System configurations
  - And more...

## Database Tables

- `admin_activity_log`: Main activity log records
- `admin_activity_entity_snapshot`: Entity snapshots for change comparison

## Permissions

Ensure admin users have the following ACL permissions:
- `CloudCommerce_AdminActivityTracker::admin`: Access to main module
- `CloudCommerce_AdminActivityTracker::activity_log`: View activity logs
- `CloudCommerce_AdminActivityTracker::config`: Modify module configuration

## Technical Details

### Architecture

- **Repository Pattern**: `ActivityLogRepository` for data persistence
- **Observer Pattern**: Tracks model save/delete events
- **Plugin Pattern**: Intercepts specific operations (auth, config, etc.)
- **Snapshot System**: `SnapshotManager` for change detection
- **Custom Logger**: Dedicated logging with rotation

### Key Components

- **Observers**: `ModelSaveObserver`, `ModelDeleteObserver`, `InvoiceRegisterObserver`
- **Plugins**: `AdminAuthPlugin`, `ConfigPlugin`, `DesignConfigPlugin`, etc.
- **Models**: `ActivityLog`, `ActivityLogRepository`, `SnapshotManager`
- **Helpers**: `Config` for centralized configuration access

## Troubleshooting

### Common Issues

1. **Permission Errors**: Ensure proper ACL permissions are assigned
2. **Database Errors**: Check database table creation and permissions
3. **Performance**: Use exclusions to reduce tracking overhead
4. **Log Size**: Configure appropriate retention periods

### Debugging

- Check logs in `var/log/admin_activity.log`
- Enable Magento developer mode for detailed error messages
- Use module's custom logger for debugging specific issues

## Requirements

- Magento 2.4.x
- PHP 8.0+

## SEO Keywords

magento 2 admin activity tracker, magento admin audit log, magento user activity monitoring, magento backend tracking, magento admin security, magento activity logger, magento admin audit trail, magento user behavior tracking, magento admin session tracking, magento security module, magento admin monitoring extension, magento activity log extension, magento admin user tracking, magento backend audit, magento admin activity monitoring, magento security audit, magento admin login tracking, magento user activity log, magento admin change tracking, magento security logging

## License

Proprietary - CloudCommerce
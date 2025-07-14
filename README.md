# Archiving System

A comprehensive document archiving and management system built with PHP and MySQL.

## Features

- **Document Management**: Upload, organize, and search documents
- **User Authentication**: Role-based access control (Admin/Staff)
- **Dynamic Forms**: Customizable document types with flexible metadata fields
- **Location Hierarchy**: Cascading dropdowns for geographic data
- **File Management**: Secure file upload and storage
- **Search & Filter**: Advanced document search capabilities
- **Backup & Restore**: Database backup and restoration tools
- **Branding**: Customizable application branding
- **Updates**: GitHub-based update system
- **Reports**: Document statistics and analytics

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- mod_rewrite enabled (for clean URLs)

## Installation

### Quick Install
1. Download the latest release from [GitHub Releases](https://github.com/YOUR_USERNAME/YOUR_REPO/releases)
2. Extract the ZIP file to your web server directory
3. Navigate to your application URL
4. Follow the installation wizard

### Manual Install
1. Clone or download this repository
2. Configure your web server to point to the application directory
3. Create a MySQL database
4. Navigate to `index.php?page=first_install`
5. Complete the setup wizard

## Configuration

### Database Configuration
Edit `config/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Application Settings
```php
define('APP_NAME', 'Archiving System');
define('BASE_URL', 'http://your-domain.com/path/');
```

## Usage

### Admin Functions
- Manage document types and fields
- Upload and organize book images for reference
- Manage user accounts
- Configure system branding
- Backup and restore data
- Check for application updates

### Staff Functions
- Upload and categorize documents
- Search and browse document archive
- Edit document metadata
- View document details and files

## Update System

The application includes a GitHub-based update system:

1. Navigate to **App Updates** in the admin menu
2. Click **"Check for Updates"**
3. Download and install updates manually
4. Follow release notes for any special instructions

## File Structure

```
archiving-system/
├── api/                 # API endpoints
├── assets/             # CSS, JS, and static files
├── config/             # Configuration files
├── database/           # Database schema and migrations
├── docs/               # Documentation
├── includes/           # Core PHP classes and utilities
├── pages/              # Application pages
├── storage/            # File uploads (not in repository)
├── data/               # Application data (not in repository)
└── index.php           # Main entry point
```

## Security

- All user inputs are sanitized and validated
- SQL injection prevention using prepared statements
- XSS protection with proper output escaping
- File upload restrictions and validation
- Session-based authentication
- Role-based access control

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please:
1. Check the documentation in the `docs/` folder
2. Search existing GitHub issues
3. Create a new issue with detailed information

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed list of changes and updates.

## Version

Current Version: 1.0.0
Build: 2025.01.14.001
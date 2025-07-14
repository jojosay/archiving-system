# Changelog
All notable changes to the Archiving System will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.1] - 2025-01-14
### Added
- GitHub-based application update system
- Progress bars for location data CSV uploads
- Enhanced error handling for CSV imports
- Real-time progress tracking for large file uploads

### Fixed
- Cascading dropdown fields now properly display existing values in edit mode
- CSV import now processes all records correctly
- Resolved constant redefinition warnings
- Improved session handling for upload progress

### Changed
- Application name simplified to "Archiving System"
- Enhanced location management UI with better visual feedback
- Improved error messages and user guidance

### Technical
- Added UpdateManager class for GitHub API integration
- Enhanced CSV importer with progress reporting
- Added version management configuration
- Improved JavaScript error handling

## [1.0.0] - 2025-01-13
### Added
- Initial release of Archiving System
- Document management and archiving
- User authentication and role-based access
- Cascading location dropdowns
- Document type management
- File upload and storage
- Search and filtering capabilities
- Backup and restore functionality
- Branding customization
- Deployment packaging system

### Security
- Secure file upload handling
- Role-based access control
- Session management
- SQL injection prevention
- XSS protection
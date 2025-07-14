# GitHub-Based App Update System Setup Guide

## Overview
This system allows your application to check for updates from a GitHub repository and notify users when new versions are available.

## Prerequisites
1. GitHub repository for your application
2. GitHub account with repository access
3. Admin access to your application

## Setup Steps

### 1. Configure GitHub Repository
1. Create a GitHub repository for your application (if not already done)
2. Note your GitHub username and repository name

### 2. Update Configuration
Edit `config/version.php` and update these values:
```php
define('GITHUB_REPO_OWNER', 'your-github-username');
define('GITHUB_REPO_NAME', 'your-repository-name');
```

### 3. Create GitHub Releases
To publish updates:
1. Go to your GitHub repository
2. Click "Releases" → "Create a new release"
3. Tag version: Use semantic versioning (e.g., `v1.0.1`, `v1.1.0`)
4. Release title: Descriptive name (e.g., "Version 1.0.1 - Bug Fixes")
5. Description: Add release notes
6. Attach files: Upload your application ZIP file
7. Click "Publish release"

### 4. Version Numbering
- Update `APP_VERSION` in `config/version.php` before each release
- Use semantic versioning: MAJOR.MINOR.PATCH
- Update `APP_BUILD` with timestamp format: YYYY.MM.DD.NNN

### 5. Access Update Manager
- Navigate to "App Updates" in the admin menu
- Click "Check for Updates" to test the system

## How It Works

### Update Check Process
1. Application queries GitHub API for latest release
2. Compares current version with latest version
3. Caches results for 24 hours (configurable)
4. Displays update status to admin users

### GitHub API Endpoints Used
- `GET /repos/{owner}/{repo}/releases/latest` - Get latest release info

### Security Features
- Only admin users can access update functionality
- No automatic downloads or installations
- Manual verification required for updates

## File Structure
```
config/
├── version.php              # Version configuration
includes/
├── update_manager.php       # Core update logic
api/
├── check_updates.php        # Update check endpoint
pages/
├── app_updates.php          # Update management UI
data/
├── update_cache.json        # Cached update information
```

## Customization Options

### Update Check Interval
Modify in `config/version.php`:
```php
define('UPDATE_CHECK_INTERVAL', 86400); // 24 hours
```

### Cache Location
Modify in `config/version.php`:
```php
define('UPDATE_CACHE_FILE', 'data/update_cache.json');
```

## Troubleshooting

### Common Issues
1. **"Unable to fetch release information"**
   - Check internet connectivity
   - Verify repository name and owner
   - Ensure repository is public or has proper access

2. **"Invalid JSON response"**
   - GitHub API rate limiting
   - Repository doesn't exist
   - Network connectivity issues

3. **"No release found"**
   - No releases published in repository
   - All releases are drafts or pre-releases

### Testing
1. Create a test release with a higher version number
2. Access the App Updates page
3. Click "Check for Updates"
4. Verify update detection works correctly

## Best Practices
1. Always test updates in a staging environment
2. Create backups before applying updates
3. Use clear, descriptive release notes
4. Follow semantic versioning consistently
5. Include migration instructions for breaking changes

## Future Enhancements
- Automatic backup before updates
- One-click update installation
- Update rollback functionality
- Notification system for available updates
- Scheduled update checks
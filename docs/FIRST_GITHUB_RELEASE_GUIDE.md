# Setting Up Your First GitHub Release

## Step-by-Step Guide

### 1. Prepare Your Repository
First, make sure your code is pushed to GitHub:

```bash
# If you haven't initialized git yet:
git init
git add .
git commit -m "Initial commit - Archiving System v1.0.0"

# Add your GitHub repository as remote:
git remote add origin https://github.com/your-username/your-repo-name.git
git push -u origin main
```

### 2. Update Version Configuration
Before creating a release, update your version number in `config/version.php`:

**Current Configuration:**
```php
// In config/config.php
define('APP_VERSION', '1.0.0');

// In config/version.php  
define('APP_BUILD', '2025.01.14.001');
```

**For Testing - Update to:**
```php
// In config/config.php
define('APP_VERSION', '1.0.1');  // Increment version

// In config/version.php
define('APP_BUILD', '2025.01.14.002');  // Increment build
```

### 3. Create GitHub Release

#### Option A: Via GitHub Web Interface
1. Go to your GitHub repository
2. Click **"Releases"** (on the right side or under Code tab)
3. Click **"Create a new release"**
4. Fill in the details:

**Tag version:** `v1.0.1`
**Release title:** `Archiving System v1.0.1 - Update Test`
**Description:**
```markdown
## What's New in v1.0.1
- Added GitHub-based update system
- Enhanced location data management with progress bars
- Fixed cascading dropdown display in edit mode
- Improved error handling and user experience

## Installation
1. Download the release ZIP file
2. Extract to your web server directory
3. Update your database if needed
4. Clear any cached files

## Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

## Support
For issues or questions, please create an issue on GitHub.
```

5. **Attach Files:** Upload your application ZIP file
6. Click **"Publish release"**

#### Option B: Via Command Line (Advanced)
```bash
# Create and push a tag
git tag v1.0.1
git push origin v1.0.1

# Then use GitHub CLI (if installed)
gh release create v1.0.1 --title "Archiving System v1.0.1" --notes "Update test release"
```

### 4. Prepare Release Package

Create a ZIP file with your application:

**Include:**
- All PHP files
- Configuration files (with sample/default values)
- Database schema
- Documentation
- Assets (CSS, JS, images)

**Exclude:**
- `.git` folder
- `data/` folder (user data)
- `storage/` folder (uploaded files)
- `backups/` folder
- Any sensitive configuration files

**Suggested ZIP structure:**
```
archiving-system-v1.0.1.zip
├── api/
├── assets/
├── config/
├── includes/
├── pages/
├── database/
├── docs/
├── index.php
├── README.md
└── CHANGELOG.md
```

### 5. Test the Update System

After creating the release:

1. **Keep your local version at 1.0.0** (don't update yet)
2. **Go to App Updates page** in your application
3. **Click "Check for Updates"**
4. **Should detect v1.0.1** as available update
5. **Click "View Release Notes"** to see the description
6. **Click "Download Update"** to go to GitHub release page

### 6. Verify Update Detection

The system should show:
```
Update Available!
Current Version: 1.0.0
Latest Version: 1.0.1
```

## Sample Release Workflow

### For Future Releases:

1. **Update version numbers**
2. **Test thoroughly**
3. **Update CHANGELOG.md**
4. **Commit changes**
5. **Create GitHub release**
6. **Attach release package**
7. **Notify users**

## Troubleshooting

### Common Issues:

**"No releases found"**
- Make sure the release is published (not draft)
- Check repository name in config/version.php
- Verify repository is public or accessible

**"Version not detected"**
- Ensure tag follows semantic versioning (v1.0.1)
- Check that tag_name in release matches expected format

**"API rate limit"**
- GitHub API allows 60 requests/hour for unauthenticated requests
- Wait an hour or authenticate with GitHub token

## Next Steps

After successful testing:
1. Update your local version to 1.0.1
2. Create regular release schedule
3. Set up automated testing
4. Consider adding update notifications
5. Plan for database migrations in updates
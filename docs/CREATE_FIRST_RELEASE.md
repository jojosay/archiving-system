# Create Your First GitHub Release - Step by Step

## Step 1: Update Version for Testing

First, let's increment the version to test the update system:

### Edit `config/config.php`:
```php
// Change from:
define('APP_VERSION', '1.0.0');

// To:
define('APP_VERSION', '1.0.1');
```

### Edit `config/version.php`:
```php
// Update build number:
define('APP_BUILD', '2025.01.14.002');
```

## Step 2: Update Repository Configuration

Make sure `config/version.php` has your correct GitHub details:
```php
define('GITHUB_REPO_OWNER', 'jojosay');
define('GITHUB_REPO_NAME', 'archiving-system');
```

## Step 3: Create Release Package

Run the preparation script:
```cmd
php scripts/prepare_release.php
```

This creates a clean ZIP file in the `releases/` folder.

## Step 4: Create GitHub Release

### Via GitHub Website:
1. **Go to:** https://github.com/jojosay/archiving-system
2. **Click "Releases"** (right sidebar or top tabs)
3. **Click "Create a new release"**
4. **Fill in details:**

**Tag version:** `v1.0.1`
**Release title:** `Archiving System v1.0.1 - First Release`
**Description:**
```markdown
## 🎉 First Official Release - Archiving System v1.0.1

### ✨ Features
- Complete document archiving and management system
- User authentication with role-based access (Admin/Staff)
- Dynamic document types with customizable fields
- Cascading location dropdowns (Region → Province → City → Barangay)
- File upload and secure storage
- Advanced search and filtering
- Database backup and restore
- Custom branding system
- **NEW: GitHub-based update system**

### 🔧 Technical Improvements
- Enhanced location data management with progress bars
- Fixed cascading dropdown display in edit mode
- Improved CSV import with real-time progress tracking
- Better error handling and user feedback

### 📋 Installation Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- mod_rewrite enabled

### 🚀 Installation
1. Download the release ZIP file below
2. Extract to your web server directory
3. Navigate to your application URL
4. Follow the installation wizard

### 🔄 Update System
This version includes a built-in update checker that connects to GitHub releases. Admins can check for updates via the "App Updates" menu.

### 📞 Support
For issues or questions, please create an issue on GitHub.

---
**Full Changelog:** https://github.com/jojosay/archiving-system/compare/v1.0.0...v1.0.1
```

5. **Attach Files:** Upload the ZIP file from `releases/` folder
6. **Click "Publish release"**

## Step 5: Test Update Detection

### Keep Local Version at 1.0.0:
1. **Don't commit the version changes yet**
2. **Go to App Updates page** in your application
3. **Click "Check for Updates"**
4. **Should detect v1.0.1 as available!**

### Expected Result:
```
Update Available!
Current Version: 1.0.0
Latest Version: 1.0.1
```

## Step 6: Verify Release

After creating the release:
1. ✅ Release appears on GitHub
2. ✅ ZIP file is downloadable
3. ✅ Update system detects new version
4. ✅ Release notes display properly

## Troubleshooting

### "No releases found"
- Make sure release is published (not draft)
- Check repository name in config
- Verify release has a tag starting with 'v'

### "Update not detected"
- Clear browser cache
- Check version format (v1.0.1)
- Verify GitHub repository is public

### "Permission denied"
- Make sure repository is public
- Check GitHub API rate limits

## Next Steps

After successful testing:
1. Commit version changes to match release
2. Plan regular release schedule
3. Set up automated testing
4. Consider adding update notifications
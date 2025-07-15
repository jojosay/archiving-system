# Release Instructions for Version 1.0.5

## What Has Been Updated

✅ **Version Files Updated:**
- `config/version.php`: Updated to version 1.0.5, build 2025.01.15.005
- `RELEASE_INFO.json`: Updated version and package name
- `scripts/simple_release.php`: Updated to create v1.0.5 package
- `CHANGELOG.md`: Added v1.0.5 entry with changes
- Created `git_commit_script_v1.0.5.ps1` for automated commit process

## Manual Steps to Complete Release

### Step 1: Create Release Package
Run this command in your PHP environment (XAMPP, etc.):
```bash
php scripts/simple_release.php
```

This will create: `releases/archiving-system-v1.0.5.zip`

### Step 2: Git Operations
Run the batch script we created:
```cmd
git_commit_script_v1.0.5.bat
```

Or manually execute these Git commands:
```bash
git status
git add .
git commit -m "feat: Version update to 1.0.5 with release preparation

Version Updates:
- Updated APP_VERSION to 1.0.5 in config/version.php
- Updated APP_BUILD to 2025.01.15.005
- Updated RELEASE_INFO.json with new version details
- Updated release scripts for version 1.0.5

Technical Improvements:
- Prepared all version references for new release
- Updated package naming for consistency
- Enhanced release preparation workflow
- Updated CHANGELOG.md with version 1.0.5 entry

Build Configuration:
- Version: 1.0.5
- Build: 2025.01.15.005
- Package: archiving-system-v1.0.5"

git tag -a v1.0.5 -m "Release v1.0.5: Version Update and Release Preparation"
git push origin main
git push origin --tags
```

### Step 3: Create GitHub Release
1. Go to: https://github.com/jojosay/archiving-system/releases/new
2. Select tag: `v1.0.5`
3. Release title: `Release v1.0.5: Version Update and Release Preparation`
4. Description:
```markdown
## Release v1.0.5: Version Update and Release Preparation

This release includes:
- Version increment to 1.0.5
- Updated build configuration
- Enhanced release preparation scripts
- Updated documentation and changelog

### Technical Updates
- Version: 1.0.5
- Build: 2025.01.15.005
- Package: archiving-system-v1.0.5

Technical updates for improved release management and version consistency across the application.
```
5. Upload the `archiving-system-v1.0.5.zip` file
6. Publish the release

## Files Modified in This Update

- `config/version.php` - Version and build number
- `RELEASE_INFO.json` - Version and package details
- `scripts/simple_release.php` - Release script version
- `CHANGELOG.md` - Added v1.0.5 entry
- `git_commit_script_v1.0.5.ps1` - New commit script

## Next Steps After Release

1. Test the update system to ensure it detects the new version
2. Verify the download and installation process
3. Update any deployment documentation if needed

## Repository Information

- GitHub Repository: https://github.com/jojosay/archiving-system
- Current Version: 1.0.5
- Build: 2025.01.15.005
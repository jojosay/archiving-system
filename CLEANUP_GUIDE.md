# Repository Cleanup Guide

## Overview
This guide helps you clean up your GitHub repository before the v1.0.6 release, removing unnecessary files while preserving important data.

## Cleanup Scripts Available

### 1. `cleanup_repo.bat` (Basic Cleanup)
**What it does:**
- Removes temporary files (tmp_*, *.tmp, *.log)
- Removes backup directories (storage_backup_*)
- Removes old deployment packages
- Removes development documentation files
- Removes old commit scripts
- Removes system files (Thumbs.db, .DS_Store)
- Cleans cache files

**What it preserves:**
- All CSV files
- Essential project files
- Configuration files
- Source code
- Current documentation
- .htaccess files

### 2. `cleanup_repo_advanced.bat` (Advanced Cleanup)
**What it does:**
- Everything from basic cleanup
- Updates .gitignore for future prevention
- Removes files from Git tracking history
- Optimizes Git repository size
- Performs garbage collection

**Use this if:**
- You want comprehensive cleanup
- Repository size is a concern
- You want to prevent future clutter

## Files That Will Be Removed

### Development Documentation
- `ICON_UPGRADE_GUIDE.md`
- `FEATHER_ICONS_IMPLEMENTATION.md`
- `FEATHER_ICONS_LOCAL_SETUP.md`
- `ICON_INTEGRATION_COMPLETE.md`
- `MODAL_ENHANCEMENT_SUMMARY.md`
- `RELEASE_INSTRUCTIONS_v*.md`
- `*_SUMMARY.md`
- `*_IMPLEMENTATION_SUMMARY.md`
- `*_FEATURE_PLAN.md`

### Backup Directories
- `storage_backup_2025-07-15_05-28-40/`
- `storage_backup_2025-07-15_05-31-22/`
- `storage_backup_2025-07-15_05-35-04/`
- `storage_backup_2025-07-15_05-37-31/`

### Deployment Packages
- `deployment/packages/municipal_accountant_s_office_deployment_v1.0.0_2025-07-13_07-31-23/`

### Temporary Files
- Any `tmp_*` files
- `*.log` files
- `*.tmp` files
- `php_error_log`

### Old Scripts
- `git_commit_script.ps1`
- `git_commit_script.bat`
- `git_commit_script_v1.0.5.ps1`

## Files That Will Be Preserved

### Essential Project Files
- All source code (PHP, CSS, JS)
- Configuration files
- Database setup files
- Current documentation
- README files
- License files

### Data Files
- **All CSV files** (preserved everywhere)
- Essential data structures
- .htaccess files for security

### Current Release Files
- `git_commit_script_v1.0.6.bat`
- `RELEASE_NOTES_v1.0.6.md`
- `CHANGELOG.md`
- Version configuration files

## How to Use

### Option 1: Basic Cleanup (Recommended)
```cmd
cleanup_repo.bat
```

### Option 2: Advanced Cleanup (For comprehensive cleaning)
```cmd
cleanup_repo_advanced.bat
```

## After Cleanup

### 1. Review Changes
```cmd
git status
```

### 2. Commit Cleanup
```cmd
git add .
git commit -m "cleanup: Remove unnecessary files for clean repo"
```

### 3. Push Changes
```cmd
git push origin main
```

### 4. Proceed with Release
```cmd
git_commit_script_v1.0.6.bat
```

## Safety Notes

1. **Backup First**: Make sure you have a backup before running cleanup
2. **CSV Files Safe**: All CSV files are explicitly preserved
3. **Essential Files Safe**: Core application files are not touched
4. **Reversible**: Basic cleanup is reversible (files only deleted locally)
5. **Git History**: Advanced cleanup modifies Git history (use with caution)

## Repository Benefits After Cleanup

- **Smaller Size**: Reduced repository size
- **Cleaner Structure**: Only essential files remain
- **Better Performance**: Faster cloning and operations
- **Professional Appearance**: Clean, organized repository
- **Easier Maintenance**: Less clutter to manage

## Recommended Workflow

1. Run `cleanup_repo.bat` (basic cleanup)
2. Review what was removed with `git status`
3. Test your application to ensure everything works
4. Commit the cleanup changes
5. Proceed with v1.0.6 release
6. Optionally run advanced cleanup later if needed

Your repository will be clean, professional, and ready for the v1.0.6 release!